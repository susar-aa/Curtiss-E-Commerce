<?php
$filepath = 'c:/xampp/htdocs/Curtiss ERP Rep App/app/src/main/java/com/example/curtiss/SyncManager.java';
$content = file_get_contents($filepath);
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (stripos($line, 'daily_routes') !== false && (stripos($line, 'UPDATE') !== false || stripos($line, 'cv.put') !== false || stripos($line, 'is_synced') !== false)) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
