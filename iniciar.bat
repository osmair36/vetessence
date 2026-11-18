@echo off
chcp 65001 >nul 2>&1
title Amigo Bicho - Servidor (Clinica Veterinaria)

set "BASEDIR=%~dp0"
set "PORT=8181"
set "PHP="

echo.
echo  ========================================================================
echo    AMIGO BICHO - SISTEMA PARA CLINICA VETERINARIA E PET SHOP
echo    Iniciando Servidor Principal...
echo  ========================================================================
echo.

:: PRIORIDADE 1: runtime portavel integrado (pasta do instalador)
if exist "%BASEDIR%runtime\php\php.exe" (
    set "PHP=%BASEDIR%runtime\php\php.exe"
    goto :php_found
)

:: PRIORIDADE 2: PHP 8.4 instalado via WinGet
for /d %%d in ("%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.4*") do (
    if exist "%%d\php.exe" (
        set "PHP=%%d\php.exe"
        goto :php_found
    )
)

:: PRIORIDADE 3: PHP global no PATH do Windows
where php.exe >nul 2>&1
if %errorlevel%==0 (
    set "PHP=php.exe"
    goto :php_found
)

echo [ERRO] PHP 8.4 nao foi localizado no sistema.
echo Certifique-se de que a pasta runtime\php existe ou instale o PHP.
echo.
pause
exit /b 1

:php_found
echo [OK] Motor PHP localizado: %PHP%

:: Configura arquivo de ambiente .env se nao existir
if not exist "%BASEDIR%.env" (
    echo [INFO] Criando arquivo de configuracao inicial (.env)...
    if exist "%BASEDIR%.env.example" (
        copy "%BASEDIR%.env.example" "%BASEDIR%.env" >nul
    )
    "%PHP%" "%BASEDIR%artisan" key:generate --force
    echo [OK] Configuracao inicial criada.
)

:: Cria banco de dados SQLite inicial se nao existir
if not exist "%BASEDIR%database\database.sqlite" (
    echo [INFO] Inicializando banco de dados local...
    type nul > "%BASEDIR%database\database.sqlite"
    "%PHP%" "%BASEDIR%artisan" migrate --force
    "%PHP%" "%BASEDIR%artisan" db:seed --force
    echo [OK] Banco de dados inicializado.
)

:: Detecta endereco IP do Servidor na rede local
set "SERVER_IP="
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /i "IPv4" ^| findstr /v "127.0.0.1"') do (
    for /f "tokens=1" %%b in ("%%a") do (
        if "%SERVER_IP%"=="" set "SERVER_IP=%%b"
    )
)
if "%SERVER_IP%"=="" set "SERVER_IP=127.0.0.1"

:: Salva informacoes da rede para os terminais clientes
(
    echo SERVIDOR_NOME=%COMPUTERNAME%
    echo SERVIDOR_IP=%SERVER_IP%
    echo PORTA=%PORT%
    echo URL_LOCAL=http://localhost:%PORT%
    echo URL_REDE=http://%SERVER_IP%:%PORT%
    echo URL_REDE_HOSTNAME=http://%COMPUTERNAME%:%PORT%
    echo ULTIMA_INICIALIZACAO=%DATE% %TIME%
) > "%BASEDIR%server_info.txt"

:: Cria atalho de rede (.url) na raiz da pasta compartilhada
(
    echo [InternetShortcut]
    echo URL=http://%SERVER_IP%:%PORT%
    echo IconIndex=0
    echo IconFile=%BASEDIR%public\favicon.ico
) > "%BASEDIR%Amigo Bicho - Terminal.url"

:: Tenta liberar a porta 8181 no Firewall do Windows (se admin)
netsh advfirewall firewall show rule name="Amigo Bicho (Porta %PORT%)" >nul 2>&1
if %errorlevel% neq 0 (
    netsh advfirewall firewall add rule name="Amigo Bicho (Porta %PORT%)" dir=in action=allow protocol=TCP localport=%PORT% profile=any >nul 2>&1
)

:: Limpa caches de inicializacao
echo [INFO] Carregando sistema...
"%PHP%" "%BASEDIR%artisan" config:clear >nul 2>&1
"%PHP%" "%BASEDIR%artisan" cache:clear >nul 2>&1
"%PHP%" "%BASEDIR%artisan" view:clear >nul 2>&1

echo.
echo  ========================================================================
echo    SERVIDOR AMIGO BICHO EM EXECUÇÃO!
echo  ========================================================================
echo.
echo    [Acesso neste Computador (Servidor)]:
echo    >> http://localhost:%PORT%
echo.
echo    [Acesso nos Terminais em Rede (Outros Computadores)]:
echo    >> http://%SERVER_IP%:%PORT%
echo    >> http://%COMPUTERNAME%:%PORT%
echo.
echo  ========================================================================
echo    COMO CONECTAR OS TERMINAIS EM REDE:
echo    1. No Servidor: compartilhe esta pasta "Amigo Bicho" na rede local.
echo    2. No Terminal (outro PC): acesse a pasta compartilhada:
echo       \\%COMPUTERNAME%\Amigo Bicho
echo    3. Execute: "Conectar ao Amigo Bicho (Terminal).bat"
echo       ou abra o atalho: "Amigo Bicho - Terminal.url"
echo  ========================================================================
echo.
echo    AVISO IMPORTANTE:
echo    Mantenha esta janela aberta enquanto a clinica estiver funcionando.
echo    Para desligar o servidor do sistema, basta fechar esta janela.
echo.
echo  ========================================================================
echo.

:: Abre o navegador local automaticamente no servidor
start "" "http://localhost:%PORT%"

:: Inicia o servidor web vinculado a todas as interfaces de rede (0.0.0.0)
"%PHP%" -d memory_limit=512M "%BASEDIR%artisan" serve --host=0.0.0.0 --port=%PORT%

pause
