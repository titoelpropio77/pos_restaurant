<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS GOURMET</title>
    <link rel="icon" href="{{ asset('abms1/imagenes/logo.png') }}" type="image/x-icon" />
    @if ($autenticado)
        <meta name="viewport" content="width=device-width, initial-scale=1">
    @else
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
    @endif
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="msapplication-tap-highlight" content="no">
    <link rel="stylesheet" href="{{ asset('abms1/css/bootstrap/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('abms1/css/puglins/font-awesome.css') }}">
    <link rel="stylesheet" href="{{ asset('abms1/css/puglins/jquery-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('abms1/css/bootstrap/datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('abms1/css/bootstrap/dataTables.bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('abms1/jQuery-contextMenu/jquery.contextMenu.css') }}">
    <link rel="stylesheet" href="{{ asset('abms1/css/miestilo.css') }}">
    <link rel="stylesheet" href="{{ asset('abms1/css/puglins/alertify.css') }}">
    <link rel="stylesheet" href="{{ asset('abms1/css/puglins/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('abms1/css/stylesautocomplete.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pos/pos.css') }}?v={{ filemtime(public_path('css/pos/pos.css')) }}">
    <style>
        .border { border-style: inset; border-width: medium; }
    </style>
    <script>
        // Variables globales que usa posgourmet.js (antes generadas desde $_SESSION y los archivos verificadores *.txt)
        var meses = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
        var diasSemana = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
        var f = new Date();
        cambiarFechaPersonalizada = 0;
        contadorProcesando = 0;
        @if ($autenticado)
            idSucursal = {{ $pos->sucursalId() }};
            ventaMultialmacen = {{ $pos->ventaMultialmacen() ? 1 : 0 }};
        @endif
        cdgmes = "{{ $opciones['codigo_mesero'] ? 'cdgme' : 'nocdgme' }}";
        salon = "{{ $opciones['salon'] ? 'salon' : 'nosalon' }}";
        meserosc = "{{ $opciones['meseros_sin_codigo'] ? 'meserosc' : 'nomeserosc' }}";
        cierreDiario = "{{ $opciones['cierre_diario'] ? 'cierreDiario' : 'nocierreDiario' }}";
        billar = "{{ $opciones['billar'] ? 'billar' : 'nobillar' }}";
        porcRest = "{{ $opciones['porcentaje_restaurante'] ? 'porceRest' : 'noporceRestr' }}";
        sinvistaempleado = "{{ $opciones['sin_vista_empleado'] ? 'sinvistaempleado' : 'nosinvistaempleado' }}";
        impresionnube = "{{ $opciones['impresion_nube'] ? 'impresionnube' : 'noimpresionnube' }}";
        backup = "nobackup";
        cambiofecha = {{ $opciones['cambio_fecha'] ? 'true' : 'false' }};
        transaccionTigoMoney = "";
        USUARIOACTUAL = "{{ $usuario?->usuario }}";
        idMesaProcesando = "{{ $autenticado ? $pos->mesaProcesandoId() : '' }}";

        // Endpoints de Laravel que reemplazan a abms1/CONTROLADORES/*.php
        var RUTAS = @json($rutas);

        function mueveReloj() {
            var momentoActual = new Date();
            var horaImprimible = ("0" + momentoActual.getHours()).slice(-2) + ":" + ("0" + momentoActual.getMinutes()).slice(-2) + ":" + ("0" + momentoActual.getSeconds()).slice(-2);
            $('#spanProcesando').css('color', contadorProcesando == 0 ? 'yellow' : '#000000');
            contadorProcesando = contadorProcesando == 0 ? 1 : 0;
            document.getElementById("h1reloj").innerText = horaImprimible;
            setTimeout(mueveReloj, 1000);
            return horaImprimible;
        }
    </script>
</head>
<body class="{{ $autenticado ? 'pos-body' : 'pos-body-login' }}" onload="mueveReloj()">

@include('pos.partials.login')
@include('pos.partials.modales')

