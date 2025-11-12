<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=ofuq_db', 'root', '');
    $stmt = $pdo->query('SELECT id, verification_token, file_path FROM certificates ORDER BY id DESC LIMIT 1');
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Latest certificate: ID=' . $cert['id'] . ', Token=' . $cert['verification_token'] . ', File=' . $cert['file_path'] . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}