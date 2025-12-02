<?php

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const DB_FILE = __DIR__ . '/data.sqlite';

/**
 * @return PDO
 */
function getDb()
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS saves (
        user_id INTEGER PRIMARY KEY,
        state_json TEXT NOT NULL,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    return $pdo;
}
/**
 * @param array<string,mixed> $payload
 * @param int $status
 */
function respond(array $payload, $status = 200)
{
    http_response_code($status);
    $json = json_encode($payload);
    if ($json === false) {
        error_log('[api.php] json_encode failed: ' . json_last_error_msg());
        echo "{\"ok\":false,\"error\":\"Došlo k interní chybě serveru.\"}";
    } else {
        echo $json;
    }
    exit;
}

function currentUserId()
{
    $id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    return is_int($id) ? $id : null;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    if ($method === 'GET' && $action === 'me') {
        $userId = currentUserId();
        if ($userId === null) {
            respond(['ok' => true, 'user' => null]);
        }

        $stmt = getDb()->prepare('SELECT id, username, created_at FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        if ($user === false) {
            $_SESSION = [];
            session_destroy();
            respond(['ok' => true, 'user' => null]);
        }

        respond(['ok' => true, 'user' => $user]);
    }

    if ($method === 'POST' && $action === 'register') {
        $input = json_decode((string)file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            respond(['ok' => false, 'error' => 'Neplatné JSON tělo.'], 400);
        }

        $inputIsArray = is_array($input);
        $usernameValue = $inputIsArray && array_key_exists('username', $input) ? $input['username'] : null;
        $passwordValue = $inputIsArray && array_key_exists('password', $input) ? $input['password'] : null;
        $username = is_string($usernameValue) ? trim($usernameValue) : '';
        $password = is_string($passwordValue) ? $passwordValue : '';

        if ($username === '' || mb_strlen($username) < 3 || mb_strlen($username) > 40) {
            respond(['ok' => false, 'error' => 'Uživatelské jméno musí mít 3–40 znaků.'], 400);
        }
        if (mb_strlen($password) < 6) {
            respond(['ok' => false, 'error' => 'Heslo musí mít alespoň 6 znaků.'], 400);
        }

        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            respond(['ok' => false, 'error' => 'Uživatel již existuje.'], 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :hash)');
        $insert->execute([':username' => $username, ':hash' => $hash]);
        $userId = (int)$pdo->lastInsertId();
        $_SESSION['user_id'] = $userId;

        respond(['ok' => true, 'user' => ['id' => $userId, 'username' => $username]]);
    }

    if ($method === 'POST' && $action === 'login') {
        $input = json_decode((string)file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            respond(['ok' => false, 'error' => 'Neplatné JSON tělo.'], 400);
        }

        $inputIsArray = is_array($input);
        $usernameValue = $inputIsArray && array_key_exists('username', $input) ? $input['username'] : null;
        $passwordValue = $inputIsArray && array_key_exists('password', $input) ? $input['password'] : null;
        $username = is_string($usernameValue) ? trim($usernameValue) : '';
        $password = is_string($passwordValue) ? $passwordValue : '';

        if ($username === '' || $password === '') {
            respond(['ok' => false, 'error' => 'Chybí přihlašovací údaje.'], 400);
        }

        $stmt = getDb()->prepare('SELECT id, password_hash FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        if ($user === false || !password_verify($password, $user['password_hash'])) {
            respond(['ok' => false, 'error' => 'Neplatné přihlašovací údaje.'], 401);
        }

        $_SESSION['user_id'] = (int)$user['id'];
        respond(['ok' => true, 'user' => ['id' => (int)$user['id'], 'username' => $username]]);
    }

    if ($method === 'POST' && $action === 'logout') {
        $_SESSION = [];
        session_destroy();
        respond(['ok' => true]);
    }

    if ($method === 'GET' && $action === 'load') {
        $userId = currentUserId();
        if ($userId === null) {
            respond(['ok' => false, 'error' => 'Nepřihlášený uživatel.'], 401);
        }

        $stmt = getDb()->prepare('SELECT state_json FROM saves WHERE user_id = :user');
        $stmt->execute([':user' => $userId]);
        $row = $stmt->fetch();
        if ($row === false) {
            respond(['ok' => true, 'hasState' => false, 'state' => null]);
        }

        /** @var array<string,mixed>|null $decoded */
        $decoded = json_decode($row['state_json'], true);
        if (json_last_error() !== JSON_ERROR_NONE || $decoded === null) {
            respond(['ok' => false, 'error' => 'Poškozený uložený stav.'], 500);
        }

        respond(['ok' => true, 'hasState' => true, 'state' => $decoded]);
    }

    if ($method === 'POST' && $action === 'save') {
        $userId = currentUserId();
        if ($userId === null) {
            respond(['ok' => false, 'error' => 'Nepřihlášený uživatel.'], 401);
        }

        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            respond(['ok' => false, 'error' => 'Chybí tělo požadavku.'], 400);
        }

        /** @var array<string,mixed>|null $decoded */
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            respond(['ok' => false, 'error' => 'Neplatné JSON tělo.'], 400);
        }

        if (!is_array($decoded)) {
            respond(['ok' => false, 'error' => 'Stav musí být objekt.'], 400);
        }

        $expectedKeys = [
            'clicks',
            'energy',
            'coins',
            'clickPower',
            'energyPerClick',
            'rebirths',
            'rebirthMultiplier',
            'autoClickers',
            'autoClickerCost',
            'autoClickerPower',
            'autoClickerPowerCost',
            'globalMultiplier',
            'globalMultiplierCost',
            'upgradeClickCost',
            'upgradeEnergyCost',
            'rebirthCost',
        ];

        foreach ($expectedKeys as $key) {
            if (!array_key_exists($key, $decoded)) {
                respond(['ok' => false, 'error' => "Chybí klíč: {$key}"], 400);
            }
        }

        $pdo = getDb();
        $encodedState = json_encode($decoded);
        if ($encodedState === false) {
            respond(['ok' => false, 'error' => 'Neplatný stav pro uložení.'], 400);
        }

        $stmt = $pdo->prepare('INSERT INTO saves (user_id, state_json, updated_at) VALUES (:user, :state, CURRENT_TIMESTAMP)
            ON CONFLICT(user_id) DO UPDATE SET state_json = excluded.state_json, updated_at = excluded.updated_at');
        $stmt->execute([':user' => $userId, ':state' => $encodedState]);

        respond(['ok' => true]);
    }

    respond(['ok' => false, 'error' => 'Nepodporovaná akce.'], 404);
} catch (Exception $e) {
    error_log('[api.php] ' . $e->getMessage());
    respond(['ok' => false, 'error' => 'Došlo k interní chybě serveru.'], 500);
}
