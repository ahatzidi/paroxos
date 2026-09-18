<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/aade.php';

$afmInput = '';
$result = null;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $afmInput = trim($_POST['afm'] ?? '');

    $response = aade_search_afm($afmInput);

    if (!$response['ok']) {
        $errorMessage = $response['error'];
    } else {
        $result = $response['data'];
        save_company($result, $response['raw']);
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Αναζήτηση Επιχείρησης ΑΑΔΕ</title>
<style>
    body { font-family: system-ui, sans-serif; max-width: 700px; margin: 40px auto; padding: 0 16px; color: #1a1a1a; }
    h1 { font-size: 1.4rem; }
    form { display: flex; gap: 8px; margin-bottom: 24px; }
    input[type="text"] { flex: 1; padding: 10px; font-size: 1rem; border: 1px solid #ccc; border-radius: 6px; }
    button { padding: 10px 20px; font-size: 1rem; background: #1a56db; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
    button:hover { background: #1544ab; }
    .error { background: #fde8e8; color: #9b1c1c; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; }
    table td, table th { text-align: left; padding: 8px; border-bottom: 1px solid #eee; vertical-align: top; }
    table th { width: 220px; color: #555; }
    ul { margin: 0; padding-left: 18px; }
    .btn { display: inline-block; margin-top: 16px; padding: 10px 20px; font-size: 1rem; background: #1a56db; color: #fff; border-radius: 6px; text-decoration: none; }
    .btn:hover { background: #1544ab; }
</style>
</head>
<body>

<h1>Αναζήτηση στοιχείων επιχείρησης (ΑΑΔΕ)</h1>

<form method="post" action="">
    <input
        type="text"
        name="afm"
        placeholder="ΑΦΜ (9 ψηφία)"
        maxlength="9"
        pattern="\d{9}"
        inputmode="numeric"
        value="<?= htmlspecialchars($afmInput, ENT_QUOTES) ?>"
        required
        autofocus
    >
    <button type="submit">Αναζήτηση</button>
</form>

<?php if ($errorMessage): ?>
    <div class="error"><?= htmlspecialchars($errorMessage, ENT_QUOTES) ?></div>
<?php endif; ?>

<?php if ($result): ?>
    <table>
        <tr><th>ΑΦΜ</th><td><?= htmlspecialchars($result['afm'], ENT_QUOTES) ?></td></tr>
        <tr><th>Επωνυμία</th><td><?= htmlspecialchars($result['onomasia'], ENT_QUOTES) ?></td></tr>
        <tr><th>Διακριτικός τίτλος</th><td><?= htmlspecialchars($result['commer_title'], ENT_QUOTES) ?></td></tr>
        <tr><th>Διεύθυνση</th><td><?= htmlspecialchars(trim($result['postal_address'] . ' ' . $result['postal_address_no']), ENT_QUOTES) ?></td></tr>
        <tr><th>Περιοχή / ΤΚ</th><td><?= htmlspecialchars($result['postal_area_description'] . ' ' . $result['postal_zip_code'], ENT_QUOTES) ?></td></tr>
        <tr><th>ΔΟΥ</th><td><?= htmlspecialchars($result['doy_descr'], ENT_QUOTES) ?></td></tr>
        <tr><th>Νομική μορφή</th><td><?= htmlspecialchars($result['legal_status_descr'], ENT_QUOTES) ?></td></tr>
        <tr><th>Κατάσταση</th><td><?= htmlspecialchars($result['firm_flag_descr'], ENT_QUOTES) ?></td></tr>
        <?php if (!empty($result['deactivation_flag_descr'])): ?>
        <tr><th>Ενεργή/Ανενεργή</th><td><?= htmlspecialchars($result['deactivation_flag_descr'], ENT_QUOTES) ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($result['activities'])): ?>
        <tr>
            <th>Δραστηριότητες</th>
            <td>
                <ul>
                <?php foreach ($result['activities'] as $act): ?>
                    <li>
                        <?= htmlspecialchars($act['description'], ENT_QUOTES) ?>
                        <?= !empty($act['kind_descr']) ? ' (' . htmlspecialchars($act['kind_descr'], ENT_QUOTES) . ')' : '' ?>
                    </li>
                <?php endforeach; ?>
                </ul>
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <a class="btn" href="novus_request_new.php?afm=<?= urlencode($result['afm']) ?>">
        Αίτηση σύνδεσης με Novus &rarr;
    </a>
<?php endif; ?>

</body>
</html>
