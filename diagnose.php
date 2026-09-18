<?php
// Διαγνωστικό εργαλείο: δείχνει τις πραγματικές μεθόδους/τύπους που ορίζει
// το WSDL της ΑΑΔΕ, ώστε να επιβεβαιώσουμε το σωστό όνομα μεθόδου.
// Διαγράψτε αυτό το αρχείο μόλις τελειώσετε τη διάγνωση.

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $client = new SoapClient($AADE_WSDL, [
        'trace' => true,
        'exceptions' => true,
        'connection_timeout' => 15,
    ]);

    echo "=== Διαθέσιμες μέθοδοι (functions) ===\n";
    foreach ($client->__getFunctions() as $fn) {
        echo $fn . "\n";
    }

    echo "\n=== Τύποι δεδομένων (types) ===\n";
    foreach ($client->__getTypes() as $type) {
        echo $type . "\n\n";
    }
} catch (Throwable $e) {
    echo 'Σφάλμα: ' . $e->getMessage() . "\n";
}
