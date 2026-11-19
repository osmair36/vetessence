@echo off
setlocal
title Amigo Bicho - Instalar Rotina Agendada no Windows

cd /d "%~dp0.."
set "BASEDIR=%CD%\"
set "PHP="

if exist "%BASEDIR%runtime\php\php.exe" set "PHP=%BASEDIR%runtime\php\php.exe"
if not "%PHP%"=="" goto :php_ok
if exist "%BASEDIR%installer\runtime\php\php.exe" set "PHP=%BASEDIR%installer\runtime\php\php.exe"
if not "%PHP%"=="" goto :php_ok
where php.exe >nul 2>&1
if %errorlevel%==0 set "PHP=php.exe"
if not "%PHP%"=="" goto :php_ok

echo [ERRO] PHP 8.4 nao localizado.
pause
exit /b 1

:php_ok
echo.
echo  ========================================================================
echo    AMIGO BICHO - AGENDADOR DE TAREFAS DO WINDOWS (ROTINA DIARIA)
echo  ========================================================================
echo.
echo Registrando tarefa "AmigoBichoScheduler" para executar schedule:run a cada 1 minuto...
echo.

schtasks /Create /F /TN "AmigoBichoScheduler" /SC MINUTE /MO 1 /TR "\"%PHP%\" -d memory_limit=512M \"%BASEDIR%artisan\" schedule:run" /RU SYSTEM >nul 2>&1
if %errorlevel%==0 goto :ok_sys

echo Tentando registrar para o usuario atual...
schtasks /Create /F /TN "AmigoBichoScheduler" /SC MINUTE /MO 1 /TR "\"%PHP%\" -d memory_limit=512M \"%BASEDIR%artisan\" schedule:run"
if not %errorlevel%==0 goto :fail

:ok_sys
echo.
echo [OK] Tarefa agendada "AmigoBichoScheduler" ativada com sucesso!
echo A Central de Lembretes de Vacinacao sera executada automaticamente todos os dias no horario configurado.
echo.
pause
exit /b 0

:fail
echo.
echo [ERRO] Nao foi possivel registrar a tarefa agendada. Execute este arquivo como Administrador.
echo.
pause
exit /b 1
