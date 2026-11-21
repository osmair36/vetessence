<?php

$width = 960;
$height = 1200;
$img = imagecreatetruecolor($width, $height);

// Cores
$cDarkBg = imagecolorallocate($img, 17, 27, 33);
$cCardBg = imagecolorallocate($img, 32, 44, 51);
$cBorder = imagecolorallocate($img, 42, 57, 66);
$cGreen = imagecolorallocate($img, 37, 211, 102);
$cTeal = imagecolorallocate($img, 0, 168, 132);
$cChatBg = imagecolorallocate($img, 11, 20, 26);
$cBubble = imagecolorallocate($img, 32, 44, 51);
$cBubbleInner = imagecolorallocate($img, 24, 34, 41);
$cWhite = imagecolorallocate($img, 240, 242, 245);
$cMuted = imagecolorallocate($img, 134, 150, 160);
$cDateBg = imagecolorallocate($img, 24, 34, 41);
$cGold = imagecolorallocate($img, 214, 195, 141);

imagefill($img, 0, 0, $cDarkBg);

$fontRegular = 'C:\\Windows\\Fonts\\arial.ttf';
$fontBold = 'C:\\Windows\\Fonts\\arialbd.ttf';

// Título no topo
imagettftext($img, 18, 0, 50, 45, $cGreen, $fontBold, "Amigo Bicho - Notificações WhatsApp");
imagettftext($img, 11, 0, 50, 72, $cMuted, $fontRegular, "Simulação visual fiel no celular com logotipo da clínica e ícones de emoji");

// Carregar logotipo da clínica
$logoPath = __DIR__ . '/public/img/logo-amigobicho.png';
$logoImg = file_exists($logoPath) ? imagecreatefrompng($logoPath) : null;

// Carregar ícones de emoji
$pawIcon = imagecreatefrompng(__DIR__ . '/public/img/emojis/paw.png');
$syrIcon = imagecreatefrompng(__DIR__ . '/public/img/emojis/syringe.png');
$calIcon = imagecreatefrompng(__DIR__ . '/public/img/emojis/calendar.png');
$hrtIcon = imagecreatefrompng(__DIR__ . '/public/img/emojis/heart.png');

// Moldura do Smartphone (X: 200 a 760)
$px1 = 200;
$py1 = 95;
$px2 = 760;
$py2 = 1140;

// Borda externa do celular
imagefilledrectangle($img, $px1 - 8, $py1 - 8, $px2 + 8, $py2 + 8, $cBorder);
// Tela interna
imagefilledrectangle($img, $px1, $py1, $px2, $py2, $cChatBg);

// Barra de Status do Celular
imagefilledrectangle($img, $px1, $py1, $px2, $py1 + 28, $cCardBg);
imagettftext($img, 10, 0, $px1 + 18, $py1 + 20, $cWhite, $fontBold, "09:02");
imagettftext($img, 10, 0, $px2 - 130, $py1 + 20, $cWhite, $fontRegular, "5G   98% [===]");

// Cabeçalho do WhatsApp
$headY = $py1 + 28;
imagefilledrectangle($img, $px1, $headY, $px2, $headY + 70, $cCardBg);

// Inserir Logotipo Real no Avatar do WhatsApp
if ($logoImg) {
    // Fundo branco circular para o logo
    imagefilledellipse($img, $px1 + 42, $headY + 35, 50, 50, $cWhite);
    // Redimensionar e copiar logo da clínica
    imagecopyresampled($img, $logoImg, $px1 + 18, $headY + 11, 0, 0, 48, 48, imagesx($logoImg), imagesy($logoImg));
} else {
    imagefilledellipse($img, $px1 + 42, $headY + 35, 50, 50, $cTeal);
    imagettftext($img, 15, 0, $px1 + 28, $headY + 41, $cWhite, $fontBold, "AB");
}

// Nome e status no cabeçalho
imagettftext($img, 13, 0, $px1 + 80, $headY + 30, $cWhite, $fontBold, "Clínica Veterinária Amigo Bicho");
// Selo de verificado
imagefilledellipse($img, $px1 + 455, $headY + 25, 14, 14, $cTeal);
imagettftext($img, 8, 0, $px1 + 451, $headY + 29, $cWhite, $fontBold, "v");
imagettftext($img, 10, 0, $px1 + 80, $headY + 52, $cTeal, $fontRegular, "Conta comercial oficial • Online");

// Pílula de Data no Chat: "HOJE"
$dateY = $headY + 95;
imagefilledrectangle($img, $px1 + 230, $dateY, $px1 + 330, $dateY + 24, $cDateBg);
imagettftext($img, 9, 0, $px1 + 262, $dateY + 16, $cMuted, $fontBold, "HOJE");

// Balão de Mensagem Recebida (X: px1 + 18 até px1 + 520)
$bx1 = $px1 + 18;
$by1 = $dateY + 40;
$bx2 = $px1 + 535;
$by2 = $by1 + 560;

imagefilledrectangle($img, $bx1, $by1, $bx2, $by2, $cBubble);

