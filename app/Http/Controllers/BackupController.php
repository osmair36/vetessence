<?php

namespace App\Http\Controllers;

use App\Services\Backup\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->middleware('can:backup');
        $this->backupService = $backupService;
    }

    /**
     * Exibe a tela principal de backups e restaurações.
     */
    public function index()
    {
        $files = $this->backupService->listBackups();
        $settings = $this->backupService->getSettings();

        // Informações sobre a base de dados atual
        $connection = config('database.default', 'sqlite');
        $driver = config("database.connections.{$connection}.driver", 'sqlite');
        $dbName = config("database.connections.{$connection}.database", 'database.sqlite');

        $dbSize = 0;
        if ($driver === 'sqlite') {
            $sqlitePath = $dbName;
            if (!file_exists($sqlitePath) && file_exists(database_path('database.sqlite'))) {
                $sqlitePath = database_path('database.sqlite');
            }
            $dbSize = file_exists($sqlitePath) ? (@filesize($sqlitePath) ?: 0) : 0;
        } else {
            try {
                $result = DB::select("SELECT SUM(data_length + index_length) as size FROM information_schema.TABLES WHERE table_schema = ?", [$dbName]);
                $dbSize = (int) ($result[0]->size ?? 0);
            } catch (\Throwable) {
                $dbSize = 0;
            }
        }

        $dbInfo = [
            'driver' => strtoupper($driver),
            'database' => basename($dbName),
            'size' => $this->backupService->formatBytes($dbSize),
            'total_backups' => $files->count(),
            'total_backup_size' => $this->backupService->formatBytes($files->sum('size')),
            'last_backup' => $files->first()['formatted_date'] ?? 'Nenhum backup realizado',
        ];

        return view('backups.index', compact('files', 'settings', 'dbInfo'));
    }

    /**
     * Cria um backup manual imediato.
     */
    public function create(Request $request): RedirectResponse
    {
        if ($request->has('compress') && $request->boolean('compress')) {
            \Illuminate\Support\Facades\Artisan::call('backup:database', ['--compress' => true]);
        } else {
            \Illuminate\Support\Facades\Artisan::call('backup:database');
        }

        $output = trim(\Illuminate\Support\Facades\Artisan::output());
        $message = !empty($output) ? $output : 'Backup gerado com sucesso.';

        return redirect()->route('backups.index')
            ->with('success', 'Backup criado: ' . $message);
    }

    /**
     * Salva as configurações de agendamento e destinos da rotina de backup.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'frequency' => 'required|in:every_six_hours,every_twelve_hours,daily,weekly,monthly',
            'time' => 'required|date_format:H:i',
            'compress' => 'nullable|boolean',
            'keep_days' => 'required|integer|min:1|max:365',
            'custom_path' => 'nullable|string|max:500',
        ]);

        if (!empty($validated['custom_path'])) {
            $test = $this->backupService->testDestination($validated['custom_path']);
            if (!$test['success']) {
                return redirect()->route('backups.index')
                    ->withInput()
                    ->with('error', 'Atenção ao salvar destino: ' . $test['message']);
            }
        }

        $this->backupService->saveSettings($validated);

        return redirect()->route('backups.index')
            ->with('success', 'Configurações de rotina de backup salvas com sucesso!');
    }

    /**
     * Testa via AJAX se o caminho de destino informado é gravável.
     */
    public function testDestination(Request $request): JsonResponse
    {
        $path = (string) $request->input('custom_path', '');
        $result = $this->backupService->testDestination($path);

        return response()->json($result);
    }

    /**
     * Efetua o download do arquivo de backup.
     */
    public function download(string $filename)
    {
        $safeFilename = basename($filename);
        $path = 'backups/' . $safeFilename;

        if (!Storage::exists($path)) {
            abort(404, 'Arquivo de backup não encontrado.');
        }

        return Storage::download($path);
    }

    /**
     * Restaura a base de dados a partir de um backup existente ou enviado por upload.
     */
    public function restore(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmation' => 'required|in:RESTAURAR',
            'filename' => 'nullable|string',
            'backup_file' => 'nullable|file|max:512000', // até 500MB
        ]);

        $filePath = null;

        // Caso 1: Upload de arquivo pelo formulário
        if ($request->hasFile('backup_file')) {
            $uploaded = $request->file('backup_file');
            $originalName = $uploaded->getClientOriginalName();
            $ext = strtolower($uploaded->getClientOriginalExtension());

            if (!in_array($ext, ['sqlite', 'sql', 'gz'])) {
                return redirect()->route('backups.index')
                    ->with('error', 'Formato de arquivo inválido. Formatos suportados: .sql, .sqlite, .gz.');
            }

            // Salva na pasta de backups
            $targetName = 'upload-' . now()->format('Y-m-d_H-i-s') . '-' . basename($originalName);
            $storedPath = $uploaded->storeAs('backups', $targetName);
            $filePath = storage_path('app/' . $storedPath);
        }
        // Caso 2: Seleção de um arquivo já existente na lista de backups
        elseif ($request->filled('filename')) {
            $safeName = basename($request->input('filename'));
            $filePath = storage_path('app/backups/' . $safeName);
        } else {
            return redirect()->route('backups.index')
                ->with('error', 'Nenhum arquivo de backup foi selecionado para restauração.');
        }

        $result = $this->backupService->restoreBackup($filePath, true);

        if ($result['success']) {
            return redirect()->route('backups.index')->with('success', $result['message']);
        }

        return redirect()->route('backups.index')->with('error', $result['message']);
    }

    /**
     * Remove backups antigos de acordo com os dias de retenção.
     */
    public function cleanup(): RedirectResponse
    {
        $deleted = $this->backupService->cleanupOldBackups();

        return redirect()->route('backups.index')
            ->with('success', "Limpeza concluída! {$deleted} backup(s) antigo(s) removido(s).");
    }

    /**
     * Exclui um backup individual.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $safeName = basename($filename);
        Storage::delete('backups/' . $safeName);

        return redirect()->route('backups.index')
            ->with('success', 'Backup removido.');
    }
}
