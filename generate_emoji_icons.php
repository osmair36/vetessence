<?php

// Gerar ícones PNG nítidos para os emojis
$iconDir = __DIR__ . '/public/img/emojis';
if (!is_dir($iconDir)) {
    mkdir($iconDir, 0777, true);
}

// 1. Pata (🐾)
$paw = imagecreatetruecolor(48, 48);
imagealphablending($paw, false);
imagesavealpha($paw, true);
$transparent = imagecolorallocatealpha($paw, 0, 0, 0, 127);
imagefill($paw, 0, 0, $transparent);
imagealphablending($paw, true);

$brown = imagecolorallocate($paw, 139, 69, 19);
$cGreen = imagecolorallocate($paw, 25, 135, 84);

// Almofada principal
imagefilledellipse($paw, 24, 30, 22, 18, $cGreen);
// 4 dedinhos
imagefilledellipse($paw, 12, 18, 8, 12, $cGreen);
imagefilledellipse($paw, 20, 13, 8, 12, $cGreen);
imagefilledellipse($paw, 28, 13, 8, 12, $cGreen);
imagefilledellipse($paw, 36, 18, 8, 12, $cGreen);
imagepng($paw, "$iconDir/paw.png");
imagedestroy($paw);

// 2. Seringa (💉)
$syr = imagecreatetruecolor(48, 48);
imagealphablending($syr, false);
imagesavealpha($syr, true);
imagefill($syr, 0, 0, $transparent);
imagealphablending($syr, true);

$cBlue = imagecolorallocate($syr, 13, 110, 253);
$cDark = imagecolorallocate($syr, 50, 50, 50);
$cGray = imagecolorallocate($syr, 180, 180, 180);
$cRed = imagecolorallocate($syr, 220, 53, 69);

// Corpo da seringa
imagefilledrectangle($syr, 16, 12, 28, 32, $cBlue);
// Êmbolo
imagefilledrectangle($syr, 20, 32, 24, 40, $cGray);
imagefilledrectangle($syr, 14, 40, 30, 43, $cDark);
// Agulha
imageline($syr, 22, 12, 22, 4, $cDark);
imageline($syr, 21, 12, 21, 4, $cDark);
imagepng($syr, "$iconDir/syringe.png");
imagedestroy($syr);

// 3. Calendário (📅)
$cal = imagecreatetruecolor(48, 48);
imagealphablending($cal, false);
imagesavealpha($cal, true);
imagefill($cal, 0, 0, $transparent);
imagealphablending($cal, true);

$cRed = imagecolorallocate($cal, 220, 53, 69);
$cWhite = imagecolorallocate($cal, 255, 255, 255);
$cBorder = imagecolorallocate($cal, 100, 100, 100);
$cDot = imagecolorallocate($cal, 70, 70, 70);

// Base branca
imagefilledrectangle($cal, 8, 12, 40, 42, $cWhite);
imagerectangle($cal, 8, 12, 40, 42, $cBorder);
// Topo vermelho
imagefilledrectangle($cal, 8, 12, 40, 20, $cRed);
// Argolas
imagefilledrectangle($cal, 14, 8, 18, 14, $cBorder);
imagefilledrectangle($cal, 30, 8, 34, 14, $cBorder);
// Pontos de dias
imagefilledrectangle($cal, 14, 25, 18, 28, $cDot);
imagefilledrectangle($cal, 22, 25, 26, 28, $cDot);
imagefilledrectangle($cal, 30, 25, 34, 28, $cDot);
imagefilledrectangle($cal, 14, 32, 18, 35, $cDot);
imagefilledrectangle($cal, 22, 32, 26, 35, $cDot);
imagefilledrectangle($cal, 30, 32, 34, 35, $cDot);
imagepng($cal, "$iconDir/calendar.png");
imagedestroy($cal);

// 4. Coração (❤️)
$hrt = imagecreatetruecolor(48, 48);
imagealphablending($hrt, false);
imagesavealpha($hrt, true);
imagefill($hrt, 0, 0, $transparent);
imagealphablending($hrt, true);

$cHeart = imagecolorallocate($hrt, 230, 57, 70);
// Dois círculos e triângulo
imagefilledellipse($hrt, 18, 18, 18, 18, $cHeart);
imagefilledellipse($hrt, 30, 18, 18, 18, $cHeart);
$points = [10, 22, 38, 22, 24, 38];
imagefilledpolygon($hrt, $points, $cHeart);
imagepng($hrt, "$iconDir/heart.png");
imagedestroy($hrt);

echo "Ícones de emoji gerados com sucesso em $iconDir\n";
