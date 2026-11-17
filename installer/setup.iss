; ============================================================
; Amigo Bicho - Inno Setup Script
; Gera instalador .exe para Windows 10/11
; ============================================================
; Para compilar: instale o Inno Setup e abra este arquivo
; Download: https://jrsoftware.org/isinfo.php
; ============================================================

#define AppName "Amigo Bicho"
#define AppVersion "1.4.0"
#define AppPublisher "HLM Tecnologia"
#define AppURL "https://github.com/osmair36/vetessence"
#define AppExeName "iniciar.bat"
#define AppIcon "public\favicon.ico"

[Setup]
AppId={{A1B2C3D4-E5F6-7890-ABCD-EF1234567890}
AppName={#AppName}
AppVersion={#AppVersion}
AppPublisher={#AppPublisher}
AppPublisherURL={#AppURL}
AppSupportURL={#AppURL}
AppUpdatesURL={#AppURL}
DefaultDirName=C:\AmigoBicho
DefaultGroupName={#AppName}
AllowNoIcons=yes
; Descomente a linha abaixo para exigir privilégios de admin:
; PrivilegesRequired=admin
OutputDir=installer\dist
OutputBaseFilename=AmigoBicho-Setup-v{#AppVersion}
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
WizardImageFile=installer\wizard.bmp
WizardSmallImageFile=installer\wizard-small.bmp
UninstallDisplayIcon={app}\public\favicon.ico
SetupIconFile=installer\setup-icon.ico
MinVersion=10.0

[Languages]
Name: "brazilianportuguese"; MessagesFile: "compiler:Languages\BrazilianPortuguese.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked
Name: "startmenuicon"; Description: "Criar atalho no Menu Iniciar"; GroupDescription: "{cm:AdditionalIcons}";

[Files]
; Aplicação Laravel completa
Source: "..\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs; \
  Excludes: ".git,*.log,node_modules,installer\dist,storage\logs\*,storage\framework\cache\*,storage\framework\sessions\*,storage\framework\views\*"

; PHP portátil (deve estar em runtime\php\)
; Baixe o PHP 8.4 "Thread Safe" ZIP de https://windows.php.net/download/
; e extraia em installer\runtime\php\
Source: "runtime\php\*"; DestDir: "{app}\runtime\php"; Flags: ignoreversion recursesubdirs createallsubdirs

[Dirs]
Name: "{app}\storage\logs"
Name: "{app}\storage\framework\cache"
Name: "{app}\storage\framework\sessions"
Name: "{app}\storage\framework\views"
Name: "{app}\bootstrap\cache"
Name: "{app}\database"

[Icons]
Name: "{group}\{#AppName}"; Filename: "{app}\iniciar.bat"; WorkingDir: "{app}"; IconFilename: "{app}\public\favicon.ico"
Name: "{group}\Desinstalar {#AppName}"; Filename: "{uninstallexe}"
Name: "{commondesktop}\{#AppName}"; Filename: "{app}\iniciar.bat"; WorkingDir: "{app}"; IconFilename: "{app}\public\favicon.ico"; Tasks: desktopicon

[Run]
; Executa o instalador do sistema após copiar os arquivos
Filename: "{app}\installer\instalar.bat"; Description: "Configurar sistema agora"; Flags: postinstall runascurrentuser shellexec; WorkingDir: "{app}"

; Abre o sistema após instalação
Filename: "{app}\iniciar.bat"; Description: "Iniciar {#AppName} agora"; Flags: postinstall nowait runascurrentuser shellexec unchecked; WorkingDir: "{app}"

[UninstallRun]
; Para o servidor PHP antes de desinstalar
Filename: "taskkill.exe"; Parameters: "/F /IM php.exe"; Flags: runhidden; RunOnceId: "StopPHP"

[Code]
procedure CurStepChanged(CurStep: TSetupStep);
var
  ResultCode: Integer;
begin
  if CurStep = ssPostInstall then
  begin
    // Cria diretórios necessários para o Laravel
    ForceDirectories(ExpandConstant('{app}\storage\logs'));
    ForceDirectories(ExpandConstant('{app}\storage\framework\cache'));
    ForceDirectories(ExpandConstant('{app}\storage\framework\sessions'));
    ForceDirectories(ExpandConstant('{app}\storage\framework\views'));
    ForceDirectories(ExpandConstant('{app}\bootstrap\cache'));
  end;
end;
