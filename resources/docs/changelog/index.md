# Changelog

## [Não versionado] — 2026-11-23

## [v1.13.0] — 2026-11-23
## [v1.12.2] — 2026-11-21
## [v1.12.1] — 2026-11-21

### Documentação e Automação
- **Manual Completo em PDF para Integração WhatsApp Cloud API (Meta)**:
  - Criado o manual executivo e técnico em PDF (`Manual_Integracao_WhatsApp_Meta_AmigoBicho.pdf` e `public/docs/Manual_Integracao_WhatsApp_Meta_AmigoBicho.pdf`) com 12 páginas, estruturação visual profissional e cabeçalhos/rodapés padronizados.
  - Passo a passo completo cobrindo: Meta for Developers, Meta Business Suite, criação de App empresarial, vinculação do número oficial da clínica, localização do Phone Number ID e geração do Token Permanente de Usuário do Sistema (sem expiração).
  - Guia de configuração no painel do Amigo Bicho (`/configuracoes/notificacoes`), parametrização da Central de Vacinas (`/vaccination-center`), modelos com placeholders e regras de consentimento LGPD.
  - Criado o script autônomo `executar_rotina_vacinas.bat` e instruções detalhadas para agendamento no Agendador de Tarefas do Windows (Task Scheduler) para disparo automático diário às 09:00.
  - Tabela de diagnóstico de erros (HTTP 401, 400, 403), consulta de logs e checklist final de homologação.
## [v1.12.0] — 2026-11-21

### Desempenho
- **Otimização global de performance do sistema**:
  - **SQLite WAL + PRAGMAs**: banco agora opera em modo WAL (`journal_mode=WAL`, `synchronous=NORMAL`, `cache_size=-64000`, `mmap_size=268435456`, `temp_store=MEMORY`). Elimina travamentos de leitura/escrita simultânea e reduz drasticamente flushes de disco.
  - **OPcache PHP ativado**: `zend_extension=opcache` habilitado em `installer/runtime/php/php.ini` com 128MB de cache, 10.000 arquivos acelerados e `validate_timestamps=1`. Elimina re-parse de mais de 2.000 arquivos PHP por requisição.
  - **Servidor multi-worker**: `iniciar.bat` agora define `PHP_CLI_SERVER_WORKERS=8`, permitindo 8 requisições concorrentes sem bloqueio (assets, AJAX, páginas simultâneas).
  - **Índices de performance nas tabelas principais** (migration `2026_11_21_090000`): adicionados 22 índices estratégicos em `medical_records` (pet_id, vet_id, date, pet+date), `pets` (name, species, is_active), `tutors` (name, cpf, phone), `pet_tutor` (pair, tutor_id), `appointments` (date+time, pet_id, vet_id, status), `invoices` (tutor_id, status, paid_at, status+due_date) e `products` (name, is_active, category_id, is_active+stock). Consultas que antes faziam full table scan agora usam índice B-tree.
  - **Preconnect/DNS-prefetch para CDNs externos**: adicionados `<link rel="preconnect">` e `<link rel="dns-prefetch">` para todos os domínios externos (Google Fonts, FontAwesome, JSDelivr, DataTables, jQuery) em `resources/views/layouts/adminlte.blade.php`, reduzindo latência de DNS na primeira renderização.
  - **DataTables com `deferRender: true`**: tabelas com muitas linhas agora renderizam DOM sob demanda em vez de inserir todos os `<tr>` de uma vez, reduzindo o tempo de resposta visual perceptível ao usuário.
  - **PRAGMAs SQLite no `AppServiceProvider.php`**: listener no evento `ConnectionEstablished` garante PRAGMAs de alta performance aplicados automaticamente em toda nova conexão (web, fila, artisan).
  - **Benchmarks verificados**: `MedicalRecord::count()` (2.466 registros) em 61ms; listagem com eager-load em 13ms; `Pet` com filtro em 1,5ms; `Appointment` do dia em 4,5ms; `Invoice` em 0,3ms.

## [v1.11.8] — 2026-11-21

### Removido
- **Remoção dos botões Taxa de Ausência e Lembretes Pendentes no Dashboard (`/dashboard`)**:
  - Removidos os cards estatísticos **Taxa de Ausência** e **Lembretes Pendentes** da grade superior de indicadores em `resources/views/dashboard.blade.php`.
  - A grade superior agora exibe 8 indicadores perfeitamente balanceados em 2 linhas de 4 colunas.
  - Otimizado `app/Http/Controllers/DashboardController.php`, eliminando o método `computeNoShowRate()` e a consulta `VaccinationReminder::where('status', 'pending')`, economizando 3 consultas ao banco de dados no carregamento do Dashboard.
## [v1.11.7] — 2026-11-21

### Corrigido
- **Correção Global de Abertura com Clique Único nos Campos de Autocomplete (`public/js/global-autocomplete.js`)**:
  - Corrigida a condição de corrida no TomSelect onde o clique único abria o dropdown no `focus`/`mousedown` e o evento subsequente de `click` interpretava o controle como já focado e aberto, executando um toggle que fechava a janela imediatamente.
  - Implementado interceptador no evento `click` na fase de captura (`useCapture = true`), bloqueando a propagação imediata do fechamento quando o dropdown acaba de ser aberto pela mesma ação de clique do usuário.
  - Eliminado o listener assíncrono em `mousedown` que competia com o ciclo de eventos do navegador.
  - O clique único agora abre e mantém a lista de opções aberta e acessível em todos os 110+ campos `<x-tom-select>` da aplicação (Agendamentos: Veterinários e Pets; Consultas; Prescrições; Vacinas; etc.), sem exigir que o usuário mantenha o botão do mouse pressionado.
## [v1.11.6] — 2026-11-20

### Removido
- **Remoção dos quadros informativos finais do Dashboard (`/dashboard`)**:
  - Removidos os quadros finais **Próximas Consultas**, **Últimos Atendimentos**, **Lembretes Atrasados** e **Parasitários Atrasados** em `resources/views/dashboard.blade.php`.
  - O painel **"Vacinações Previstas para Hoje"** (Central Automatizada de Vacinas WhatsApp) passa a ser o último elemento informativo do Dashboard.
  - Eliminadas do `app/Http/Controllers/DashboardController.php` as consultas desnecessárias ao banco de dados que alimentavam essas listagens (`$upcomingAppointments`, `$recentRecords`, `$lowStockProducts`, `$overdueReminders` e `$overdueParasiteList`), otimizando ainda mais o carregamento do Dashboard.
## [v1.11.5] — 2026-11-20

### Corrigido
- **Correção Global de Paginação e Setas Desfiguradas**:
  - Implementado conjunto completo de views de paginação Bootstrap 4 / AdminLTE nativas em `resources/views/vendor/pagination/` (`default.blade.php`, `bootstrap-4.blade.php`, `bootstrap-5.blade.php`, `tailwind.blade.php` e versões simples).
  - Eliminado o problema das setas SVG gigantes (Tailwind chevrons sem dimensões explícitas) que preenchiam toda a tela.
  - Traduzido o texto descritivo para Português do Brasil ("Mostrando de X até Y de Z registros") e adicionadas chaves de tradução em `lang/pt_BR.json`, `resources/lang/pt_BR.json` e arquivos de paginação.
  - Criado espaçamento e alinhamento responsivo entre os botões de navegação ("Anterior", "Próximo", números de página) e o texto descritivo (`justify-content-between`, margens verticais `mb-2 mb-md-0`), evitando que fiquem colados.
  - Registrado `Paginator::useBootstrapFour()` e `Paginator::defaultView('vendor.pagination.default')` em `AppServiceProvider.php`.
  - Adicionadas regras de CSS global em `resources/views/layouts/adminlte.blade.php` com trava de dimensão máxima para qualquer SVG de navegação (`max-width: 1.15rem; max-height: 1.15rem;`).
  - Atualizado `installer/setup.iss` para a versão `1.11.5` e gerado novo instalador Windows `AmigoBicho-Instalador-v1.11.5.exe`.
## [v1.11.4] — 2026-11-20

