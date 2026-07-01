<?php
$filepath = 'c:/xampp/htdocs/Curtiss ERP Rep App/app/src/main/java/com/example/curtiss/SyncManager.java';
$content = file_get_contents($filepath);
$lines = explode("\n", $content);
for ($i = 590; $i < 671; $i++) {
    if (isset($lines[$i])) {
        echo "Line " . ($i + 1) . ": " . $lines[$i] . "\n";
    }
}
