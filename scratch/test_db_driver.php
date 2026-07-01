<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'suzxlabs'); 
define('DB_PASS', 'Susara@200611003614');    
define('DB_NAME', 'curtiss_erp'); 

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
    $options = [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    $dbh = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    $stmt = $dbh->prepare("SELECT id, username, role, status FROM users WHERE username = 'thedriver'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($user);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
