<?php
$content = file_get_contents('c:/xampp/htdocs/Curtiss-ERP/app/Controllers/RepDashboardController.php');
preg_match_all('/.*quantity_on_hand.*/i', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $match) {
    echo "Match: " . $match[0] . " at offset " . $match[1] . "\n";
}
