@echo off
setlocal enabledelayedexpansion

rem ========================================================================
rem AMIGO BICHO - ROTINA AUTOMATIZADA DE LEMBRETES DE VACINACAO (WHATSAPP)
rem Pode ser executado manualmente ou agendado no Agendador de Tarefas do Windows
rem ========================================================================

set "BASEDIR=%~dp0"
cd /d "%BASEDIR%"

set "PHP="
if exist "%BASEDIR%runtime\php\php.exe" set "PHP=%BASEDIR%runtime\php\php.exe"
if not "%PHP%"=="" goto :php_found
if exist "%BASEDIR%installer\runtime\php\php.exe" set "PHP=%BASEDIR%installer\runtime\php\php.exe"
if not "%PHP%"=="" goto :php_found
for /d %%d in ("%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.4*") do (
    if exist "%%d\php.exe" set "PHP=%%d\php.exe"
)
if not "%PHP%"=="" goto :php_found
where php.exe >nul 2>&1
if %errorlevel%==0 set "PHP=php.exe"
if not "%PHP%"=="" goto :php_found

echo [ERRO] PHP nao localizado.
exit /b 1

:php_found
echo [%DATE% %TIME%] Iniciando processamento de lembretes de vacinacao...
"%PHP%" -d memory_limit=512M "%BASEDIR%artisan" vaccines:process-reminders >> "%BASEDIR%storage\logs\rotina_vacinas.log" 2>&1

if %errorlevel%==0 (
    echo [%DATE% %TIME%] Rotina concluida com sucesso!
) else (
    echo [%DATE% %TIME%] Falha na execucao da rotina. Verifique storage\logs\rotina_vacinas.log
)

exit /b %errorlevel%
