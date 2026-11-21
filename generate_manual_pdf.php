<?php

require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$html = <<<'HTML'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Manual de Integração WhatsApp Cloud API (Meta) - Amigo Bicho</title>
    <style>
        @page {
            margin: 22mm 16mm 22mm 16mm;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            color: #212529;
            margin: 0;
            padding: 0;
        }

        /* Capa */
        .cover-page {
            text-align: center;
            padding-top: 30mm;
            page-break-after: always;
        }

        .cover-badge {
            display: inline-block;
            background-color: #e8f5e9;
            color: #1b5e20;
            padding: 5px 14px;
            border-radius: 15px;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid #c8e6c9;
            margin-bottom: 20px;
        }

        .cover-title {
            font-size: 24pt;
            font-weight: bold;
            color: #0f5132;
            margin: 0 0 10px 0;
            line-height: 1.2;
        }

        .cover-subtitle {
            font-size: 13pt;
            color: #495057;
            margin: 0 0 25px 0;
            font-weight: normal;
        }

        .cover-divider {
            width: 70px;
            height: 4px;
            background-color: #198754;
            margin: 0 auto 30px auto;
            border-radius: 2px;
        }

        .cover-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 16px 20px;
            max-width: 440px;
            margin: 0 auto 35mm auto;
            text-align: left;
            font-size: 9pt;
        }

        .cover-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .cover-box td {
            padding: 3px 4px;
        }

        .cover-box td.label {
            font-weight: bold;
            color: #495057;
            width: 38%;
        }

        .cover-box td.val {
            color: #212529;
        }

        .cover-footer-note {
            font-size: 8.5pt;
            color: #6c757d;
            line-height: 1.4;
        }

        /* Títulos */
        h1 {
            font-size: 15pt;
            color: #0f5132;
            border-bottom: 2px solid #198754;
            padding-bottom: 4px;
            margin-top: 18px;
            margin-bottom: 12px;
        }

        h2 {
            font-size: 11.5pt;
            color: #1b4332;
            margin-top: 16px;
            margin-bottom: 8px;
            border-left: 3px solid #198754;
            padding-left: 6px;
        }

        h3 {
            font-size: 10pt;
            color: #212529;
            margin-top: 12px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        p {
            margin: 0 0 6px 0;
            text-align: justify;
        }

        ul, ol {
            margin: 0 0 8px 0;
            padding-left: 20px;
        }

        li {
            margin-bottom: 3px;
        }

        .page-break {
            page-break-after: always;
        }

        /* Caixas de Alerta */
        .alert {
            padding: 7px 11px;
            margin: 8px 0;
            border-radius: 4px;
            font-size: 8.5pt;
            line-height: 1.35;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #664d03;
            border-left: 4px solid #ffc107;
            border-top: 1px solid #ffe69c;
            border-right: 1px solid #ffe69c;
            border-bottom: 1px solid #ffe69c;
        }

        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
            border-left: 4px solid #198754;
            border-top: 1px solid #badbcc;
            border-right: 1px solid #badbcc;
            border-bottom: 1px solid #badbcc;
        }

        .alert-info {
            background-color: #cff4fc;
            color: #055160;
            border-left: 4px solid #0dcaf0;
            border-top: 1px solid #b6effb;
            border-right: 1px solid #b6effb;
            border-bottom: 1px solid #b6effb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
            border-left: 4px solid #dc3545;
            border-top: 1px solid #f5c2c7;
            border-right: 1px solid #f5c2c7;
            border-bottom: 1px solid #f5c2c7;
        }

        .alert-title {
            font-weight: bold;
            margin-bottom: 2px;
            display: block;
            text-transform: uppercase;
            font-size: 8pt;
            letter-spacing: 0.5px;
        }

        /* Tabelas */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 14px 0;
            font-size: 8.5pt;
        }

        table.data-table th, table.data-table td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }

        table.data-table th {
            background-color: #f1f8f4;
            color: #0f5132;
            font-weight: bold;
        }

        table.data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }

        /* Código */
        code {
            background-color: #f1f3f5;
            color: #b02a37;
            padding: 1px 4px;
            border-radius: 3px;
            font-family: Courier, monospace;
            font-size: 8.5pt;
        }

        pre {
            background-color: #212529;
            color: #f8f9fa;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: Courier, monospace;
            font-size: 8pt;
            line-height: 1.35;
            margin: 8px 0 12px 0;
        }

        /* Sumário */
        .toc-list {
            list-style: none;
            padding-left: 0;
            margin: 15px 0;
        }

        .toc-list li {
            padding: 6px 0;
            border-bottom: 1px dotted #ced4da;
            font-size: 9pt;
        }

        .toc-list li .title {
            font-weight: bold;
            color: #0f5132;
        }

        .toc-list li .desc {
            font-size: 8pt;
            color: #6c757d;
            display: block;
            margin-top: 1px;
        }

        .compact-list ol, .compact-list ul { margin: 0 0 5px 0; padding-left: 18px; }
        .compact-list li { margin-bottom: 2px; font-size: 9pt; }
        .compact-list p { margin: 0 0 4px 0; font-size: 9pt; }

        .text-success { color: #198754; }
        .text-danger { color: #dc3545; }
        .font-weight-bold { font-weight: bold; }
    </style>
</head>
<body>

    <!-- ============================================================== -->
    <!-- CAPA DO MANUAL -->
    <!-- ============================================================== -->
    <div class="cover-page">
        <div class="cover-badge">Guia Oficial de Implantação e Operação</div>
        <h1 class="cover-title">Manual de Integração<br>WhatsApp Cloud API (Meta)</h1>
        <div class="cover-subtitle">Notificações e Lembretes Automáticos de Vacinação para Clientes</div>
        <div class="cover-divider"></div>

        <div class="cover-box">
            <table>
                <tr>
                    <td class="label">Sistema:</td>
                    <td class="val">Amigo Bicho — Gestão Veterinária</td>
                </tr>
                <tr>
                    <td class="label">Versão da Aplicação:</td>
                    <td class="val">v1.12.0 ou superior</td>
                </tr>
                <tr>
                    <td class="label">Módulo Abordado:</td>
                    <td class="val">Central de Lembretes de Vacinas (WhatsApp)</td>
                </tr>
                <tr>
                    <td class="label">Provedor Homologado:</td>
                    <td class="val">Meta WhatsApp Cloud API (Graph v21.0)</td>
                </tr>
                <tr>
                    <td class="label">Tipo de Conexão:</td>
                    <td class="val">API Oficial em Nuvem (Sem QR-Code / Sem Celular)</td>
                </tr>
                <tr>
                    <td class="label">Data de Publicação:</td>
                    <td class="val">Novembro de 2026</td>
                </tr>
            </table>
        </div>

        <p class="cover-footer-note">
            Este manual descreve o passo a passo completo para cadastrar a conta comercial junto à Meta (Facebook Developers),
            gerar o Token Permanente de Usuário do Sistema, configurar as credenciais no Amigo Bicho e programar a rotina automática diária no Windows.
        </p>
    </div>

    <!-- ============================================================== -->
    <!-- SUMÁRIO EXECUTIVO -->
    <!-- ============================================================== -->
    <h1>Sumário do Manual</h1>
    <p>Siga cada uma das partes deste guia em ordem cronológica para garantir o funcionamento imediato e ininterrupto:</p>

    <ul class="toc-list">
        <li>
            <span class="title">1. Visão Geral e Vantagens da Meta Cloud API</span>
            <span class="desc">Por que a API oficial da Meta é a melhor escolha, gratuidade de 1.000 conversas/mês e arquitetura no sistema.</span>
        </li>
        <li>
            <span class="title">2. Parte 1: Configuração Completa na Meta (Passo a Passo)</span>
            <span class="desc">Criação da conta de desenvolvedor, Gerenciador de Negócios, aplicativo empresarial, cadastro do número da clínica, localização do Phone Number ID e geração do Token Permanente de Usuário do Sistema.</span>
        </li>
        <li>
            <span class="title">3. Parte 2: Configuração Dentro da Aplicação Amigo Bicho</span>
            <span class="desc">Inserção das credenciais no painel de Notificações, seleção do provedor oficial e execução do primeiro teste manual.</span>
        </li>
        <li>
            <span class="title">4. Parte 3: Central de Lembretes Automáticos de Vacinas</span>
            <span class="desc">Parametrização dos lembretes (7 dias antes, no dia do vencimento e em atraso), personalização dos modelos de mensagens, placeholders dinâmicos e regras de consentimento LGPD (opt-in).</span>
        </li>
        <li>
            <span class="title">5. Parte 4: Automação no Windows (Agendador de Tarefas)</span>
            <span class="desc">Configuração do script <code>executar_rotina_vacinas.bat</code> no Agendador de Tarefas do Windows para envio diário autônomo.</span>
        </li>
        <li>
            <span class="title">6. Parte 5: Diagnóstico, Resolução de Erros e Boas Práticas</span>
            <span class="desc">Tabela de códigos de erro HTTP da Meta (401, 400, 403), consulta de logs no sistema e recomendações para evitar bloqueios.</span>
        </li>
        <li>
            <span class="title">7. Anexo: Checklist de Homologação Final</span>
            <span class="desc">Lista rápida para checagem dos itens essenciais antes de iniciar a operação diária.</span>
        </li>
    </ul>

    <div class="alert alert-info">
        <span class="alert-title">Nota de Prerequisito</span>
        Para seguir este tutorial, você precisará de: acesso com perfil Administrador ao Amigo Bicho, uma conta pessoal no Facebook para acessar a plataforma Meta for Developers, e o número de telefone celular ou fixo que será utilizado como canal oficial da clínica.
    </div>

    <div class="page-break"></div>

    <!-- ============================================================== -->
    <!-- SEÇÃO 1: VISÃO GERAL -->
    <!-- ============================================================== -->
    <h1>1. Visão Geral e Vantagens da Meta Cloud API</h1>

    <p>
        O <strong>Amigo Bicho</strong> possui suporte nativo e direto à <strong>WhatsApp Cloud API</strong>, a plataforma em nuvem disponibilizada e mantida diretamente pela <strong>Meta (empresa controladora do WhatsApp, Facebook e Instagram)</strong>.
    </p>

    <h2>Por que a WhatsApp Cloud API da Meta é a melhor opção?</h2>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 26%;">Critério</th>
                <th style="width: 37%;">Meta WhatsApp Cloud API (Oficial)</th>
                <th style="width: 37%;">APIs Não Oficiais / QR Code</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Estabilidade e Quedas</strong></td>
                <td><span class="text-success font-weight-bold">100% em nuvem da Meta</span>. Nunca desconecta nem exige celular ligado ou com bateria.</td>
                <td><span class="text-danger">Altamente instável</span>. Desconecta com frequência quando o celular descarrega ou perde sinal Wi-Fi.</td>
            </tr>
            <tr>
                <td><strong>Risco de Banimento</strong></td>
                <td><span class="text-success font-weight-bold">Zero risco</span>. Canal oficial aprovado pela Meta para empresas e clínicas.</td>
                <td><span class="text-danger">Altíssimo risco</span>. A Meta identifica robôs não oficiais e bane o número do WhatsApp permanentemente.</td>
            </tr>
            <tr>
                <td><strong>Custo e Mensalidades</strong></td>
                <td><span class="text-success font-weight-bold">1.000 conversas gratuitas</span> todo mês por conta comercial. Custo zero para a maioria das clínicas.</td>
                <td>Cobram mensalidades fixas em reais (R$ 80 a R$ 250/mês) mesmo que você envie poucas mensagens.</td>
            </tr>
            <tr>
                <td><strong>Velocidade de Envio</strong></td>
                <td>Instantânea (servidores diretos da Meta no Brasil e no mundo).</td>
                <td>Lenta e sujeita à latência da conexão do smartphone.</td>
            </tr>
        </tbody>
    </table>

    <h2>Como a integração opera no Amigo Bicho</h2>
    <p>
        A aplicação conecta-se à API Graph da Meta no endpoint oficial da versão 21.0. Para funcionar perfeitamente, o sistema requer apenas duas credenciais:
    </p>
    <ul>
        <li><strong>Phone Number ID</strong>: Identificador numérico do número de envio cadastrado na Meta.</li>
        <li><strong>Access Token (Permanente)</strong>: Chave criptográfica de longa duração gerada por um Usuário do Sistema dentro do seu Gerenciador de Negócios Meta.</li>
    </ul>

    <div class="page-break"></div>

    <!-- ============================================================== -->
    <!-- SEÇÃO 2: PARTE 1 - CONFIGURAÇÃO NA META -->
    <!-- ============================================================== -->
    <h1>2. Parte 1: Configuração Completa na Meta (Passo a Passo)</h1>

    <p>
        Siga detalhadamente os passos abaixo na plataforma da Meta para obter as duas credenciais necessárias.
    </p>

    <h2>Passo 1.1: Acessar a Conta Meta for Developers</h2>
    <ol>
        <li>Abra o navegador e acesse o portal: <strong>developers.facebook.com</strong>.</li>
        <li>No canto superior direito, clique em <strong>Entrar (Log In)</strong> e acesse com a conta do Facebook do gestor ou da clínica.</li>
        <li>Se for o seu primeiro acesso ao portal de desenvolvedores, clique em <strong>Começar (Get Started)</strong>, confirme seu e-mail e selecione o perfil <strong>Proprietário/Desenvolvedor</strong>.</li>
    </ol>

    <h2>Passo 1.2: Acessar ou Criar o Gerenciador de Negócios (Meta Business)</h2>
    <ol>
        <li>Acesse <strong>business.facebook.com</strong> ou o Gerenciador de Negócios em <strong>business.facebook.com/settings</strong>.</li>
        <li>Se a clínica já possui uma página oficial no Facebook ou perfil no Instagram, a empresa já existirá. Caso não possua, clique em <strong>Criar uma conta comercial</strong> e preencha a Razão Social/Nome da Clínica e seu e-mail comercial.</li>
    </ol>

    <h2>Passo 1.3: Criar um Aplicativo Empresarial na Meta</h2>
    <ol>
        <li>No portal <strong>Meta for Developers</strong> (developers.facebook.com), clique em <strong>Meus Aplicativos (My Apps)</strong> no menu superior.</li>
        <li>Clique no botão verde <strong>Criar aplicativo (Create App)</strong>.</li>
        <li>Na pergunta <em>"O que você quer que seu aplicativo faça?"</em>, selecione a opção <strong>Outro (Other)</strong> e clique em <strong>Avançar</strong>.</li>
        <li>Na tela seguinte, selecione o tipo: <strong>Empresa (Business)</strong> e clique em <strong>Avançar</strong>.</li>
        <li>Preencha os dados do aplicativo:
            <ul>
                <li><strong>Nome do aplicativo</strong>: Digite <code>Amigo Bicho - Notificacoes</code> (ou o nome da sua clínica).</li>
                <li><strong>E-mail de contato</strong>: O e-mail do responsável técnico da clínica.</li>
                <li><strong>Conta empresarial</strong>: Selecione a conta comercial da clínica criada no Passo 1.2.</li>
            </ul>
        </li>
        <li>Clique em <strong>Criar aplicativo</strong> e insira sua senha do Facebook se solicitado.</li>
    </ol>

    <h2>Passo 1.4: Adicionar o Produto WhatsApp ao Aplicativo</h2>
    <ol>
        <li>No Painel de Controle do Aplicativo recém-criado, role a página até a seção <strong>Adicionar produtos ao seu aplicativo</strong>.</li>
        <li>Localize o cartão com o ícone do <strong>WhatsApp</strong> e clique no botão <strong>Configurar (Set Up)</strong>.</li>
        <li>A Meta criará automaticamente a estrutura da API do WhatsApp Cloud integrada ao seu aplicativo.</li>
    </ol>

    <div class="alert alert-warning">
        <span class="alert-title">Importante: Número de Teste vs. Número Real da Clínica</span>
        A Meta disponibiliza inicialmente um <strong>"Número de teste do WhatsApp"</strong>. Esse número de teste só consegue disparar mensagens para até 5 números de telefone que você cadastrar manualmente na tela da Meta como destinatários autorizados. Para disparar para <strong>todos os clientes e tutores da sua clínica</strong>, você deve vincular o <strong>número oficial da sua clínica</strong> (Passo 1.5).
    </div>

    <div class="page-break"></div>

    <h2>Passo 1.5: Cadastrar o Número Oficial de WhatsApp da Clínica</h2>
    <p>
        Para vincular o número de telefone da sua clínica à API da Meta:
    </p>

    <div class="alert alert-danger">
        <span class="alert-title">Atenção Crítica: Requisito de Número Desvinculado</span>
        O número que você cadastrar na Cloud API da Meta <strong>NÃO pode estar ativo no aplicativo do WhatsApp normal nem no WhatsApp Business em nenhum aparelho smartphone!</strong><br>
        Se o número já estiver sendo usado em um celular, você deve antes abrir o aplicativo do WhatsApp no aparelho, ir em <em>Configurações &gt; Conta &gt; Apagar minha conta</em> (faça backup das conversas antes caso deseje). Se for um número fixo da clínica ou um novo chip exclusivo para notificações, basta cadastrá-lo diretamente.
    </div>

    <ol>
        <li>No menu lateral esquerdo do aplicativo, clique em <strong>WhatsApp</strong> &gt; <strong>Configuração da API (API Setup)</strong>.</li>
        <li>Role a página até a <strong>Etapa 5: Adicionar número de telefone</strong> e clique no botão <strong>Adicionar número de telefone</strong>.</li>
        <li>Preencha o <strong>Perfil do WhatsApp Business</strong>:
            <ul>
                <li><strong>Nome de exibição do WhatsApp</strong>: O nome oficial da clínica (ex: <code>Clínica Veterinária Amigo Bicho</code>). Este é o nome que os tutores verão no cabeçalho do WhatsApp.</li>
                <li><strong>Fuso horário</strong>: <code>América/São Paulo (GMT-3)</code>.</li>
                <li><strong>Categoria</strong>: <code>Medicina e Saúde</code> ou <code>Outro / Serviços para Pets</code>.</li>
                <li><strong>Descrição da empresa</strong>: Breve resumo dos atendimentos da clínica.</li>
            </ul>
        </li>
        <li>Clique em <strong>Avançar</strong>. Digite o número com o DDD (ex: <code>11 98888-7777</code> ou número fixo com DDD).</li>
        <li>Escolha o método de confirmação: <strong>Mensagem de Texto (SMS)</strong> ou <strong>Ligação telefônica</strong> (ideal para números fixos).</li>
        <li>Informe o código de 6 dígitos recebido e confirme. Pronto! O número da sua clínica agora faz parte da Cloud API oficial.</li>
    </ol>

    <h2>Passo 1.6: Localizar o Phone Number ID</h2>
    <ol>
        <li>Ainda na tela <strong>WhatsApp &gt; Configuração da API (API Setup)</strong>:</li>
        <li>No campo suspenso <strong>De (From)</strong>, certifique-se de que o número da clínica cadastrado está selecionado.</li>
        <li>Logo abaixo do número selecionado, localize o campo denominado:
            <br><strong>Identificação do número de telefone (Phone number ID)</strong>.
        </li>
        <li>Copie essa sequência numérica (geralmente possui cerca de 15 a 16 dígitos, como por exemplo: <code>105948372615029</code>).</li>
        <li>Guarde esse número: ele será colado no campo correspondente dentro do Amigo Bicho.</li>
    </ol>

    <div class="alert alert-warning">
        <span class="alert-title">Não Confunda com o WABA ID!</span>
        Abaixo do Phone Number ID existe outro campo chamado <em>Identificação da conta do WhatsApp Business (WABA ID)</em>. <strong>Não use o WABA ID</strong>. A integração do Amigo Bicho exige exclusivamente o <strong>Phone Number ID</strong>.
    </div>

    <div class="page-break"></div>

    <h2>Passo 1.7: Criar Usuário do Sistema e Gerar o Token Permanente</h2>

    <div class="alert alert-danger">
        <span class="alert-title">AVISO CRUCIAL: O Token Temporário Expira em 24 Horas!</span>
        Na tela de testes da Meta existe um botão com um token azul rotulado <em>"Token de acesso temporário"</em>. <strong>NUNCA use esse token temporário em produção</strong>, pois após exatamente 24 horas o envio de mensagens irá parar com erro <code>Cloud API error: 401</code>.<br>
        Siga o procedimento obrigatório abaixo para gerar o <strong>Token Permanente de Usuário do Sistema</strong> que nunca expira:
    </div>

    <ol>
        <li>Acesse o painel de <strong>Configurações do Negócio da Meta</strong>:
            <br><strong>business.facebook.com/settings</strong>
        </li>
        <li>No menu lateral esquerdo, na seção <strong>Usuários</strong>, clique em <strong>Usuários do sistema (System users)</strong>.</li>
        <li>Clique no botão azul <strong>Adicionar (Add)</strong>:
            <ul>
                <li><strong>Nome do usuário do sistema</strong>: Digite <code>AmigoBicho-Servidor</code>.</li>
                <li><strong>Função do usuário do sistema</strong>: Selecione obrigatoriamente <strong>Administrador (Admin)</strong>.</li>
                <li>Clique em <strong>Criar usuário do sistema</strong>.</li>
            </ul>
        </li>
        <li>Com o usuário <code>AmigoBicho-Servidor</code> selecionado na lista, clique no botão <strong>Atribuir ativos (Assign Assets)</strong>:
            <ul>
                <li>Na coluna esquerda, selecione <strong>Aplicativos (Apps)</strong>.</li>
                <li>Marque o aplicativo criado anteriormente (<code>Amigo Bicho - Notificacoes</code>).</li>
                <li>Na coluna da direita, role até o final e ative a chave <strong>Controle total / Gerenciar aplicativo (Full control)</strong>.</li>
                <li>Clique em <strong>Salvar alterações</strong>.</li>
            </ul>
        </li>
        <li>Ainda com o usuário selecionado, clique no botão <strong>Gerar novo token (Generate new token)</strong>:
            <ul>
                <li><strong>Aplicativo</strong>: Selecione <code>Amigo Bicho - Notificacoes</code>.</li>
                <li><strong>Expiração do token (Token expiration)</strong>: Mude de 60 dias para <strong>Nunca (Never)</strong>.</li>
                <li><strong>Permissões disponíveis (Available permissions)</strong>: Role a lista e marque as duas opções essenciais:
                    <br>&nbsp;&nbsp;✔ <strong><code>whatsapp_business_messaging</code></strong> (Permite o envio de mensagens)
                    <br>&nbsp;&nbsp;✔ <strong><code>whatsapp_business_management</code></strong> (Permite gerenciar a conta)
                </li>
            </ul>
        </li>
        <li>Clique em <strong>Gerar token</strong> na parte inferior do modal.</li>
        <li>Uma janela abrirá exibindo um token longo que inicia com <code>EAA...</code>.</li>
        <li>Clique no botão <strong>Copiar</strong> e salve esse token em um local seguro (bloco de notas protegido), pois a Meta nunca mais exibirá esse código completo por razões de segurança!</li>
    </ol>

    <h2>Passo 1.8: Forma de Pagamento e Gratuidade da Meta</h2>
    <p>
        A Meta oferece <strong>1.000 conversas de serviço gratuitas todo mês</strong> por conta do WhatsApp Business. As mensagens de lembrete de vacinas e consultas enquadram-se na categoria de serviço.
    </p>
    <p>
        Para que a conta opere sem limitações de volume após o período inicial, acesse <strong>Configurações do Negócio &gt; Contas do WhatsApp &gt; Configurações de Pagamento</strong> e cadastre um cartão de crédito corporativo. Nenhuma cobrança será realizada enquanto o volume mensal estiver dentro das 1.000 conversas gratuitas.
    </p>

    <div class="page-break"></div>

    <!-- ============================================================== -->
    <!-- SEÇÃO 3: PARTE 2 - CONFIGURAÇÃO NO AMIGO BICHO -->
    <!-- ============================================================== -->
    <h1>3. Parte 2: Configuração Dentro da Aplicação Amigo Bicho</h1>

    <p>
        Com o <strong>Phone Number ID</strong> (Passo 1.6) e o <strong>Access Token Permanente</strong> (Passo 1.7) em mãos, a configuração no Amigo Bicho é extremamente simples.
    </p>

    <h2>Passo 2.1: Cadastrar as Credenciais no Painel de Notificações</h2>
    <ol>
        <li>Abra o navegador e acesse o Amigo Bicho: <code>http://localhost:8181</code> (ou o endereço IP do servidor na rede local).</li>
        <li>Faça login com um perfil administrador (ex: <code>super@vet.com</code>).</li>
        <li>No menu lateral esquerdo, expanda <strong>Configurações do Sistema</strong> e clique em <strong>Notificações</strong> (URL direta: <code>/configuracoes/notificacoes</code>).</li>
        <li>Na tela que se abrir, clique na aba <strong>WhatsApp</strong> (ícone verde do WhatsApp).</li>
        <li>No campo de seleção <strong>Provedor de WhatsApp</strong>, selecione a opção:
            <br><strong>WhatsApp Cloud API (Meta)</strong>.
        </li>
        <li>Os campos específicos aparecerão na tela:
            <ul>
                <li><strong>Access Token (permanente)</strong>: Cole o token gerado que começa com <code>EAA...</code>.</li>
                <li><strong>Phone Number ID</strong>: Cole o identificador numérico copiado da Meta (ex: <code>105948372615029</code>).</li>
            </ul>
        </li>
        <li>Clique no botão verde <strong>Salvar Configurações</strong> no rodapé da página.</li>
        <li>O sistema exibirá a notificação: <em>"Configurações atualizadas com sucesso!"</em>.</li>
    </ol>

    <div class="alert alert-success">
        <span class="alert-title">Pronto! A Integração com a Meta está Concluída</span>
        O Amigo Bicho agora está diretamente autenticado nos servidores da Meta. Qualquer disparo de mensagem via WhatsApp utilizará esse canal oficial com criptografia e entrega imediata.
    </div>

    <h2>Passo 2.2: Testar o Envio Manual de um Lembrete</h2>
    <p>
        Para validar se as credenciais estão 100% corretas antes de ligar a rotina automática:
    </p>
    <ol>
        <li>No menu lateral, acesse <strong>Atendimento &gt; Vacinas</strong> ou cadastre uma vacina preventiva em um animal de teste.</li>
        <li>Certifique-se de que o tutor do animal de teste possui um <strong>número de celular válido com DDD</strong> e o WhatsApp marcado como autorizado.</li>
        <li>Acesse <strong>Atendimento &gt; Central de Vacinas &gt; Aba "Lembretes Pendentes"</strong> ou acesse o registro da vacina e clique em <strong>Enviar Lembrete WhatsApp</strong>.</li>
        <li>Aguarde 2 segundos. A mensagem será transmitida aos servidores da Meta e entregue no celular do destinatário.</li>
        <li>Para conferir o log detalhado da transmissão, acesse no menu lateral: <strong>Configurações do Sistema &gt; Logs de Notificação</strong>. A linha correspondente exibirá o status <strong>Enviado (sent)</strong> acompanhado do ID da mensagem retornado pela Meta.</li>
    </ol>

    <div class="page-break"></div>

    <!-- ============================================================== -->
    <!-- SEÇÃO 4: PARTE 3 - CENTRAL DE VACINAS AUTOMÁTICA -->
    <!-- ============================================================== -->
    <h1>4. Parte 3: Central de Lembretes Automáticos de Vacinas</h1>

    <p>
        O Amigo Bicho conta com uma central inteligente dedicada exclusivamente ao ciclo de vida das vacinações preventivas, acessível no menu <strong>Atendimento &gt; Central de Vacinas</strong> (URL: <code>/vaccination-center</code>).
    </p>

    <h2>Passo 3.1: Configurar os Parâmetros da Rotina Automática</h2>
    <p>
        Na Central de Vacinas, clique na aba <strong>Configurações e Modelos</strong>. Você encontrará os seguintes controles operacionais:
    </p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 32%;">Campo / Opção</th>
                <th style="width: 20%;">Padrão Recomendado</th>
                <th style="width: 48%;">Descrição e Comportamento</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Envio Automático</strong></td>
                <td><span class="text-success font-weight-bold">ATIVADO</span></td>
                <td>Chave geral. Quando ativada, autoriza o sistema a despachar mensagens via WhatsApp para os tutores identificados pela rotina diária.</td>
            </tr>
            <tr>
                <td><strong>Horário da Execução Diária</strong></td>
                <td><code>09:00</code></td>
                <td>Horário matutino ideal (horário de Brasília) para envio dos lembretes aos clientes, garantindo alta taxa de leitura sem incômodo.</td>
            </tr>
            <tr>
                <td><strong>Lembrete 7 Dias Antes</strong></td>
                <td><span class="text-success font-weight-bold">ATIVADO</span></td>
                <td>Identifica vacinas cujo reforço vença exatamente daqui a 7 dias, permitindo ao tutor planejar o agendamento prévio.</td>
            </tr>
            <tr>
                <td><strong>Lembrete no Dia do Vencimento</strong></td>
                <td><span class="text-success font-weight-bold">ATIVADO</span></td>
                <td>Dispara no dia exato em que a vacina completa o período de reforço anual ou semestral.</td>
            </tr>
            <tr>
                <td><strong>Lembrete de Vacinas em Atraso</strong></td>
                <td><span class="text-success font-weight-bold">ATIVADO</span></td>
                <td>Acompanha animais com vacinas já vencidas que ainda não compareceram à clínica para a aplicação.</td>
            </tr>
            <tr>
                <td><strong>Intervalo entre Reenvios de Atraso</strong></td>
                <td><code>7 dias</code></td>
                <td>Espaçamento mínimo em dias entre um aviso de atraso e o próximo, evitando que o tutor se sinta importunado.</td>
            </tr>
            <tr>
                <td><strong>Limite Máximo de Tentativas</strong></td>
                <td><code>3 tentativas</code></td>
                <td>Após 3 avisos sem retorno do tutor, a rotina interrompe reenvios automáticos para preservar a reputação do número de WhatsApp.</td>
            </tr>
            <tr>
                <td><strong>Janela Máxima de Atraso</strong></td>
                <td><code>180 dias</code></td>
                <td>Ignora atrasos históricos superiores a 6 meses para não disparar lembretes desatualizados para clientes inativos.</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>Passo 3.2: Personalização dos Modelos de Mensagem</h2>
    <p>
        O sistema permite personalizar o texto exato de cada um dos 3 momentos do ciclo vacinal. Você pode utilizar as seguintes <strong>variáveis dinâmicas (placeholders)</strong> que o Amigo Bicho preenche automaticamente antes de enviar:
    </p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Variável</th>
                <th style="width: 35%;">Exemplo Substituído</th>
                <th style="width: 35%;">Origem no Sistema</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>{NOME_TUTOR}</code></td>
                <td>Maria Silva</td>
                <td>Nome do tutor no cadastro</td>
            </tr>
            <tr>
                <td><code>{NOME_ANIMAL}</code></td>
                <td>Thor</td>
                <td>Nome do animal vacinado</td>
            </tr>
            <tr>
                <td><code>{NOME_VACINA}</code></td>
                <td>Vacina Polivalente V10</td>
                <td>Nome do imunizante aplicado</td>
            </tr>
            <tr>
                <td><code>{DATA_VACINACAO}</code></td>
                <td>28/11/2026</td>
                <td>Data prevista do reforço</td>
            </tr>
            <tr>
                <td><code>{LABORATORIO}</code></td>
                <td>Zoetis</td>
                <td>Fabricante (se preenchido)</td>
            </tr>
            <tr>
                <td><code>{DATA_APLICACAO}</code></td>
                <td>28/11/2025</td>
                <td>Data da última dose dada</td>
            </tr>
        </tbody>
    </table>

    <h3>Exemplo de Modelo Recomendado para 7 Dias Antes:</h3>
    <pre>Olá, {NOME_TUTOR}! Tudo bem? 🐾

Estamos passando para lembrar que a vacinação do seu pet {NOME_ANIMAL} está prevista para daqui a 7 dias.

💉 Vacina: {NOME_VACINA}
📅 Data prevista: {DATA_VACINACAO}

É muito importante manter o calendário de vacinação do seu companheiro em dia para protegê-lo contra doenças graves.

Entre em contato conosco para agendar o melhor horário para a aplicação!
Será um grande prazer receber vocês! ❤️
Clínica Veterinária Amigo Bicho</pre>

    <h2>Passo 3.3: Requisitos Obrigatórios nos Cadastros (LGPD e Formato)</h2>
    <p>
        Para que a rotina automática selecione o tutor e execute o disparo com sucesso, dois requisitos simples devem ser atendidos:
    </p>

    <ol>
        <li>
            <strong>Telefone com DDD Válido</strong>: O cadastro do tutor deve conter um número de celular válido com código de área (ex: <code>(11) 98765-4321</code>). O sistema higieniza o número automaticamente removendo pontuações e prefixando o código de país <code>55</code>.
        </li>
        <li>
            <strong>Consentimento LGPD (Opt-in de WhatsApp)</strong>:
            <br>Em conformidade com a <em>Lei Geral de Proteção de Dados (LGPD)</em> e com as políticas anti-spam da Meta, o campo <strong>WhatsApp Autorizado (Opt-in)</strong> do tutor deve estar marcado como <strong>Sim</strong>.
            <br>Tutores cujo consentimento esteja pendente ou recusado são <strong>automaticamente ignorados</strong> pela rotina automática, impedindo que sua clínica receba denúncias de mensagens não solicitadas na Meta.
            <br><em>Dica:</em> Você pode gerenciar o consentimento de todos os tutores em massa na aba <strong>Tutores</strong> da Central de Vacinas.
        </li>
    </ol>

    <div class="page-break"></div>

    <!-- ============================================================== -->
    <!-- SEÇÃO 5: PARTE 4 - AUTOMAÇÃO NO WINDOWS -->
    <!-- ============================================================== -->
    <div class="compact-list">
    <h1>5. Parte 4: Automação no Windows (Agendador de Tarefas)</h1>

    <p>
        Para que os lembretes sejam processados e enviados todos os dias às 09:00 sem que nenhum funcionário precise clicar manualmente em botões, configuramos uma tarefa automática nativa do Windows através do <strong>Agendador de Tarefas (Task Scheduler)</strong>.
    </p>

    <h2>O Script de Execução Autônoma</h2>
    <p>
        O Amigo Bicho já possui um script executável pronto localizado na raiz do sistema:
        <br><strong><code>C:\Amigo Bicho\executar_rotina_vacinas.bat</code></strong>
    </p>
    <p>
        Esse script localiza automaticamente o motor portátil do PHP da aplicação, executa o comando <code>php artisan vaccines:process-reminders</code> e grava um histórico detalhado em <code>storage/logs/rotina_vacinas.log</code>.
    </p>

    <h2>Passo a Passo: Criar a Tarefa no Agendador do Windows</h2>
    <ol>
        <li>No teclado do computador servidor da clínica, pressione as teclas <strong><code>Windows + R</code></strong>.</li>
        <li>Na caixinha Executar que se abre, digite <strong><code>taskschd.msc</code></strong> e pressione <strong>Enter</strong>.</li>
        <li>A janela do <strong>Agendador de Tarefas</strong> será exibida.</li>
        <li>No painel de <strong>Ações</strong> à direita, clique em <strong>Criar Tarefa Básica... (Create Basic Task)</strong>.</li>
        <li>Na primeira etapa (Nome):
            <ul>
                <li><strong>Nome</strong>: Digite <code>Amigo Bicho - Notificacoes de Vacinas WhatsApp</code>.</li>
                <li><strong>Descrição</strong>: <code>Disparo diário de lembretes automáticos de vacinação via WhatsApp Cloud API</code>.</li>
                <li>Clique em <strong>Avançar</strong>.</li>
            </ul>
        </li>
        <li>Na etapa <strong>Disparador (Trigger)</strong>:
            <ul>
                <li>Selecione <strong>Diariamente (Daily)</strong> e clique em <strong>Avançar</strong>.</li>
                <li>Defina o horário inicial para <strong><code>09:00:00</code></strong>.</li>
                <li>Deixe a repetição em <strong>A cada 1 dia(s)</strong> e clique em <strong>Avançar</strong>.</li>
            </ul>
        </li>
        <li>Na etapa <strong>Ação (Action)</strong>:
            <ul>
                <li>Selecione <strong>Iniciar um programa (Start a program)</strong> e clique em <strong>Avançar</strong>.</li>
                <li>No campo <strong>Programa/script</strong>, clique em Procurar e selecione:
                    <br><code>C:\Amigo Bicho\executar_rotina_vacinas.bat</code>
                </li>
                <li>No campo <strong>Iniciar em (opcional)</strong>, digite o caminho da pasta:
                    <br><code>C:\Amigo Bicho</code>
                </li>
                <li>Clique em <strong>Avançar</strong>.</li>
            </ul>
        </li>
        <li>Marque a opção <em>"Abrir a caixa de diálogo Propriedades ao clicar em Concluir"</em> e clique em <strong>Concluir</strong>.</li>
        <li>Na tela de Propriedades da tarefa:
            <ul>
                <li>Na aba <strong>Geral</strong>, marque a opção: <strong>Executar com privilégios mais altos (Run with highest privileges)</strong>.</li>
                <li>Selecione a opção: <strong>Executar estando o usuário conectado ou não</strong> (garante que a tarefa rode mesmo que o computador esteja na tela de bloqueio).</li>
            </ul>
        </li>
        <li>Clique em <strong>OK</strong> para salvar.</li>
    </ol>

    <div class="alert alert-success">
        <span class="alert-title">Automação 100% Concluída!</span>
        A partir de agora, rigorosamente às 09:00 de cada dia, o Windows executará a rotina. O Amigo Bicho verificará todas as vacinas que vencem em 7 dias, as que vencem hoje e as atrasadas, montará as mensagens personalizadas com o nome de cada pet e tutor e as enviará via Meta Cloud API.
    </div>
    </div>

    <div class="page-break"></div>

    <!-- ============================================================== -->
    <!-- SEÇÃO 6: PARTE 5 - DIAGNÓSTICO E ERROS -->
    <!-- ============================================================== -->
    <h1>6. Parte 5: Diagnóstico, Resolução de Erros e Boas Práticas</h1>

    <h2>Tabela de Erros Comuns e Como Resolver Imediatamente</h2>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Código de Retorno</th>
                <th style="width: 35%;">Causa Raiz Identificada</th>
                <th style="width: 40%;">Ação Corretiva Imediata</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Cloud API error: 401</strong><br>(Unauthorized)</td>
                <td>O Access Token fornecido está incorreto ou era um token temporário que expirou após 24 horas.</td>
                <td>Siga o <strong>Passo 1.7</strong> deste manual e gere um <strong>Token Permanente de Usuário do Sistema</strong> na Meta. Atualize o campo em Configurações &gt; Notificações.</td>
            </tr>
            <tr>
                <td><strong>Cloud API error: 400</strong><br>(Bad Request)</td>
                <td>O número do tutor destinatário está em formato incorreto ou o Phone Number ID informado é inválido.</td>
                <td>Verifique o telefone no cadastro do tutor (deve ter DDD). Confira se o Phone Number ID cadastrado no sistema confere exatamente com o ID da Meta.</td>
            </tr>
            <tr>
                <td><strong>Cloud API error: 403</strong><br>(Forbidden)</td>
                <td>O Usuário do Sistema da Meta não recebeu o ativo do aplicativo ou faltam as permissões obrigatórias.</td>
                <td>No Gerenciador de Negócios da Meta, acerte as permissões do usuário do sistema marcando <code>whatsapp_business_messaging</code> e dê Controle Total sobre o App.</td>
            </tr>
            <tr>
                <td><strong>Lembrete Ignorado</strong><br>(No Opt-in)</td>
                <td>O tutor não possui consentimento de WhatsApp registrado no cadastro (LGPD).</td>
                <td>Acesse a aba <strong>Tutores</strong> na Central de Vacinas e clique no botão verde para marcar o WhatsApp como <strong>Autorizado</strong>.</td>
            </tr>
            <tr>
                <td><strong>Telefone não recebe</strong><br>(Número de Teste)</td>
                <td>A conta da Meta ainda está usando o número de teste e o destinatário não foi autorizado na lista da Meta.</td>
                <td>Conclua o <strong>Passo 1.5</strong> vinculando o número oficial da sua clínica à Meta.</td>
            </tr>
        </tbody>
    </table>

    <h2>Onde Consultar os Logs de Envio no Sistema</h2>
    <ul>
        <li>
            <strong>Painel Web</strong>: Acesse <strong>Configurações do Sistema &gt; Logs de Notificação</strong> (URL: <code>/notification-logs</code>). Todas as mensagens enviadas ficam registradas com data, hora, tutor, animal, canal, status (Enviado ou Falha) e a resposta exata da API da Meta.
        </li>
        <li>
            <strong>Arquivo de Log do Windows</strong>: Abra o arquivo <code>C:\Amigo Bicho\storage\logs\rotina_vacinas.log</code> para visualizar a saída completa da execução diária agendada pelo Windows.
        </li>
    </ul>

    <h2>Boas Práticas para Evitar Bloqueios e Manter Alta Reputação</h2>
    <ol>
        <li><strong>Respeite pedidos de cancelamento</strong>: Se algum tutor solicitar interrupção de lembretes, abra o cadastro dele e desmarque o campo <em>"WhatsApp Autorizado"</em>.</li>
        <li><strong>Mantenha os cadastros limpos</strong>: Ao cadastrar novos clientes, sempre confira se o celular possui o DDD correto da região.</li>
        <li><strong>Tom cordial e humanizado</strong>: Os modelos padrão do Amigo Bicho foram redigidos com foco na saúde e bem-estar do pet, garantindo uma recepção afetuosa pelos tutores.</li>
    </ol>

    <div class="page-break"></div>

    <!-- ============================================================== -->
    <!-- ANEXO: CHECKLIST FINAL -->
    <!-- ============================================================== -->
    <h1>7. Anexo: Checklist de Homologação Final</h1>

    <p>
        Utilize a tabela de verificação abaixo antes de considerar a implantação 100% finalizada:
    </p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%; text-align: center;">Status</th>
                <th style="width: 45%;">Item de Verificação</th>
                <th style="width: 45%;">Resultado Esperado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center; font-size: 14pt;">[ &nbsp; ]</td>
                <td>Aplicativo Empresarial criado na Meta</td>
                <td>App do tipo "Empresa" associado ao Gerenciador de Negócios oficial da clínica.</td>
            </tr>
            <tr>
                <td style="text-align: center; font-size: 14pt;">[ &nbsp; ]</td>
                <td>Número da clínica vinculado e validado</td>
                <td>Número verificado por SMS ou ligação e desvinculado do app do smartphone.</td>
            </tr>
            <tr>
                <td style="text-align: center; font-size: 14pt;">[ &nbsp; ]</td>
                <td>Phone Number ID copiado</td>
                <td>Identificador numérico do telefone obtido na tela de API Setup da Meta.</td>
            </tr>
            <tr>
                <td style="text-align: center; font-size: 14pt;">[ &nbsp; ]</td>
                <td>Usuário do Sistema criado com Token Permanente</td>
                <td>Token gerado com expiração "Nunca" e permissão <code>whatsapp_business_messaging</code>.</td>
            </tr>
            <tr>
                <td style="text-align: center; font-size: 14pt;">[ &nbsp; ]</td>
                <td>Credenciais salvas no Amigo Bicho</td>
                <td>Provedor "WhatsApp Cloud API (Meta)" selecionado e salvo em Notificações.</td>
            </tr>
            <tr>
                <td style="text-align: center; font-size: 14pt;">[ &nbsp; ]</td>
                <td>Teste manual de envio bem-sucedido</td>
                <td>Mensagem teste recebida no WhatsApp e log "Enviado" registrado no sistema.</td>
            </tr>
            <tr>
                <td style="text-align: center; font-size: 14pt;">[ &nbsp; ]</td>
                <td>Envio Automático ativado na Central de Vacinas</td>
                <td>Opção ativada na aba Configurações e Modelos com horário definido para 09:00.</td>
            </tr>
            <tr>
                <td style="text-align: center; font-size: 14pt;">[ &nbsp; ]</td>
                <td>Agendador de Tarefas do Windows configurado</td>
                <td>Tarefa diária criada para executar <code>executar_rotina_vacinas.bat</code> às 09:00.</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 25px; padding: 14px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; text-align: center;">
        <h3 style="margin-top: 0; color: #0f5132;">Suporte e Manutenção Amigo Bicho</h3>
        <p style="margin-bottom: 0; font-size: 8.5pt; color: #495057;">
            Em caso de dúvidas na configuração ou alterações de números corporativos da clínica,
            consulte a equipe de suporte técnico ou a documentação interna do sistema Amigo Bicho.
        </p>
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

// Adicionar Cabeçalho e Rodapé profissionais com contagem exata de páginas via Canvas
$canvas = $dompdf->getCanvas();
$fontMetrics = $dompdf->getFontMetrics();
$fontNormal = $fontMetrics->getFont('Helvetica', 'normal');
$fontBold = $fontMetrics->getFont('Helvetica', 'bold');
$totalPages = $canvas->get_page_count();

for ($p = 2; $p <= $totalPages; $p++) {
    $canvas->reopen_object($canvas->open_object());
    // Cabeçalho
    $canvas->text(45, 25, "AMIGO BICHO — GESTÃO VETERINÁRIA", $fontBold, 7.5, [0.09, 0.53, 0.33]);
    $canvas->text(330, 25, "MANUAL DE INTEGRAÇÃO WHATSAPP META (API)", $fontNormal, 7.5, [0.4, 0.4, 0.4]);
    $canvas->line(45, 35, 550, 35, [0.85, 0.85, 0.85], 0.5);

    // Rodapé
    $canvas->line(45, 805, 550, 805, [0.85, 0.85, 0.85], 0.5);
    $canvas->text(45, 815, "Clínica Veterinária Amigo Bicho • Documentação Oficial de Operação", $fontNormal, 7.5, [0.45, 0.45, 0.45]);
    $pageStr = "Página {$p} de {$totalPages}";
    $canvas->text(495, 815, $pageStr, $fontBold, 7.5, [0.2, 0.2, 0.2]);
    $canvas->close_object();
}

$pdfOutput = $dompdf->output();

$target1 = __DIR__ . '/public/docs/Manual_Integracao_WhatsApp_Meta_AmigoBicho.pdf';
$target2 = __DIR__ . '/Manual_Integracao_WhatsApp_Meta_AmigoBicho.pdf';

file_put_contents($target1, $pdfOutput);
file_put_contents($target2, $pdfOutput);

echo "PDF gerado com sucesso nos dois destinos!\n";
echo "Total de páginas: " . $totalPages . "\n";
echo "1. $target1 (" . number_format(filesize($target1) / 1024, 1) . " KB)\n";
echo "2. $target2 (" . number_format(filesize($target2) / 1024, 1) . " KB)\n";
