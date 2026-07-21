<?php
// assets/phpqrcode/qrlib.php - Simplified QR Code Generator

class QRcode {
    public static function png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4) {
        // Use Google Chart API as fallback
        $chl = urlencode($text);
        $qr = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=$chl&choe=UTF-8";
        
        if ($outfile) {
            file_put_contents($outfile, file_get_contents($qr));
            return true;
        }
        
        return $qr;
    }
}

define('QR_ECLEVEL_L', 'L');
define('QR_ECLEVEL_M', 'M');
define('QR_ECLEVEL_Q', 'Q');
define('QR_ECLEVEL_H', 'H');
?>