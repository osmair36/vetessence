@echo off
chcp 65001 >nul
title Amigo Bicho - Clínica Veterinária

:: ============================================================
:: Launcher do Sistema Amigo Bicho
:: Inicia o servidor PHP e abre o navegador automaticamente
:: ============================================================

set "BASEDIR=%~dp0"
set "PHPDIR=%BASEDIR%runtime\php"
set "PHP=%PHPDIR%\php.exe"
set "ARTISAN=%BASEDIR%artisan"
set "PORT=8080"
set "URL=http://127.0.0.1:%PORT%"

echo.
echo  ╔══════════════════════════════════════════╗
echo  ║   Amigo Bicho - Clínica Veterinária      ║
echo  ║   Iniciando o sistema...                  ║
echo  ╚══════════════════════════════════════════╝
echo.

:: Verifica se o PHP existe
if not exist "%PHP%" (
    echo [ERRO] PHP não encontrado em: %PHPDIR%
    echo Execute o instalador novamente.
    pause
    exit /b 1
)

:: Verifica se o .env existe
if not exist "%BASEDIR%.env" (
    echo [INFO] Criando arquivo de configuração...
    copy "%BASEDIR%.env.example" "%BASEDIR%.env" >nul
    "%PHP%" "%ARTISAN%" key:generate --force
)

:: Verifica se o banco de dados existe
if not exist "%BASEDIR%database\database.sqlite" (
    echo [INFO] Criando banco de dados...
    type nul > "%BASEDIR%database\database.sqlite"
    "%PHP%" "%ARTISAN%" migrate --force
    "%PHP%" "%ARTISAN%" db:seed --force
)

:: Limpa cache
echo [INFO] Otimizando sistema...
"%PHP%" "%ARTISAN%" config:clear >nul 2>&1
"%PHP%" "%ARTISAN%" cache:clear >nul 2>&1
"%PHP%" "%ARTISAN%" view:clear >nul 2>&1

echo.
echo [OK] Sistema pronto!
echo [OK] Acesse: %URL%
echo.
echo Para encerrar o sistema, feche esta janela.
echo.

:: Aguarda 2 segundos e abre o navegador
timeout /t 2 /nobreak >nul
start "" "%URL%"

:: Inicia o servidor PHP
"%PHP%" "%ARTISAN%" serve --host=127.0.0.1 --port=%PORT%

pause
