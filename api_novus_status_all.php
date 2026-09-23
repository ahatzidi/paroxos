<?php
// Read-only JSON API: επιστρέφει ΟΛΕΣ τις αιτήσεις Novus (όλων των ΑΦΜ) σε ένα combined
// JSON — afm, status, is_b2b, is_b2c, customer_email. Απαιτεί API key — δεν είναι
// δημόσιο endpoint (το customer_email είναι προσωπικό δεδομένο, και εδώ εκτίθενται όλες
// οι εγγραφές μαζί).
//
// Χρήση (POST):
//   POST /api_novus_status_all.php
//   Body (x-www-form-urlencoded ή JSON): api_key=...
//   Ή το κλειδί σε header: X-API-KEY: ...
//
// curl -X POST https://paroxos.totalschool.gr/api_novus_status_all.php \
//   -H "X-API-KEY: abf42617426cd2782df761e8b74b2fda1147a519610f020ccc13de726e489035"

require_once __DIR__ . '/db.php';

// Ίδιο hardcoded κλειδί με το api_novus_status.php.
const API_KEY = 'abf42617426cd2782df761e8b74b2fda1147a519610f020ccc13de726e489035';

header('Content-Type: application/json; charset=utf-8');

function json_error($httpCode, $message) {
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error(405, 'Επιτρέπεται μόνο POST.');
}

$input = $_POST;
if (empty($input)) {
    $rawBody = file_get_contents('php://input');
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}

$providedKey = $_SERVER['HTTP_X_API_KEY'] ?? ($input['api_key'] ?? '');

if (!hash_equals(API_KEY, (string) $providedKey)) {
    json_error(401, 'Μη έγκυρο ή απόν API key (header X-API-KEY ή πεδίο api_key).');
}

$requests = list_all_novus_requests();

$data = array_map(function ($row) {
    return [
        'afm' => $row['company_afm'],
        'request_id' => $row['novus_request_id'],
        'status' => $row['status'],
        'is_b2b' => $row['is_b2b'] !== null ? (bool) $row['is_b2b'] : null,
        'is_b2c' => $row['is_b2c'] !== null ? (bool) $row['is_b2c'] : null,
        'customer_email' => $row['customer_email'],
    ];
}, $requests);

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
