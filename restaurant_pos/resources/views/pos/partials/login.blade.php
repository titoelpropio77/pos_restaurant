{{-- Ingreso al POS (legacy: modalLogin.php). Se conservan los id que usa el JS. --}}
<div class="modal fade in pos-login" id="ModalLogin" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('login.store') }}">
            @csrf
            <div class="pos-login-head">
                <h1><img src="{{ asset('assets/brand/logo-light-tagline.png') }}" alt="Mesatek, sistema para restaurantes"></h1>
                <p>Ingrese sus datos para abrir la caja</p>
            </div>

            <div class="pos-login-body">
                @if (session('error_login') || $errors->any())
                    <div class="alert alert-danger" id="divError">
                        <i class="fa fa-exclamation-circle"></i> {{ session('error_login') ?? $errors->first() }}
                    </div>
                @endif

                <span class="pos-label">Correo</span>
                <div class="pos-login-field">
                    <i class="fa fa-envelope"></i>
                    <input type="email" value="{{ old('email') }}" placeholder="Correo" name="email" id="email" autocomplete="username" autofocus>
                </div>

                <span class="pos-label">Contraseña</span>
                <div class="pos-login-field">
                    <i class="fa fa-lock"></i>
                    <input type="password" placeholder="Contraseña" name="password" id="password" autocomplete="current-password">
                </div>

                <span class="pos-label">Tipo de cambio (Bs por US$)</span>
                <div class="pos-login-field">
                    <i class="fa fa-usd"></i>
                    <input type="text" value="{{ old('exchangeRate', number_format($exchangeRateCurrent ?: 6.96, 2, '.', '')) }}" readonly name="exchangeRate" id="loginExchangeRate">
                    <button type="button" class="pos-login-tc" onclick="updateExchangeRate()" title="Un administrador debe escribir su usuario y contraseña arriba">
                        <i class="fa fa-pencil"></i> Cambiar
                    </button>
                </div>

                <button type="submit" id="btnSave" name="btnLogin" class="pos-btn-invoice">
                    <i class="fa fa-sign-in"></i> INGRESAR
                </button>
            </div>
        </form>
    </div>
</div>
