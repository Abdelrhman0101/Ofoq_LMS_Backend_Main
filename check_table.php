<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=ofuq_db', 'root', '');
    $stmt = $pdo->query('DESCRIBE certificates');
    echo "Certificates table structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . ' - ' . ($row['Default'] ?: 'no default') . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}