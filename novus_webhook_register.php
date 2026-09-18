<?php
// Μονή χρήση: δηλώνει το endpoint μας στη Novus (POST /api/v1/webhooks) και δείχνει
// το secret που επιστρέφεται — ΜΙΑ ΦΟΡΑ. Αντιγράψτε το αμέσως στο config.php
// ($NOVUS_WEBHOOK_SECRET) και μετά διαγράψτε ή προστατέψτε αυτό το αρχείο.

require_once __DIR__ . '/novus.php';

header('Content-Type: text/plain; charset=utf-8');

global $APP_BASE_URL;

if (empty($APP_BASE_URL)) {
    die('Ρυθμίστε πρώτα το $APP_BASE_URL στο config.php.');
}

$webhookUrl = rtrim($APP_BASE_URL, '/') . '/novus_webhook.php';

if (($_GET['action'] ?? '') === 'list') {
    $response = novus_list_webhooks();
    echo $response['ok']
        ? print_r($response['data'], true)
        : 'Σφάλμα: ' . novus_format_error($response['error'], $response['raw_error']);
    exit;
}

echo "Δηλώνω webhook URL: $webhookUrl\n\n";

$response = novus_register_webhook($webhookUrl);

if (!$response['ok']) {
    echo 'Σφάλμα: ' . novus_format_error($response['error'], $response['raw_error']) . "\n";
    exit;
}

echo "Επιτυχία!\n\n";
echo "endpointId: " . ($response['data']['endpointId'] ?? '—') . "\n";
echo "secret:     " . ($response['data']['secret'] ?? '—') . "\n\n";
echo "ΣΗΜΑΝΤΙΚΟ: αντιγράψτε το secret στο config.php ως \$NOVUS_WEBHOOK_SECRET — δεν ξαναεμφανίζεται.\n";
echo "Μετά διαγράψτε αυτό το αρχείο (novus_webhook_register.php) από τον server.\n";
