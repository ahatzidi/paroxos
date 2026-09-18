<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/novus.php';

$requestId = trim($_GET['id'] ?? '');
if ($requestId === '') {
    die('Λείπει το requestId. <a href="index.php">Αρχική</a>.');
}

$response = novus_get_request($requestId);

if (!$response['ok']) {
    http_response_code($response['http_code'] === 404 ? 404 : 502);
    die('Σφάλμα ανάκτησης αίτησης: ' . htmlspecialchars(novus_format_error($response['error'], $response['raw_error']), ENT_QUOTES));
}

$data = $response['data'];

$local = get_novus_request($requestId);
$afm = $local['company_afm'] ?? ($data['companyDetails']['vatNumber'] ?? '');
save_novus_request($afm, $data);

$statusLabels = [
    'PENDING_SIGNATURE' => 'Αναμονή υπογραφής',
    'UNDER_REVIEW' => 'Υπό έλεγχο από τη Novus',
    'ACTION_REQUIRED' => 'Απαιτείται ενέργεια — απορρίφθηκε το αρχείο',
    'APPROVED' => 'Εγκρίθηκε',
    'REJECTED' => 'Απορρίφθηκε',
    'CANCELLED' => 'Ακυρώθηκε',
];
$statusLabel = $statusLabels[$data['status']] ?? $data['status'];

