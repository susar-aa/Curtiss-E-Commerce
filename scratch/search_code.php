<?php
function searchDir($dir, $keyword) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            searchDir($path, $keyword);
        } else {
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($path);
                if (strpos($content, $keyword) !== false) {
                    echo "Found in: $path\n";
                }
            }
        }
    }
}
searchDir(__DIR__ . '/../app', 'deleted_list');
searchDir(__DIR__ . '/../app', 'deleted_invoices');
searchDir(__DIR__ . '/../app', 'delete_invoice');
