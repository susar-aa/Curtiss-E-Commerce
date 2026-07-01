<?php
$filepath = 'c:/xampp/htdocs/Curtiss-ERP/app/Views/rep-tracking/index.php';
$content = file_get_contents($filepath);
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (stripos($line, 'invoice') !== false || stripos($line, 'bill') !== false || stripos($line, 'sales') !== false || stripos($line, 'route') !== false) {
        if ($i < 200) { // print first few matches
            echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
        }
    }
}
