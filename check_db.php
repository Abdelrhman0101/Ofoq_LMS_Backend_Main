<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=ofuq_db', 'root', '');
    $count = $pdo->query('SELECT COUNT(*) FROM certificates')->fetchColumn();
    echo 'Total certificates: ' . $count . PHP_EOL;
    
    if ($count > 0) {
        $cert = $pdo->query('SELECT id, verification_token, file_path FROM certificates LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        echo 'First certificate:' . PHP_EOL;
        echo '  ID: ' . $cert['id'] . PHP_EOL;
        echo '  Token: ' . $cert['verification_token'] . PHP_EOL;
        echo '  File Path: ' . $cert['file_path'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}