### Removido
- **Remoção dos gráficos do Dashboard (`/dashboard`)**:
  - Removidos os 3 cards visuais de gráficos: **Receita Mensal**, **Atendimentos por tipo** e **Distribuição por Espécie** em `resources/views/dashboard.blade.php`.
  - Removido o carregamento do bundle externo `chart.umd.min.js` e as rotinas de renderização Chart.js, reduzindo o tempo de carregamento da página.
  - Otimizado `app/Http/Controllers/DashboardController.php`, eliminando 3 consultas agregadas ao banco de dados (`revenueByMonth`, `appointmentsByType` e `speciesDistribution`) que eram executadas exclusivamente para esses gráficos.
  - Conforme solicitado, geração do novo instalador mantida pendente para o próximo lote de alterações.
## [v1.11.3] — 2026-11-20

### Melhorado
- **Novo ícone da aplicação com o logotipo Amigo Bicho**:
  - Criado `public/amigo-bicho.ico` multi-resolução (256, 128, 64, 48, 32, 24 e 16 px) gerado por `installer/tools/build-icon.php` a partir de `installer/assets/logo-amigo-bicho.jpg` — logotipo completo nos tamanhos grandes (Área de Trabalho) e cabeças do gato e do cachorro nos tamanhos pequenos (barra de tarefas/listas).
  - Aplicado ao executável do instalador, ao desinstalador, aos atalhos da Área de Trabalho e do Menu Iniciar (`installer/setup.iss`) e aos atalhos de Terminal em rede (`Conectar ao Amigo Bicho (Terminal).bat`, `Criar Atalho no Desktop do Terminal.bat` e `installer/bootstrap.php`).
  - Novo instalador `AmigoBicho-Instalador-v1.11.3.exe`.
## [v1.11.2] — 2026-11-20

### Melhorado
- **Novo Instalador Windows `AmigoBicho-Instalador-v1.11.2.exe`**:
  - Atualizado `installer/setup.iss` para `1.11.2` e compilado instalador completo com a base `database/database.sqlite` compactada (`VACUUM`, `integrity_check=ok`) já contendo os **30.716 registros de vacinação espelhados com 100% de fidelidade ao SISVETW** (`v1.11.1`), a Central Automatizada de Vacinas (`v1.10.0`) e a Melhoria Global de Autocomplete (`v1.11.0`), seguindo integralmente as políticas blindadas da `v1.8.5`.
## [v1.11.1] — 2026-11-20

### Corrigido
- **Auditoria e Espelhamento Rigoroso das Datas de Vacinação (`Data Aplicação` e `Próxima Dose`) com o SISVETW (`DDFI_V01.DBF`)**:
  - Classificação rigorosa de todos os 61.452 registros ativos do `DDFI_V01.DBF` separando **aplicações reais** (30.641 registros) de **anotações/avisos não aplicados** (28.009 linhas como `AVISADO VIA WHATS / X`, `X / X`, `NÃO FEZ`, `TELEFONE NÃO ATENDE`, `RECOMENDO TUTORA`, `DEIXOU A VACINA PAGA`, etc.) que anteriormente eram importados erroneamente como vacinas aplicadas — eliminando datas de aplicação falsas (inclusive futuras em 2026–2028) e atrasos reais que estavam ocultos/empurrados 1 ano adiante.
  - Preservação de 11.824 anotações de contato do SISVETW no campo `last_contact_notes`/`last_contact_at` da vacina real correspondente (sem gerar vacinas falsas).
  - Regra de prioridade em 3 níveis baseada no bloco físico `recno + 1` do `DDFI_V01.DBF` para vincular `next_date`, corrigindo colisões entre códigos distintos (ex.: Ficha `5034` V10 Adulto `26/09/2026` → `26/09/2027`) e notas antigas remanescentes (ex.: Ficha `4629`).
  - Nova migration (`2026_11_20_110000_make_date_nullable_on_vaccinations_table`) permitindo importar fielmente os 75 agendamentos puros do SISVETW sem dose aplicada anterior (`status = 'scheduled'`, `date = NULL`, `next_date = <data exata do SISVETW>`), recuperando 13+ agendamentos futuros de filhotes e exames (ex.: Fichas `5079`, `5080`, `5081`, `4104`, `5074`, `5078`, `5087`, `5001`).
  - Atualizados `VaccinationController` (`index`, `certificate`, `forecast`), `VaccinationNotificationCenterService` (conclusão automática de agendamentos com `date = NULL` + famílias vacinais `111`, `019/50`, `30/033`) e badge `Agendada` em `vaccinations/index.blade.php`.
  - Verificação integral pós-reimportação: **0 divergências** entre `date`/`next_date` e `DDFI_V01.DBF` nos 30.716 registros finais e **100% dos demais módulos preservados intactos**.
## [v1.11.0] — 2026-11-19

### Melhorado
- **Melhoria Global nos Campos de Pesquisa / Autocomplete (`public/js/global-autocomplete.js`)**:
  - Centralizada a inicialização global dos **110+ campos** `<x-tom-select>` (em 59 telas) e campos `.select2` em `public/js/global-autocomplete.js`, removendo `openOnFocus: false` de `layouts/adminlte.blade.php`.
  - Todos os campos de pesquisa com autocomplete agora **abrem imediatamente a lista de opções ao clicar ou receber foco**, mantendo a filtragem por digitação, fechamento automático após seleção sem reabertura em loop, `z-index` compatível com modais Bootstrap e limite seguro de 80 opções renderizadas no DOM (evitando lentidão em listas com milhares de pets/tutores).
  - Adicionado suporte nativo centralizado para carregamento via AJAX (`data-ajax-url`) com `preload: 'focus'` e cache em memória (TTL 60s), compatibilidade com DOM dinâmico (`MutationObserver` + Livewire `morph.updated` + modais Bootstrap) e fallback seguro para campos `.select2` (ex.: `invoices/create.blade.php`).
- **Novo Instalador Windows `AmigoBicho-Instalador-v1.11.0.exe`**:
  - Compilado com todas as melhorias acumuladas (`v1.9.0` Vacinações/Laboratório + `v1.10.0` Central Automatizada de Vacinas WhatsApp + `v1.11.0` Autocomplete Global), seguindo integralmente as políticas blindadas de CRLF/ASCII, `bootstrap.php`, `APP_KEY` automática e permissões de pasta.
## [v1.10.0] — 2026-11-19

### Adicionado
- **Central Automatizada de Notificações e Lembretes de Vacinação via WhatsApp**:
  - **Painel de Vacinações no Dashboard**: 7 indicadores clicáveis (Vencem Hoje, Em Atraso, Próximos 7 dias, Próximos 30 dias, WhatsApp Enviadas, Aguardando Envio e Falhas de Envio) + tabela detalhada de vacinações previstas para hoje com ações rápidas (histórico, cadastro do animal, cadastro do tutor, pré-visualização e envio manual de WhatsApp, registrar contato e registrar vacinação aplicada).
  - **Central Administrativa (`/central-vacinas`)**: abas dedicadas para Acompanhamento por Classificação, Histórico e Fila de Mensagens (com 7 estados: Preparada, Aguardando envio, Enviada à API, Entregue, Lida, Com falha e Cancelada + botão Reprocessar), Gestão de Tutores (sem telefone válido, sem autorização LGPD, recusados e autorizados) e Configurações Gerais + 3 Modelos de Mensagens Editáveis (protegidos por `can:configuracoes`).
  - **Rotina Automática Diária (`php artisan vaccines:process-reminders`)**: executada no horário configurável (padrão `09:00`, `America/Sao_Paulo`), com trava anti-concorrência (`Cache::lock` / `withoutOverlapping()`), chave única `idempotency_key` no banco contra duplicidades, periodicidade configurável para atrasos e limite máximo de tentativas; inclui script `installer/instalar-agendador-windows.bat` para Windows Task Scheduler.
  - **Reutilização da Integração WhatsApp + Webhooks**: reutiliza integralmente `NotificationService` (Z-API, Cloud API, Weni, Twilio), extrai `messageId`/`zaapId` na Z-API e adiciona endpoint `POST /api/whatsapp/webhook/{provider}` para confirmação assíncrona de entrega (`delivered`) e leitura (`read`).
  - **Controle de Consentimento LGPD (Opt-in/Opt-out)**: campos `whatsapp_opt_in`, `whatsapp_opt_in_at`, `whatsapp_opt_out_at` e `whatsapp_opt_in_source` em `tutors` + controle no `TutorForm` e na Central; mensagens automáticas só são enviadas mediante autorização expressa.
  - **Conclusão e Cancelamento Automáticos**: ao registrar nova vacinação aplicada ou alterar `next_date`, o sistema conclui a pendência anterior e cancela automaticamente lembretes futuros relacionados.
