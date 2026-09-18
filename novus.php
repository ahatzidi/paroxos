<?php
// Συναρτήσεις κλήσης του Novus Onboarding API.
// Έγγραφο αναφοράς: Novus Onboarding API v1.0

require_once __DIR__ . '/config.php';

function novus_uuid4() {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Γενική κλήση JSON endpoint. Επιστρέφει:
// ['ok' => bool, 'http_code' => int, 'data' => array|null, 'error' => array|null, 'raw_error' => string|null]
function novus_call($method, $path, $jsonBody = null, $extraHeaders = []) {
    global $NOVUS_API_URL, $NOVUS_API_KEY;

    $ch = curl_init(rtrim($NOVUS_API_URL, '/') . $path);

    $headers = array_merge(['API-KEY: ' . $NOVUS_API_KEY], $extraHeaders);

    if ($jsonBody !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonBody, JSON_UNESCAPED_UNICODE));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $body = curl_exec($ch);

    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'http_code' => 0, 'data' => null, 'error' => null, 'raw_error' => $error];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($body, true);

    if ($httpCode >= 200 && $httpCode < 300 && !empty($decoded['success'])) {
        return ['ok' => true, 'http_code' => $httpCode, 'data' => $decoded['data'], 'error' => null, 'raw_error' => null];
    }

    return [
        'ok' => false,
        'http_code' => $httpCode,
        'data' => null,
        'error' => $decoded['error'] ?? null,
        'raw_error' => $decoded === null ? $body : null,
    ];
}

// Δημιουργία αίτησης onboarding. $idempotencyKey είναι προαιρετικό αλλά προτείνεται.
function novus_create_request($payload, $idempotencyKey = null) {
    $headers = [];
    if ($idempotencyKey) {
        $headers[] = 'Idempotency-Key: ' . $idempotencyKey;
    }

    return novus_call('POST', '/api/v1/requests', $payload, $headers);
}

function novus_get_request($requestId) {
    return novus_call('GET', '/api/v1/requests/' . urlencode($requestId));
}

function novus_get_request_history($requestId) {
    return novus_call('GET', '/api/v1/requests/' . urlencode($requestId) . '/history');
}

function novus_cancel_request($requestId, $reason = null) {
    $payload = $reason ? ['reason' => $reason] : null;
    return novus_call('DELETE', '/api/v1/requests/' . urlencode($requestId), $payload);
}

// Κατεβάζει το PDF σύμβασης (unsigned/signed) από τη Novus.
// Επιστρέφει: ['ok' => bool, 'http_code' => int, 'body' => string|null, 'content_type' => string|null, 'error' => string|null]
function novus_download_contract($requestId, $kind = 'unsigned', $version = null) {
    global $NOVUS_API_URL, $NOVUS_API_KEY;

    $query = ['kind' => $kind];
    if ($version !== null) {
        $query['version'] = $version;
    }

    $url = rtrim($NOVUS_API_URL, '/') . '/api/v1/requests/' . urlencode($requestId) . '/contract?' . http_build_query($query);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['API-KEY: ' . $NOVUS_API_KEY]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $body = curl_exec($ch);

    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'http_code' => 0, 'body' => null, 'content_type' => null, 'error' => $error];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $decoded = json_decode($body, true);
        return [
            'ok' => false,
            'http_code' => $httpCode,
            'body' => null,
            'content_type' => null,
            'error' => $decoded['error']['message'] ?? ('HTTP ' . $httpCode),
        ];
    }

    return ['ok' => true, 'http_code' => $httpCode, 'body' => $body, 'content_type' => $contentType, 'error' => null];
}

// Ανεβάζει την υπογεγραμμένη σύμβαση (multipart/form-data, πεδίο contractFile).
function novus_upload_signed_contract($requestId, $tmpFilePath, $originalFilename) {
    global $NOVUS_API_URL, $NOVUS_API_KEY;

    $url = rtrim($NOVUS_API_URL, '/') . '/api/v1/requests/' . urlencode($requestId) . '/signed-contract';

    $cfile = new CURLFile($tmpFilePath, 'application/pdf', $originalFilename);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['API-KEY: ' . $NOVUS_API_KEY]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['contractFile' => $cfile]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $body = curl_exec($ch);

    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'http_code' => 0, 'data' => null, 'error' => null, 'raw_error' => $error];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($body, true);

    if ($httpCode >= 200 && $httpCode < 300 && !empty($decoded['success'])) {
        return ['ok' => true, 'http_code' => $httpCode, 'data' => $decoded['data'], 'error' => null, 'raw_error' => null];
    }

    return [
        'ok' => false,
        'http_code' => $httpCode,
        'data' => null,
        'error' => $decoded['error'] ?? null,
        'raw_error' => $decoded === null ? $body : null,
    ];
}

// Μορφοποιεί ένα σφάλμα Novus (['code'=>..,'message'=>..,'details'=>[]]) σε αναγνώσιμο κείμενο.
function novus_format_error($error, $rawError = null) {
    if (!$error) {
        return $rawError ?: 'Άγνωστο σφάλμα επικοινωνίας με τη Novus.';
    }

    $message = $error['message'] ?? ($error['code'] ?? 'Σφάλμα Novus');

    if (!empty($error['details']) && is_array($error['details'])) {
        $parts = [];
        foreach ($error['details'] as $d) {
            $parts[] = ($d['field'] ?? '?') . ': ' . ($d['message'] ?? '');
        }
        $message .= ' (' . implode(', ', $parts) . ')';
    }

    return $message;
}
