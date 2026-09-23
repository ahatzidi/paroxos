<?php
// Read-only JSON API: επιστρέφει τις αιτήσεις Novus ενός ΑΦΜ (status, is_b2b, is_b2c,
// customer_email). Απαιτεί API key — δεν είναι δημόσιο endpoint (το customer_email
// είναι προσωπικό δεδομένο).
//
// Χρήση:
//   GET /api_novus_status.php?afm=094019245
//   Header: X-API-KEY: <το $PAROXOS_API_KEY από το config.php>

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_error($httpCode, $message) {
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

global $PAROXOS_API_KEY;

$providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (empty($PAROXOS_API_KEY) || !hash_equals((string) $PAROXOS_API_KEY, (string) $providedKey)) {
    json_error(401, 'Μη έγκυρο ή απόν API key (header X-API-KEY).');
}

$afm = preg_replace('/\D/', '', $_GET['afm'] ?? '');

if (strlen($afm) !== 9) {
    json_error(400, 'Το afm πρέπει να αποτελείται από 9 ψηφία.');
}

$requests = list_novus_requests_by_afm($afm);

$data = array_map(function ($row) {
    return [
        'request_id' => $row['novus_request_id'],
        'status' => $row['status'],
        'is_b2b' => $row['is_b2b'] !== null ? (bool) $row['is_b2b'] : null,
        'is_b2c' => $row['is_b2c'] !== null ? (bool) $row['is_b2c'] : null,
        'customer_email' => $row['customer_email'],
    ];
}, $requests);

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
