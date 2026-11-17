@echo off
chcp 65001 >nul 2>&1
title Amigo Bicho - Clinica Veterinaria

set "BASEDIR=%~dp0"
set "PORT=8080"
set "URL=http://127.0.0.1:%PORT%"
set "PHP="

echo.
echo  ===========================================
echo   Amigo Bicho - Clinica Veterinaria
echo   Iniciando o sistema...
echo  ===========================================
echo.

:: PRIORIDADE 1: PHP 8.4 do WinGet (mais confiavel)
for /d %%d in ("%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.4*") do (
    if exist "%%d\php.exe" (
        set "PHP=%%d\php.exe"
        goto :php_found
    )
)

:: PRIORIDADE 2: runtime local (instalador portavel)
if exist "%BASEDIR%runtime\php\php.exe" (
    set "PHP=%BASEDIR%runtime\php\php.exe"
    goto :php_found
)

:: PRIORIDADE 3: PHP no PATH (apenas se for 8.4+)
where php.exe >nul 2>&1
if %errorlevel%==0 (
    for /f "tokens=2 delims= " %%v in ('php.exe --version 2^>nul ^| findstr /i "PHP 8.4"') do (
        set "PHP=php.exe"
        goto :php_found
    )
)

:: Nenhum PHP 8.4 encontrado
echo [ERRO] PHP 8.4 nao encontrado no sistema.
echo.
echo Instale o PHP 8.4 com o comando (como Administrador):
echo.
echo   winget install PHP.PHP.8.4
echo.
echo Depois reinicie este programa.
echo.
pause
exit /b 1

:php_found
echo [OK] PHP: %PHP%

:: Verifica versao
for /f "tokens=2 delims= " %%v in ('"%PHP%" --version 2^>nul ^| findstr "PHP 8"') do (
    echo [OK] Versao: %%v
)
echo.

:: Verifica se o .env existe
if not exist "%BASEDIR%.env" (
    echo [INFO] Criando arquivo de configuracao...
    if exist "%BASEDIR%.env.example" (
        copy "%BASEDIR%.env.example" "%BASEDIR%.env" >nul
    )
    "%PHP%" "%BASEDIR%artisan" key:generate --force
    echo [OK] Configuracao criada.
    echo.
)

:: Verifica se o banco de dados existe
if not exist "%BASEDIR%database\database.sqlite" (
    echo [INFO] Criando banco de dados...
    type nul > "%BASEDIR%database\database.sqlite"
    "%PHP%" "%BASEDIR%artisan" migrate --force
    "%PHP%" "%BASEDIR%artisan" db:seed --force
    echo [OK] Banco de dados criado.
    echo.
)

:: Limpa cache
echo [INFO] Inicializando...
"%PHP%" "%BASEDIR%artisan" config:clear >nul 2>&1
"%PHP%" "%BASEDIR%artisan" cache:clear >nul 2>&1
"%PHP%" "%BASEDIR%artisan" view:clear >nul 2>&1
echo [OK] Pronto!
echo.
echo  ===========================================
echo   Sistema iniciado com sucesso!
echo   Acesse: %URL%
echo   Login:  super@vet.com
echo   Senha:  password
echo  ===========================================
echo.
echo   NAO feche esta janela enquanto usar
echo   o sistema. Para encerrar, feche aqui.
echo.

:: Aguarda e abre o navegador
timeout /t 3 /nobreak >nul
start "" "%URL%"

:: Inicia o servidor PHP
"%PHP%" "%BASEDIR%artisan" serve --host=127.0.0.1 --port=%PORT%

pause
