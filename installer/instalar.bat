@echo off
chcp 65001 >nul 2>&1
title Amigo Bicho - Instalador do Servidor

set "BASEDIR=%~dp0..\"
if "%BASEDIR:~-1%"=="\" set "BASEDIR=%BASEDIR:~0,-1%\"

echo.
echo  ========================================================================
echo    AMIGO BICHO - CONFIGURANDO SISTEMA PARA USO LOCAL E EM REDE
echo  ========================================================================
echo.

set "PHP=%BASEDIR%runtime\php\php.exe"

if not exist "%PHP%" (
    where php.exe >nul 2>&1
    if %errorlevel%==0 (
        set "PHP=php.exe"
    ) else (
        echo [ERRO] PHP portavel nao encontrado em %PHP%.
        pause
        exit /b 1
    )
)

echo [1/6] Motor PHP verificado com sucesso.

:: Configura arquivo .env
echo [2/6] Configurando arquivo de ambiente (.env)...
if not exist "%BASEDIR%.env" (
    if exist "%BASEDIR%.env.example" (
        copy "%BASEDIR%.env.example" "%BASEDIR%.env" >nul
    )
    "%PHP%" "%BASEDIR%artisan" key:generate --force
    echo [OK] Arquivo .env gerado com nova chave criptografica.
) else (
    echo [OK] Arquivo .env ja existente mantido com seguranca.
)

:: Garante diretorios de storage
echo [3/6] Criando diretorios de sistema...
if not exist "%BASEDIR%storage\app\backups" mkdir "%BASEDIR%storage\app\backups"
if not exist "%BASEDIR%storage\framework\cache" mkdir "%BASEDIR%storage\framework\cache"
if not exist "%BASEDIR%storage\framework\sessions" mkdir "%BASEDIR%storage\framework\sessions"
if not exist "%BASEDIR%storage\framework\views" mkdir "%BASEDIR%storage\framework\views"
if not exist "%BASEDIR%storage\logs" mkdir "%BASEDIR%storage\logs"
if not exist "%BASEDIR%bootstrap\cache" mkdir "%BASEDIR%bootstrap\cache"
if not exist "%BASEDIR%database" mkdir "%BASEDIR%database"

:: Garante banco de dados SQLite
echo [4/6] Verificando base de dados...
if not exist "%BASEDIR%database\database.sqlite" (
    type nul > "%BASEDIR%database\database.sqlite"
    "%PHP%" "%BASEDIR%artisan" migrate --force
    "%PHP%" "%BASEDIR%artisan" db:seed --force
    echo [OK] Banco de dados criado e populado.
) else (
    "%PHP%" "%BASEDIR%artisan" migrate --force >nul 2>&1
    echo [OK] Base de dados validada com sucesso.
)

:: Libera porta 8181 no Firewall do Windows para acesso em rede
echo [5/6] Configurando regra no Firewall do Windows (Porta 8181)...
netsh advfirewall firewall show rule name="Amigo Bicho (Porta 8181)" >nul 2>&1
if %errorlevel% neq 0 (
    netsh advfirewall firewall add rule name="Amigo Bicho (Porta 8181)" dir=in action=allow protocol=TCP localport=8181 profile=any >nul 2>&1
    echo [OK] Porta 8181 liberada no Firewall para conexao dos terminais em rede.
) else (
    echo [OK] Regra de Firewall ja ativa.
)

:: Limpa e prepara caches
echo [6/6] Otimizando componentes...
"%PHP%" "%BASEDIR%artisan" config:clear >nul 2>&1
"%PHP%" "%BASEDIR%artisan" cache:clear >nul 2>&1
"%PHP%" "%BASEDIR%artisan" view:clear >nul 2>&1
"%PHP%" "%BASEDIR%artisan" storage:link >nul 2>&1

:: Detecta IP do servidor
set "SERVER_IP="
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4" ^| findstr /v "127.0.0.1"') do (
    for /f "tokens=1" %%b in ("%%a") do (
        if "%SERVER_IP%"=="" set "SERVER_IP=%%b"
    )
)
if "%SERVER_IP%"=="" set "SERVER_IP=127.0.0.1"

(
    echo SERVIDOR_NOME=%COMPUTERNAME%
    echo SERVIDOR_IP=%SERVER_IP%
    echo PORTA=8181
    echo URL_LOCAL=http://localhost:8181
    echo URL_REDE=http://%SERVER_IP%:8181
    echo URL_REDE_HOSTNAME=http://%COMPUTERNAME%:8181
    echo DATA_INSTALACAO=%DATE% %TIME%
) > "%BASEDIR%server_info.txt"

(
    echo [InternetShortcut]
    echo URL=http://%SERVER_IP%:8181
    echo IconIndex=0
    echo IconFile=%BASEDIR%public\favicon.ico
) > "%BASEDIR%Amigo Bicho - Terminal.url"

echo.
echo  ========================================================================
echo    CONFIGURACAO CONCLUIDA COM SUCESSO!
echo  ========================================================================
echo.
timeout /t 2 >nul
exit /b 0
