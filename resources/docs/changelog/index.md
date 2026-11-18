# Changelog

## [Não versionado] — 2026-11-18

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