<div class="pos-app">
    {{-- Barra superior --}}
    <header class="pos-topbar">
        <div class="pos-brand">
            <img src="{{ asset('abms1/imagenes/logo.png') }}" alt="">
            <span>POS<strong>Gourmet</strong></span>
        </div>

        <div class="pos-search">
            <i class="fa fa-search"></i>
            <input type="text" id="inputBuscarProducto" onkeyup="buscarProductoKeyUp(this)" placeholder="Buscar producto (mín. 3 letras)" autocomplete="off">
        </div>

        <div class="pos-chips">
            <label class="pos-chip pos-chip-select" title="Sucursal">
                <i class="fa fa-building-o"></i>
                <select id="selectSucursal" @if ($pos->ventaMultialmacen()) onchange="cambiarSucursal($(this).val())" @endif>
                    <option>{{ $pos->sucursalNombre() }}</option>
                </select>
            </label>
            <span class="pos-chip" id="labelTurno" title="Turno"><i class="fa fa-sun-o"></i><span id="vturno"></span></span>
            <span class="pos-chip" id="labelTipoCambio" title="Tipo de cambio">
                <i class="fa fa-usd"></i>T/C <span id="spanTipoCambio">{{ number_format($pos->tipoCambio(), 2, ',', ' ') }}<input id="tipoCambio" type="hidden" value="{{ $pos->tipoCambio() }}"></span>
            </span>
            <span class="pos-chip" id="labelUsuario" title="Usuario"><i class="fa fa-user"></i><span id="spanUsuario"></span></span>
            <div class="pos-clock">
                <span id="h1reloj"></span>
                <small id="h3FechaActual"></small>
            </div>
            <form method="POST" action="{{ route('logout') }}" id="fromLogin" class="pos-topbar-actions" onkeypress="if (event.keyCode == 13) event.returnValue = false;">
                @csrf
                @if ($usuario?->esAdministrador())
                    <a href="{{ route('admin.dashboard') }}" class="pos-icon-btn" title="Panel de administración"><i class="fa fa-cog"></i></a>
                @endif
                <button class="pos-icon-btn pos-icon-btn-danger" name="btnlogout" title="Salir"><i class="fa fa-sign-out"></i></button>
            </form>
        </div>
    </header>

    <main class="pos-main">
        {{-- Zona de trabajo: menú, mesas, caja, domicilio --}}
        <section class="pos-workspace">
            <ul class="nav pos-tabs">
                <li class="active"><a href="#tab_1" data-toggle="tab" id="aFacturacion"><i class="fa fa-cutlery"></i> Menú</a></li>
                <li><a href="#tab_4" data-toggle="tab" onclick="listarMesas()"><i class="fa fa-th-large"></i> <span id="aMesas">Mesas</span> <span class="pos-badge" id="spanCantMesa">0</span></a></li>
                <li><a href="#tab_2" data-toggle="tab"><i class="fa fa-exchange"></i> Ingreso / Egreso</a></li>
                <li><a href="#tab_3" data-toggle="tab"><i class="fa fa-motorcycle"></i> Domicilio</a></li>
                <li><a href="#tab_5" data-toggle="tab"><i class="fa fa-bell-o"></i> Pedidos online</a></li>
            </ul>

            <div class="tab-content pos-tab-content">
                <div class="tab-pane active pos-pane-menu" id="tab_1">
                    <div id="divMenuGrupo" class="pos-categorias"></div>
                    <div id="divProducto" class="pos-productos"></div>
                </div>

                <div class="tab-pane pos-pane" id="tab_4">
                    <div class="pos-pane-header">
                        <h4>Salón</h4>
                        <select id="selectMeseroMesas" class="form-control">
                            <option value="0">SELECCIONE EL MESERO</option>
                        </select>
                    </div>
                    <div id="divMesa" class="pos-mesas"></div>
                    <p class="pos-hint"><i class="fa fa-info-circle"></i> Toque una mesa libre para abrirla con los productos del ticket, u ocupada para ver el detalle o agregar.</p>
                </div>

                <div class="tab-pane pos-pane" id="tab_2">
                    <div class="pos-toolbar">
                        <button class="btn btn-primary" id="btnGastosNuevo">NUEVO</button>
                        <button class="btn btn-default" id="btnGastosAnular">ANULAR</button>
                        <button class="btn btn-success" id="btnGastosGrabar" onclick="guardarGasto()">GRABAR</button>
                        <button class="btn btn-link" id="btnGastosCancelar" onclick="$('#btnGastosNuevo').text('NUEVO'),
                            $('#inputGastosEntregado').attr('readonly', true),
                            $('#inputGastosConcepto').attr('readonly', true),
                            $('#inputGastosMonto').attr('readonly', true),
                            $('#inputFacturaEgreso').attr('readonly', true),
                            $('#inputGastosEntregado').val(''),
                            $('#inputGastosConcepto').val(''),
                            $('#inputGastosMonto').val(''),
                            $('#inputFacturaEgreso').val('');
                            $('#fechaFactura').val('');
                            $('#nitProveedor').val('');
                            $('#nroAutorizacion').val('');
                            $('#codigoControl').val('');
                            $('#inputNroFactura').val('');
                            $('#checkboxCreditoFiscal').prop('checked',false);
                            $('#checkboxIncluyeFactura').prop('checked',false);
                            $('#inputDebitoFiscal').val('');">CANCELAR</button>
                    </div>
                    <div class="pos-form-grid">
                        <label>Nro.<input type="number" id="inputGastosNro" class="form-control" disabled></label>
                        <label class="pos-span-2">Entregado a<input type="text" id="inputGastosEntregado" class="form-control" readonly></label>
                        <label class="pos-span-2">Concepto<input type="text" id="inputGastosConcepto" class="form-control" readonly></label>
                        <label>Monto Bs.<input type="number" id="inputGastosMonto" class="form-control" step="0.001" readonly onchange="($('#inputDebitoFiscal').val(this.value*0.13)).toFixed(2)"></label>
                    </div>
                    <div class="pos-checks">
                        <label><input type="checkbox" value="1" id="checkboxIncluyeFactura" onclick="activarIval(this)"> Incluye factura</label>
                        <label><input type="checkbox" checked id="checkboxSalioCaja"> ¿Salió de caja?</label>
                    </div>
                    <div class="pos-table-wrap">
                        <table class="table pos-table">
                            <thead><tr><th>Nro.</th><th>Entregado</th><th>Concepto</th><th class="text-right">Monto</th><th>Fecha</th><th>Usuario</th></tr></thead>
                            <tbody id="tbodyGastos"></tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane pos-pane" id="tab_3">
                    <div class="pos-form-grid">
                        <label>Teléfono<input type="text" class="form-control" id="telefonoCliente" placeholder="Teléfono"></label>
                        <label class="pos-span-2">Nombre del cliente<input type="text" class="form-control" id="NombreClienteDomicilio" placeholder="Nombre del cliente"></label>
                        <label>NIT<input type="text" class="form-control" id="telefonoNitCliente" placeholder="NIT del cliente"></label>
                        <label class="pos-span-4">Dirección<textarea id="direccionCliente" rows="4" class="form-control"></textarea></label>
                    </div>
                    <div class="pos-toolbar">
                        <button class="btn btn-success" onclick="obtenerDireccionTelf()"><i class="fa fa-check"></i> USAR EN EL TICKET</button>
                        <button class="btn btn-default" data-toggle="modal" data-target="#ModalListarCliente"><i class="fa fa-users"></i> LISTAR CLIENTES</button>
                    </div>
                </div>

                <div class="tab-pane pos-pane" id="tab_5">
                    <p class="pos-hint"><i class="fa fa-bell-o"></i> No hay pedidos online.</p>
                </div>
            </div>
        </section>

        {{-- Ticket de la venta en curso --}}
        <aside class="pos-ticket">
            <div class="pos-ticket-head">
                <div>
                    <span class="pos-label">Orden</span>
                    <input type="text" id="orden" class="pos-orden" readonly value="{{ $pos->ordenDia() }}">
                    <span id="spanProcesando" class="pos-procesando"></span>
                </div>
                <div class="pos-ticket-head-actions">
                    <button class="btn btn-default btn-sm" data-toggle="modal" data-target="#ModalaHistoricoDia" onclick="listarHistorico()"><i class="fa fa-history"></i> Histórico</button>
                    <button class="btn btn-default btn-sm" onclick="limpiarDetalle()"><i class="fa fa-file-o"></i> Nuevo</button>
                </div>
            </div>

            <div class="pos-cliente">
                <input id="nombreCli" class="form-control" value="SIN NOMBRE" placeholder="Nombre del cliente" onClick="this.setSelectionRange(0, this.value.length)">
                <input type="text" id="nitCli" class="form-control" placeholder="NIT / CI" onClick="this.setSelectionRange(0, this.value.length)">
                <button class="btn btn-default" data-toggle="modal" data-target="#ModalListarCliente" title="Listar clientes"><i class="fa fa-address-book-o"></i></button>
            </div>
            <div class="pos-codigo">
                <input type="text" class="form-control" id="inputCodigoBarra" onchange="verificarCodBarraProd(this)" placeholder="Código de barras">
                <input type="text" class="form-control" id="inputCantCodBarra" placeholder="Cant." onchange="agregarCantProdCodBarra(this)">
            </div>

            <button id="botonOculto" tipoProducto="" subTotal="" nombre="df" precio="1" idPro="2" cantidad="0" style="display: none">oculto</button>

            <div class="pos-detalle">
                <table class="table pos-table" id="tablaDetalle">
                    <thead>
                        <tr><th class="text-right">Cant</th><th>Descripción</th><th class="text-right">Precio</th><th class="text-right">Subtotal</th><th></th></tr>
                    </thead>
                    <tbody id="tbdoyDetalle"></tbody>
                </table>
                <div class="pos-detalle-vacio"><i class="fa fa-shopping-basket"></i><span>Toque un producto para agregarlo</span></div>
            </div>

            <div class="pos-ticket-foot">
                <div class="pos-opciones">
                    <label><input id="inputTarjeta" type="checkbox" onclick="cargarTG()"> <i class="fa fa-credit-card"></i> Tarjeta</label>
                    <label><input type="checkbox" id="checkCodigoQr"> <i class="fa fa-qrcode"></i> Código QR</label>
                </div>

                <form name="cantidades" class="pos-keypad" onsubmit="return false">
                    <input type="text" class="form-control" id="cant" name="ans" placeholder="Cantidad">
                    @for ($i = 1; $i < 10; $i++)
                        <button type="button" numero="{{ $i }}" onClick="document.cantidades.ans.value+={{ $i }}">{{ $i }}</button>
                    @endfor
                    <button type="button" onClick="document.cantidades.ans.value+='.'">.</button>
                    <button type="button" onClick="document.cantidades.ans.value+='0'">0</button>
                    <button type="reset" class="pos-keypad-clear">C</button>
                </form>

                <div class="pos-totales">
                    <div><span class="pos-label">Total Bs</span><input type="text" id="totalBs" value="0.00" readonly></div>
                    <div class="pos-total-usd"><span class="pos-label">Total US$</span><input type="text" id="totalUsd" value="0.00" readonly></div>
                </div>
                <button class="pos-btn-facturar" onclick="llenarDatosModalF()" salida="" id="btnFacturar"><i class="fa fa-check-circle"></i> COBRAR <small>F12</small></button>
            </div>
        </aside>
    </main>
