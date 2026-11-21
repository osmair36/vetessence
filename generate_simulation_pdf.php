<?php

require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$logoPath = str_replace('\\', '/', __DIR__ . '/public/img/logo-amigobicho.png');
$pawPath = str_replace('\\', '/', __DIR__ . '/public/img/emojis/paw.png');
$syrPath = str_replace('\\', '/', __DIR__ . '/public/img/emojis/syringe.png');
$calPath = str_replace('\\', '/', __DIR__ . '/public/img/emojis/calendar.png');
$hrtPath = str_replace('\\', '/', __DIR__ . '/public/img/emojis/heart.png');

$html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Simulação Visual - Mensagens WhatsApp de Vacinas - Amigo Bicho</title>
    <style>
        @page {
            margin: 15mm 12mm 15mm 12mm;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9.5pt;
            line-height: 1.4;
            color: #212529;
            margin: 0;
            padding: 0;
        }

        .header-title {
            text-align: center;
            margin-bottom: 10px;
        }

        .header-title h1 {
            font-size: 15pt;
            color: #0f5132;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-title p {
            font-size: 8.5pt;
            color: #495057;
            margin: 0;
        }

        .divider {
            width: 60px;
            height: 3px;
            background-color: #198754;
            margin: 5px auto 12px auto;
        }

        /* Moldura de Smartphone */
        .phone-wrapper {
            width: 325px;
            margin: 0 auto 10px auto;
            border: 8px solid #2b2d42;
            border-radius: 26px;
            background-color: #efeae2;
            overflow: hidden;
            box-shadow: 0 6px 16px rgba(0,0,0,0.25);
        }

        /* Barra de Status do Celular */
        .phone-status-bar {
            background-color: #005c4b;
            color: #ffffff;
            padding: 5px 14px;
            font-size: 7pt;
            font-weight: bold;
        }

        .phone-status-bar .time { float: left; }
        .phone-status-bar .icons { float: right; }

        /* Cabeçalho do WhatsApp */
        .wa-header {
            background-color: #008069;
            color: #ffffff;
            padding: 7px 10px;
            clear: both;
        }

        .wa-avatar {
            width: 36px;
            height: 36px;
            float: left;
            margin-right: 8px;
            border-radius: 50%;
            background-color: #ffffff;
            padding: 1px;
            text-align: center;
        }

        .wa-avatar img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: block;
        }

        .wa-contact-info {
            float: left;
            width: 220px;
        }

        .wa-contact-name {
            font-size: 9pt;
            font-weight: bold;
            color: #ffffff;
            margin: 1px 0 0 0;
            white-space: nowrap;
        }

        .wa-contact-status {
            font-size: 7pt;
            color: #d1f2eb;
            margin: 0;
        }

        .clear {
            clear: both;
        }

        /* Área de Mensagens */
        .wa-chat-area {
            background-color: #efeae2;
            padding: 10px 10px 12px 10px;
            min-height: 340px;
        }

        .wa-date-pill {
            text-align: center;
            margin-bottom: 10px;
        }

        .wa-date-pill span {
            background-color: #ffffff;
            color: #54656f;
            font-size: 7pt;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 6px;
            border: 1px solid #e1ded9;
            text-transform: uppercase;
        }

        /* Balão de Mensagem Recebida */
        .wa-bubble-received {
            background-color: #ffffff;
            border-radius: 0 8px 8px 8px;
            padding: 8px 10px 6px 10px;
            max-width: 285px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.13);
            position: relative;
            margin-bottom: 8px;
            border: 1px solid #e2ddd5;
        }

        /* Card de Identificação da Clínica no Topo */
        .clinic-id-card {
            background-color: #f4fbf6;
            border: 1px solid #cde9d6;
            border-radius: 5px;
            padding: 5px 8px;
            margin-bottom: 8px;
        }

        .clinic-id-card img {
            width: 32px;
            height: 32px;
            float: left;
            margin-right: 6px;
        }

        .clinic-id-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #0f5132;
            margin: 0;
            line-height: 1.2;
        }

        .clinic-id-sub {
            font-size: 6.5pt;
            color: #6c757d;
            margin: 0;
        }

        .wa-message-text {
            font-size: 8.5pt;
            line-height: 1.35;
            color: #111b21;
            margin: 0;
        }

        .wa-message-time {
            text-align: right;
            font-size: 6.5pt;
            color: #667781;
            margin-top: 4px;
        }

        /* Ícones de Emoji como Imagens */
        .emoji-icon {
            width: 14px;
            height: 14px;
            vertical-align: -2px;
            display: inline-block;
        }

        /* Barra Inferior do Celular (Digitação) */
        .wa-bottom-bar {
            background-color: #f0f2f5;
            padding: 6px 10px;
            border-top: 1px solid #e1ded9;
        }

        .wa-input-fake {
            background-color: #ffffff;
            border-radius: 18px;
            padding: 4px 12px;
            font-size: 7pt;
            color: #8696a0;
            border: 1px solid #d1d7db;
        }

        /* Caixa de Informações do Cenário */
        .scenario-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-left: 4px solid #198754;
            border-radius: 4px;
            padding: 8px 12px;
            margin: 0 auto 12px auto;
            max-width: 490px;
            font-size: 8pt;
        }

        .scenario-card h3 {
            margin: 0 0 3px 0;
            font-size: 9.5pt;
            color: #0f5132;
        }

        .scenario-card table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
        }

        .scenario-card td {
            padding: 2px 4px;
        }

        .scenario-card td.lbl {
            font-weight: bold;
            color: #495057;
            width: 28%;
        }

        .page-break {
            page-break-after: always;
        }

        /* Tabela de Placeholders */
        table.ref-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 8.5pt;
        }

        table.ref-table th, table.ref-table td {
            border: 1px solid #dee2e6;
            padding: 5px 8px;
            text-align: left;
        }

        table.ref-table th {
            background-color: #f1f8f4;
            color: #0f5132;
            font-weight: bold;
        }

        code {
            background-color: #f1f3f5;
            color: #b02a37;
            padding: 1px 4px;
            border-radius: 3px;
            font-family: Courier, monospace;
            font-size: 8pt;
        }

        .tip-box {
            background-color: #d1e7dd;
            color: #0f5132;
            border-left: 4px solid #198754;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 8pt;
            margin-top: 8px;
        }
    </style>
