<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/novus.php';

$afm = preg_replace('/\D/', '', $_GET['afm'] ?? '');
if (strlen($afm) !== 9) {
    die('Μη έγκυρο ΑΦΜ. <a href="index.php">Επιστροφή στην αναζήτηση</a>.');
}

$company = get_company_by_afm($afm);
if (!$company) {
    die('Δεν βρέθηκαν αποθηκευμένα στοιχεία για το ΑΦΜ ' . htmlspecialchars($afm, ENT_QUOTES) . '. <a href="index.php">Κάντε πρώτα αναζήτηση στην ΑΑΔΕ</a>.');
}

$idempotencyKey = novus_uuid4();
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Αίτηση σύνδεσης με Novus</title>
<style>
    body { font-family: system-ui, sans-serif; max-width: 700px; margin: 40px auto; padding: 0 16px; color: #1a1a1a; }
    h1 { font-size: 1.4rem; }
    h2 { font-size: 1.05rem; margin-top: 28px; border-bottom: 1px solid #eee; padding-bottom: 6px; }
    label { display: block; font-size: 0.85rem; color: #555; margin-top: 12px; margin-bottom: 4px; }
    input[type="text"], input[type="email"], input[type="date"], select {
        width: 100%; padding: 8px; font-size: 1rem; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box;
    }
    input[readonly] { background: #f5f5f5; color: #666; }
    .checkboxes { display: flex; gap: 20px; margin-top: 6px; }
    .checkboxes label { display: flex; align-items: center; gap: 6px; font-size: 1rem; color: #1a1a1a; margin: 0; }
    .checkboxes input { width: auto; }
    button { margin-top: 24px; padding: 12px 24px; font-size: 1rem; background: #1a56db; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
    button:hover { background: #1544ab; }
    .hint { font-size: 0.8rem; color: #888; margin-top: 2px; }
    #isp-fields { display: none; border: 1px dashed #ccc; border-radius: 8px; padding: 12px; margin-top: 10px; }
    #isp-fields.visible { display: block; }
</style>
</head>
<body>

<h1>Αίτηση σύνδεσης με Novus</h1>
<p>ΑΦΜ: <strong><?= htmlspecialchars($afm, ENT_QUOTES) ?></strong> — <?= htmlspecialchars($company['onomasia'], ENT_QUOTES) ?></p>

<form method="post" action="novus_request_create.php">
    <input type="hidden" name="afm" value="<?= htmlspecialchars($afm, ENT_QUOTES) ?>">
    <input type="hidden" name="idempotency_key" value="<?= htmlspecialchars($idempotencyKey, ENT_QUOTES) ?>">

    <h2>Στοιχεία επιχείρησης</h2>

    <label for="legalName">Επωνυμία</label>
    <input type="text" id="legalName" name="legalName" value="<?= htmlspecialchars($company['onomasia'], ENT_QUOTES) ?>" required>

    <label for="tradeName">Διακριτικός τίτλος</label>
    <input type="text" id="tradeName" name="tradeName" value="<?= htmlspecialchars($company['commer_title'], ENT_QUOTES) ?>">

    <label for="vatNumber">ΑΦΜ</label>
    <input type="text" id="vatNumber" name="vatNumber" value="<?= htmlspecialchars($afm, ENT_QUOTES) ?>" readonly>

    <label for="taxOffice">ΔΟΥ</label>
    <input type="text" id="taxOffice" name="taxOffice" value="<?= htmlspecialchars($company['doy_descr'], ENT_QUOTES) ?>" required>

    <label>Τύπος συναλλαγών</label>
    <div class="checkboxes">
        <label><input type="checkbox" name="transactionTypes[]" value="B2B" id="tt-b2b" checked> B2B</label>
        <label><input type="checkbox" name="transactionTypes[]" value="B2C" id="tt-b2c"> B2C</label>
    </div>
    <div class="hint">Τουλάχιστον ένα. Αν επιλέξετε B2C, χρειάζονται και τα στοιχεία παρόχου internet παρακάτω.</div>

    <h2>Διεύθυνση</h2>

    <label for="city">Πόλη</label>
    <input type="text" id="city" name="city" value="<?= htmlspecialchars($company['postal_area_description'], ENT_QUOTES) ?>" required>

    <label for="streetAddress">Οδός &amp; αριθμός</label>
    <input type="text" id="streetAddress" name="streetAddress" value="<?= htmlspecialchars(trim($company['postal_address'] . ' ' . $company['postal_address_no']), ENT_QUOTES) ?>" required>

    <label for="postalCode">Τ.Κ.</label>
    <input type="text" id="postalCode" name="postalCode" value="<?= htmlspecialchars($company['postal_zip_code'], ENT_QUOTES) ?>" required>

    <h2>Στοιχεία επικοινωνίας</h2>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    <label for="phone">Τηλέφωνο</label>
    <input type="text" id="phone" name="phone" placeholder="+30... ή 10ψήφιο" required>

    <label for="backupPhone">Εναλλακτικό τηλέφωνο</label>
    <input type="text" id="backupPhone" name="backupPhone" placeholder="+30... ή 10ψήφιο (προαιρετικό)">

    <h2>Νόμιμος εκπρόσωπος</h2>

    <label for="adminFullName">Ονοματεπώνυμο</label>
    <input type="text" id="adminFullName" name="adminFullName" required>

    <label for="adminVatNumber">ΑΦΜ εκπροσώπου</label>
    <input type="text" id="adminVatNumber" name="adminVatNumber" maxlength="9" pattern="\d{9}" required>

    <h2>Στοιχεία εναλλακτικού παρόχου internet, για παράδειγμα συμβόλαιο κινητής τηλεφωνίας <span class="hint">(υποχρεωτικά μόνο για B2C)</span></h2>

    <div id="isp-fields">
        <label for="ispProviderName">Πάροχος</label>
        <input type="text" id="ispProviderName" name="ispProviderName">

        <label for="ispContractNumber">Αριθμός σύμβασης</label>
        <input type="text" id="ispContractNumber" name="ispContractNumber">

        <label for="ispContractDate">Ημερομηνία σύμβασης</label>
        <input type="date" id="ispContractDate" name="ispContractDate">
    </div>

    <button type="submit">Υποβολή αίτησης</button>
</form>

<script>
    const b2c = document.getElementById('tt-b2c');
    const ispBox = document.getElementById('isp-fields');
    function syncIsp() {
        ispBox.classList.toggle('visible', b2c.checked);
        ['ispProviderName', 'ispContractNumber', 'ispContractDate'].forEach(function (id) {
            document.getElementById(id).required = b2c.checked;
        });
    }
    b2c.addEventListener('change', syncIsp);
    syncIsp();
</script>

</body>
</html>