</div>

<div id="loading">
    <img src="{{ asset('abms1/imagenes/loading1.gif') }}" style="margin: -29px 0 0 -30px; position: absolute; top: 35%; left: 39%; display: block; height: 32%;">
</div>

<script src="{{ asset('abms1/js/plugins/jquery.js') }}"></script>
<script>
    // El frontend heredado parsea cada respuesta con JSON.parse(res): se piden como texto.
    $.ajaxSetup({
        dataType: 'text',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'X-Requested-With': 'XMLHttpRequest' }
    });
</script>
<script src="{{ asset('abms1/js/bootstrap/bootstrap.js') }}"></script>
<script src="{{ asset('abms1/js/plugins/alertify.js') }}"></script>
<script src="{{ asset('abms1/js/plugins/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('abms1/js/bootstrap/dataTables.bootstrap.js') }}"></script>
<script src="{{ asset('abms1/js/bootstrap/bootstrap-datepicker.js') }}"></script>
<script src="{{ asset('abms1/fullcalendar/lib/moment.min.js') }}"></script>
<script src="{{ asset('abms1/jQuery-contextMenu/jquery.contextMenu.js') }}"></script>
<script src="{{ asset('abms1/js/plugins/jquery.autocomplete.js') }}"></script>
<script src="{{ asset('abms1/js/plugins/toastr.min.js') }}"></script>
<script src="{{ asset('abms1/js/literal.js') }}"></script>
<script src="{{ asset('abms1/js/plugins/HERRAMIENTAS.js') }}"></script>

