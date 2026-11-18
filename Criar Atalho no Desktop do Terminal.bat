@echo off
chcp 65001 >nul 2>&1
title Amigo Bicho - Criar Atalho no Terminal

set "SCRIPT_DIR=%~dp0"
set "SERVER_IP="
set "SERVER_NAME="
set "PORT=8181"

if exist "%SCRIPT_DIR%server_info.txt" (
    for /f "usebackq tokens=1,2 delims==" %%a in ("%SCRIPT_DIR%server_info.txt") do (
        if "%%a"=="SERVIDOR_IP" set "SERVER_IP=%%b"
        if "%%a"=="SERVIDOR_NOME" set "SERVER_NAME=%%b"
        if "%%a"=="PORTA" set "PORT=%%b"
    )
)

if "%SERVER_IP%"=="" (
    for /f "tokens=3 delims=\" %%s in ("%SCRIPT_DIR%") do (
        set "SERVER_NAME=%%s"
    )
)

if not "%SERVER_IP%"=="" (
    set "APP_URL=http://%SERVER_IP%:%PORT%"
) else if not "%SERVER_NAME%"=="" (
    set "APP_URL=http://%SERVER_NAME%:%PORT%"
) else (
    set "APP_URL=http://localhost:%PORT%"
)

set "DESKTOP_PATH=%USERPROFILE%\Desktop"
if exist "%DESKTOP_PATH%" (
    (
        echo [InternetShortcut]
        echo URL=%APP_URL%
        echo IconIndex=0
        if exist "%SCRIPT_DIR%public\favicon.ico" (
            echo IconFile=%SCRIPT_DIR%public\favicon.ico
        )
    ) > "%DESKTOP_PATH%\Amigo Bicho.url"
    echo.
    echo ========================================================================
    echo  Atalho "Amigo Bicho" criado com sucesso na sua Area de Trabalho!
    echo  URL configurada: %APP_URL%
    echo ========================================================================
    echo.
) else (
    echo [ERRO] Nao foi possivel localizar a Area de Trabalho (%DESKTOP_PATH%).
)

pause
