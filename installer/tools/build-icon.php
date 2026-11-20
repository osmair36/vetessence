<?php
/**
 * Gera o icone multi-resolucao do Amigo Bicho (public/amigo-bicho.ico) a partir do logotipo.
 * Uso: runtime\php\php.exe installer\tools\build-icon.php
 *
 * - Tamanhos >= 48px: logotipo completo (gato + cachorro + "AMIGO Bicho") centralizado em quadrado.
 * - Tamanhos <= 32px: apenas as cabecas do gato e do cachorro (legiveis em tamanho pequeno).
 */

$base = dirname(__DIR__, 2);
$src = $base . '/installer/assets/logo-amigo-bicho.jpg';
$out = $base . '/public/amigo-bicho.ico';

$img = imagecreatefromjpeg($src);
if (!$img) {
    fwrite(STDERR, "Nao foi possivel ler {$src}\n");
    exit(1);
}
$w = imagesx($img);
$h = imagesy($img);

// Cor de fundo amarela do logotipo (amostrada no canto superior esquerdo)
$bg = imagecolorat($img, 5, 5);
$bgR = ($bg >> 16) & 0xFF; $bgG = ($bg >> 8) & 0xFF; $bgB = $bg & 0xFF;

function squareCanvas($srcImg, int $sx, int $sy, int $sw, int $sh, int $size, array $rgb, float $padPct): GdImage
{
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, true);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefill($canvas, 0, 0, $transparent);

    // Fundo amarelo com cantos arredondados
    $fill = imagecolorallocate($canvas, $rgb[0], $rgb[1], $rgb[2]);
    $r = (int) round($size * 0.18);
    imagefilledrectangle($canvas, $r, 0, $size - 1 - $r, $size - 1, $fill);
    imagefilledrectangle($canvas, 0, $r, $size - 1, $size - 1 - $r, $fill);
    foreach ([[$r, $r], [$size - 1 - $r, $r], [$r, $size - 1 - $r], [$size - 1 - $r, $size - 1 - $r]] as [$cx, $cy]) {
        imagefilledellipse($canvas, $cx, $cy, $r * 2, $r * 2, $fill);
    }

    $pad = (int) round($size * $padPct);
    $avail = $size - 2 * $pad;
    $scale = min($avail / $sw, $avail / $sh);
    $dw = max(1, (int) round($sw * $scale));
    $dh = max(1, (int) round($sh * $scale));
    $dx = (int) floor(($size - $dw) / 2);
    $dy = (int) floor(($size - $dh) / 2);
    imagecopyresampled($canvas, $srcImg, $dx, $dy, $sx, $sy, $dw, $dh, $sw, $sh);
    return $canvas;
}

$sizes = [256, 128, 64, 48, 32, 24, 16];
$entries = [];
foreach ($sizes as $size) {
    if ($size >= 48) {
        $canvas = squareCanvas($img, 0, 0, $w, $h, $size, [$bgR, $bgG, $bgB], 0.04);
    } else {
        // Recorte das cabecas do gato e do cachorro
        $cx = (int) round($w * 0.21); $cy = (int) round($h * 0.06);
        $cw = (int) round($w * 0.58); $ch = (int) round($h * 0.40);
        $canvas = squareCanvas($img, $cx, $cy, $cw, $ch, $size, [$bgR, $bgG, $bgB], 0.03);
    }
    ob_start();
    imagepng($canvas, null, 9);
    $png = ob_get_clean();
    imagedestroy($canvas);
    $entries[] = ['size' => $size, 'data' => $png];
}

// Monta arquivo ICO (entradas PNG - suportado pelo Windows Vista+)
$count = count($entries);
$header = pack('vvv', 0, 1, $count);
$dir = '';
$body = '';
$offset = 6 + 16 * $count;
foreach ($entries as $e) {
    $dim = $e['size'] >= 256 ? 0 : $e['size'];
    $len = strlen($e['data']);
    $dir .= pack('CCCCvvVV', $dim, $dim, 0, 0, 1, 32, $len, $offset);
    $body .= $e['data'];
    $offset += $len;
}
file_put_contents($out, $header . $dir . $body);

// Previa PNG 256px para conferencia
file_put_contents($base . '/installer/assets/amigo-bicho-icon-preview.png', $entries[0]['data']);

echo "Icone gerado: {$out} (" . filesize($out) . " bytes, tamanhos: " . implode(', ', $sizes) . ")\n";
