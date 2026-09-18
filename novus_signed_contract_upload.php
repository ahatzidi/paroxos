<?php
require_once __DIR__ . '/novus.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$requestId = trim($_POST['requestId'] ?? '');

if ($requestId === '' || empty($_FILES['contractFile']) || $_FILES['contractFile']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    die('Δεν στάλθηκε αρχείο. <a href="novus_request_view.php?id=' . urlencode($requestId) . '">Επιστροφή</a>.');
}

$file = $_FILES['contractFile'];

// Βασικός έλεγχος τύπου/μεγέθους πριν το στείλουμε στη Novus (η ίδια επίσης το ελέγχει).
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($mime !== 'application/pdf') {
    http_response_code(415);
    die('Το αρχείο πρέπει να είναι PDF. <a href="novus_request_view.php?id=' . urlencode($requestId) . '">Επιστροφή</a>.');
}

if ($file['size'] > 15 * 1024 * 1024) {
    http_response_code(413);
    die('Το αρχείο ξεπερνά τα 15MB. <a href="novus_request_view.php?id=' . urlencode($requestId) . '">Επιστροφή</a>.');
}

$response = novus_upload_signed_contract($requestId, $file['tmp_name'], $file['name']);

if (!$response['ok']) {
    http_response_code(422);
    die('Το ανέβασμα απέτυχε: ' . htmlspecialchars(novus_format_error($response['error'], $response['raw_error']), ENT_QUOTES)
        . ' <a href="novus_request_view.php?id=' . urlencode($requestId) . '">Επιστροφή</a>.');
}

header('Location: novus_request_view.php?id=' . urlencode($requestId) . '&uploaded=1');
exit;
