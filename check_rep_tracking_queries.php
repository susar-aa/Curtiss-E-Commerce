<?php
$filepath = 'c:/xampp/htdocs/Curtiss-ERP/app/Controllers/RepTrackingController.php';
$content = file_get_contents($filepath);
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (preg_match('/public\s+function\s+(\w+)/i', $line, $m)) {
        echo "Line " . ($i + 1) . ": " . $m[1] . "\n";
    }
}
