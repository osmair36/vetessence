@extends('layouts.auth', ['title' => 'Login'])

@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf
    
    @if ($errors->any())
    <div class="alert alert-danger py-2 px-3 mb-3" style="border-radius: 12px; font-size: 0.85rem; border: none; background-color: #fee2e2; color: #991b1b;">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-circle mr-2" style="font-size: 1rem;"></i>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    
    <div class="form-floating-custom">
        <label for="email">E-mail</label>
        <div class="input-group-modern">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" id="email" name="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   placeholder="seu@email.com" 
                   value="{{ old('email') }}" 
                   required autofocus autocomplete="username">
        </div>
    </div>
    
    <div class="form-floating-custom">
        <label for="password">Senha</label>
        <div class="input-group-modern">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" id="password" name="password" 
                   class="form-control @error('password') is-invalid @enderror" 
                   placeholder="Digite sua senha" 
                   required autocomplete="current-password">
        </div>
    </div>
    
    <div class="d-flex align-items-center justify-content-between mb-4">
        <label class="custom-checkbox-modern" for="remember">
            <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <span>Lembrar-me</span>
        </label>
        
        @unless(config('demo.enabled'))
            <a href="{{ route('password.request') }}" class="forgot-link">Esqueceu a senha?</a>
        @endunless
    </div>

    <button type="submit" class="btn-modern-primary">
        <i class="fas fa-sign-in-alt"></i>
        <span>Entrar</span>
    </button>
</form>
@endsection

@push('after_login_box')
    @if(config('demo.enabled'))
    <style>
        .demo-box {
            width: 100%;
            max-width: 440px;
            margin: 1.25rem auto 0;
        }
        .demo-accounts {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25);
            border: none;
        }
        .demo-accounts .card-header {
            background: #f8fafc;
            border-bottom: 1px solid #edf2f7;
            padding: 0.65rem 1rem;
        }
        .demo-accounts .card-header h3 {
            font-size: 0.85rem;
            font-weight: 700;
            color: #4a5568;
            margin: 0;
        }
        .demo-accounts .card-body {
            padding: 0.75rem 1rem !important;
        }
        .demo-accounts table {
            font-size: 0.78rem;
            margin-bottom: 0;
        }
        .demo-accounts table th,
        .demo-accounts table td {
            padding: 0.35rem 0.5rem;
            white-space: nowrap;
            border-color: #f1f5f9;
        }
        .demo-accounts table code {
            font-size: 0.76rem;
            background: #f1f5f9;
            color: #334155;
            padding: 0.15rem 0.35rem;
            border-radius: 4px;
        }
        .demo-accounts .demo-note {
            font-size: 0.72rem;
            margin-top: 0.5rem;
            color: #94a3b8;
        }
    </style>
    <div class="demo-box">
        <div class="card demo-accounts">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-circle mr-1 text-primary"></i> Contas de demonstração</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th>Perfil</th>
                            <th>Email</th>
                            <th>Senha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(config('demo.accounts') as $account)
                        <tr>
                            <td><strong>{{ $account['profile'] }}</strong></td>
                            <td><code>{{ $account['email'] }}</code></td>
                            <td><code>{{ $account['password'] }}</code></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="demo-note mb-0 text-center">
                    Ambiente de demonstração: as senhas destas contas não podem ser alteradas.
                </p>
            </div>
        </div>
    </div>
    @endif
@endpush