## [v1.9.0] — 2026-11-19

### Adicionado
- **Campo Laboratório no Módulo de Vacinação**:
  - Nova migration (`2026_11_19_100000_add_laboratory_and_sisvetw_tracking_to_vaccinations`) adicionando a coluna `laboratory` (sincronizada com `manufacturer`), `status`, e campos de rastreabilidade (`sisvetw_recno`, `sisvetw_ficha`, `sisvetw_cod_vac`) na tabela `vaccinations`, além da tabela de mapeamento permanente `sisvetw_pet_maps`.
  - Campo **Laboratório** (com autocomplete de laboratórios veterinários + texto livre) e **Lote / Partida** e **Dose** disponibilizados no cadastro, edição, visualização, listagem geral (com busca por laboratório/lote), previsão de vacinas, ficha clínica do animal, portal do tutor, carteira de vacinação, certificado PDF e relatório impresso.
- **Reimportação Controlada e Exclusiva de Vacinações do SISVETW (`php artisan sisvetw:reimport-vaccinations`)**:
  - Serviço `SisvetwVaccinationReimportService` com suporte a `--dry-run`, `--rollback=<ndjson>`, preservação automática de lançamentos manuais feitos na Vetessense e mapeamento seguro 1-para-1 de animais (`sisvetw_pet_maps`).

### Corrigido
- **Datas Originais de Aplicação e Próxima Dose das Vacinas (SISVETW → Vetessense)**:
  - Eliminado o cálculo artificial `+1 ano` que distorcia todos os 61.429 registros importados anteriormente; agora `date` e `next_date` refletem exatamente as datas reais registradas em `DDFI_V01.DBF` (49.692 vacinas com próxima dose real vinculada e 8.958 sem próxima dose definida como `NULL`), consolidando 2.402 agendamentos futuros sem criar registros falsos de aplicação no futuro.
  - Corrigida exibição do nome da vacina na Carteira de Vacinação (`card-print.blade.php`) e da próxima dose no Certificado PDF (`certificate-pdf.blade.php`).
## [v1.8.5] — 2026-11-18

