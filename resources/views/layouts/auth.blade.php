<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? branding('clinic_name', 'Amigo Bicho') }} - Acesso ao Sistema</title>
    <link rel="icon" type="image/x-icon" href="{{ branding_favicon_url() }}">
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <style>{!! branding_css_vars() !!}</style>
    <style>
        :root {
            --brand-primary: {{ branding('primary_color', '#1E7E34') }};
            --brand-primary-hover: color-mix(in srgb, var(--brand-primary, #1E7E34) 85%, black);
            --brand-bg: {{ branding('sidebar_bg', '#132E27') }};
        }
        
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            background: radial-gradient(circle at 50% 20%, #1e453b 0%, #0d211b 100%) !important;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            color: #2d3748;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 440px;
            margin: auto;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.08);
            overflow: hidden;
            border: none;
        }

        .auth-header {
            padding: 2.5rem 2rem 1.25rem 2rem;
            text-align: center;
            background: #ffffff;
        }

        .auth-logo-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            max-width: 100%;
        }

        .auth-logo-img {
            max-height: 90px;
            max-width: 220px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .auth-clinic-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 0.25rem 0;
            line-height: 1.35;
        }

        .auth-subtitle {
            font-size: 0.875rem;
            color: #718096;
            margin: 0;
            font-weight: 500;
        }

        .auth-body {
            padding: 1rem 2rem 2.25rem 2rem;
        }

        .form-floating-custom {
            margin-bottom: 1.25rem;
        }

        .form-floating-custom label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.45rem;
            display: block;
        }

        .input-group-modern {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group-modern .input-icon {
            position: absolute;
            left: 1.1rem;
            color: #a0aec0;
            font-size: 0.95rem;
            pointer-events: none;
            transition: color 0.2s ease;
            z-index: 4;
        }

        .input-group-modern .form-control {
            height: 48px;
            padding: 0.625rem 1rem 0.625rem 2.75rem;
            font-size: 0.9375rem;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background-color: #f8fafc;
            color: #1a202c;
            transition: all 0.2s ease;
            width: 100%;
        }

        .input-group-modern .form-control:focus {
            background-color: #ffffff;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(30, 126, 52, 0.12);
            outline: none;
        }

        .input-group-modern:focus-within .input-icon {
            color: var(--brand-primary);
        }

        .btn-modern-primary {
            height: 48px;
            background: var(--brand-primary);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-size: 0.975rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 14px rgba(30, 126, 52, 0.35);
            transition: all 0.2s ease;
            cursor: pointer;
            width: 100%;
        }

        .btn-modern-primary:hover {
            background: var(--brand-primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(30, 126, 52, 0.45);
            color: #ffffff;
        }

        .btn-modern-primary:active {
            transform: translateY(0);
        }

        .custom-checkbox-modern {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            margin: 0;
            user-select: none;
        }

        .custom-checkbox-modern input[type="checkbox"] {
            width: 17px;
            height: 17px;
            border-radius: 4px;
            cursor: pointer;
            accent-color: var(--brand-primary);
        }

        .custom-checkbox-modern span {
            font-size: 0.85rem;
            color: #4a5568;
            font-weight: 500;
        }

        .forgot-link {
            font-size: 0.85rem;
            color: var(--brand-primary);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .forgot-link:hover {
            color: var(--brand-primary-hover);
            text-decoration: underline;
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
        }

        .portal-switch-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 0.65rem 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #e2e8f0;
            font-size: 0.825rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .portal-switch-card:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            transform: translateY(-1px);
            text-decoration: none;
        }

        .portal-switch-card i.fa-paw {
            color: #F4A261;
        }

        .copyright-text {
            color: rgba(255, 255, 255, 0.45);
            font-size: 0.75rem;
            margin-top: 1.1rem;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                @php
                    $logoUrl = branding_logo_url();
                    $hasLogo = (bool) $logoUrl;
                    $clinicName = branding('clinic_name', 'Amigo Bicho');
                    $showClinicName = branding('show_clinic_name', '1') === '1';
                @endphp

                <div class="auth-logo-box">
                    @if($hasLogo)
                        <img src="{{ $logoUrl }}" class="auth-logo-img" alt="{{ $clinicName }}">
                    @else
                        <i class="fas fa-paw fa-3x" style="color: var(--brand-primary);"></i>
                    @endif
                </div>

                @if($showClinicName)
                    <h1 class="auth-clinic-name">{{ $clinicName }}</h1>
                @endif
                <p class="auth-subtitle">Acesse sua conta para continuar</p>
            </div>

            <div class="auth-body">
                @yield('content')
            </div>
        </div>

        <div class="auth-footer">
            @if(Route::has('portal.login'))
            <a href="{{ route('portal.login') }}" class="portal-switch-card">
                <i class="fas fa-paw"></i>
                <span>É tutor de pet? <strong>Acesse o Portal do Tutor</strong></span>
                <i class="fas fa-arrow-right" style="font-size: 0.7rem; opacity: 0.7;"></i>
            </a>
            @endif

            <div class="copyright-text">
                &copy; {{ date('Y') }} {{ $clinicName }} &bull; Gestão Veterinária Integrada
            </div>
        </div>

        @stack('after_login_box')
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
