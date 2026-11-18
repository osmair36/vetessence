; ============================================================
; Amigo Bicho - Script Inno Setup 6
; Gera instalador executável (.exe) para Windows 10 e 11
; Suporte a Servidor Local e Terminais em Rede
; ============================================================

#define AppName "Amigo Bicho"
#define AppVersion "1.8.1"
#define AppPublisher "Amigo Bicho - Clínica Veterinária"
#define AppURL "https://github.com/osmair36/vetessence"
#define AppExeName "iniciar.bat"

[Setup]
AppId={{E6F7A8B9-C0D1-4E2F-A3B4-5C6D7E8F9A0B}
AppName={#AppName}
AppVersion={#AppVersion}
AppPublisher={#AppPublisher}
AppPublisherURL={#AppURL}
AppSupportURL={#AppURL}
AppUpdatesURL={#AppURL}
DefaultDirName=C:\Amigo Bicho
DefaultGroupName={#AppName}
AllowNoIcons=yes
PrivilegesRequired=admin
OutputDir=dist
OutputBaseFilename=AmigoBicho-Instalador-v{#AppVersion}
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
SetupIconFile=..\public\favicon.ico
UninstallDisplayIcon={app}\public\favicon.ico
MinVersion=10.0

[Languages]
Name: "brazilianportuguese"; MessagesFile: "compiler:Languages\BrazilianPortuguese.isl"

[Tasks]
Name: "desktopicon"; Description: "Criar atalho do Servidor na Área de Trabalho"; GroupDescription: "Atalhos adicionais:"; Flags: unchecked
Name: "startmenuicon"; Description: "Criar atalho no Menu Iniciar"; GroupDescription: "Atalhos adicionais:"

[Files]
; Aplicação Amigo Bicho completa com dependências vendor
Source: "..\\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs; \
  Excludes: ".git\*,.git,.github\*,node_modules\*,installer\dist\*,installer\runtime\*,scratch\*,tests\*,*.log,storage\logs\*,storage\framework\cache\*,storage\framework\sessions\*,storage\framework\views\*,storage\app\backups\*,database\database.sqlite"

; Base de dados: só copia se não existir, protegendo dados de re-instalações
Source: "..\database\database.sqlite"; DestDir: "{app}\database"; Flags: onlyifdoesntexist

; Motor PHP portátil 8.4 Thread Safe integrado
Source: "runtime\php\*"; DestDir: "{app}\runtime\php"; Flags: ignoreversion recursesubdirs createallsubdirs

[Dirs]
Name: "{app}\storage\logs"
Name: "{app}\storage\app\backups"
Name: "{app}\storage\framework\cache"
Name: "{app}\storage\framework\sessions"
Name: "{app}\storage\framework\views"
Name: "{app}\bootstrap\cache"
Name: "{app}\database"

[Icons]
; Atalhos no Menu Iniciar e Desktop
Name: "{group}\{#AppName} (Servidor)"; Filename: "{app}\iniciar.bat"; WorkingDir: "{app}"; IconFilename: "{app}\public\favicon.ico"
Name: "{group}\Conectar Terminal em Rede"; Filename: "{app}\Conectar ao Amigo Bicho (Terminal).bat"; WorkingDir: "{app}"; IconFilename: "{app}\public\favicon.ico"
Name: "{group}\Desinstalar {#AppName}"; Filename: "{uninstallexe}"
Name: "{commondesktop}\{#AppName} (Servidor)"; Filename: "{app}\iniciar.bat"; WorkingDir: "{app}"; IconFilename: "{app}\public\favicon.ico"; Tasks: desktopicon

[Run]
; Configura o banco, firewall e ambiente pós-cópia
Filename: "{app}\installer\instalar.bat"; Description: "Configurar ambiente do sistema e rede"; Flags: runhidden waituntilterminated; WorkingDir: "{app}"

; Opção para iniciar o servidor imediatamente ao finalizar o assistente
Filename: "{app}\iniciar.bat"; Description: "Iniciar o Servidor {#AppName} agora"; Flags: postinstall nowait runascurrentuser shellexec; WorkingDir: "{app}"

[UninstallRun]
; Encerra processos do PHP ao desinstalar
Filename: "taskkill.exe"; Parameters: "/F /IM php.exe"; Flags: runhidden; RunOnceId: "StopPHP"
Filename: "netsh.exe"; Parameters: "advfirewall firewall delete rule name=""Amigo Bicho (Porta 8181)"""; Flags: runhidden; RunOnceId: "DelFirewall"

[Code]
procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssPostInstall then
  begin
    ForceDirectories(ExpandConstant('{app}\storage\logs'));
    ForceDirectories(ExpandConstant('{app}\storage\app\backups'));
    ForceDirectories(ExpandConstant('{app}\storage\framework\cache'));
    ForceDirectories(ExpandConstant('{app}\storage\framework\sessions'));
    ForceDirectories(ExpandConstant('{app}\storage\framework\views'));
    ForceDirectories(ExpandConstant('{app}\bootstrap\cache'));
    ForceDirectories(ExpandConstant('{app}\database'));
  end;
end;
