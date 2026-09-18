<?php
// Proxy κατεβάσματος του PDF σύμβασης — το API key της Novus δεν εκτίθεται ποτέ στον client.

require_once __DIR__ . '/novus.php';

$requestId = trim($_GET['id'] ?? '');
$kind = ($_GET['kind'] ?? 'unsigned') === 'signed' ? 'signed' : 'unsigned';

if ($requestId === '') {
    http_response_code(400);
    die('Λείπει το requestId.');
}

$result = novus_download_contract($requestId, $kind);

if (!$result['ok']) {
    http_response_code($result['http_code'] ?: 502);
    die('Αδυναμία λήψης σύμβασης: ' . htmlspecialchars($result['error'], ENT_QUOTES));
}

$filename = $kind . '-contract-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $requestId) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($result['body']));
echo $result['body'];
