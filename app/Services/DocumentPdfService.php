<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DocumentPdfService
{
    /**
     * Gera e baixa um documento em PDF padronizado via Dompdf.
     */
    public static function download(string $view, array $data, string $filename, string $orientation = 'portrait')
    {
        $data['isPdf'] = true;
        $data['pageOrientation'] = $orientation;

        // Sanitiza o nome do arquivo para download seguro
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        if (!str_ends_with(strtolower($safeFilename), '.pdf')) {
            $safeFilename .= '.pdf';
        }

        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper('a4', $orientation);

        return $pdf->download($safeFilename);
    }

    /**
     * Renderiza o PDF para visualização em stream (na aba do navegador).
     */
    public static function stream(string $view, array $data, string $filename, string $orientation = 'portrait')
    {
        $data['isPdf'] = true;
        $data['pageOrientation'] = $orientation;

        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        if (!str_ends_with(strtolower($safeFilename), '.pdf')) {
            $safeFilename .= '.pdf';
        }

        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper('a4', $orientation);

        return $pdf->stream($safeFilename);
    }
}
