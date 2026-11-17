# Como Gerar o Instalador .exe — Amigo Bicho

## Pré-requisitos (apenas para gerar o .exe)

1. **Inno Setup 6** — baixe em: https://jrsoftware.org/isinfo.php
2. **PHP 8.4 Thread Safe** (Windows x64 ZIP) — baixe em: https://windows.php.net/download/

---

## Passo a passo para gerar o instalador

### 1. Preparar o PHP portátil

```
1. Baixe o PHP 8.4.x "Thread Safe" (zip) de: https://windows.php.net/download/
2. Extraia o zip para a pasta:
   C:\Amigo Bicho\installer\runtime\php\
3. Dentro dessa pasta deve existir: php.exe, php.ini-production, etc.
4. Renomeie "php.ini-production" para "php.ini"
5. Abra o php.ini e habilite as extensões (remova o ";" de cada linha):
   extension=curl
   extension=fileinfo
   extension=gd
   extension=mbstring
   extension=openssl
   extension=pdo_sqlite
   extension=sqlite3
   extension=zip
6. Adicione ao php.ini:
   extension_dir = "ext"
```

### 2. Compilar o instalador

```
1. Abra o Inno Setup Compiler
2. Vá em: File > Open
3. Selecione: C:\Amigo Bicho\installer\setup.iss
4. Clique em: Build > Compile (ou pressione F9)
5. O instalador será gerado em: C:\Amigo Bicho\installer\dist\AmigoBicho-Setup-v1.4.0.exe
```

### 3. Testar o instalador

Execute `AmigoBicho-Setup-v1.4.0.exe` em qualquer computador Windows 10/11.

---

## O que o instalador faz

1. Copia todos os arquivos do sistema para `C:\AmigoBicho` (padrão, o usuário pode mudar)
2. Inclui o PHP portátil (sem precisar instalar nada)
3. Cria atalho no Desktop e Menu Iniciar
4. Executa a configuração automática (banco de dados SQLite, chave de segurança)
5. Popula o banco com dados iniciais (usuários, permissões, cidades do Brasil)

---

## Como usar após a instalação

1. Clique duas vezes no atalho **"Amigo Bicho"** no Desktop
2. Uma janela de terminal irá abrir e o sistema iniciará automaticamente
3. O navegador abrirá em: **http://127.0.0.1:8080**
4. Faça login com:
   - **Email:** `super@vet.com`
   - **Senha:** `password`

> ⚠️ **Não feche a janela do terminal** enquanto estiver usando o sistema. Feche-a apenas quando quiser encerrar.

---

## Módulos disponíveis

| Módulo | Acesso |
|--------|--------|
| Prontuários | /medical-records |
| Agendamentos | /appointments |
| Grooming Board | /grooming-board |
| PDV (Ponto de Venda) | /pdv |
| Táxi Pet | /pet-transports |
| Farmácia / Estoque | /products |
| Financeiro | /invoices |
| Tutores e Pets | /tutors |
| Usuários | /users |
| Configurações | /settings |

---

## Usuários de demonstração

| Perfil | Email | Senha |
|--------|-------|-------|
| Super Admin | super@vet.com | password |
| Veterinário | vet@vet.com | password |
| Recepção | recep@vet.com | password |
| Financeiro | financeiro@vet.com | password |
| Portal Tutor | tutor@vet.com | password |