@if (! $autenticado)
    <script>$('#ModalLogin').modal('show');</script>
@else
    <script>
        $("#spanUsuario").text(" {{ $usuario->usuario }}");
        login = "{{ $usuario->usuario }}";
        begintime = "{{ $pos->horaInicioManana() }}";
        endtime = "{{ $pos->horaFinManana() }}";
        vf_Venta = "";
        f_Venta = new Date();
        contador = 1;

        // Turno MAÑANA entre hora inicio y fin; antes de la hora de inicio la venta pertenece al día anterior
        function myTimer() {
            var d = new Date();
            var htost = ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
            var vturno;
            if (cambiarFechaPersonalizada == 0) {
                vf_Venta = d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate();
                if (htost >= begintime && htost <= endtime) {
                    vturno = "MAÑANA";
                } else {
                    vturno = "NOCHE";
                    if (htost < begintime) {
                        f_Venta = restarDias(new Date());
                        vf_Venta = f_Venta.getFullYear() + "-" + (f_Venta.getMonth() + 1) + "-" + f_Venta.getDate();
                        if (contador == 1) {
                            $('#fechaHistorico').val(f_Venta.getDate() + "/" + (f_Venta.getMonth() + 1) + "/" + f_Venta.getFullYear());
                            contador = 0;
                        }
                    }
                }
                document.title = vf_Venta + " POS GOURMET";
            } else {
                vturno = "NOCHE";
            }
            document.getElementById("vturno").innerHTML = vturno;
        }
        function restarDias(fecha) {
            fecha.setDate(fecha.getDate() - 1);
            return fecha;
        }
        setInterval(myTimer, 1000);
        $.fn.datepicker.defaults.format = "dd/mm/yyyy";
        $('.datepicker').datepicker({ autoclose: true, dateFormat: 'dd/mm/yyyy' })
            .datepicker("setDate", f_Venta)
            .on('changeDate', function () { listarHistorico(); });
        myTimer();
    </script>
    <script src="{{ asset('js/pos/posgourmet.js') }}?v={{ filemtime(public_path('js/pos/posgourmet.js')) }}"></script>
