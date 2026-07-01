<?php
$filepath = 'c:/xampp/htdocs/Curtiss ERP Rep App/app/src/main/java/com/example/curtiss/SyncManager.java';
$content = file_get_contents($filepath);
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (stripos($line, 'mappings') !== false || stripos($line, 'server_id') !== false) {
        if ($i > 770 && $i < 950) {
            echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
        }
    }
}
