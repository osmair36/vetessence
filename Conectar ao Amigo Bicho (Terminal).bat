@echo off
chcp 65001 >nul 2>&1
title Amigo Bicho - Conexão do Terminal

set "SCRIPT_DIR=%~dp0"
set "SERVER_IP="
set "SERVER_NAME="
set "PORT=8181"

echo.
echo  ========================================================================
echo    AMIGO BICHO - TERMINAL CLIENTE
echo    Conectando ao Servidor da Clinica...
echo  ========================================================================
echo.

:: 1. Tenta ler o arquivo server_info.txt gerado pelo servidor
if exist "%SCRIPT_DIR%server_info.txt" (
    for /f "usebackq tokens=1,2 delims==" %%a in ("%SCRIPT_DIR%server_info.txt") do (
        if "%%a"=="SERVIDOR_IP" set "SERVER_IP=%%b"
        if "%%a"=="SERVIDOR_NOME" set "SERVER_NAME=%%b"
        if "%%a"=="PORTA" set "PORT=%%b"
    )
)

:: 2. Se nao encontrou no server_info.txt, tenta extrair o nome do servidor do caminho UNC (\\SERVIDOR\pasta\)
if "%SERVER_IP%"=="" (
    for /f "tokens=3 delims=\" %%s in ("%SCRIPT_DIR%") do (
        set "SERVER_NAME=%%s"
    )
)

:: Define URL final para acesso
if not "%SERVER_IP%"=="" (
    set "APP_URL=http://%SERVER_IP%:%PORT%"
) else if not "%SERVER_NAME%"=="" (
    set "APP_URL=http://%SERVER_NAME%:%PORT%"
) else (
    set "APP_URL=http://localhost:%PORT%"
)

echo [OK] Servidor identificado: %APP_URL%
echo.
echo Abrindo o sistema no seu navegador padrao...
start "" "%APP_URL%"

:: Pergunta se deseja criar o atalho na Area de Trabalho deste terminal
echo.
echo  ------------------------------------------------------------------------
echo  Deseja criar um atalho do Amigo Bicho na Area de Trabalho deste computador?
echo  ------------------------------------------------------------------------
set /p CRIAR_ATALHO="Digite S para Sim ou N para Nao [S/N] (padrao S): "
if /i "%CRIAR_ATALHO%"=="N" goto :fim

:: Cria o atalho .url na Area de Trabalho do usuario atual
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
    echo [SUCESSO] Atalho criado na sua Area de Trabalho!
    echo Na proxima vez, basta dar dois cliques no icone "Amigo Bicho" no seu Desktop.
)

:fim
echo.
echo Conexao concluida com sucesso.
timeout /t 3 >nul
exit /b 0
