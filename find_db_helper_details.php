<?php
$filepath = 'c:/xampp/htdocs/Curtiss ERP Rep App/app/src/main/java/com/example/curtiss/DatabaseHelper.java';
$content = file_get_contents($filepath);
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (stripos($line, 'clearLocalData') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
