<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



// Debug en deva
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$width = 160;
$height = 50;
$image = imagecreatetruecolor($width, $height);

$white = imagecolorallocate($image, 255, 255, 255);
$gray = imagecolorallocate($image, 200, 200, 200);
$black = imagecolorallocate($image, 0, 0, 0);

imagefill($image, 0, 0, $white);

// Vérifie la police
$font_path = __DIR__ . '/../assets/fonts/DejaVuSans.ttf';
if (!file_exists($font_path)) {
    imagestring($image, 5, 10, 20, "Police manquante", $black);
    header('Content-type: image/png');
    imagepng($image);
    imagedestroy($image);
    exit;
}

// Mot à recopier
$word = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5));

// Enregistre le mot à valider
$_SESSION['captcha_answer'] = $word;

for ($i = 0; $i < strlen($word); $i++) {
    $angle = random_int(-25, 25);
    $size = random_int(22, 26);
    $x = 10 + $i * 25;
    $y = random_int(30, 42);
    $letter = $word[$i];
    imagettftext($image, $size, $angle, $x, $y, $black, $font_path, $letter);
}

// Bruit visuel
for ($i = 0; $i < 5; $i++) {
    imageline($image, 0, random_int(0, $height), $width, random_int(0, $height), $gray);
}
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, random_int(0, $width), random_int(0, $height), $gray);
}

header('Content-type: image/png');
imagepng($image);
imagedestroy($image);
