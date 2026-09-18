<?php
// Endpoint που δηλώνουμε στη Novus (POST /api/v1/webhooks) για ειδοποιήσεις.
// Κανόνες από το έγγραφο Novus Onboarding API:
//  1. Ελέγχουμε το X-Novus-Signature (HMAC-SHA256 του raw body με το secret μας).
//  2. Αγνοούμε διπλά eventId (retries).
//  3. Το webhook λέει "κάτι άλλαξε", όχι την αλήθεια -> κάνουμε GET στο πραγματικό αίτημα.
//  4. Απαντάμε 2xx γρήγορα.

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/novus.php';
require_once __DIR__ . '/mail.php';

header('Content-Type: text/plain; charset=utf-8');

$rawBody = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_X_NOVUS_SIGNATURE'] ?? '';
$eventIdHeader = $_SERVER['HTTP_X_NOVUS_EVENT_ID'] ?? '';

$signatureValid = novus_verify_webhook_signature($rawBody, $signatureHeader);

if (!$signatureValid) {
    error_log('Novus webhook: μη έγκυρη ή απούσα υπογραφή (eventId header: ' . $eventIdHeader . ')');
    http_response_code(401);
    echo 'invalid signature';
    exit;
}

$payload = json_decode($rawBody, true);

if (!is_array($payload) || empty($payload['eventId'])) {
    http_response_code(400);
    echo 'invalid payload';
    exit;
}

$eventId = $payload['eventId'];
$eventType = $payload['event'] ?? '';
$occurredAt = $payload['occurredAt'] ?? null;
$requestId = $payload['data']['requestId'] ?? null;

// Retry της Novus με ήδη γνωστό eventId: απλώς το επιβεβαιώνουμε, δεν το ξαναεπεξεργαζόμαστε.
if (webhook_event_exists($eventId)) {
    http_response_code(200);
    echo 'duplicate, ok';
    exit;
}

$local = $requestId ? get_novus_request($requestId) : null;
$companyAfm = $local['company_afm'] ?? null;

// Η πραγματική κατάσταση έρχεται πάντα από GET, όχι από το ίδιο το webhook.
$freshData = null;
if ($requestId) {
    $response = novus_get_request($requestId);
    if ($response['ok']) {
        $freshData = $response['data'];
        $companyAfm = $companyAfm ?: ($freshData['companyDetails']['vatNumber'] ?? null);
        save_novus_request($companyAfm ?: '', $freshData);
    } else {
        error_log('Novus webhook: αποτυχία GET /requests/' . $requestId . ' — ' . novus_format_error($response['error'], $response['raw_error']));
    }
}

$eventDbId = save_webhook_event($eventId, $eventType, $requestId, $companyAfm, $occurredAt, true, $payload);

$mailResult = novus_send_webhook_notification($eventType, $requestId, $companyAfm, $payload, $freshData);
mark_webhook_event_email_sent($eventDbId, $mailResult['ok']);

if (!$mailResult['ok']) {
    error_log('Novus webhook: αποτυχία αποστολής email — ' . $mailResult['error']);
}

http_response_code(200);
echo 'ok';

// Χτίζει και στέλνει το email ειδοποίησης για ένα webhook event.
function novus_send_webhook_notification($eventType, $requestId, $companyAfm, $payload, $freshData) {
    global $mail_to, $APP_BASE_URL;

    if (empty($mail_to)) {
        return ['ok' => false, 'error' => 'Δεν έχει οριστεί $mail_to στο config.php'];
    }

    $company = $companyAfm ? get_company_by_afm($companyAfm) : null;
    $companyName = $company['onomasia'] ?? ($freshData['companyDetails']['legalName'] ?? '—');

    $status = $freshData['status'] ?? ($payload['data']['status'] ?? '—');
    $previousStatus = $payload['data']['previousStatus'] ?? '—';
    $message = $payload['data']['message'] ?? ($freshData['message'] ?? '');

    $subject = sprintf('[Paroxos] Novus: %s — %s', $eventType, $companyName !== '—' ? $companyName : $requestId);

    $viewUrl = $requestId
        ? rtrim((string) $APP_BASE_URL, '/') . '/novus_request_view.php?id=' . urlencode($requestId)
        : null;

    $rows = [
        'Event' => $eventType,
        'Event ID' => $payload['eventId'] ?? '—',
        'Ημερομηνία' => $payload['occurredAt'] ?? '—',
        'Request ID' => $requestId ?: '—',
        'ΑΦΜ' => $companyAfm ?: '—',
        'Επιχείρηση' => $companyName,
        'Κατάσταση' => $status,
        'Προηγούμενη κατάσταση' => $previousStatus,
        'Μήνυμα' => $message ?: '—',
    ];

    $htmlRows = '';
    foreach ($rows as $label => $value) {
        $htmlRows .= '<tr><th style="text-align:left;padding:4px 12px 4px 0;color:#555;">'
            . htmlspecialchars($label, ENT_QUOTES) . '</th><td style="padding:4px 0;">'
            . nl2br(htmlspecialchars((string) $value, ENT_QUOTES)) . '</td></tr>';
    }

    $html = '<div style="font-family: system-ui, sans-serif;">'
        . '<h2>Νέο event από τη Novus</h2>'
        . '<table>' . $htmlRows . '</table>';

    if ($viewUrl) {
        $html .= '<p><a href="' . htmlspecialchars($viewUrl, ENT_QUOTES) . '">Προβολή αίτησης &rarr;</a></p>';
    }

    $html .= '</div>';

    return smtp_send_mail($mail_to, $subject, $html);
}