@endif

<script>
    document.getElementById("h3FechaActual").innerText = diasSemana[f.getDay()] + ", " + f.getDate() + " de " + meses[f.getMonth()] + " de " + f.getFullYear();
    if (idMesaProcesando != "") {
        $.post(RUTAS.mesasLiberarProcesando, {idMesa: idMesaProcesando}, function () {
            idMesaProcesando = "";
        }).fail(function (xhr, ajaxOptions, thrownError) {
            failErrors(xhr, ajaxOptions, thrownError);
        });
    }
    $('#loading').css('display', 'none');
    if (salon == "salon") {
        $('#aMesas').text("Atención");
    }
    // Un administrador habilita la edición del tipo de cambio en la pantalla de ingreso
    function actualizarTipoCambio() {
        $.post(RUTAS.autorizarTipoCambio, {usuario: $('#usuario').val(), password: $('#password').val()}, function (res) {
            var json = JSON.parse(res);
            if (json.result == "habilitado") {
                $('#loginTipoCambio').attr('readonly', false);
                alertify.success("AHORA PUEDE CAMBIAR EL TIPO DE CAMBIO");
            } else {
                alertify.error("NO HABILITADO PARA CAMBIAR EL TIPO DE CAMBIO");
                $('#loginTipoCambio').attr('readonly', true);
            }
        });
    }
    if (cierreDiario == "nocierreDiario") {
        $('#btnCierreDiario').css('display', 'none');
    }
</script>
</body>
</html>