</head>
<body>

    <!-- ============================================================== -->
    <!-- CENÁRIO 1: 7 DIAS ANTES -->
    <!-- ============================================================== -->
    <div class="header-title">
        <h1>Simulação Visual de Notificação WhatsApp</h1>
        <p>Pré-visualização fiel com Logotipo da Clínica e Emojis como Imagens</p>
        <div class="divider"></div>
    </div>

    <div class="scenario-card">
        <h3>Cenário 1: Lembrete de Antecedência (7 Dias Antes)</h3>
        <p style="margin: 0 0 3px 0; color: #495057;">
            Disparo automático preventivo para que o cliente se programe e agende a vacinação com antecedência.
        </p>
        <table>
            <tr>
                <td class="lbl">Tutor (Cliente):</td>
                <td>Mariana Souza</td>
                <td class="lbl">Pet (Paciente):</td>
                <td>Thor (Golden Retriever)</td>
            </tr>
            <tr>
                <td class="lbl">Vacina:</td>
                <td>Vacina Polivalente V10</td>
                <td class="lbl">Data Prevista:</td>
                <td><strong>28/11/2026</strong> (em 7 dias)</td>
            </tr>
            <tr>
                <td class="lbl">Gatilho do Sistema:</td>
                <td colspan="3"><code>vaccine_reminders.template_7_days</code> (Disparo às 09:00)</td>
            </tr>
        </table>
    </div>

    <!-- MOLDURA DO SMARTPHONE -->
    <div class="phone-wrapper">
        <div class="phone-status-bar">
            <span class="time">09:02</span>
            <span class="icons">5G &bull; 98% [===]</span>
            <div class="clear"></div>
        </div>

        <div class="wa-header">
            <div class="wa-avatar">
                <img src="{$logoPath}" alt="Logo">
            </div>
            <div class="wa-contact-info">
                <div class="wa-contact-name">Amigo Bicho &bull; Clínica</div>
                <div class="wa-contact-status">Conta comercial oficial &bull; Online</div>
            </div>
            <div class="clear"></div>
        </div>

        <div class="wa-chat-area">
            <div class="wa-date-pill">
                <span>Hoje</span>
            </div>

            <div class="wa-bubble-received">
                <!-- Card de Identificação da Clínica no Topo -->
                <div class="clinic-id-card">
                    <img src="{$logoPath}" alt="Logo">
                    <div class="clinic-id-title">CLÍNICA VETERINÁRIA AMIGO BICHO</div>
                    <div class="clinic-id-sub">Medicina Veterinária &bull; Lembretes Sanitários</div>
                    <div class="clear"></div>
                </div>

                <div class="wa-message-text">
                    <p style="margin: 0 0 8px 0; font-weight: bold;">
                        Olá, Mariana Souza! Tudo bem? <img src="{$pawPath}" class="emoji-icon" alt="🐾">
                    </p>
                    <p style="margin: 0 0 8px 0;">
                        Estamos passando para lembrar que a vacinação do seu pet <strong>Thor</strong> está prevista para daqui a 7 dias.
                    </p>
                    <p style="margin: 0 0 4px 0; font-weight: bold; color: #0f5132;">
                        <img src="{$syrPath}" class="emoji-icon" alt="💉"> Vacina: Vacina Polivalente V10
                    </p>
                    <p style="margin: 0 0 8px 0; font-weight: bold; color: #0f5132;">
                        <img src="{$calPath}" class="emoji-icon" alt="📅"> Data prevista: 28/11/2026
                    </p>
                    <p style="margin: 0 0 8px 0;">
                        É importante manter o acompanhamento da vacinação do seu companheiro em dia.
                    </p>
                    <p style="margin: 0 0 8px 0;">
                        Entre em contato conosco para agendar a aplicação!
                    </p>
                    <p style="margin: 0 0 4px 0;">
                        Será um prazer receber vocês! <img src="{$hrtPath}" class="emoji-icon" alt="❤️">
                    </p>
                </div>
                <div class="wa-message-time">09:00</div>
            </div>
        </div>

        <div class="wa-bottom-bar">
            <div class="wa-input-fake">Mensagem...</div>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- ============================================================== -->
    <!-- CENÁRIO 2: NO DIA DO VENCIMENTO -->
    <!-- ============================================================== -->
    <div class="header-title">
        <h1>Simulação Visual de Notificação WhatsApp</h1>
        <p>Pré-visualização fiel com Logotipo da Clínica e Emojis como Imagens</p>
        <div class="divider"></div>
    </div>

    <div class="scenario-card">
        <h3>Cenário 2: Lembrete no Dia da Vacina (Vencimento Hoje)</h3>
        <p style="margin: 0 0 3px 0; color: #495057;">
            Disparo no dia exato em que o reforço da vacina vence no calendário sanitário do paciente.
        </p>
        <table>
            <tr>
                <td class="lbl">Tutor (Cliente):</td>
                <td>Carlos Eduardo Mendes</td>
                <td class="lbl">Pet (Paciente):</td>
                <td>Mel (Poodle Toy)</td>
            </tr>
            <tr>
                <td class="lbl">Vacina:</td>
                <td>Vacina Antirrábica</td>
                <td class="lbl">Data Prevista:</td>
                <td><strong>21/11/2026</strong> (Hoje)</td>
            </tr>
            <tr>
                <td class="lbl">Gatilho do Sistema:</td>
                <td colspan="3"><code>vaccine_reminders.template_due_today</code> (Disparo às 09:00)</td>
            </tr>
        </table>
    </div>

    <!-- MOLDURA DO SMARTPHONE -->
    <div class="phone-wrapper">
        <div class="phone-status-bar">
            <span class="time">09:02</span>
            <span class="icons">Wi-Fi &bull; 95% [===]</span>
            <div class="clear"></div>
        </div>

        <div class="wa-header">
            <div class="wa-avatar">
                <img src="{$logoPath}" alt="Logo">
            </div>
            <div class="wa-contact-info">
                <div class="wa-contact-name">Amigo Bicho &bull; Clínica</div>
                <div class="wa-contact-status">Conta comercial oficial &bull; Online</div>
            </div>
            <div class="clear"></div>
        </div>

        <div class="wa-chat-area">
            <div class="wa-date-pill">
                <span>Hoje</span>
            </div>

            <div class="wa-bubble-received">
                <!-- Card de Identificação da Clínica no Topo -->
                <div class="clinic-id-card">
                    <img src="{$logoPath}" alt="Logo">
                    <div class="clinic-id-title">CLÍNICA VETERINÁRIA AMIGO BICHO</div>
                    <div class="clinic-id-sub">Medicina Veterinária &bull; Lembretes Sanitários</div>
                    <div class="clear"></div>
                </div>

                <div class="wa-message-text">
                    <p style="margin: 0 0 8px 0; font-weight: bold;">
                        Olá, Carlos Eduardo Mendes! Tudo bem? <img src="{$pawPath}" class="emoji-icon" alt="🐾">
                    </p>
                    <p style="margin: 0 0 8px 0;">
                        Hoje está prevista a vacinação do seu pet <strong>Mel</strong>.
                    </p>
                    <p style="margin: 0 0 4px 0; font-weight: bold; color: #0f5132;">
                        <img src="{$syrPath}" class="emoji-icon" alt="💉"> Vacina: Vacina Antirrábica
                    </p>
                    <p style="margin: 0 0 8px 0; font-weight: bold; color: #0f5132;">
                        <img src="{$calPath}" class="emoji-icon" alt="📅"> Data prevista: 21/11/2026
                    </p>
                    <p style="margin: 0 0 8px 0;">
                        Estamos à disposição para agendar a aplicação e manter a saúde do seu companheiro em dia.
                    </p>
                    <p style="margin: 0 0 4px 0;">
                        Entre em contato conosco!
                    </p>
                </div>
                <div class="wa-message-time">09:00</div>
            </div>
        </div>

        <div class="wa-bottom-bar">
            <div class="wa-input-fake">Mensagem...</div>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- ============================================================== -->
    <!-- CENÁRIO 3: VACINA EM ATRASO -->
    <!-- ============================================================== -->
    <div class="header-title">
        <h1>Simulação Visual de Notificação WhatsApp</h1>
        <p>Pré-visualização fiel com Logotipo da Clínica e Emojis como Imagens</p>
        <div class="divider"></div>
    </div>

    <div class="scenario-card">
        <h3>Cenário 3: Lembrete de Vacina em Atraso (Reforço Pendente)</h3>
        <p style="margin: 0 0 3px 0; color: #495057;">
            Disparo com tom acolhedor e focado na proteção do animal para vacinas que já venceram.
        </p>
        <table>
            <tr>
                <td class="lbl">Tutor (Cliente):</td>
                <td>Juliana Costa</td>
                <td class="lbl">Pet (Paciente):</td>
                <td>Bob (Shih Tzu)</td>
            </tr>
            <tr>
                <td class="lbl">Vacina:</td>
                <td>Vacina contra Gripe Canina</td>
                <td class="lbl">Data Prevista:</td>
                <td><strong>14/11/2026</strong> (7 dias de atraso)</td>
            </tr>
            <tr>
                <td class="lbl">Gatilho do Sistema:</td>
                <td colspan="3"><code>vaccine_reminders.template_overdue</code> (Disparo a cada 7 dias, até 3 vezes)</td>
            </tr>
        </table>
    </div>

    <!-- MOLDURA DO SMARTPHONE -->
    <div class="phone-wrapper">
        <div class="phone-status-bar">
            <span class="time">09:02</span>
            <span class="icons">5G &bull; 100% [===]</span>
            <div class="clear"></div>
        </div>

        <div class="wa-header">
            <div class="wa-avatar">
                <img src="{$logoPath}" alt="Logo">
            </div>
            <div class="wa-contact-info">
                <div class="wa-contact-name">Amigo Bicho &bull; Clínica</div>
                <div class="wa-contact-status">Conta comercial oficial &bull; Online</div>
            </div>
            <div class="clear"></div>
        </div>

        <div class="wa-chat-area">
            <div class="wa-date-pill">
                <span>Hoje</span>
            </div>

            <div class="wa-bubble-received">
                <!-- Card de Identificação da Clínica no Topo -->
                <div class="clinic-id-card">
                    <img src="{$logoPath}" alt="Logo">
                    <div class="clinic-id-title">CLÍNICA VETERINÁRIA AMIGO BICHO</div>
                    <div class="clinic-id-sub">Medicina Veterinária &bull; Lembretes Sanitários</div>
                    <div class="clear"></div>
                </div>

                <div class="wa-message-text">
                    <p style="margin: 0 0 8px 0; font-weight: bold;">
                        Olá, Juliana Costa! Tudo bem? <img src="{$pawPath}" class="emoji-icon" alt="🐾">
                    </p>
                    <p style="margin: 0 0 8px 0;">
                        Identificamos que a vacinação do seu pet <strong>Bob</strong> ainda consta como pendente em nosso sistema.
                    </p>
                    <p style="margin: 0 0 4px 0; font-weight: bold; color: #0f5132;">
                        <img src="{$syrPath}" class="emoji-icon" alt="💉"> Vacina: Vacina contra Gripe Canina
                    </p>
                    <p style="margin: 0 0 8px 0; font-weight: bold; color: #0f5132;">
                        <img src="{$calPath}" class="emoji-icon" alt="📅"> Data prevista: 14/11/2026
                    </p>
                    <p style="margin: 0 0 8px 0;">
                        Gostaríamos de lembrar que é importante manter o calendário de vacinação atualizado.
                    </p>
                    <p style="margin: 0 0 8px 0;">
                        Entre em contato conosco para verificar a situação e agendar o atendimento.
                    </p>
                    <p style="margin: 0 0 4px 0;">
                        Aguardamos vocês! <img src="{$hrtPath}" class="emoji-icon" alt="❤️">
                    </p>
                </div>
                <div class="wa-message-time">09:00</div>
            </div>
        </div>

        <div class="wa-bottom-bar">
            <div class="wa-input-fake">Mensagem...</div>
        </div>
    </div>

    <div class="page-break"></div>

    <!-- ============================================================== -->
    <!-- GUIA DE CAMPOS E CUSTOMIZAÇÃO -->
    <!-- ============================================================== -->
    <div class="header-title">
        <h1>Análise de Campos e Personalização</h1>
        <p>Como customizar o texto e as variáveis diretamente no Amigo Bicho</p>
        <div class="divider"></div>
    </div>

    <h2 style="font-size: 10.5pt; color: #0f5132; margin-top: 5px;">Variáveis Dinâmicas Disponíveis nos Modelos</h2>
    <p>
        Você pode alterar livremente os textos no sistema. Sempre que a mensagem for enviada,
        o sistema substituirá automaticamente os códigos abaixo pelos dados reais do cliente:
    </p>

    <table class="ref-table">
        <thead>
            <tr>
                <th style="width: 25%;">Variável (Código)</th>
                <th style="width: 35%;">O que o Sistema Preenche</th>
                <th style="width: 40%;">Exemplo no WhatsApp</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>{NOME_TUTOR}</code></td>
                <td>Primeiro e segundo nome do cliente</td>
                <td>Mariana Souza</td>
            </tr>
            <tr>
                <td><code>{NOME_ANIMAL}</code></td>
                <td>Nome de registro do animalzinho</td>
                <td>Thor</td>
            </tr>
            <tr>
                <td><code>{NOME_VACINA}</code></td>
                <td>Nome da vacina ou imunizante</td>
                <td>Vacina Polivalente V10</td>
            </tr>
            <tr>
                <td><code>{DATA_VACINACAO}</code></td>
                <td>Data formatada do vencimento</td>
                <td>28/11/2026</td>
            </tr>
            <tr>
                <td><code>{LABORATORIO}</code></td>
                <td>Fabricante da vacina (se cadastrado)</td>
                <td>Zoetis / MSD / Dechra</td>
            </tr>
            <tr>
                <td><code>{DATA_APLICACAO}</code></td>
                <td>Data da última dose aplicada</td>
                <td>28/11/2025</td>
            </tr>
        </tbody>
    </table>

    <h2 style="font-size: 10.5pt; color: #0f5132; margin-top: 12px;">Como e Onde Alterar os Textos no Sistema</h2>
    <ol style="margin-bottom: 8px;">
        <li>Acesse o menu lateral do sistema: <strong>Atendimento &gt; Central de Vacinas</strong>.</li>
        <li>Clique na aba superior <strong>Configurações e Modelos</strong>.</li>
        <li>Role a página até os campos de texto:
            <ul>
                <li><strong>Modelo de Mensagem (7 dias antes)</strong></li>
                <li><strong>Modelo de Mensagem (Vence hoje)</strong></li>
                <li><strong>Modelo de Mensagem (Em atraso)</strong></li>
            </ul>
        </li>
        <li>Faça os ajustes de texto desejados e clique no botão verde <strong>Salvar Configurações</strong>.</li>
    </ol>

    <div class="tip-box">
        <strong>💡 Informações Importantes sobre Logotipo e Emojis:</strong>
        <ul style="margin: 3px 0 0 0; padding-left: 18px;">
            <li><strong>Logotipo no WhatsApp Real:</strong> Na conta oficial da Meta Cloud API (Passo 1.5 do manual), você faz o upload do arquivo <code>public/img/logo-amigobicho.png</code> como a foto de perfil da conta comercial. Assim, todo cliente receberá a mensagem com o logotipo da clínica identificando a conversa.</li>
            <li><strong>Emojis no Celular Real:</strong> No WhatsApp real, todos os emojis (🐾, 💉, 📅, ❤️) são interpretados e exibidos nativamente pelo sistema do celular do cliente como imagens coloridas em alta resolução. O problema do '?' ocorria apenas aqui na geração do PDF/PNG devido à fonte Helvetica/Arial padrão do Windows não possuir esses caracteres, o que agora foi totalmente resolvido com a incorporação direta dos ícones gráficos.</li>
        </ul>
    </div>

