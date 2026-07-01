<?php
define('INC_ALLOW', true);

$logFiles = [
    'C:\xampp\php\logs\php_error_log',
    'C:\xampp\apache\logs\error.log'
];

foreach ($logFiles as $logFile) {
    if (file_exists($logFile)) {
        echo "=== Reading $logFile ===\n";
        $content = file_get_contents($logFile);
        $lines = explode("\n", $content);
        $matches = array_filter($lines, function($line) {
            return stripos($line, 'StockLedger') !== false || stripos($line, 'approveGRN') !== false;
        });
        print_r(array_slice($matches, -20)); // Print last 20 matches
    } else {
        echo "Log file $logFile does not exist.\n";
    }
}
unlink(__FILE__);
