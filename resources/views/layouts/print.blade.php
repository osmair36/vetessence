<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $docTitle ?? $title ?? branding_clinic_name() }}</title>
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        @page {
            size: {{ $pageOrientation ?? 'portrait' }};
            margin: 1.2cm;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #111;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        /* Barra de controle web / tela */
        .no-print-bar {
            background: #132E27;
            color: #fff;
            padding: 10px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            font-family: Arial, sans-serif;
        }
        .no-print-bar .brand-title {
            font-weight: bold;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .no-print-bar .actions {
            display: flex;
            gap: 10px;
        }
        .btn-action {
            display: inline-block;
            background: #1E7E34;
            color: #fff !important;
            padding: 6px 14px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-action:hover {
            background: #176529;
        }
        .btn-action-secondary {
            background: #495057;
        }
        .btn-action-secondary:hover {
            background: #343a40;
        }
        .btn-action-close {
            background: #6c757d;
        }
        .btn-action-close:hover {
            background: #5a6268;
        }

        .document-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 10px 25px 30px;
        }

        /* Elementos de layout */
        .section-box {
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 14px;
            background: #fff;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f4f5f7;
            padding: 6px 10px;
            border-bottom: 1px solid #ddd;
            color: #222;
        }
        .section-body {
            padding: 10px;
        }

        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .info-row {
            display: table-row;
        }
        .info-cell {
            display: table-cell;
            padding: 4px 6px;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #444;
            font-size: 10px;
            text-transform: uppercase;
        }
        .info-value {
            color: #000;
        }

        /* Tabelas */
        table.table-doc {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 10px;
            font-size: 10.5px;
        }
        table.table-doc th, table.table-doc td {
            border: 1px solid #ccc;
            padding: 5px 8px;
            text-align: left;
        }
        table.table-doc th {
            background: #eaeaea;
            font-weight: bold;
            color: #222;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.5px;
        }
        table.table-doc tr:nth-child(even) td {
            background: #fafafa;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .font-bold {
            font-weight: bold;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .badge-danger  { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .badge-info    { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        /* Assinaturas */
        .signature-area {
            margin-top: 35px;
            width: 100%;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            border: none !important;
            padding: 0 15px;
            text-align: center;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 260px;
            margin: 0 auto 4px;
            padding-top: 4px;
        }
        .signature-name {
            font-weight: bold;
            font-size: 11px;
        }
        .signature-sub {
            font-size: 9.5px;
            color: #555;
        }

        /* Rodapé de documento */
        .doc-footer {
            margin-top: 25px;
            border-top: 1px solid #ccc;
            padding-top: 6px;
            font-size: 8.5px;
            color: #666;
            text-align: center;
            page-break-inside: avoid;
        }

        /* Print media */
        @media print {
            .no-print, .no-print-bar {
                display: none !important;
            }
            .document-container {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            body {
                background: #fff !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    @if(!isset($isPdf) || !$isPdf)
    <div class="no-print-bar no-print">
        <div class="brand-title">
            <span>🐾 {{ branding_clinic_name() }}</span>
            <span style="opacity: 0.6; font-size: 12px;">| {{ $title ?? 'Visualização de Documento' }}</span>
        </div>
        <div class="actions">
            <button onclick="window.print()" class="btn-action">
                🖨️ Imprimir
            </button>
            @if(isset($pdfUrl) && $pdfUrl)
            <a href="{{ $pdfUrl }}" class="btn-action btn-action-secondary">
                📥 Baixar PDF
            </a>
            @endif
            <button onclick="window.close()" class="btn-action btn-action-close">
                ✕ Fechar
            </button>
        </div>
    </div>
    @endif

    <div class="document-container">
        <x-report-header :title="$title ?? null" :subtitle="$subtitle ?? null" />

        @yield('content')

        <div class="doc-footer">
            <p style="margin: 0;">
                Documento emitido em {{ now()->format('d/m/Y \à\s H:i:s') }}
                @auth
                    por {{ auth()->user()->name }}
                @endauth
                — {{ branding_clinic_name() }}
            </p>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
