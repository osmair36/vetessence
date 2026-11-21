@echo off
setlocal
title Amigo Bicho - Servidor

cd /d "%~dp0"
set "BASEDIR=%CD%\"
set "PORT=8181"
set "PHP="

echo.
echo  ========================================================================
echo    AMIGO BICHO - SISTEMA PARA CLINICA VETERINARIA E PET SHOP
echo    Iniciando Servidor Principal...
echo  ========================================================================
echo.

rem PRIORIDADE 1: runtime portavel integrado - pasta da instalacao
if exist "%BASEDIR%runtime\php\php.exe" set "PHP=%BASEDIR%runtime\php\php.exe"
if not "%PHP%"=="" goto :php_found

rem PRIORIDADE 2: runtime dentro da pasta installer - ambiente de desenvolvimento
if exist "%BASEDIR%installer\runtime\php\php.exe" set "PHP=%BASEDIR%installer\runtime\php\php.exe"
if not "%PHP%"=="" goto :php_found

rem PRIORIDADE 3: PHP 8.4 instalado via WinGet
for /d %%d in ("%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.4*") do (
    if exist "%%d\php.exe" set "PHP=%%d\php.exe"
)
if not "%PHP%"=="" goto :php_found

rem PRIORIDADE 4: PHP global no PATH do Windows
where php.exe >nul 2>&1
if %errorlevel%==0 set "PHP=php.exe"
if not "%PHP%"=="" goto :php_found

echo [ERRO] PHP 8.4 nao foi localizado no sistema.
echo Certifique-se de que a pasta runtime\php existe ou reinstale o Amigo Bicho.
echo.
pause
exit /b 1

:php_found
echo [OK] Motor PHP localizado: %PHP%

rem Verifica se o servidor ja esta rodando na porta 8181
"%PHP%" "%BASEDIR%installer\bootstrap.php" --check-port=%PORT%
if %errorlevel%==0 goto :already_running

rem Libera porta no Firewall se executado como administrador
netsh advfirewall firewall show rule name="Amigo Bicho Porta %PORT%" >nul 2>&1
if not %errorlevel%==0 netsh advfirewall firewall add rule name="Amigo Bicho Porta %PORT%" dir=in action=allow protocol=TCP localport=%PORT% profile=any >nul 2>&1

rem Prepara ambiente, .env, banco SQLite, IP de rede e atalho de terminal
echo [INFO] Preparando ambiente e validando banco de dados...
"%PHP%" -d memory_limit=512M "%BASEDIR%installer\bootstrap.php" --prepare --port=%PORT%
if not %errorlevel%==0 goto :prepare_error

rem Agenda abertura do navegador assim que o servidor estiver pronto na porta 8181
start /b "" "%PHP%" "%BASEDIR%installer\bootstrap.php" --open-browser --port=%PORT%

rem Habilita multiplos workers concorrentes no servidor embutido do PHP (evita travamento com varias requisicoes/assets)
set PHP_CLI_SERVER_WORKERS=8

rem Inicia o servidor HTTP diretamente vinculado a todas as interfaces de rede
echo [INFO] Servidor web iniciando na porta %PORT% (com %PHP_CLI_SERVER_WORKERS% workers concorrentes)...
echo.
"%PHP%" -d memory_limit=512M -d upload_max_filesize=256M -d post_max_size=256M -d max_execution_time=120 -S 0.0.0.0:%PORT% -t "%BASEDIR%public" "%BASEDIR%server.php"

echo.
echo [AVISO] O servidor foi encerrado com codigo %errorlevel%.
pause
exit /b %errorlevel%

:already_running
echo.
echo [OK] O servidor Amigo Bicho ja esta em execucao na porta %PORT%.
echo Abrindo o sistema no navegador...
start "" "http://localhost:%PORT%"
exit /b 0

:prepare_error
echo.
echo [ERRO] Falha ao preparar o ambiente do Amigo Bicho.
echo Verifique as mensagens acima e as permissoes da pasta %BASEDIR%.
echo.
pause
exit /b 1
