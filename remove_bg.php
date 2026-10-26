<?php
$inputFile = __DIR__ . '/public/images/logo-bmos.jpg';
$outputFile = __DIR__ . '/public/images/logo-bmos-transparent.png';

$img = imagecreatefromjpeg($inputFile);

if (!$img) {
    die("Failed to load image");
}

$width = imagesx($img);
$height = imagesy($img);

$png = imagecreatetruecolor($width, $height);
imagesavealpha($png, true);

$transparent = imagecolorallocatealpha($png, 0, 0, 0, 127);
imagefill($png, 0, 0, $transparent);

for ($x = 0; $x < $width; $x++) {
    for ($y = 0; $y < $height; $y++) {
        $color = imagecolorat($img, $x, $y);
        $r = ($color >> 16) & 0xFF;
        $g = ($color >> 8) & 0xFF;
        $b = $color & 0xFF;
        
        // Tolerance for black background
        if ($r < 20 && $g < 20 && $b < 20) {
            // Keep transparent
        } else {
            $alpha = 0;
            // Edge smoothing
            if ($r < 50 && $g < 50 && $b < 50) {
                $max = max($r, $g, $b);
                $alpha = 127 - (int)(($max / 50) * 127);
            }
            $newColor = imagecolorallocatealpha($png, $r, $g, $b, $alpha);
            imagesetpixel($png, $x, $y, $newColor);
        }
    }
}

imagepng($png, $outputFile);
echo "Success\n";
