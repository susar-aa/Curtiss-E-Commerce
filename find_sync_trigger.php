<?php
$dir = new RecursiveDirectoryIterator('c:/xampp/htdocs/Curtiss ERP Rep App/app/src/main/java');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'java') {
        $content = file_get_contents($file->getPathname());
        if (stripos($content, 'SyncProgressActivity') !== false || stripos($content, 'SyncLogsActivity') !== false || stripos($content, 'startManualPushSync') !== false) {
            echo "File: " . $file->getPathname() . "\n";
            preg_match_all('/.*(SyncProgressActivity|SyncLogsActivity|startManualPushSync).*/i', $content, $matches);
            foreach ($matches[0] as $match) {
                echo "  " . trim($match) . "\n";
            }
        }
    }
}
