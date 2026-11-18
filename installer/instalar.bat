@echo off
setlocal
title Amigo Bicho - Configuracao do Servidor

cd /d "%~dp0.."
set "BASEDIR=%CD%\"
set "PORT=8181"
set "PHP="

echo.
echo  ========================================================================
echo    AMIGO BICHO - CONFIGURANDO SISTEMA PARA USO LOCAL E EM REDE
echo  ========================================================================
echo.

if exist "%BASEDIR%runtime\php\php.exe" set "PHP=%BASEDIR%runtime\php\php.exe"
if not "%PHP%"=="" goto :php_ok
if exist "%BASEDIR%installer\runtime\php\php.exe" set "PHP=%BASEDIR%installer\runtime\php\php.exe"
if not "%PHP%"=="" goto :php_ok
where php.exe >nul 2>&1
if %errorlevel%==0 set "PHP=php.exe"
if not "%PHP%"=="" goto :php_ok

echo [ERRO] Motor PHP nao encontrado em %BASEDIR%runtime\php\php.exe
exit /b 1

:php_ok
echo [1/4] Motor PHP verificado: %PHP%

echo [2/4] Ajustando permissoes de escrita das pastas de dados...
icacls "%BASEDIR%." /grant *S-1-5-32-545:M /C /Q >nul 2>&1
if exist "%BASEDIR%database" icacls "%BASEDIR%database" /grant *S-1-5-32-545:(OI)(CI)M /T /C /Q >nul 2>&1
if exist "%BASEDIR%storage" icacls "%BASEDIR%storage" /grant *S-1-5-32-545:(OI)(CI)M /T /C /Q >nul 2>&1
if exist "%BASEDIR%bootstrap\cache" icacls "%BASEDIR%bootstrap\cache" /grant *S-1-5-32-545:(OI)(CI)M /T /C /Q >nul 2>&1

echo [3/4] Configurando ambiente, banco de dados e rede...
"%PHP%" -d memory_limit=512M "%BASEDIR%installer\bootstrap.php" --prepare --install --port=%PORT%
if not %errorlevel%==0 exit /b %errorlevel%

echo [4/4] Configurando regra no Firewall do Windows na porta %PORT%...
netsh advfirewall firewall show rule name="Amigo Bicho Porta %PORT%" >nul 2>&1
if not %errorlevel%==0 netsh advfirewall firewall add rule name="Amigo Bicho Porta %PORT%" dir=in action=allow protocol=TCP localport=%PORT% profile=any >nul 2>&1

echo.
echo  ========================================================================
echo    CONFIGURACAO CONCLUIDA COM SUCESSO!
echo  ========================================================================
echo.
exit /b 0
