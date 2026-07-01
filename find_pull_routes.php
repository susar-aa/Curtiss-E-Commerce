<?php
$filepath = 'c:/xampp/htdocs/Curtiss ERP Rep App/app/src/main/java/com/example/curtiss/SyncManager.java';
$content = file_get_contents($filepath);
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if ($i < 590 && (stripos($line, 'daily_routes') !== false || stripos($line, 'server_routes') !== false)) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
