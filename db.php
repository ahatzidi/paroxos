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
