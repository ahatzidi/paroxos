<?php
// Απλή σύνδεση στη βάση με mysqli.

require_once __DIR__ . '/config.php';

function get_db() {
    static $conn = null;

    if ($conn !== null) {
        return $conn;
    }

    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;

    $conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    if (!$conn) {
        die('Αδυναμία σύνδεσης στη βάση δεδομένων: ' . mysqli_connect_error());
    }
    mysqli_set_charset($conn, 'utf8mb4');

    return $conn;
}

// Αποθηκεύει/ενημερώνει τα στοιχεία μιας επιχείρησης.
function save_company($data, $raw) {
    $conn = get_db();

    $sql = "INSERT INTO companies (
                afm, onomasia, commer_title, postal_address, postal_address_no,
                postal_zip_code, postal_area_description, doy_descr,
                legal_status_descr, firm_flag_descr, deactivation_flag_descr,
                activities_json, raw_response_json
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                onomasia = VALUES(onomasia),
                commer_title = VALUES(commer_title),
                postal_address = VALUES(postal_address),
                postal_address_no = VALUES(postal_address_no),
                postal_zip_code = VALUES(postal_zip_code),
                postal_area_description = VALUES(postal_area_description),
                doy_descr = VALUES(doy_descr),
                legal_status_descr = VALUES(legal_status_descr),
                firm_flag_descr = VALUES(firm_flag_descr),
                deactivation_flag_descr = VALUES(deactivation_flag_descr),
                activities_json = VALUES(activities_json),
                raw_response_json = VALUES(raw_response_json)";

    $stmt = mysqli_prepare($conn, $sql);

    $activitiesJson = json_encode($data['activities'], JSON_UNESCAPED_UNICODE);
    $rawJson = json_encode($raw, JSON_UNESCAPED_UNICODE);

    mysqli_stmt_bind_param(
        $stmt,
        'sssssssssssss',
        $data['afm'],
        $data['onomasia'],
        $data['commer_title'],
        $data['postal_address'],
        $data['postal_address_no'],
        $data['postal_zip_code'],
        $data['postal_area_description'],
        $data['doy_descr'],
        $data['legal_status_descr'],
        $data['firm_flag_descr'],
        $data['deactivation_flag_descr'],
        $activitiesJson,
        $rawJson
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Επιστρέφει τα αποθηκευμένα στοιχεία μιας επιχείρησης βάσει ΑΦΜ, ή null.
function get_company_by_afm($afm) {
    $conn = get_db();
    $stmt = mysqli_prepare($conn, 'SELECT * FROM companies WHERE afm = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $afm);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

// Αποθηκεύει/ενημερώνει μια αίτηση Novus από τα δεδομένα που επέστρεψε το API (data block).
function save_novus_request($companyAfm, $data, $idempotencyKey = null) {
    $conn = get_db();

    $sql = "INSERT INTO novus_requests (
                company_afm, novus_request_id, request_type, status,
                contract_number, contract_date, template_version,
                provisioning_status, aade_statement_status, idempotency_key, raw_json
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                request_type = VALUES(request_type),
                status = VALUES(status),
                contract_number = VALUES(contract_number),
                contract_date = VALUES(contract_date),
                template_version = VALUES(template_version),
                provisioning_status = VALUES(provisioning_status),
                aade_statement_status = VALUES(aade_statement_status),
                raw_json = VALUES(raw_json)";

    $stmt = mysqli_prepare($conn, $sql);

    $contractNumber = $data['contract']['contractNumber'] ?? null;
    $contractDate = $data['contract']['contractDate'] ?? null;
    $templateVersion = $data['contract']['templateVersion'] ?? null;
    $provisioningStatus = $data['provisioning']['status'] ?? null;
    $aadeStatementStatus = $data['aadeStatement']['status'] ?? null;
    $rawJson = json_encode($data, JSON_UNESCAPED_UNICODE);

    mysqli_stmt_bind_param(
        $stmt,
        'sssssssssss',
        $companyAfm,
        $data['requestId'],
        $data['requestType'],
        $data['status'],
        $contractNumber,
        $contractDate,
        $templateVersion,
        $provisioningStatus,
        $aadeStatementStatus,
        $idempotencyKey,
        $rawJson
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Επιστρέφει την τοπικά αποθηκευμένη αίτηση Novus βάσει requestId, ή null.
function get_novus_request($novusRequestId) {
    $conn = get_db();
    $stmt = mysqli_prepare($conn, 'SELECT * FROM novus_requests WHERE novus_request_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $novusRequestId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

// Επιστρέφει όλες τις αιτήσεις Novus για ένα ΑΦΜ, πιο πρόσφατες πρώτα.
function list_novus_requests_by_afm($afm) {
    $conn = get_db();
    $stmt = mysqli_prepare($conn, 'SELECT * FROM novus_requests WHERE company_afm = ? ORDER BY created_at DESC');
    mysqli_stmt_bind_param($stmt, 's', $afm);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $rows;
}

// True αν έχουμε ήδη καταγράψει αυτό το eventId (προστασία από διπλά webhook deliveries).
function webhook_event_exists($eventId) {
    $conn = get_db();
    $stmt = mysqli_prepare($conn, 'SELECT id FROM novus_webhook_events WHERE event_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $eventId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $exists = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    return $exists;
}

// Καταγράφει ένα webhook event. Επιστρέφει το id της νέας εγγραφής.
function save_webhook_event($eventId, $eventType, $requestId, $companyAfm, $occurredAt, $signatureValid, $payload) {
    $conn = get_db();

    $sql = 'INSERT INTO novus_webhook_events
                (event_id, event_type, novus_request_id, company_afm, occurred_at, signature_valid, payload_json)
            VALUES (?, ?, ?, ?, ?, ?, ?)';

    $stmt = mysqli_prepare($conn, $sql);

    $occurredAtSql = null;
    if ($occurredAt) {
        $ts = strtotime($occurredAt);
        if ($ts !== false) {
            $occurredAtSql = date('Y-m-d H:i:s', $ts);
        }
    }
    $signatureValidInt = $signatureValid ? 1 : 0;
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

    mysqli_stmt_bind_param(
        $stmt,
        'sssssis',
        $eventId,
        $eventType,
        $requestId,
        $companyAfm,
        $occurredAtSql,
        $signatureValidInt,
        $payloadJson
    );

    mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    return $id;
}

// Σημειώνει ότι στάλθηκε (ή όχι) το email ειδοποίησης για ένα event.
function mark_webhook_event_email_sent($eventDbId, $sent) {
    $conn = get_db();
    $stmt = mysqli_prepare($conn, 'UPDATE novus_webhook_events SET email_sent = ? WHERE id = ?');
    $sentInt = $sent ? 1 : 0;
    mysqli_stmt_bind_param($stmt, 'ii', $sentInt, $eventDbId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
