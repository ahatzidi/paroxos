# Paroxos

Απλή εφαρμογή σε plain PHP/HTML (χωρίς frameworks, χωρίς composer) που θα
συνδέεται με δύο πλατφόρμες:

1. **ΑΑΔΕ** — βασική αναζήτηση στοιχείων επιχειρήσεων βάσει ΑΦΜ (webservice `RgWsPublic2`).
2. **Πάροχος** — θα προστεθεί σε επόμενο βήμα.

## Βήμα 1: Αναζήτηση επιχείρησης βάσει ΑΦΜ

### Απαιτήσεις

- PHP 8+ με τις επεκτάσεις `soap` και `mysqli` (ενεργές by default στα
  περισσότερα shared hosting)
- MariaDB/MySQL
- Στοιχεία πρόσβασης στο δημόσιο webservice Μητρώου της ΑΑΔΕ. Γίνεται δωρεάν
  εγγραφή/ενεργοποίηση μέσω του ΑΦΜ της επιχείρησής σας εδώ:
  https://www1.aade.gr/webtax/wspublicreg/wspublicreg.php

### Εγκατάσταση (3 βήματα)

1. Αντιγράψτε το `config.example.php` σε `config.php` και συμπληρώστε:
   - στοιχεία σύνδεσης MariaDB
   - το username/password από την ΑΑΔΕ
   - το `AADE_AFM_CALLED_BY` (το ΑΦΜ σας)

   ```bash
   cp config.example.php config.php
   ```

2. Δημιουργήστε τη βάση και τον πίνακα:

   ```bash
   mysql -u root -p -e "CREATE DATABASE paroxos CHARACTER SET utf8mb4"
   mysql -u root -p paroxos < sql/schema.sql
   ```

3. Ανεβάστε όλα τα αρχεία σε οποιοδήποτε hosting με PHP, ή δοκιμάστε τοπικά:

   ```bash
   php -S localhost:8000
   ```

   και ανοίξτε `http://localhost:8000`.

### Αρχεία

- `index.php` — η φόρμα αναζήτησης ΑΦΜ (όλο το UI + λογική σε ένα αρχείο).
- `aade.php` — συνάρτηση `aade_search_afm($afm)` που καλεί το SOAP webservice
  της ΑΑΔΕ (με WS-Security header, όπως απαιτείται).
- `db.php` — σύνδεση mysqli + συνάρτηση `save_company()` που αποθηκεύει τα
  αποτελέσματα στον πίνακα `companies` (cache, ώστε να μη γίνεται κλήση στην
  ΑΑΔΕ σε κάθε προβολή).
- `config.php` — τα δικά σας στοιχεία (δεν ανεβαίνει σε git).

> Σημείωση: τα ονόματα πεδίων της απάντησης της ΑΑΔΕ ενδέχεται να διαφέρουν
> ελαφρώς ανάλογα με την έκδοση του WSDL. Αν κάποιο πεδίο εμφανίζεται κενό,
> κάντε `var_dump($response['raw'])` μέσα στο `aade_search_afm()` για να δείτε
> τα ακριβή ονόματα που επιστρέφει η ΑΑΔΕ.
