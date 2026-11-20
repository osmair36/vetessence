@echo off
setlocal
title Amigo Bicho - Criar Atalho no Terminal

set "SCRIPT_DIR=%~dp0"
set "SERVER_IP="
set "SERVER_NAME="
set "PORT=8181"
set "APP_URL="

if not exist "%SCRIPT_DIR%server_info.txt" goto :detect_unc
for /f "usebackq tokens=1,2 delims==" %%a in ("%SCRIPT_DIR%server_info.txt") do (
    if "%%a"=="SERVIDOR_IP" set "SERVER_IP=%%b"
    if "%%a"=="SERVIDOR_NOME" set "SERVER_NAME=%%b"
    if "%%a"=="PORTA" set "PORT=%%b"
)

:detect_unc
if not "%SERVER_IP%"=="" goto :build_url
if not "%SERVER_NAME%"=="" goto :build_url
for /f "tokens=1 delims=\" %%s in ("%SCRIPT_DIR%") do set "SERVER_NAME=%%s"

:build_url
if not "%SERVER_IP%"=="" set "APP_URL=http://%SERVER_IP%:%PORT%"
if "%APP_URL%"=="" if not "%SERVER_NAME%"=="" set "APP_URL=http://%SERVER_NAME%:%PORT%"
if "%APP_URL%"=="" set "APP_URL=http://localhost:%PORT%"

set "DESKTOP_PATH="
for /f "usebackq delims=" %%d in (`powershell -NoProfile -Command "[Environment]::GetFolderPath('Desktop')" 2^>nul`) do set "DESKTOP_PATH=%%d"
if "%DESKTOP_PATH%"=="" set "DESKTOP_PATH=%USERPROFILE%\Desktop"
if not exist "%DESKTOP_PATH%" goto :desktop_error

>"%DESKTOP_PATH%\Amigo Bicho.url" echo [InternetShortcut]
>>"%DESKTOP_PATH%\Amigo Bicho.url" echo URL=%APP_URL%
>>"%DESKTOP_PATH%\Amigo Bicho.url" echo IconIndex=0
if exist "%SCRIPT_DIR%public\amigo-bicho.ico" >>"%DESKTOP_PATH%\Amigo Bicho.url" echo IconFile=%SCRIPT_DIR%public\amigo-bicho.ico

echo.
echo ========================================================================
echo  Atalho "Amigo Bicho" criado com sucesso na sua Area de Trabalho!
echo  URL configurada: %APP_URL%
echo ========================================================================
echo.
pause
exit /b 0

:desktop_error
echo [ERRO] Nao foi possivel localizar a Area de Trabalho: %DESKTOP_PATH%
pause
exit /b 1
