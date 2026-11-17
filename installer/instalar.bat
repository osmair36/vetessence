@echo off
chcp 65001 >nul
title Amigo Bicho - Instalador

:: ============================================================
:: Instalador do Sistema Amigo Bicho
:: Configura o ambiente na primeira execução
:: ============================================================

set "BASEDIR=%~dp0"

echo.
echo  ╔══════════════════════════════════════════════════════╗
echo  ║   Amigo Bicho - Instalador                           ║
echo  ║   Sistema para Clínica Veterinária e Pet Shop        ║
echo  ╚══════════════════════════════════════════════════════╝
echo.

:: Verifica privilégios de administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [AVISO] Execute como Administrador para melhores resultados.
    echo.
)

set "PHPDIR=%BASEDIR%runtime\php"
set "PHP=%PHPDIR%\php.exe"

echo [1/5] Verificando PHP...
if not exist "%PHP%" (
    echo [ERRO] PHP não encontrado. Baixando...
    echo Por favor, execute o instalador .exe completo.
    pause
    exit /b 1
)
"%PHP%" --version
echo [OK] PHP encontrado.
echo.

echo [2/5] Instalando dependências...
if not exist "%BASEDIR%vendor\autoload.php" (
    "%PHP%" "%BASEDIR%composer.phar" install --no-dev --optimize-autoloader --working-dir="%BASEDIR%"
) else (
    echo [OK] Dependências já instaladas.
)
echo.

echo [3/5] Configurando ambiente...
if not exist "%BASEDIR%.env" (
    copy "%BASEDIR%.env.example" "%BASEDIR%.env" >nul
    "%PHP%" "%BASEDIR%artisan" key:generate --force
    echo [OK] Arquivo .env criado.
) else (
    echo [OK] Configuração já existe.
)
echo.

echo [4/5] Criando banco de dados...
if not exist "%BASEDIR%database\database.sqlite" (
    type nul > "%BASEDIR%database\database.sqlite"
)
"%PHP%" "%BASEDIR%artisan" migrate --force
"%PHP%" "%BASEDIR%artisan" db:seed --force
echo [OK] Banco de dados criado.
echo.

echo [5/5] Otimizando...
"%PHP%" "%BASEDIR%artisan" config:cache >nul 2>&1
"%PHP%" "%BASEDIR%artisan" route:cache >nul 2>&1
echo [OK] Sistema otimizado.
echo.

echo  ╔══════════════════════════════════════════════════════╗
echo  ║   INSTALAÇÃO CONCLUÍDA!                              ║
echo  ║                                                      ║
echo  ║   Para iniciar o sistema, clique em:                 ║
echo  ║   >> iniciar.bat                                     ║
echo  ║                                                      ║
echo  ║   Acesso inicial:                                    ║
echo  ║   URL:   http://127.0.0.1:8080                       ║
echo  ║   Email: super@vet.com                               ║
echo  ║   Senha: password                                    ║
echo  ╚══════════════════════════════════════════════════════╝
echo.
pause
