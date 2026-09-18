# Paroxos

Εφαρμογή PHP/MariaDB που θα συνδέεται με δύο πλατφόρμες:

1. **ΑΑΔΕ** — βασική αναζήτηση στοιχείων επιχειρήσεων βάσει ΑΦΜ (webservice `RgWsPublic2`).
2. **Πάροχος** — θα προστεθεί σε επόμενο βήμα.

## Βήμα 1: Αναζήτηση επιχείρησης βάσει ΑΦΜ

### Απαιτήσεις

- PHP 8.1+ με τις επεκτάσεις `soap` και `pdo_mysql`
- MariaDB/MySQL
- Στοιχεία πρόσβασης στο δημόσιο webservice Μητρώου της ΑΑΔΕ. Γίνεται δωρεάν
  εγγραφή/ενεργοποίηση μέσω του ΑΦΜ της επιχείρησής σας εδώ:
  https://www1.aade.gr/webtax/wspublicreg/wspublicreg.php

### Εγκατάσταση

```bash
cp config/config.example.php config/config.php
```

Συμπληρώστε στο `config/config.php`:
- τα στοιχεία σύνδεσης στη MariaDB
- το username/password που πήρατε από την ΑΑΔΕ
- το `afm_called_by` (το ΑΦΜ για λογαριασμό του οποίου γίνεται η κλήση)

Δημιουργήστε τη βάση και εκτελέστε το schema:

```bash
mysql -u root -p -e "CREATE DATABASE paroxos CHARACTER SET utf8mb4"
mysql -u root -p paroxos < sql/schema.sql
```

Τρέξτε τον ενσωματωμένο server για δοκιμή:

```bash
php -S localhost:8000 -t public
```

και ανοίξτε `http://localhost:8000`.

### Πώς λειτουργεί

- `public/index.php` — η φόρμα αναζήτησης ΑΦΜ.
- `src/Aade/AadeClient.php` — SOAP client με WS-Security header για το
  webservice `RgWsPublicAfmMethod` της ΑΑΔΕ.
- `src/Aade/CompanyRepository.php` — αποθηκεύει/ενημερώνει τα αποτελέσματα
  στον πίνακα `companies` (cache, ώστε να μη γίνεται κλήση στην ΑΑΔΕ σε κάθε
  προβολή).

> Σημείωση: τα ονόματα πεδίων της απάντησης της ΑΑΔΕ ενδέχεται να διαφέρουν
> ελαφρώς ανάλογα με την έκδοση του WSDL. Αν κάποιο πεδίο εμφανίζεται κενό,
> ελέγξτε το raw response (`$response['raw']` στο `AadeClient::searchByAfm`)
> για τα ακριβή ονόματα.