// Card de Identificação da Clínica no Topo da Mensagem (Logotipo + Nome)
$cardY = $by1 + 12;
imagefilledrectangle($img, $bx1 + 12, $cardY, $bx2 - 12, $cardY + 68, $cBubbleInner);
if ($logoImg) {
    imagecopyresampled($img, $logoImg, $bx1 + 20, $cardY + 8, 0, 0, 52, 52, imagesx($logoImg), imagesy($logoImg));
}
imagettftext($img, 11, 0, $bx1 + 82, $cardY + 28, $cGold, $fontBold, "CLÍNICA VETERINÁRIA AMIGO BICHO");
imagettftext($img, 9, 0, $bx1 + 82, $cardY + 48, $cMuted, $fontRegular, "Medicina Veterinária & Saúde Animal");

// Corpo da Mensagem
$textStartY = $cardY + 95;

// Linha 1: Olá, Mariana Souza! Tudo bem? + [PAW ICON]
imagettftext($img, 11, 0, $bx1 + 18, $textStartY, $cWhite, $fontBold, "Olá, Mariana Souza! Tudo bem?");
imagecopyresampled($img, $pawIcon, $bx1 + 275, $textStartY - 16, 0, 0, 20, 20, 48, 48);

// Linhas de introdução
imagettftext($img, 10.5, 0, $bx1 + 18, $textStartY + 30, $cWhite, $fontRegular, "Estamos passando para lembrar que a vacinação do seu pet");
imagettftext($img, 10.5, 0, $bx1 + 18, $textStartY + 54, $cWhite, $fontBold, "Thor");
imagettftext($img, 10.5, 0, $bx1 + 58, $textStartY + 54, $cWhite, $fontRegular, "está prevista para daqui a 7 dias.");

// Linha da Vacina: [SYRINGE ICON] + Vacina: Vacina Polivalente V10
imagecopyresampled($img, $syrIcon, $bx1 + 18, $textStartY + 84, 0, 0, 20, 20, 48, 48);
imagettftext($img, 11, 0, $bx1 + 44, $textStartY + 99, $cGreen, $fontBold, "Vacina: Vacina Polivalente V10");

// Linha da Data: [CALENDAR ICON] + Data prevista: 28/11/2026
imagecopyresampled($img, $calIcon, $bx1 + 18, $textStartY + 118, 0, 0, 20, 20, 48, 48);
imagettftext($img, 11, 0, $bx1 + 44, $textStartY + 133, $cGreen, $fontBold, "Data prevista: 28/11/2026");

// Texto de acompanhamento
imagettftext($img, 10.5, 0, $bx1 + 18, $textStartY + 172, $cWhite, $fontRegular, "É muito importante manter o acompanhamento da vacinação");
imagettftext($img, 10.5, 0, $bx1 + 18, $textStartY + 196, $cWhite, $fontRegular, "do seu companheiro em dia para protegê-lo.");

// Chamada para ação
imagettftext($img, 10.5, 0, $bx1 + 18, $textStartY + 236, $cWhite, $fontRegular, "Entre em contato conosco para agendar a aplicação!");

// Linha final com coração: Será um prazer receber vocês! + [HEART ICON]
imagettftext($img, 10.5, 0, $bx1 + 18, $textStartY + 276, $cWhite, $fontRegular, "Será um grande prazer receber vocês!");
imagecopyresampled($img, $hrtIcon, $bx1 + 285, $textStartY + 260, 0, 0, 20, 20, 48, 48);

// Linha divisória suave
imageline($img, $bx1 + 18, $textStartY + 305, $bx2 - 18, $textStartY + 305, $cBorder);

// Rodapé institucional na mensagem
imagettftext($img, 9, 0, $bx1 + 18, $textStartY + 328, $cMuted, $fontBold, "Clínica Veterinária Amigo Bicho • Tel/WhatsApp: (11) 99999-8888");

// Horário da mensagem
imagettftext($img, 10, 0, $bx2 - 55, $by2 - 12, $cMuted, $fontRegular, "09:00");

// Barra Inferior (Digitação)
$barY = $py2 - 55;
imagefilledrectangle($img, $px1, $barY, $px2, $py2, $cCardBg);
imagefilledrectangle($img, $px1 + 15, $barY + 10, $px2 - 15, $py2 - 10, $cBorder);
imagettftext($img, 11, 0, $px1 + 35, $barY + 30, $cMuted, $fontRegular, "Mensagem...");

// Salvar a imagem final
$targetPng1 = 'C:\\Amigo Bicho\\Simulacao_WhatsApp_Mensagem.png';
$targetPng2 = 'C:\\Amigo Bicho\\public\\docs\\Simulacao_WhatsApp_Mensagem.png';
$targetPng3 = 'C:\\Amigo Bicho\\Simulacao_WhatsApp_Com_Logo.png';

@imagepng($img, $targetPng1);
@imagepng($img, $targetPng2);
@imagepng($img, $targetPng3);
imagedestroy($img);

if ($logoImg) imagedestroy($logoImg);
imagedestroy($pawIcon);
imagedestroy($syrIcon);
imagedestroy($calIcon);
imagedestroy($hrtIcon);

echo "Imagem PNG gerada com sucesso em:\n";
echo "1. $targetPng1 (" . number_format(filesize($targetPng1) / 1024, 1) . " KB)\n";
echo "2. $targetPng2 (" . number_format(filesize($targetPng2) / 1024, 1) . " KB)\n";
