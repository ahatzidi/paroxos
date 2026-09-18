# Paroxos

Απλή εφαρμογή σε plain PHP/HTML (χωρίς frameworks, χωρίς composer) που θα
συνδέεται με δύο πλατφόρμες:

1. **ΑΑΔΕ** — βασική αναζήτηση στοιχείων επιχειρήσεων βάσει ΑΦΜ (webservice `RgWsPublic2`).
2. **Novus Conceptus** — αίτηση σύνδεσης πελάτη ως πάροχος ηλεκτρονικής τιμολόγησης (Novus Onboarding API v1.0).

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

## Βήμα 2: Αίτηση σύνδεσης με Novus

### Απαιτήσεις

- Επέκταση `curl` (ενεργή by default)
- API key από τη Novus (ζητήστε πρώτα το δοκιμαστικό, από `provider-dev.timologisi.online`)

### Ρύθμιση

Στο `config.php` συμπληρώστε:

```php
$NOVUS_API_URL = 'https://provider-dev.timologisi.online'; // ή provider.timologisi.online στην παραγωγή
$NOVUS_API_KEY = 'nvuscnspts_...';
```

Ο πίνακας `novus_requests` δημιουργείται μαζί με τα υπόλοιπα από το `sql/schema.sql`.

### Ροή

1. Ο χρήστης κάνει αναζήτηση ΑΦΜ στο `index.php` (Βήμα 1) — τα στοιχεία αποθηκεύονται στο `companies`.
2. Πατάει «Αίτηση σύνδεσης με Novus» → `novus_request_new.php?afm=...`, μια φόρμα προσυμπληρωμένη
   με ό,τι ήδη γνωρίζουμε από την ΑΑΔΕ (επωνυμία, ΔΟΥ, διεύθυνση) — ο χρήστης συμπληρώνει μόνο τα
   υπόλοιπα (επικοινωνία, νόμιμος εκπρόσωπος, τύπος συναλλαγών, στοιχεία ISP αν υπάρχει B2C).
3. Το `novus_request_create.php` στέλνει `POST /api/v1/requests` στη Novus, αποθηκεύει την αίτηση
   στο `novus_requests` και ανακατευθύνει στο `novus_request_view.php?id=<requestId>`.
4. Στο `novus_request_view.php`:
   - βλέπεις την τρέχουσα κατάσταση (κάνει live `GET /api/v1/requests/{id}` κάθε φορά)
   - αν χρειάζεται υπογραφή, κατεβάζεις το PDF σύμβασης (`novus_contract_download.php`, proxy
     ώστε να μην εκτίθεται το API key) και ανεβάζεις την υπογεγραμμένη (`novus_signed_contract_upload.php`)
   - μετά την έγκριση βλέπεις την πρόοδο ενεργοποίησης (`provisioning`) και τη δήλωση στην ΑΑΔΕ (`aadeStatement`)

### Αρχεία

- `novus.php` — όλες οι κλήσεις προς το Novus API (`novus_create_request`, `novus_get_request`,
  `novus_download_contract`, `novus_upload_signed_contract`, κ.λπ.), πάνω σε `curl`.
- `novus_request_new.php` — η φόρμα αίτησης, προσυμπληρωμένη από το `companies`.
- `novus_request_create.php` — POST handler, στέλνει την αίτηση στη Novus με `Idempotency-Key`.
- `novus_request_view.php` — κατάσταση αίτησης, κατέβασμα/ανέβασμα σύμβασης.
- `novus_contract_download.php` — proxy κατεβάσματος PDF (το API key μένει στον server).
- `novus_signed_contract_upload.php` — ανέβασμα υπογεγραμμένης σύμβασης.

> Το webservice απαιτεί πραγματικό ΑΦΜ με σωστό check digit — δοκιμαστικά «123456789» απορρίπτονται.
> Δείτε το `9. Παγίδες` στο έγγραφο Novus Onboarding API για τις πιο συνηθισμένες παγίδες
> (`contract: null` σε `LINK_EXISTING`, μία ανοιχτή αίτηση ανά ΑΦΜ, κ.λπ.).
