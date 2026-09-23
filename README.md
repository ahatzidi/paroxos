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
- `turnstile.php` — συνάρτηση `turnstile_verify()` που επαληθεύει στο backend το
  Cloudflare Turnstile token της φόρμας αναζήτησης (προστασία από bots).

### Cloudflare Turnstile

Η φόρμα αναζήτησης ΑΦΜ (`index.php`) προστατεύεται με [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/).
Φτιάξτε ένα site στο [Cloudflare dashboard](https://dash.cloudflare.com/?to=/:account/turnstile)
και συμπληρώστε στο `config.php`:

```php
$TURNSTILE_SITE_KEY = '...';   // δημόσιο, μπαίνει στο HTML
$TURNSTILE_SECRET_KEY = '...'; // μυστικό, χρησιμοποιείται μόνο server-side
```

Χωρίς έγκυρο `$TURNSTILE_SECRET_KEY` η επαλήθευση αποτυγχάνει πάντα και η φόρμα
δεν θα δέχεται υποβολές — βεβαιωθείτε ότι είναι συμπληρωμένο πριν βάλετε την
εφαρμογή σε παραγωγή.

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
5. Αν η αίτηση είναι τύπου `NEW_CONTRACT` και το (μη υπογεγραμμένο) αρχείο σύμβασης
   ληφθεί κανονικά από τη Novus, το `novus_request_create.php` στέλνει αυτόματα email
   στον πελάτη (στο `contactInfo.email` της φόρμας) με CC στο `$mail_to`, ενημερώνοντάς
   τον ότι απομένει η εξουσιοδότηση του παρόχου προς την ΑΑΔΕ. Χρησιμοποιεί το πρότυπο
   `email_templates/notification.html`. Στο `LINK_EXISTING` (χωρίς αρχείο σύμβασης) δεν
   στέλνεται αυτό το email.

### Αρχεία

- `novus.php` — όλες οι κλήσεις προς το Novus API (`novus_create_request`, `novus_get_request`,
  `novus_download_contract`, `novus_upload_signed_contract`, κ.λπ.), πάνω σε `curl`.
- `novus_request_new.php` — η φόρμα αίτησης, προσυμπληρωμένη από το `companies`.
- `novus_request_create.php` — POST handler, στέλνει την αίτηση στη Novus με `Idempotency-Key`.
- `novus_request_view.php` — κατάσταση αίτησης, κατέβασμα/ανέβασμα σύμβασης.
- `novus_contract_download.php` — proxy κατεβάσματος PDF (το API key μένει στον server).
- `novus_signed_contract_upload.php` — ανέβασμα υπογεγραμμένης σύμβασης.
- `email_templates/notification.html` — πρότυπο email (πλαίσιο/branding), με `%%MSG%%`
  ως σημείο εισαγωγής του μηνύματος. Γεμίζεται με `render_notification_email()` (στο `mail.php`).

> Το webservice απαιτεί πραγματικό ΑΦΜ με σωστό check digit — δοκιμαστικά «123456789» απορρίπτονται.
> Δείτε το `9. Παγίδες` στο έγγραφο Novus Onboarding API για τις πιο συνηθισμένες παγίδες
> (`contract: null` σε `LINK_EXISTING`, μία ανοιχτή αίτηση ανά ΑΦΜ, κ.λπ.).

## Βήμα 3: Webhook από τη Novus + ειδοποίηση email

Κάθε φορά που αλλάζει κατάσταση μια αίτηση (υπογράφηκε, εγκρίθηκε, ενεργοποιήθηκε ο
πελάτης κ.λπ.), η Novus μπορεί να καλεί ένα endpoint μας αντί να κάνουμε εμείς polling.

### Ρύθμιση

Στο `config.php` συμπληρώστε τα στοιχεία SMTP (Amazon SES) και το δημόσιο URL της
εγκατάστασης:

```php
$NOVUS_WEBHOOK_SECRET = ''; // συμπληρώνεται ΜΕΤΑ την εγγραφή, βλ. παρακάτω
$APP_BASE_URL = 'https://paroxos.totalschool.gr';

$mailhost = 'email-smtp.eu-west-1.amazonaws.com';
$mailport = 587;
$mailusername = '...';
$mailpassword = '...';
$mail_from_email = 'noreply@yourdomain.gr';
$mail_from_name = 'Paroxos';
$mail_to = 'you@yourdomain.gr'; // πού θα φτάνουν οι ειδοποιήσεις
```

Ο πίνακας `novus_webhook_events` δημιουργείται μαζί με τα υπόλοιπα από το `sql/schema.sql`.

### Δήλωση του webhook στη Novus

Όταν είστε έτοιμοι να ενημερώσετε τη Novus, ανοίξτε στο browser (μία φορά):

```
https://paroxos.totalschool.gr/novus_webhook_register.php
```

Θα καλέσει `POST /api/v1/webhooks` και θα τυπώσει ένα `secret` — **εμφανίζεται μόνο
αυτή τη φορά**. Αντιγράψτε το αμέσως στο `config.php` (`$NOVUS_WEBHOOK_SECRET`) και
μετά διαγράψτε ή κλειδώστε το `novus_webhook_register.php` (π.χ. με `.htaccess` ή
διαγραφή από τον server), ώστε να μην μπορεί κανείς να ξαναδηλώσει webhook.

### Πώς λειτουργεί το `novus_webhook.php`

1. Επαληθεύει το header `X-Novus-Signature` (HMAC-SHA256 του raw body με το secret) —
   αν δεν ταιριάζει, απαντά 401 και δεν επεξεργάζεται τίποτα.
2. Αν το `eventId` το έχουμε ήδη δει (retry της Novus), απαντά 200 χωρίς να το
   ξαναεπεξεργαστεί.
3. Κάνει `GET /api/v1/requests/{requestId}` για την πραγματική κατάσταση (το webhook
   λέει μόνο «κάτι άλλαξε», όχι την αλήθεια) και ενημερώνει το `novus_requests`.
4. Καταγράφει το event στο `novus_webhook_events`, συνδεδεμένο με το `requestId` και
   το ΑΦΜ της εταιρίας.
5. Στέλνει email (μέσω SMTP) στο `$mail_to` με τα στοιχεία του event και link προς
   `novus_request_view.php`.

### Αρχεία

- `novus_webhook.php` — το endpoint (URL προς δήλωση: `$APP_BASE_URL/novus_webhook.php`).
- `novus_webhook_register.php` — μονής χρήσης script δήλωσης/λήψης secret.
- `mail.php` — ελάχιστος SMTP client (STARTTLS + AUTH LOGIN), χωρίς PHPMailer/composer.
- Στο `novus.php`: `novus_register_webhook()`, `novus_list_webhooks()`,
  `novus_delete_webhook()`, `novus_verify_webhook_signature()`.
- Στο `db.php`: `webhook_event_exists()`, `save_webhook_event()`, `mark_webhook_event_email_sent()`.

> Η αποστολή email γίνεται συγχρονισμένα μέσα στο ίδιο request πριν απαντήσουμε στη
> Novus. Αν το SMTP είναι αργό, καθυστερεί λίγο το 200 OK — αποδεκτό για τον όγκο
> events που περιγράφει το API, αλλά αν χρειαστεί ποτέ ταχύτερο ack, ο πιο απλός δρόμος
> είναι να καταγράφεται το event πρώτα και να στέλνεται το email με ξεχωριστό cron.

## Βήμα 4: Υπενθύμιση σε πελάτες με status ACTION_REQUIRED

`cron_action_required_reminder.php` είναι ένα CLI-only script (αρνείται να τρέξει μέσω
web request) που:

1. Βρίσκει όλες τις αιτήσεις Novus με `status = 'ACTION_REQUIRED'` που έχουν
   αποθηκευμένο `customer_email` και δεν έχουν λάβει υπενθύμιση τις τελευταίες 24 ώρες.
2. Στέλνει σε κάθε πελάτη email με link προς τη σελίδα του (`novus_request_view.php`) και
   mailto σύνδεσμο προς `support@totalschool.io`. CC στο `$mail_to`.
3. Σημειώνει `last_reminder_sent_at` ώστε να μη στέλνεται ξανά μέσα στο ίδιο 24ωρο, όσο
   συχνά κι αν τρέχει το cron.

Προσθέστε στο cron του server (π.χ. Cloudways Cron Job Management):

```
0 9 * * * /usr/bin/php /full/path/to/paroxos/cron_action_required_reminder.php >> /full/path/to/paroxos/cron.log 2>&1
```

Το `customer_email` καταγράφεται αυτόματα στο `novus_requests` όταν δημιουργείται μια
αίτηση (`novus_request_create.php`), οπότε δεν χρειάζεται επιπλέον ρύθμιση.

## Βήμα 5: Read-only API κατάστασης αίτησης

`api_novus_status.php` επιστρέφει σε JSON τις αιτήσεις Novus ενός ΑΦΜ (μπορεί να υπάρχουν
παραπάνω από μία), με τα πεδία `status`, `is_b2b`, `is_b2c`, `customer_email`.

Δεν είναι δημόσιο — το `customer_email` είναι προσωπικό δεδομένο. Απαιτεί το header
`X-API-KEY` να ταιριάζει με το `$PAROXOS_API_KEY` του `config.php` (παράγετέ το με
`openssl rand -hex 32`).

```bash
curl -H "X-API-KEY: <το κλειδί σας>" \
  "https://paroxos.totalschool.gr/api_novus_status.php?afm=094019245"
```

```json
{
  "success": true,
  "data": [
    {
      "request_id": "req_8f7b2c9a",
      "status": "UNDER_REVIEW",
      "is_b2b": true,
      "is_b2c": false,
      "customer_email": "info@papacorp.gr"
    }
  ]
}
```

Χωρίς έγκυρο ΑΦΜ ή σωστό API key, επιστρέφει `400`/`401` με `{"success": false, "error": "..."}`.