$uploadMessage = $_GET['uploaded'] ?? null;
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Αίτηση Novus <?= htmlspecialchars($data['requestId'], ENT_QUOTES) ?></title>
<style>
    body { font-family: system-ui, sans-serif; max-width: 700px; margin: 40px auto; padding: 0 16px; color: #1a1a1a; }
    h1 { font-size: 1.4rem; }
    h2 { font-size: 1.05rem; margin-top: 28px; border-bottom: 1px solid #eee; padding-bottom: 6px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table td, table th { text-align: left; padding: 8px; border-bottom: 1px solid #eee; vertical-align: top; }
    table th { width: 220px; color: #555; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 0.85rem; background: #e0e7ff; color: #3730a3; }
    .badge.approved, .badge.completed { background: #d1fae5; color: #065f46; }
    .badge.rejected, .badge.action_required, .badge.cancelled, .badge.failed { background: #fde8e8; color: #9b1c1c; }
    .notice { background: #fef3c7; color: #92400e; padding: 10px 14px; border-radius: 6px; margin: 12px 0; }
    .callout { background: #fff7ed; border: 2px solid #f59e0b; color: #7c2d12; padding: 14px 18px; border-radius: 8px; margin: 16px 0; font-size: 1.02rem; }
    .success { background: #d1fae5; color: #065f46; padding: 10px 14px; border-radius: 6px; margin: 12px 0; }
    .btn { display: inline-block; padding: 10px 20px; font-size: 1rem; background: #1a56db; color: #fff; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; margin-top: 8px; }
    .btn:hover { background: #1544ab; }
    input[type="file"] { margin-top: 8px; }
</style>
</head>
<body>

<h1>Αίτηση Novus</h1>
<p>
    ΑΦΜ: <strong><?= htmlspecialchars($afm, ENT_QUOTES) ?></strong> &middot;
    requestId: <code><?= htmlspecialchars($data['requestId'], ENT_QUOTES) ?></code>
</p>

<p><span class="badge <?= strtolower($data['status']) ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES) ?></span></p>

<?php if ($data['status'] === 'PENDING_SIGNATURE'): ?>
    <div class="callout">
        Η σύμβασή σας έχει ετοιμαστεί. Πρέπει να την κατεβάσετε, να την υπογράψετε, και να
        την ανεβάσετε στο πεδίο «Ανέβασμα» ώστε να σταλεί στον πάροχο.
    </div>
<?php elseif (!empty($data['message'])): ?>
    <p><?= htmlspecialchars($data['message'], ENT_QUOTES) ?></p>
<?php endif; ?>

<?php if ($uploadMessage === '1'): ?>
    <div class="success">Η υπογεγραμμένη σύμβαση ανέβηκε επιτυχώς. Η αίτηση είναι τώρα υπό έλεγχο.</div>
<?php endif; ?>

<?php if ($data['requestType'] === 'NEW_CONTRACT' && !empty($data['contract'])): ?>
    <h2>Σύμβαση</h2>
    <table>
        <tr><th>Αριθμός σύμβασης</th><td><?= htmlspecialchars($data['contract']['contractNumber'], ENT_QUOTES) ?></td></tr>
        <tr><th>Ημερομηνία</th><td><?= htmlspecialchars($data['contract']['contractDate'], ENT_QUOTES) ?></td></tr>
    </table>

    <a class="btn" href="novus_contract_download.php?id=<?= urlencode($data['requestId']) ?>&kind=unsigned">
        Κατέβασμα σύμβασης (μη υπογεγραμμένη)
    </a>

    <?php if (in_array($data['status'], ['PENDING_SIGNATURE', 'ACTION_REQUIRED'], true)): ?>
        <h2>Ανέβασμα υπογεγραμμένης σύμβασης</h2>
        <form method="post" action="novus_signed_contract_upload.php" enctype="multipart/form-data">
            <input type="hidden" name="requestId" value="<?= htmlspecialchars($data['requestId'], ENT_QUOTES) ?>">
            <input type="file" name="contractFile" accept="application/pdf" required>
            <div><button class="btn" type="submit">Ανέβασμα</button></div>
        </form>
    <?php elseif (in_array($data['status'], ['UNDER_REVIEW', 'APPROVED'], true)): ?>
        <a class="btn" href="novus_contract_download.php?id=<?= urlencode($data['requestId']) ?>&kind=signed">
            Κατέβασμα υπογεγραμμένης σύμβασης
        </a>
    <?php endif; ?>
<?php elseif ($data['requestType'] === 'LINK_EXISTING'): ?>
    <div class="notice">Το ΑΦΜ έχει ήδη ενεργή σύμβαση μέσω άλλου software house — δεν χρειάζεται νέα σύμβαση/υπογραφή.</div>
<?php endif; ?>

<?php if (!empty($data['provisioning'])): ?>
    <h2>Ενεργοποίηση</h2>
    <table>
        <tr><th>Κατάσταση</th><td><?= htmlspecialchars($data['provisioning']['status'], ENT_QUOTES) ?></td></tr>
        <tr><th>Καταχώρηση πελάτη</th><td><?= htmlspecialchars($data['provisioning']['clientAdded'] ?? '-', ENT_QUOTES) ?></td></tr>
        <tr><th>Σύνδεση</th><td><?= htmlspecialchars($data['provisioning']['clientLinked'] ?? '-', ENT_QUOTES) ?></td></tr>
        <tr><th>Σύμβαση</th><td><?= htmlspecialchars($data['provisioning']['contractUploaded'] ?? '-', ENT_QUOTES) ?></td></tr>
        <tr><th>Δήλωση ΑΑΔΕ</th><td><?= htmlspecialchars($data['provisioning']['statementSent'] ?? '-', ENT_QUOTES) ?></td></tr>
        <?php if (!empty($data['provisioning']['error'])): ?>
        <tr><th>Σφάλμα</th><td><?= htmlspecialchars($data['provisioning']['error'], ENT_QUOTES) ?></td></tr>
        <?php endif; ?>
    </table>
<?php endif; ?>

<?php if (!empty($data['aadeStatement'])): ?>
    <h2>Δήλωση Παρόχου (ΑΑΔΕ)</h2>
    <table>
        <tr><th>Κατάσταση</th><td><?= htmlspecialchars($data['aadeStatement']['status'], ENT_QUOTES) ?></td></tr>
        <?php if (!empty($data['aadeStatement']['acceptDate'])): ?>
        <tr><th>Ημ. αποδοχής</th><td><?= htmlspecialchars($data['aadeStatement']['acceptDate'], ENT_QUOTES) ?></td></tr>
        <?php endif; ?>
    </table>
<?php endif; ?>

<p style="margin-top:24px;"><a href="novus_request_view.php?id=<?= urlencode($data['requestId']) ?>">&#8635; Ανανέωση κατάστασης</a></p>

</body>
</html>