</body>
</html>
HTML;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$canvas = $dompdf->getCanvas();
$fontMetrics = $dompdf->getFontMetrics();
$fontNormal = $fontMetrics->getFont('Helvetica', 'normal');
$fontBold = $fontMetrics->getFont('Helvetica', 'bold');
$totalPages = $canvas->get_page_count();

for ($p = 1; $p <= $totalPages; $p++) {
    $canvas->reopen_object($canvas->open_object());
    $canvas->line(35, 810, 560, 810, [0.85, 0.85, 0.85], 0.5);
    $canvas->text(35, 820, "Amigo Bicho — Simulação Visual de Notificações WhatsApp • Central de Vacinas", $fontNormal, 7.5, [0.45, 0.45, 0.45]);
    $pageStr = "Página {$p} de {$totalPages}";
    $canvas->text(505, 820, $pageStr, $fontBold, 7.5, [0.2, 0.2, 0.2]);
    $canvas->close_object();
}

$pdfOutput = $dompdf->output();

$target1 = __DIR__ . '/public/docs/Simulacao_Mensagens_WhatsApp_Vacinas.pdf';
$target2 = __DIR__ . '/Simulacao_Mensagens_WhatsApp_Vacinas.pdf';
$target3 = __DIR__ . '/Simulacao_WhatsApp_Vacinas_Com_Logo.pdf';

if (!is_dir(dirname($target1))) {
    @mkdir(dirname($target1), 0777, true);
}

@file_put_contents($target1, $pdfOutput);
@file_put_contents($target2, $pdfOutput);
@file_put_contents($target3, $pdfOutput);

echo "Simulação PDF atualizada com sucesso!\n";
echo "Total de páginas: " . $totalPages . "\n";
echo "1. $target1 (" . number_format(filesize($target1) / 1024, 1) . " KB)\n";
echo "2. $target3 (" . number_format(filesize($target3) / 1024, 1) . " KB)\n";
