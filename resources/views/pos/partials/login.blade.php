{{-- Ingreso al POS (legacy: modalLogin.php). Se conservan los id que usa el JS. --}}
<div class="modal fade in pos-login" id="ModalLogin" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('login') }}">
            @csrf
            <div class="pos-login-head">
                <h1><img src="{{ asset('assets/marca/logo-claro-lema.png') }}" alt="Mesatek, sistema para restaurantes"></h1>
                <p>Ingrese sus datos para abrir la caja</p>
            </div>

            <div class="pos-login-body">
                @if (session('error_login') || $errors->any())
                    <div class="alert alert-danger" id="divError">
                        <i class="fa fa-exclamation-circle"></i> {{ session('error_login') ?? $errors->first() }}
                    </div>
                @endif

                <span class="pos-label">Usuario</span>
                <div class="pos-login-field">
                    <i class="fa fa-user"></i>
                    <input type="text" style="text-transform: uppercase;" value="{{ old('usuario') }}" placeholder="Usuario" name="usuario" id="usuario" autocomplete="username" autofocus>
                </div>

                <span class="pos-label">Contraseña</span>
                <div class="pos-login-field">
                    <i class="fa fa-lock"></i>
                    <input type="password" placeholder="Contraseña" name="password" id="password" autocomplete="current-password">
                </div>

                <span class="pos-label">Tipo de cambio (Bs por US$)</span>
                <div class="pos-login-field">
                    <i class="fa fa-usd"></i>
                    <input type="text" value="{{ old('tipoCambio', number_format($tipoCambioActual ?: 6.96, 2, '.', '')) }}" readonly name="tipoCambio" id="loginTipoCambio">
                    <button type="button" class="pos-login-tc" onclick="actualizarTipoCambio()" title="Un administrador debe escribir su usuario y contraseña arriba">
                        <i class="fa fa-pencil"></i> Cambiar
                    </button>
                </div>

                <button type="submit" id="btnGuardar" name="btnLogin" class="pos-btn-facturar">
                    <i class="fa fa-sign-in"></i> INGRESAR
                </button>
            </div>
        </form>
    </div>
</div>
