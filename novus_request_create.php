<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/novus.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

function field($name) {
    return trim($_POST[$name] ?? '');
}

$afm = preg_replace('/\D/', '', field('afm'));
$idempotencyKey = field('idempotency_key') ?: novus_uuid4();
$transactionTypes = $_POST['transactionTypes'] ?? [];

$payload = [
    'companyDetails' => [
        'legalName' => field('legalName'),
        'tradeName' => field('tradeName'),
        'vatNumber' => $afm,
        'taxOffice' => field('taxOffice'),
        'transactionTypes' => array_values(array_intersect($transactionTypes, ['B2B', 'B2C'])),
    ],
    'address' => [
        'city' => field('city'),
        'streetAddress' => field('streetAddress'),
        'postalCode' => field('postalCode'),
    ],
    'contactInfo' => [
        'email' => field('email'),
        'phone' => field('phone'),
        'backupPhone' => field('backupPhone'),
    ],
    'administrator' => [
        'fullName' => field('adminFullName'),
        'vatNumber' => field('adminVatNumber'),
    ],
];

if (in_array('B2C', $payload['companyDetails']['transactionTypes'], true)) {
    $payload['ispDetails'] = [
        'providerName' => field('ispProviderName'),
        'contractNumber' => field('ispContractNumber'),
        'contractDate' => field('ispContractDate'),
    ];
}

if (empty($payload['contactInfo']['backupPhone'])) {
    unset($payload['contactInfo']['backupPhone']);
}
if (empty($payload['companyDetails']['tradeName'])) {
    unset($payload['companyDetails']['tradeName']);
}

$response = novus_create_request($payload, $idempotencyKey);

if (!$response['ok']) {
    $errorMessage = novus_format_error($response['error'], $response['raw_error']);

    // Διπλή ανοιχτή αίτηση: δείξε τη σχετική αίτηση αντί για σκέτο σφάλμα.
    if (($response['error']['code'] ?? '') === 'DUPLICATE_OPEN_REQUEST') {
        $existingId = $response['error']['context']['existingRequestId'] ?? null;
        if ($existingId) {
            header('Location: novus_request_view.php?id=' . urlencode($existingId));
            exit;
        }
    }

    http_response_code(422);
    ?>
    <!DOCTYPE html>
    <html lang="el">
    <head><meta charset="UTF-8"><title>Σφάλμα αίτησης Novus</title></head>
    <body style="font-family: system-ui, sans-serif; max-width: 700px; margin: 40px auto; padding: 0 16px;">
        <h1>Η αίτηση απέτυχε</h1>
        <p style="background:#fde8e8;color:#9b1c1c;padding:12px 16px;border-radius:6px;">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES) ?>
        </p>
        <p><a href="novus_request_new.php?afm=<?= urlencode($afm) ?>">&larr; Επιστροφή στη φόρμα</a></p>
    </body>
    </html>
    <?php
    exit;
}

save_novus_request($afm, $response['data'], $idempotencyKey);

header('Location: novus_request_view.php?id=' . urlencode($response['data']['requestId']));
exit;