### Corrigido
- **Reescrita Completa e Blindagem dos Scripts `.bat` e do Instalador Desktop (`v1.8.5`)**:
  - **Quebras de linha Windows (CRLF) + ASCII puro**: convertidos `iniciar.bat`, `installer/instalar.bat`, `Conectar ao Amigo Bicho (Terminal).bat` e `Criar Atalho no Desktop do Terminal.bat` de LF (Unix) para CRLF estrito (`\r\n`) e adicionada regra `*.bat text eol=crlf` no `.gitattributes` (eliminando erros de salto `goto` e corrupção de blocos no `cmd.exe`).
  - **Eliminação de lógica frágil em lote**: movida toda a validação de ambiente, geração automática de `APP_KEY` vazia no `.env`, inspeção rápida do SQLite via PDO, detecção inteligente de IP da rede local (ignorando adaptadores virtuais WSL/Hyper-V) e geração de `server_info.txt`/atalho `.url` para o script dedicado `installer/bootstrap.php`.
  - **Servidor HTTP direto e abertura sincronizada do navegador**: `iniciar.bat` agora detecta se a porta 8181 já está ativa, inicia `php -S 0.0.0.0:8181 -t public server.php` diretamente com 512M de memória e 256M de upload, e aguarda o servidor responder na porta antes de abrir o navegador.
  - **Caminho absoluto para SQLite**: ajustado `config/database.php` para resolver `database/database.sqlite` sempre via `database_path('database.sqlite')`, independente do diretório de trabalho.
  - **Permissões e limpeza no instalador Inno Setup (`installer/setup.iss`)**: exclusão de arquivos locais de desenvolvimento (`.env`, caches em `bootstrap/cache/*.php`, `server_info.txt`, backups antigos) e concessão automática de permissão de escrita (`users-modify` + `icacls`) nas pastas de dados (`database`, `storage`, `bootstrap/cache`).
  - **PHP portátil otimizado**: `installer/runtime/php/php.ini` atualizado com `upload_max_filesize = 256M`, `post_max_size = 256M`, `max_execution_time = 120`, `date.timezone = "America/Sao_Paulo"` e `extension=intl`.
  - **Scripts de Terminal em rede corrigidos**: corrigida extração de nome de servidor em caminhos UNC (`tokens=1 delims=\`) e detecção automática de Área de Trabalho (compatível com redirecionamento OneDrive).
## [v1.8.4] — 2026-11-18

### Corrigido
- **Correção da Rotina de Inicialização do Banco no Instalador e Servidor**:
  - Eliminação da falha de sintaxe de lote (`'else' não é reconhecido como um comando interno ou externo`) nos scripts `iniciar.bat` e `installer/instalar.bat` através de rotulagem direta com `goto :db_ready`.
  - Normalização da detecção de caminho absoluto (`cd /d "%~dp0.."`) em `installer/instalar.bat`, evitando falsos negativos com caminhos relativos contendo `..\` que causavam a recriação indevida da base de dados pré-existente.
  - Otimização do seeder `GeoSeeder` com guard de contagem (`City::count() >= 5000`) e transação `DB::transaction()`, eliminando o atraso de mais de 10 minutos (614 segundos) na inserção de cidades brasileiras.
## [v1.8.3] — 2026-11-18

### Corrigido
- **Correção da Conexão Padrão de Banco de Dados para SQLite no Instalador Desktop**:
  - Ajuste de `config/database.php` definindo `default => env('DB_CONNECTION', 'sqlite')` (evitando fallback incorreto para MySQL em máquinas sem servidor MySQL instalado).
  - Atualização do modelo `.env.example` com `DB_CONNECTION=sqlite` e `DB_DATABASE=database/database.sqlite`.
  - Inclusão de rotina de auto-recuperação nos scripts `iniciar.bat` e `installer/instalar.bat` para garantir que o arquivo `.env` utilize SQLite automaticamente.
  - Empacotamento explícito do `.env` com SQLite pré-configurado no instalador Inno Setup (`installer/setup.iss`).
- **Aumento do Limite de Memória PHP para 512M (Correção do Erro Fatal na Tela de Pets)**:
  - Adição de `@ini_set('memory_limit', '512M')` em `bootstrap/app.php`, `public/index.php` e `server.php` para garantir execução estável mesmo com grande volume de dados ou Livewire.
- **Exclusão Segura de Pets com Tutores Vinculados**:
  - Ajuste no evento de exclusão do modelo `Pet` e no método `PetController::destroy` para desvincular os tutores automaticamente (`detach()`) dentro de transação, eliminando o erro `RuntimeException`.
- **Formulário de Funcionários (Unidade, Departamento, Cargo e Tipo de Contrato)**:
  - Otimização do componente Livewire `EmployeeForm` para renderizar dinamicamente as opções de Unidade, Departamento, Cargo e Tipo de Contrato sem perda de estado em re-renderizações.
  - Criação do seeder `DepartmentPositionSeeder` e inclusão automática de `BreedDefaultSeeder` no `DatabaseSeeder` e rotinas de inicialização.
## [v1.8.2] — 2026-11-18

### Adicionado
- **Ficha Clínica Veterinária Completa do Animal (Castrado, Bravo, Patologias e Cirurgias)**:
  - Adição dos campos `is_neutered` (Castrado: Sim / Não), `is_aggressive` (Animal Bravo / Alerta de Manejo), `pathologies` (Patologias / Condições Pré-existentes) e `surgical_history` (Cirurgias Prévias) na tabela `pets` e modelo `Pet`.
  - Exibição de banner de alerta vermelho destacado na ficha clínica do paciente para animais bravos (`ANIMAL BRAVO / CUIDADO NO MANEJO CLÍNICO`), visível também nas listagens e na impressão oficial da ficha.
  - Alertas de patologias pré-existentes (cardiopata, diabético, atópico, etc.) e histórico cirúrgico em destaque visual no perfil do paciente.
  - Barra de ações rápidas clínicas na ficha do animal (`pets/show.blade.php`), com acesso direto em 1 clique para Novo Atendimento, Vacina, Vermífugo/Antiparasitário (Simparic), Receita, Pedido de Laboratório, Exame de Imagem, Emitir Termo e Atestado de Viagem.
  - Card integrado de **Vermífugos e Antiparasitários** no perfil do animal com indicação visual de repetições/reforços vencidos ou em dia.
- **Data de Nascimento do Tutor**:
  - Inclusão do campo `birth_date` na tabela `tutors` e no formulário de cadastro/edição do tutor (`TutorForm`), com exibição no perfil do cliente.
- **7 Modelos Oficiais de Termos de Consentimento Veterinário**:
  - Criação do seeder `ConsentTemplateSeeder` com 7 modelos pré-cadastrados conforme padrões ético-jurídicos do CFMV:
    1. *Termo de Consentimento para Internamento Hospitalar*
    2. *Termo de Consentimento para Eutanásia e Destinação de Cadáver*
    3. *Termo de Consentimento e Risco Cirúrgico / Anestésico*
    4. *Termo de Ciência e Cuidados para Paciente Atópico / Dermatite Alérgica*
    5. *Termo de Esclarecimento e Consentimento de Vacinação*
    6. *Termo de Ciência e Autorização para Tosa de Raças com Subpelo (Spitz Alemão, Chow Chow, Husky, etc.)*
    7. *Termo de Declaração Sanitária e Viagem / Trânsito Animal*
  - Preenchimento dinâmico e inteligente: ao selecionar o animal ou o modelo de termo, o sistema preenche automaticamente os dados do tutor, documento e o texto completo do termo para assinatura e impressão imediata.
## [v1.8.1] — 2026-11-18

### Adicionado
- **Instalador Autônomo para Windows 10 e 11 e Suporte a Rede Local (Terminais Clientes)**:
  - Geração de instalador executável completo (`AmigoBicho-Instalador-*.exe`) via Inno Setup 6 compilando a aplicação, o banco SQLite com dados reais importados, todas as dependências e o motor PHP 8.4 ZTS integrado.
  - Script de inicialização do Servidor (`iniciar.bat`) com vinculação a todas as interfaces de rede (`--host=0.0.0.0 --port=8181`), memória ampliada para 512M (`-d memory_limit=512M`), liberação automática da porta no Firewall do Windows e abertura automática do navegador.
  - Arquitetura de rede local (LAN) com pasta compartilhada: qualquer terminal cliente acessa a pasta `\\SERVIDOR\Amigo Bicho` e conecta-se à aplicação instantaneamente através do script `Conectar ao Amigo Bicho (Terminal).bat` ou do atalho `Amigo Bicho - Terminal.url`, sem necessidade de instalar PHP ou servidores adicionais no terminal.
  - Script utilitário `Criar Atalho no Desktop do Terminal.bat` para instalação com 1 clique do atalho na Área de Trabalho dos computadores clientes com ícone oficial.
  - Preservação da base de dados SQLite (`database/database.sqlite`) com flag `onlyifdoesntexist` no instalador, garantindo que futuras atualizações nunca sobrescrevam os dados clínicos e cadastrais já existentes.
## [v1.8.0] — 2026-11-18

### Adicionado
- **Módulo Completo de Backup e Restauração da Base de Dados**:
  - Desenvolvimento do serviço `App\Services\Backup\BackupService` para execução, agendamento, compactação Gzip, validação de integridade e restauração completa de bases de dados (SQLite e MySQL).
  - Rotina de backup manual sob demanda com suporte a compactação rápida e download direto.
  - Rotina automática periódica e configurável pelo usuário: seleção de frequência (a cada 6h, a cada 12h, diária, semanal ou mensal), horário de disparo e política de retenção por dias.
  - Suporte a múltiplos locais de destino: além do armazenamento local do sistema (`storage/app/backups`), permite configurar pastas externas ou de rede (HD externo, pendrive, etc.) com teste de gravação em tempo real.
  - Rotina de restauração segura (via lista de backups ou upload de arquivo `.sql`, `.sqlite`, `.gz`) com geração automática de snapshot prévio de segurança e confirmação explícita de segurança (`RESTAURAR`).
  - Criação do comando CLI `php artisan backup:restore {file} {--no-snapshot}` para restaurações avançadas via terminal.
  - Inclusão do botão de acesso **"Backup do Sistema"** no menu lateral posicionado logo abaixo da opção **"Configurações"**.
## [v1.7.5] — 2026-11-18

### Alterado
- **Novo Logo Oficial Amigo Bicho no Topo do Menu Lateral (Sidebar)**:
  - Substituição do texto estático "Amigo Bicho / Clínica Veterinária" no topo do menu lateral pelo novo logotipo oficial ilustrado (gato, cachorro e tipografia oficial).
  - Dimensionamento responsivo no layout AdminLTE (`max-height: 110px; max-width: 175px`) com cantos arredondados (`border-radius: 12px`), sombra sutil e destaque sobre o fundo escuro do menu lateral.
  - Suporte completo ao modo recolhido (`sidebar-collapse`), redimensionando o logotipo automaticamente para 42x42px centralizado.
  - Atualização dos arquivos de imagem padrão e de fallback em `public/img/logo-sidebar.png`, `public/img/logo-amigobicho.png`, `public/img/logo-default.png` e `public/img/logositebackft.png`.
## [v1.7.4] — 2026-11-18

### Corrigido
- **Campos de Funcionário no Modal (Unidade, Departamento, Cargo e Tipo de Contrato)**:
  - Substituição de componentes `<x-tom-select>` com `wire:ignore` por selects HTML nativos do Bootstrap 4 integrados ao Livewire v3 (`wire:model` e `wire:model.live`), eliminando o problema de campos em branco no modal.
  - Implementação de filtragem dinâmica reativa de cargos por departamento (`updatedDepartmentId`), preservando o cargo selecionado na edição de funcionários existentes.
  - Remoção de regra de senha obrigatória no contexto de funcionários (`employee`), alinhando o backend com a interface onde o funcionário recebe convite por e-mail para definir sua própria senha.
  - Remoção de scripts desnecessários e incompatíveis de recarga de TomSelect (`refreshTomSelects`) nas listagens e detalhes de funcionários e usuários.
- **Otimização de Memória e Busca de Tutores no Modal de Pets**:
  - Eliminação da renderização de 2.824 elementos `<option>` no DOM do modal de Pets, substituindo por busca interativa leve (`tutorSearch`) com autocompletar e limite de resultados, reduzindo drasticamente o processamento do Livewire v3.
  - Configuração de `ini_set('memory_limit', '512M')` no `public/index.php`, prevenindo erros de memória exaurida (`Allowed memory size exhausted`) ao lidar com bases volumosas.
  - Remoção de carregamentos desnecessários de todos os tutores em `PetController::create()` e `PetController::edit()`.
- **Desvinculação Segura e Exclusão de Pets Sem Registros Clínicos**:
  - Ajuste do hook `deleting` no modelo `Pet` para desvincular automaticamente os registros de `pet_tutor` ao excluir o animal, impedindo o disparo de `RuntimeException` não tratada.
  - Proteção mantida e validada amigavelmente contra exclusão de animais que possuam prontuários, agendamentos ou histórico de vacinação.
## [v1.7.3] — 2026-11-18

### Corrigido
- **Otimização Extrema do Livewire PetForm e Suporte Universal a Raças**:
  - Remoção da propriedade pública pesada `$tutors` que serializava 2.824 modelos no estado do Livewire (reduzindo o payload de rede de 1,6 MB para <5 KB e eliminando travamentos de DOM no modal).
  - Implementação do hook nativo do Livewire v3 `updatedSpecies($value)` para recarga imediata e sem conflitos de rede ao trocar de espécie.
  - Expansão do catálogo `breed_defaults` para 202 raças abrangendo todas as espécies (caninos, felinos, equinos, bovinos, aves, lagomorfos, roedores e répteis).
  - Adicionada opção "Outra / Não listada (digitar manualmente)..." com campo de texto automático para qualquer espécie ou raça não catalogada.
  - Limpeza forçada de views e caches compilados do Blade.
## [v1.7.2] — 2026-11-18

### Corrigido
- **Catálogo de Raças e Seleção no Formulário do Pet**:
  - Povoamento e sincronização da tabela `breed_defaults` com 140 raças catalogadas (seeder padrão + raças da base legada).
  - Atualização do componente Livewire `PetForm` para exibir todas as raças disponíveis e filtrar dinamicamente por espécie via `wire:model.live`.
  - Vínculo automático de `breed_default_id` em todos os 4.271 animais cadastrados.
- **Tratamento Seguro na Exclusão de Pets**:
  - Correção de erro `RuntimeException` ao tentar excluir um pet com vínculo de tutor em `PetController::destroy`.
  - Desvinculação transacional segura do tutor antes de remover pets de teste sem prontuários ou agendamentos, com validações amigáveis contra remoção de animais com histórico clínico ativo.
## [v1.7.1] — 2026-11-18

### Corrigido
- **Otimização de Memória e Paginação em Listagens com Base Real Volumosa**:
  - Correção de estouro de memória (`Allowed memory size exhausted`) ao acessar `/pets` e demais telas de listagem após a carga dos 4.271 animais e 2.824 tutores reais.
  - Implementação de paginação de 20 registros por página em `PetController::index`, `TutorController::index`, `ProductController::index`, `VaccinationController::index`, `MedicalRecordController::index` e `AppointmentController::index`.
  - Otimização de consultas adicionando `withCount('pets')` no `TutorController` para eliminar queries N+1 na contagem de animais por tutor.
  - Adicionados filtros avançados de busca por texto, espécie, categoria e intervalo de datas com links de paginação persistentes (`withQueryString()`) nas views Blade correspondentes.
  - Configurado limite de memória de segurança de 512M no `AppServiceProvider::boot()` para navegação web e operações em lote.
  - Consumo de memória de pico na renderização de `/pets` reduzido de >128 MB para apenas 49 MB.
## [v1.7.0] — 2026-11-18

### Adicionado
- **Importador de Base Legada FoxPro (SISVETW -> Amigo Bicho)**:
  - Criação do comando Artisan `php artisan sisvetw:import {--path=C:\SISVETW} {--fresh} {--dry-run}` para migração completa e segura da base legada em Visual FoxPro (.DBF e .FPT).
  - Desenvolvimento do serviço `App\Services\SisvetwImportService` com leitor binário nativo puro PHP para arquivos DBF e blocos de memo FPT com transcodificação CP1252/Windows-1252 para UTF-8.
  - Implementação de processamento em streaming via PHP Generators (`yield`) e gravação em lotes (batch inserts de 1.000 registros), garantindo consumo de memória constante O(1).
  - Rotina de higienização prévia (`--fresh`) de tabelas transacionais e operacionais para descarte seguro de dados fictícios de demonstração antes da carga real.
  - Carga real completa com integridade referencial validada:
    - **Fornecedores (`DDFORN01.DBF`)**: 3 fornecedores importados.
    - **Produtos e Estoque (`DDPROD01.DBF`)**: 591 produtos/medicamentos com códigos de barras, unidades, preços de custo/venda e níveis de estoque.
    - **Tutores / Clientes (`DDPROP01.DBF`)**: 2.824 tutores com telefones sanitizados e vínculos familiares.
    - **Animais / Pacientes (`DDFICH01.DBF`)**: 4.271 animais mapeados com raças, espécies (canino, felino, etc.), pelagem, datas de nascimento e vínculos `pet_tutor`.
    - **Históricos Clínicos e Prontuários (`ddfich01.FPT`)**: 2.466 registros médicos históricos preservados e consolidados em `medical_records`.
    - **Histórico de Vacinações (`DDFI_V01.DBF`)**: 61.429 aplicações de vacinas com lotes, laboratórios e datas de revacinação.
    - **Histórico de Pesagens (`DDPESO01.DBF`)**: 9.017 pesagens cronológicas importadas em `weight_records`, atualizando o peso mais recente no cadastro de cada paciente.
    - **Agendamentos e Atendimento (`DDAGEN02.DBF` + `.FPT`)**: 5.000 agendamentos históricos importados e vinculados à unidade principal.
  - Adicionada proteção de paginação e limitação segura nas rotas de relatório geral de vacinações (`VaccinationController::reportPrint` e `reportPdf`) contra sobrecarga de memória em bases volumosas.
## [v1.6.0] — 2026-11-17

### Adicionado
- **Finalização de 100% dos Módulos do Sistema de Impressão e PDF (Projeto Amigo Bicho)**:
  - **Módulo Hospedagem (Hotel Pet)**: Ficha de hospedagem individual (`boardings/{id}/print` e `pdf`) contendo dados do animal, tutor, período, diária, instruções de alimentação/medicação, tarefas diárias e assinaturas.
  - **Módulo Odontologia / Gráfico Dental**: Ficha de odontograma e avaliação clínica bucal (`dental-charts/{id}/print` e `pdf`) com índice de tártaro, gengivite, halitose, condições dentárias registradas por dente/quadrante e recomendações.
  - **Módulo Monitoramento Anestésico**: Boletim anestésico cirúrgico (`anesthesia-monitorings/{id}/print` e `pdf`, formato paisagem) com protocolo, MPA, indução, manutenção, taxa de fluidos e curva cronológica de sinais vitais (FC, FR, SpO2, PA Sist/Diast, Temp, ETCO2 e plano).
  - **Módulo Comissões**: Comprovante individual de comissão por atendimento (`commissions/{id}/print` e `pdf`) e Relatório Geral de Comissões por período/profissional (`commissions/report/print` e `pdf`, formato paisagem) com totalizações de vendas e comissões.
  - **Módulo Escala da Equipe**: Espelho de escala de trabalho e plantões (`staff-schedules/print` e `pdf`, formato paisagem) com turnos, horários, unidades e resumo por profissional.
  - **Módulo Solicitações de Folga e Afastamentos**: Relatório gerencial de folgas e afastamentos da equipe (`staff-time-off/print` e `pdf`) com status de aprovação e períodos.
  - **Módulo Telemedicina / Teleconsultas**: Ficha e registro de teleconsulta (`teleconsultations/{id}/print` e `pdf`) com plataforma utilizada, horários de início/fim, duração e observações clínicas.
  - **Módulo Protocolos de Vacinação**: Manual e calendário de protocolos vacinais (`vaccine-protocols/print` e `pdf`) com divisão por espécies (canina, felina, etc.), indicação de vacinas core/não-core, doses iniciais e intervalos de reforço.
  - **Relacionamento Universal Pet-Tutor**: Adicionados métodos `tutor()` e accessor `getTutorAttribute()` no modelo `Pet` para resiliência e integridade em todas as telas e impressões.
  - **Expansão da Suíte de Testes Automatizados**: `tests/Feature/Controllers/PrintPdfComprehensiveTest.php` expandido para 25 testes completos e 101 asserções, com 100% de sucesso.

## [v1.5.0] — 2026-11-17

### Adicionado
- **Implementação Completa e Padronizada de Impressão e Geração de PDF (Projeto Amigo Bicho)**:
  - Criação do layout central de impressão A4 `resources/views/layouts/print.blade.php` com suporte a orientação Retrato/Paisagem, barra de ferramentas de controle (`window.print()`, download de PDF e fechar) oculta na impressão (`@media print`), e inclusão automática do cabeçalho oficial padronizado `<x-report-header />`.
  - Serviço centralizado `App\Services\DocumentPdfService` integrando Dompdf com sanitização de nomes de arquivos, suporte a stream e download em conformidade com o padrão visual Amigo Bicho.
  - **Módulo Pets (Animais)**: Ficha cadastral e clínica completa individual imprimível (`pets/{pet}/print`) e para download em PDF (`pets/{pet}/pdf`), com histórico de vacinas, vermífugos, pesagens e prontuários.
  - **Módulo Tutores**: Ficha cadastral completa do tutor (`tutors/{tutor}/print` e `pdf`) com relação de animais vinculados, dados de contato e termo de declaração.
  - **Módulo Prontuários (Atendimentos Clínicos)**: Ficha individual de consulta com anamnese, sinais vitais, diagnóstico e conduta (`medical-records/{id}/print` e `pdf`) e Histórico Clínico Cronológico Geral do animal (`pets/{pet}/medical-records/print` e `pdf`).
  - **Módulo Vacinas**: Carteira de vacinação do animal (`pets/{pet}/vaccination-card/print` e `pdf`), Relatório Geral de Imunizações (`vaccinations/report/print` e `pdf`) e padronização do certificado de vacinação existente.
  - **Módulo Vermífugos e Antiparasitários**: Histórico individual de aplicações do animal (`pets/{pet}/parasite-controls/print` e `pdf`) e Relatório Geral de Controle de Parasitas (`parasite-controls/report/print` e `pdf`).
  - **Módulo Laboratório**: Laudo laboratorial detalhado com tabela de resultados, faixas de referência e destaque de alterações (`laboratory-orders/{id}/print` e `pdf`).
  - **Módulo Diagnóstico por Imagem**: Laudo de raio-x e ultrassonografia (`imaging-exams/{id}/print` e `pdf`) com descrição de achados, impressão diagnóstica e recomendações.
  - **Módulo Cirurgias**: Ficha e boletim cirúrgico (`surgeries/{surgery}/print` e `pdf`) com avaliação pré-operatória, descrição técnica, cuidados pós-operatórios e assinaturas.
  - **Módulo Internação**: Ficha completa de internação (`hospitalizations/{hospitalization}/print` e `pdf`) com dados de admissão, evolução diária, fluidoterapia, prescrições e resumo de alta.
  - **Módulo Termos de Consentimento**: Termo de Consentimento Livre e Esclarecido padronizado (`consent-forms/{consentForm}/print` e `pdf`).
  - **Módulo Certificados Sanitários**: Padronização visual do cabeçalho do Certificado Sanitário e do Certificado Veterinário Internacional (CVI) com `<x-report-header />` e rota de impressão em navegador.
  - **Módulo Banho & Tosa (Pet Shop)**: Ordem de Serviço individual (`grooming/{appointment}/order/print` e `pdf`) com checklist de entrada/saída, comportamento, restrições e termos; e Programação Diária do Banho & Tosa (`grooming-board/print` e `pdf`).
  - **Módulo Pacotes e Assinaturas (Pet Shop)**: Contrato e Ficha de Controle de Utilização de Pacote (`pet-shop-subscriptions/{sub}/print` e `pdf`) com saldo de créditos e histórico de consumos.
  - **Módulo Transporte Pet (Táxi Dog)**: Ordem de transporte (`pet-transports/{transport}/print` e `pdf`) com rota (coleta e destino), horários, pertences e confirmação de entrega.
  - **Módulo Estoque & Inventário**: Listagem de Inventário Físico para contagem e posição de estoque (`products/inventory/print` e `pdf`), Extrato de Movimentações (`stock/movements/print` e `pdf`) e Relatório de Lotes com Vencimento Próximo (`stock/expiring/print` e `pdf`).
  - **Módulo Pedidos de Compra**: Pedido de compra e espelho para fornecedor (`purchase-orders/{po}/print` e `pdf`) com guia de conferência física de recebimento.
  - **Módulo Faturas & Recibos (Financeiro)**: Fatura e Recibo de Quitação Financeira (`invoices/{invoice}/print` e `pdf`) com discriminação de serviços/produtos e declaração de quitação.
  - **Módulo Relatórios Financeiros**: Extrato de faturamento e exportação em PDF estruturado (`reports/export/print` e `reports/export/pdf`).
  - **Módulo Medicamentos Controlados (Portaria 344)**: Livro de Registro e Mapa Mensal de Substâncias Controladas (`controlled-substances/reports/monthly/print` e `pdf`) com assinatura da Resp. Técnica Dra. Joelma Scarabelli Neves.
  - Testes automatizados de integração completos em `tests/Feature/Controllers/PrintPdfComprehensiveTest.php` cobrindo 18 modalidades com 74 asserções bem-sucedidas.
## [v1.4.11] — 2026-11-17

### Alterado
- **Identidade e Rebranding Completo para Amigo Bicho**:
  - Atualização de todas as ocorrências, textos de exibição, rodapés, cabeçalhos e fallbacks de branding de `VetEssence` para `Amigo Bicho` em todas as views Blade (`adminlte`, `app`, `guest`, `mobile`, `sidebar`, `portal`, `branding`, `prescriptions`, `health-certificates`, `invoices`, `vaccinations`).
  - Atualização dos modelos e comandos de e-mail, SMS, lembretes de vacina e rotinas de agendamento para assinar como `Amigo Bicho` e `Equipe Amigo Bicho`.
  - Atualização dos descritores de fatura em gateways de pagamento (Mercado Pago e Payer) para `AMIGO BICHO`.
  - Atualização das chaves e descrições PIX para `AMIGO BICHO CLINICA VETERINARIA` e `admin@amigobicho.com`.
  - Atualização dos manuais de usuário, manuais técnicos, tutores e documentação oficial (`resources/docs`, `README.md`, `install.sh`).
  - Limpeza total de referências obsoletas em testes unitários e de integração.
## [v1.3.0] — 2026-11-17

### Adicionado
- **Projeto Amigo Bicho (Fase 2)**:
  - Identidade visual da Clínica Veterinária e Pet Shop Amigo Bicho com logotipo oficial e paleta de cores institucional (verde esmeralda, verde escuro corporativo, laranja acolhedor).
  - Parametrização cadastral da filial matriz com localização em Assis Chateaubriand - PR (Rua Rio de Janeiro, 178 - Jardim América, CEP 85935-000, telefones (44) 3528-2481 / (44) 99833-0660).
  - Cadastro da Dra. Joelma Scarabelli Neves como Médica Veterinária e Responsável Técnica (CRMV-PR 08147).
  - Seeder de configurações institucionais `AmigoBichoSettingsSeeder` integrado à execução do `DatabaseSeeder`.
  - Testes de feature `AmigoBichoBrandingTest` para validação de branding, seeders e escopo geográfico.

## [v1.2.1] — 2026-10-08
## [v1.2.0] — 2026-10-08

### Adicionado
- Controle de medicamentos fracionáveis por unidade (`ml`, `mg`, `g`, comprimido, dose e unidade).
- Lotes e recipientes por filial, consumo parcial, devolução, FEFO e validade pós-abertura.
- Reservas para atendimento/internação, notificações para Farmácia e registros de administração.
- Preço/custo separado para embalagem inteira e unidade fracionada.
- Idempotência no ledger de movimentos e compatibilidade gradual com `products.stock`.
## [v1.1.5] — 2026-10-08
## [v1.1.4] — 2026-08-13
## [v1.1.3] — 2026-08-13
## [v1.1.2] — 2026-08-13
## [v1.1.1] — 2026-08-13
## [v1.1.0] — 2026-08-13
## [v1.0.3] — 2026-08-10

### Alterado
- **Gateways de pagamento na documentação**: README, manual técnico e manual do usuário passam a citar apenas os gateways disponíveis para uso — **PIX**, **Mercado Pago** e **MultiplusCard (PinPDV)**. Removidas as referências a Payer (suspenso), PagSeguro, Stripe e Stone (não disponíveis na UI).
## [v1.0.2] — 2026-08-10

### Alterado
- **Provedores fiscais desativados**: FocusNFe e Spedy desativados na emissão de notas (NFS-e, NFC-e e NF-e). Deixaram de ser selecionáveis na Configuração NF — a validação aceita apenas Webmania® e NFE.io. As classes `FocusNfeProvider`/`SpedyProvider` e os mapeamentos de resolução foram mantidos no código (configs existentes continuam funcionando). Referências removidas da documentação (manuais usuário e técnico).
- **Rodapé corrigido**: lado esquerdo passa a exibir o **nome da clínica** vindo do branding (`© 2026 {clínica}`) em vez do texto fixo "Clínica Veterinária"; lado direito exibe **Amigo Bicho** com a versão atual (`config('app.version')`, arquivo `VERSION`) em vez do "v1.0" fixo e do nome de clínica do branding.
## [Não versionado] — 2026-08-03

### Adicionado
- **Emissão automática de notas opcional**: checkbox `Emissão automática ao confirmar pagamento` nas configurações NFS-e e NF-e/NFC-e (`emit_auto`). Se desativado, a nota deixa de ser emitida automaticamente no pagamento e passa a ser emitida apenas manualmente pelo botão "Emitir Nota Fiscal" na fatura paga. Comportamento padrão mantido (auto-emissão ativa).
- **Autoversionamento**: comando `php artisan version:bump [major|minor|patch|X.Y.Z]` com fonte única em `VERSION`; atualiza `package.json`, README badge, changelog, tag git e GitHub Release; exibe versão atual na página de Auto-Update

### Corrigido
- Listener `EmitirNfseOnPaid`/`EmitirNfeOnPaid` respeitam a flag `emit_auto` antes de emitir

## [v1.0.1] — 2026-08-03

### Adicionado
- **PIX: regra de conflito revisada**: PIX não conflita mais com outros provedores (apenas com outro PIX). Gateways não-PIX ignoram PIX na verificação de conflito
- **PIX: exibição por unidade**: QR Code PIX aparece apenas se houver gateway PIX ativo. Precedência: unidade específica → fallback global
- **PIX no Portal**: QR Code só exibido se gateway PIX ativo para canais portal/both
- **MultiplusCard (PinPDV)**: provider completo com webhook, polling, TipoPagamento, suporte a PDV e Ambos
- **Mercado Pago PDV avançado**: terminal Point Smart 2, sandbox auto-simulate, 409 Conflict retry, cancelPendingOrdersOnTerminal, listagem de devices, auto-PDV mode
- **Mercado Pago verificação reforçada**: não confia em querystring — consulta API para validar status real
- **Payer API Gateway**: implementação completa (auth OAuth JWT, validate→execute, query/cancel, webhook). Suspenso posteriormente (parceria encerrada, código mantido)
- **Documentação**: diagramas `32-fluxo-pagamento` e `15-fluxo-fatura` atualizados; regras de PIX e conflito documentadas; Payer documentado como suspenso

### Corrigido
- PIX: `findConflictingActiveGateway` agora permite coexistência com MP/MultiplusCard
- `PaymentService::getActiveGatewayForChannel`: prioriza gateways não-PIX (`ORDER BY provider = 'pix' ASC`)
- Testes: `PaymentGatewayControllerTest` e `PaymentGatewayTest` atualizados para nova regra de conflito
- Duplicação de código: `filterConfig()` extraído para método reutilizável
## [Não versionado] — 2026-07-21

### Adicionado
- **Mercado Pago ativo no Portal**: SDK `mercadopago/dx-php` instalado; `charge()` para PDV retorna erro; `supportedChannels()` → `['portal']`; checkout usa `sandbox_init_point` quando sandbox
- **Pagamento verificado por API**: retorno do checkout não confia em `collection_status=approved` da querystring — chama `PaymentService::processWebhook()` para consultar API MP e validar status real
- **MailerSend provider**: `app/Services/Notification/Email/MailerSendProvider.php` implementando `EmailProvider` com SDK send + attachments
- **Z-API corrigido**: URL `https://api.z-api.io/instances/{instance}/token/{token}/send-text`; telefone sanitizado (digits only, DDI 55); `Authorization: Bearer` removido
- **NotificationLog**: `VaccinationReminderController::send()` registra envios tipo `vaccine_reminder` — consultável em Conf. Sistema → Logs de Notificação
- **StaffSchedule**: campo `is_vet_shift` (booleano) adicionado ao formulário create/edit; `branch_id` obrigatório com detecção de conflito branch-aware
- **VetAvailabilityService**: `getAvailableVets()` consulta `staff_schedules.branch_id` em vez de `users.branch_id`
- **PDV/maquininha removido**: `channel` validation restrito a `portal`; opções `pdv`/`both` removidas dos formulários; JS morto removido
- **Retrocompatibilidade `both`**: `scopeByChannel('portal')` busca `portal` e `both`; `isPortal()` true para `both`; `getActiveGatewayForChannel('portal')` busca ambos
- **Invoice::gateway()**: relationship BelongsTo adicionada para `gateway_id`
- **Portal logout**: redireciona para `route('login')` em vez de `route('portal.login')`
- **Appointment status**: labels em português no edit view
- **Documentação**: auditoria completa (127 discrepâncias encontradas); lote 1 corrigido (index, financeiro, tutor-manual); lote 2 corrigido (08-estoque, 10-agendamento, 13-usuarios, 17-notificacoes, 19-configuracoes, technical-manual/index, changelog)

### Corrigido
- Documentation: NFe/NFCe/NFSe nomenclatura corrigida; Webmania NFSe auth corrigida (Bearer token, não headers X-App); Tecnospeed removido; WhatsApp providers atualizados; Gateway providers atualizados (MP ativo, Stone inativo, canal portal-only)

## [Não versionado] — 2026-06-24

### Adicionado

#### Diferenciais Competitivos (Phase ZG)
- **Estoque Inteligente**:
  - Dashboard com 6 widgets (produtos, abaixo do ponto de reposição, valor em estoque, vencimentos, assinaturas ativas, economia)
  - Cálculo automático de consumo médio diário (últimos 90 dias)
  - Ponto de reposição: `(consumo_médio × lead_time) + estoque_segurança`
  - Sugestão de compra com quantidade recomendada
  - Alerta de vencimentos com filtro por período (15/30/60/90 dias)
  - Comandos agendados: `stock:forecast --recalculate` (03:00) e `stock:forecast --alert-expiry` (06:00)
  - `StockForecastService` com `suggestPurchaseOrder()`, `expiringProducts()`
  - Permissões: `stock.forecast`, `stock.reorder`
  - 3 views: dashboard, sugestão de reposição, vencimentos
  - 10 testes unitários (`StockForecastServiceTest`)

- **Pacotes Petshop**:
  - 3 models: `PetShopPackage`, `PetShopSubscription`, `PetShopConsumption`
  - 3 controllers: CRUD completo de pacotes e assinaturas
  - Pacotes com preço promocional, validade, serviços inclusos
  - Assinaturas com usos, economia calculada, renovação automática (`subscriptions:renew`)
  - Comando `subscriptions:renew` para renovar assinaturas expiradas
  - Permissões: `pet-shop-packages.*`, `pet-shop-subscriptions.*`
  - Testes: `PetShopPackageControllerTest` (8 testes)

- **Petlove (Insurance Provider)**:
  - `PetloveProvider` com `checkEligibility()`, `requestPreAuthorization()`, `submitClaim()`, `checkStatus()`
  - Registrado em `InsuranceProviderFactory` com chave `'petlove'`
  - Campos: `external_policy_id`, `eligibility_last_checked_at` em `convenio_pet`
  - Permissão: `insurance.petlove` na seção Financeiro
  - 11 testes unitários (`PetloveProviderTest`)

- **Sidebar**: links para dashboard de estoque, sugestão de reposição, vencimentos, pacotes, assinaturas
- **RoleController**: labels/grupos/seções para pet-shop-packages, pet-shop-subscriptions, insurance, communication-templates
- **PermissionSeeder**: 11 novas permissões ZG + atribuição a `estoque`
- **Migration dedicada**: `seed_zg_permissions` para deploys existentes
- Total: ~~40 testes ZG passando~~ (40/40)

### Corrigido
- `InsuranceProviderFactoryTest`: mensagem de exceção em português vs inglês
- `Products index view`: null-safe `expiration_date?->format('Y-m-d')`

## [Não versionado] — 2026-06-19

### Adicionado

#### Gestão de Permissões na Criação/Edição de Perfis
- Formulário de criação/edição de perfis agora exibe todas as 284 permissões Spatie agrupadas em cards
- Cada grupo possui **título em português** e checkbox **"Marcar todos"**
- Permissões sincronizadas automaticamente com Spatie Permission ao salvar
- Criação/edição/exclusão de perfil mantém sync entre `App\Models\Role` e `Spatie\Permission\Models\Role`
- Validação com feedback visual (`is-invalid`) para nome, slug e permissões

### Corrigido
- Descrição de perfil exibia tags HTML na listagem — agora usa `strip_tags()`

### Alterado
- `Role::permissions()` renomeado para `spatiePermissions()` e aponta para `\Spatie\Permission\Models\Permission`

#### Mapa de Execução de Procedimentos Veterinários
- 3 novas tabelas: `execution_maps`, `execution_tasks`, `execution_logs`
- 3 models: `ExecutionMap`, `ExecutionTask`, `ExecutionLog`
- `ExecutionBoard` — Livewire componente na aba "Execução" da internação, com geração de tarefas a partir de prescrições, execução inline e destaque para atrasadas
- `ExecutionMapIndex` — Livewire listagem com filtros por status, busca, ordenação priorizando internações ativas
- Parse de frequência textual (8/8h, BID, SID, etc.) para horários de administração
- Data migration que popula mapas para internações com prescrições existentes
- **Novo perfil Técnico** (`tecnico`) com permissões `execution-maps.view`, `execution-maps.execute`, `hospitalizations.view`, `tutors.view`, `pets.view`, `staff-notes.*`
- 3 gates: `execution-maps`, `execution-maps.execute`, `execution-maps.manage`
- Menu lateral "Mapa de Execução" na seção Clínico (gate `execution-maps`)
- Atualizações de documentação nos manuais do usuário, técnico e changelog

## [Não versionado] — 2026-06-08

### Adicionado

#### NF-e (Nota Fiscal Eletrônica de Produtos)
- Suporte completo a NF-e com 3 provedores (FocusNFe, NFE.io, Webmania)
- `NfeConfig` — configuração de provedor de NF-e (sistêmico, sem branch_id)
- `NfeInvoice` — modelo para notas fiscais de produto emitidas
- `NfeService` + `NfeResult` — orquestrador e DTO no padrão dos providers de NFSe
- 3 implementações de `NfeProvider`: FocusNFe, NFE.io, Webmania
- Controller de NF-e com listagem, detalhes, download XML/PDF/DANFE, emissão e cancelamento
- Campos fiscais em produtos: NCM, CFOP, CST, CSOSN, alíquotas ICMS/IPI/PIS/COFINS
- Campos fiscais em serviços: código de serviço LC 116, CNAE, alíquota ISS
- Campos fiscais em filiais: IE, IE ST, CRT
- `item_type` em `InvoiceItem`: `service`, `product`, `avulso` com validação no controller
- Roteamento inteligente ao pagar: itens de serviço → NFSe, itens de produto → NF-e + dedução de estoque
- Auto-edição de NF-e via listener `EmitirNfeOnPaid`
- Dedução automática de estoque via listener `DeductStockOnPaid`
- Comando `nfe:emit-pending` para reprocessar notas pendentes
- `GenerateInvoiceFromAppointment` agora cria itens de produto para vacinas vinculadas
- Permissões: `nfe.view`, `nfe.emit`, `nfe.cancel`, `nfe-config.edit`
- 4 views de NF-e (index, show, config, export)
- Integração NF-e nas views de fatura (index e show)

#### Vet Shift Scheduling
- Coluna `is_vet_shift` em `staff_schedules` (booleano, default false)
- `VetAvailabilityService` — serviço de disponibilidade em tempo real para o Portal do Tutor
- `StaffScheduleObserver` — cancela automaticamente consultas quando a escala do veterinário muda
- `VetAvailabilityController` no Portal (API): `availableVets`, `vetSlots`, `vetDates`
- Tela "Plantões de Veterinários" na sidebar (filtro apenas turnos com `is_vet_shift = true`)
- Demo seed: 30 dias de turnos de vet para 3 veterinários em 3 filiais

### Corrigido
- `StaffSchedule.php` — adicionado `is_vet_shift` ao `$casts` como booleano
- `VetAvailabilityService.php` — `Carbon::parse()` com Carbon instances (double time spec)
- `StaffScheduleFactory.php` — `dateTimeThisWeek` substituído por `dateTimeBetween`

## [Não versionado] — 2026-05-28

### Adicionado
- Cancelamento de faturas (botão na listagem e no detalhe, status `cancelled`)
- Mapeamento Tipo de Atendimento → Serviço (`service_type_maps`)
- Método `cancel()` no `InvoiceController`

### Corrigido
- `@push('scripts')` sem tag `<script>` em 29 views causando `is not defined`
- Save do `MedicalRecordForm` — `vet_id` alterado para `user_id`
- Dashboard: script Chart.js carregado como stylesheet; `@stack('scripts')` dentro de tag `<script>`; gráfico consultando `Appointment` em vez de `MedicalRecord`
- Preview de foto no PetForm: `onerror="this.remove()"` para evitar 404
- Listagem de pets: dimensão fixa via `style` em vez de classes Tailwind
- DataTables: `columns` explícito para evitar erro TN18 com `colspan`
- NFSe: `InvoicePaid::dispatch()` adicionado no `pay()`; `use App\Models\NfseConfig` adicionado ao controller
- Rota `invoices.pay` registrada em `web.php`

## Versão 1.0.0 — 2026-05

### Adicionado

#### Infraestrutura (Fases A–G)
- Schema completo com migrations para todos os módulos
- 11 papéis (roles) com 160+ permissões Spatie
- Middleware `SetBranchContext` + escopo global `BranchScope`
- Módulos RH: Departamentos, Cargos, Funcionários, Escalas de Plantão
- Papel `super-financial` com acesso financeiro global
- Gates e controllers auditados com Spatie permissions

#### Testes (Fases H–M)
- ~335 testes unitários de modelos (90 arquivos)
- ~313 testes de feature de controllers (53 controllers)
- 15 testes de comandos Artisan
- 34 testes de serviços (Pix, Email, BranchContext)
- 10 testes de traits (Auditable, BranchScoped, HasPhoto)
- 12 testes de integração (fluxos completos)
- Schema–model mismatches corrigidos (40+ colunas em 10 tabelas via 8 migrations)

#### Conformidade Regulatória (Fase N)
- ANVISA: substâncias controladas, receituário especial, relatórios mensais/anuais
- LGPD: consentimento, exportação, anonimização, política de retenção
- CFMV: prontuário digital, prescrição com verificação QR, telemedicina, CVI

#### Features Clínicas (Fases P–Q)
- Eutanásia/cremação (`pet_death_records`)
- Avaliação pré-anestésica (ASA score, checklist)
- Dietas prescritas (renais, hepáticas, etc.)
- Claims de convênio (envio automático via API Porto Seguro)
- CVI (Certificado Veterinário Internacional)
- Triagem ER (painel colorido Manchester, status flow)
- Lote/validade em produtos com alerta de vencimento
- Fluxo de aprovação de orçamento (Treatment Plan)
- Microchip / RG Animal
- Auto-faturamento pós-consulta
- Comissões de veterinários
- Prescrição eletrônica validável (QR + hash SHA-256)
- Conciliação bancária (importação OFX/QIF/CSV)

#### Workflow Diário (Fase S)
- Calendário visual FullCalendar 6 com drag-and-drop
- Dashboard com KPIs (receita, agendamentos, triagem, estoque)
- Chat interno em tempo real
- Certificado de vacina PDF (layout CFMV)
- Integração WhatsApp/SMS/E-mail com provedores(Z-API, Twilio, Mailgun, etc.)
- Modo mobile `/m` com navegação inferior
- Pedidos de compra (solicitação → aprovação → recebimento)

#### Cobertura 100% (Fase T)
- Timeline do paciente (eventos consolidados)
- Dedução automática de estoque
- Calculadora de dosagem
- Lembrete automático de consultas
- Portal do tutor completo (prontuários, exames, prescrições)
- Previsão de vacinas a vencer
- Scanner de código de barras/QR
- Tabela de preços por espécie/porte
- Transferência de estoque entre filiais
- Preferências de notificação do tutor
- Protocolos de emergência
- Dashboard corporativo multi-unidade

#### Manutenção & Governança (Fase U)
- Auto-update via GitHub (painel admin)
- Rebranding (logo, cores, nome da clínica)
- Documentação do sistema (/docs) com 25 manuais + manual técnico

#### Modal CRUD (Fase V)
- 29 Livewire form components em modais Bootstrap
- 27 index views convertidas para modal
- Delete com SweetAlert2 global

#### NFSe — Nota Fiscal Eletrônica (Fase W)
- 5 provedores: Webmania®, FocusNFe, Spedy, NFE.io
- Adapter Pattern com interface única
- Emissão manual e automática ao faturar
- Cancelamento (prazo 24h), exportação XML, PDF
- Dados fiscais por filial
- Permissões: `nfse.view`, `nfse.emit`, `nfse.cancel`, `nfse-config.edit`

### Corrigido
- `User.php:hasRole()` — tratamento de parâmetro Spatie Collection
- `StaffNote.php` — cast `branch_id` como `datetime` (corrigido)
- `Category.php` — FKs apontando para `branch_id` em vez de `id`
- `MedicalRecord.php` — `vet_id` (coluna é `user_id`)
- `StockMovement.php` — fillable com colunas inexistentes
- `ConvenioPet.php` — fillable com colunas inexistentes
- `CommunicationQueue.php` — nome da tabela incorreto
- `HospitalizationFluidTherapy.php` — nome da tabela incorreto
- 6 controllers sem passar variáveis necessárias para as views
- 9 rotas incorretas (ordem, validação, parâmetros faltantes)
