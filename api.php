<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'load') {
    $state = $_SESSION['rebirth_game_state'] ?? null;
    if ($state === null) {
        echo json_encode([
            'ok' => true,
            'hasState' => false,
            'state' => null,
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'hasState' => true,
        'state' => $state,
    ], JSON_THROW_ON_ERROR);
    exit;
}

if ($method === 'POST' && $action === 'save') {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => 'Missing request body',
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    try {
        /** @var array<string,mixed>|null $decoded */
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => 'Invalid JSON body',
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => 'State must be an object',
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    // Very simple validation of expected keys (you can extend this)
    $expectedKeys = [
        'clicks',
        'energy',
        'coins',
        'clickPower',
        'energyPerClick',
        'rebirths',
        'rebirthMultiplier',
        'upgradeClickCost',
        'upgradeEnergyCost',
        'rebirthCost',
    ];

    foreach ($expectedKeys as $key) {
        if (!array_key_exists($key, $decoded)) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => "Missing key: {$key}",
            ], JSON_THROW_ON_ERROR);
            exit;
        }
    }

    $_SESSION['rebirth_game_state'] = $decoded;

    echo json_encode([
        'ok' => true,
    ], JSON_THROW_ON_ERROR);
    exit;
}

http_response_code(404);
echo json_encode([
    'ok' => false,
    'error' => 'Unsupported action',
], JSON_THROW_ON_ERROR);
