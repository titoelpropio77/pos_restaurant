<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    @include('partials.brand')
    @if ($authenticated)
        <meta name="viewport" content="width=device-width, initial-scale=1">
    @else
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
    @endif
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="msapplication-tap-highlight" content="no">
    <link rel="stylesheet" href="{{ asset('legacy/css/bootstrap/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy/css/puglins/font-awesome.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy/css/puglins/jquery-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy/css/bootstrap/datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy/css/bootstrap/dataTables.bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy/jQuery-contextMenu/jquery.contextMenu.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy/css/miestilo.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy/css/puglins/alertify.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy/css/puglins/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('legacy/css/stylesautocomplete.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pos/pos.css') }}?v={{ filemtime(public_path('css/pos/pos.css')) }}">
    <style>
        .border { border-style: inset; border-width: medium; }
    </style>
    <script>
        // Variables globales que usa pos.js (antes generadas desde $_SESSION y los archivos verificadores *.txt)
        var months = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
        var daysWeek = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
        var f = new Date();
        changeDateCustom = 0;
        counterProcessing = 0;
        @if($authenticated)
            idBranch = {{ $pos->branchId() }};
            multiBranchSales = {{ $pos->multiBranchSales() ? 1 : 0 }};
        @endif
        cdgmes = "{{ $options['code_waiter'] ? 'waiter_code' : 'no_waiter_code' }}";
        dining_room = "{{ $options['dining_room'] ? 'dining_room' : 'no_dining_room' }}";
        waiters_no_code = "{{ $options['waiters_without_code'] ? 'waiters_no_code' : 'no_waiters_no_code' }}";
        dailyClose = "{{ $options['daily_close'] ? 'dailyClose' : 'nocierreDaily' }}";
        billiards = "{{ $options['billiards'] ? 'billiards' : 'no_billiards' }}";
        percentRest = "{{ $options['percentage_restaurant'] ? 'porceRest' : 'noporceRestr' }}";
        no_staff_view = "{{ $options['without_view_employee'] ? 'no_staff_view' : 'with_staff_view' }}";
        cloud_print = "{{ $options['print_cloud'] ? 'cloud_print' : 'no_cloud_print' }}";
        backup = "no_backup";
        change_date = {{ $options['change_date'] ? 'true' : 'false' }};
        transactionTigoMoney = "";
        CURRENT_USER = "{{ $user?->username }}";
        idTableProcessing = "{{ $authenticated ? $pos->tableProcessingId() : '' }}";

        // Endpoints de Laravel que reemplazan a legacy/CONTROLADORES/*.php
        var ROUTES = @json($routes);

        function movesClock() {
            var momentCurrent = new Date();
            var timePrintable = ("0" + momentCurrent.getHours()).slice(-2) + ":" + ("0" + momentCurrent.getMinutes()).slice(-2) + ":" + ("0" + momentCurrent.getSeconds()).slice(-2);
            $('#spanProcessing').css('color', counterProcessing == 0 ? 'yellow' : '#000000');
            counterProcessing = counterProcessing == 0 ? 1 : 0;
            document.getElementById("h1_clock").innerText = timePrintable;
            setTimeout(movesClock, 1000);
            return timePrintable;
        }
    </script>
</head>
<body class="{{ $authenticated ? 'pos-body' : 'pos-body-login' }}" onload="movesClock()">

@include('pos.partials.login')
@include('pos.partials.modals')

<div class="pos-app">
    {{-- Barra superior --}}
    <header class="pos-topbar">
        <div class="pos-brand">
            <img class="pos-brand-logo" src="{{ asset('assets/brand/logo-dark.png') }}" alt="Mesatek">
            <img class="pos-brand-icon" src="{{ asset('assets/brand/icon-dark.png') }}" alt="Mesatek">
        </div>

        <div class="pos-search">
            <i class="fa fa-search"></i>
            <input type="text" id="inputSearchProduct" onkeyup="searchProductKeyUp(this)" placeholder="Buscar producto (mín. 3 letras)" autocomplete="off">
        </div>

        <div class="pos-chips">
            @include('partials.restaurant-switch', ['class' => 'pos-restaurant-select'])
            <label class="pos-chip pos-chip-select" title="Sucursal">
                <i class="fa fa-building-o"></i>
                <select id="selectBranch" @if ($pos->multiBranchSales()) onchange="changeBranch($(this).val())" @endif>
                    <option>{{ $pos->branchName() }}</option>
                </select>
            </label>
            <span class="pos-chip" id="labelShift" title="Turno"><i class="fa fa-sun-o"></i><span id="shift_value"></span></span>
            <span class="pos-chip" id="labelExchangeRate" title="Tipo de cambio">
                <i class="fa fa-usd"></i>T/C <span id="spanExchangeRate">{{ number_format($pos->exchangeRate(), 2, ',', ' ') }}<input id="exchangeRate" type="hidden" value="{{ $pos->exchangeRate() }}"></span>
            </span>
            <span class="pos-chip" id="labelUser" title="Usuario"><i class="fa fa-user"></i><span id="spanUser"></span></span>
            <div class="pos-clock">
                <span id="h1_clock"></span>
                <small id="h3CurrentDate"></small>
            </div>
            <form method="POST" action="{{ route('logout') }}" id="fromLogin" class="pos-topbar-actions" onkeypress="if (event.keyCode == 13) event.returnValue = false;">
                @csrf
                @if ($user?->canAccessPanel())
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
                <li class="active"><a href="#tab_1" data-toggle="tab" id="aBilling"><i class="fa fa-cutlery"></i> Menú</a></li>
                <li><a href="#tab_4" data-toggle="tab" onclick="listTables()"><i class="fa fa-th-large"></i> <span id="aTables">Mesas</span> <span class="pos-badge" id="spanQtyTable">0</span></a></li>
                <li><a href="#tab_2" data-toggle="tab"><i class="fa fa-exchange"></i> Ingreso / Egreso</a></li>
                <li><a href="#tab_3" data-toggle="tab"><i class="fa fa-motorcycle"></i> Domicilio</a></li>
                <li><a href="#tab_5" data-toggle="tab"><i class="fa fa-bell-o"></i> Pedidos online</a></li>
            </ul>

            <div class="tab-content pos-tab-content">
                <div class="tab-pane active pos-pane-menu" id="tab_1">
                    <div id="divMenuGroup" class="pos-categories"></div>
                    <div id="divProduct" class="pos-products"></div>
                </div>

                <div class="tab-pane pos-pane" id="tab_4">
                    <div class="pos-pane-header">
                        <h4>Salón</h4>
                        <select id="selectWaiterTables" class="form-control">
                            <option value="0">SELECCIONE EL MESERO</option>
                        </select>
                    </div>
                    <div id="divTable" class="pos-diningTables"></div>
                    <p class="pos-hint"><i class="fa fa-info-circle"></i> Toque una mesa libre para abrirla con los productos del ticket, u ocupada para ver el detalle o agregar.</p>
                </div>

                <div class="tab-pane pos-pane" id="tab_2">
                    <div class="pos-toolbar">
                        <button class="btn btn-primary" id="btnExpensesNew">NUEVO</button>
                        <button class="btn btn-default" id="btnExpensesVoid">ANULAR</button>
                        <button class="btn btn-success" id="btnExpensesSave" onclick="saveExpense()">GRABAR</button>
                        <button class="btn btn-link" id="btnExpensesCancel" onclick="$('#btnExpensesNew').text('NUEVO'),
                            $('#inputExpensesDelivered').attr('readonly', true),
                            $('#inputExpensesConcept').attr('readonly', true),
                            $('#inputExpensesAmount').attr('readonly', true),
                            $('#inputInvoiceExpense').attr('readonly', true),
                            $('#inputExpensesDelivered').val(''),
                            $('#inputExpensesConcept').val(''),
                            $('#inputExpensesAmount').val(''),
                            $('#inputInvoiceExpense').val('');
                            $('#dateInvoice').val('');
                            $('#taxIdSupplier').val('');
                            $('#numberAuthorization').val('');
                            $('#controlCode').val('');
                            $('#inputNumberInvoice').val('');
                            $('#checkboxTaxCredit').prop('checked',false);
                            $('#checkboxIncludesInvoice').prop('checked',false);
                            $('#inputDebitFiscal').val('');">CANCELAR</button>
                    </div>
                    <div class="pos-form-grid">
                        <label>Nro.<input type="number" id="inputExpensesNumber" class="form-control" disabled></label>
                        <label class="pos-span-2">Entregado a<input type="text" id="inputExpensesDelivered" class="form-control" readonly></label>
                        <label class="pos-span-2">Concepto<input type="text" id="inputExpensesConcept" class="form-control" readonly></label>
                        <label>Monto Bs.<input type="number" id="inputExpensesAmount" class="form-control" step="0.001" readonly onchange="($('#inputDebitFiscal').val(this.value*0.13)).toFixed(2)"></label>
                    </div>
                    <div class="pos-checks">
                        <label><input type="checkbox" value="1" id="checkboxIncludesInvoice" onclick="activateValue(this)"> Incluye factura</label>
                        <label><input type="checkbox" checked id="checkboxLeftCashRegister"> ¿Salió de caja?</label>
                    </div>
                    <div class="pos-table-wrap">
                        <table class="table pos-table">
                            <thead><tr><th>Nro.</th><th>Entregado</th><th>Concepto</th><th class="text-right">Monto</th><th>Fecha</th><th>Usuario</th></tr></thead>
                            <tbody id="tbodyExpenses"></tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane pos-pane" id="tab_3">
                    <div class="pos-form-grid">
                        <label>Teléfono<input type="text" class="form-control" id="phoneCustomer" placeholder="Teléfono"></label>
                        <label class="pos-span-2">Nombre del cliente<input type="text" class="form-control" id="NameCustomerDelivery" placeholder="Nombre del cliente"></label>
                        <label>NIT<input type="text" class="form-control" id="phoneTaxIdCustomer" placeholder="NIT del cliente"></label>
                        <label class="pos-span-4">Dirección<textarea id="addressCustomer" rows="4" class="form-control"></textarea></label>
                    </div>
                    <div class="pos-toolbar">
                        <button class="btn btn-success" onclick="getAddressPhone()"><i class="fa fa-check"></i> USAR EN EL TICKET</button>
                        <button class="btn btn-default" data-toggle="modal" data-target="#ModalListCustomer"><i class="fa fa-users"></i> LISTAR CLIENTES</button>
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
                    <input type="text" id="order_number" class="pos-order-number" readonly value="{{ $pos->dailyOrder() }}">
                    <span id="spanProcessing" class="pos-processing"></span>
                </div>
                <div class="pos-ticket-head-actions">
                    <button class="btn btn-default btn-sm" data-toggle="modal" data-target="#ModalHistoryDay" onclick="listHistory()"><i class="fa fa-history"></i> Histórico</button>
                    <button class="btn btn-default btn-sm" onclick="clearDetail()"><i class="fa fa-file-o"></i> Nuevo</button>
                </div>
            </div>

            <div class="pos-customer">
                <input id="nameCustomer" class="form-control" value="SIN NOMBRE" placeholder="Nombre del cliente" onClick="this.setSelectionRange(0, this.value.length)">
                <input type="text" id="customerTaxId" class="form-control" placeholder="NIT / CI" onClick="this.setSelectionRange(0, this.value.length)">
                <button class="btn btn-default" data-toggle="modal" data-target="#ModalListCustomer" title="Listar clientes"><i class="fa fa-address-book-o"></i></button>
            </div>
            <div class="pos-code">
                <input type="text" class="form-control" id="inputBarcode" onchange="verifyBarcodeProd(this)" placeholder="Código de barras">
                <input type="text" class="form-control" id="inputQtyBarcode" placeholder="Cant." onchange="addQtyProdBarcode(this)">
            </div>

            <button id="buttonHidden" tipoProducto="" subTotal="" nombre="df" precio="1" idPro="2" cantidad="0" style="display: none">oculto</button>

            <div class="pos-detail">
                <table class="table pos-table" id="tableDetail">
                    <thead>
                        <tr><th class="text-right">Cant</th><th>Descripción</th><th class="text-right">Precio</th><th class="text-right">Subtotal</th><th></th></tr>
                    </thead>
                    <tbody id="tbodyDetail"></tbody>
                </table>
                <div class="pos-detail-empty"><i class="fa fa-shopping-basket"></i><span>Toque un producto para agregarlo</span></div>
            </div>

            <div class="pos-ticket-foot">
                <div class="pos-options">
                    <label><input id="inputCard" type="checkbox" onclick="loadTg()"> <i class="fa fa-credit-card"></i> Tarjeta</label>
                    <label><input type="checkbox" id="checkCodeQr"> <i class="fa fa-qrcode"></i> Código QR</label>
                </div>

                <form name="quantities" class="pos-keypad" onsubmit="return false">
                    <input type="text" class="form-control" id="qty" name="ans" placeholder="Cantidad">
                    @for ($i = 1; $i < 10; $i++)
                        <button type="button" numero="{{ $i }}" onClick="document.cantidades.ans.value+={{ $i }}">{{ $i }}</button>
                    @endfor
                    <button type="button" onClick="document.cantidades.ans.value+='.'">.</button>
                    <button type="button" onClick="document.cantidades.ans.value+='0'">0</button>
                    <button type="reset" class="pos-keypad-clear">C</button>
                </form>

                <div class="pos-totals">
                    <div><span class="pos-label">Total Bs</span><input type="text" id="totalBs" value="0.00" readonly></div>
                    <div class="pos-total-usd"><span class="pos-label">Total US$</span><input type="text" id="totalUsd" value="0.00" readonly></div>
                </div>
                <button class="pos-btn-invoice" onclick="fillDataModalF()" salida="" id="btnInvoice"><i class="fa fa-check-circle"></i> COBRAR <small>F12</small></button>
            </div>
        </aside>
    </main>
</div>

<div id="loading">
    <img src="{{ asset('legacy/images/loading1.gif') }}" style="margin: -29px 0 0 -30px; position: absolute; top: 35%; left: 39%; display: block; height: 32%;">
</div>

<script src="{{ asset('legacy/js/plugins/jquery.js') }}"></script>
<script>
    // El frontend heredado parsea cada respuesta con JSON.parse(res): se piden como texto.
    $.ajaxSetup({
        dataType: 'text',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'X-Requested-With': 'XMLHttpRequest' }
    });
</script>
<script src="{{ asset('legacy/js/bootstrap/bootstrap.js') }}"></script>
<script src="{{ asset('legacy/js/plugins/alertify.js') }}"></script>
<script src="{{ asset('legacy/js/plugins/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('legacy/js/bootstrap/dataTables.bootstrap.js') }}"></script>
<script src="{{ asset('legacy/js/bootstrap/bootstrap-datepicker.js') }}"></script>
<script src="{{ asset('legacy/fullcalendar/lib/moment.min.js') }}"></script>
<script src="{{ asset('legacy/jQuery-contextMenu/jquery.contextMenu.js') }}"></script>
<script src="{{ asset('legacy/js/plugins/jquery.autocomplete.js') }}"></script>
<script src="{{ asset('legacy/js/plugins/toastr.min.js') }}"></script>
<script src="{{ asset('legacy/js/literal.js') }}"></script>
<script src="{{ asset('legacy/js/plugins/HERRAMIENTAS.js') }}"></script>

@if (! $authenticated)
    <script>$('#ModalLogin').modal('show');</script>
@else
    <script>
        $("#spanUser").text(" {{ $user->username }}");
        @if (session('error'))
            alertify.error(@json(session('error')));
        @endif
        login = "{{ $user->username }}";
        begintime = "{{ $pos->timeStartMorning() }}";
        endtime = "{{ $pos->timeEndMorning() }}";
        vf_sale = "";
        f_sale = new Date();
        counter = 1;

        // Turno MAÑANA entre hora inicio y fin; antes de la hora de inicio la venta pertenece al día anterior
        function myTimer() {
            var d = new Date();
            var htost = ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
            var shift_value;
            if (changeDateCustom == 0) {
                vf_sale = d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate();
                if (htost >= begintime && htost <= endtime) {
                    shift_value = "MAÑANA";
                } else {
                    shift_value = "NOCHE";
                    if (htost < begintime) {
                        f_sale = subtractDays(new Date());
                        vf_sale = f_sale.getFullYear() + "-" + (f_sale.getMonth() + 1) + "-" + f_sale.getDate();
                        if (counter == 1) {
                            $('#dateHistory').val(f_sale.getDate() + "/" + (f_sale.getMonth() + 1) + "/" + f_sale.getFullYear());
                            counter = 0;
                        }
                    }
                }
                document.title = vf_sale + " {{ config('app.name') }}";
            } else {
                shift_value = "NOCHE";
            }
            document.getElementById("shift_value").innerHTML = shift_value;
        }
        function subtractDays(date) {
            date.setDate(date.getDate() - 1);
            return date;
        }
        setInterval(myTimer, 1000);
        $.fn.datepicker.defaults.format = "dd/mm/yyyy";
        $('.datepicker').datepicker({ autoclose: true, dateFormat: 'dd/mm/yyyy' })
            .datepicker("setDate", f_sale)
            .on('changeDate', function () { listHistory(); });
        myTimer();
    </script>
    <script src="{{ asset('js/pos/print-agent.js') }}?v={{ filemtime(public_path('js/pos/print-agent.js')) }}"></script>
    <script src="{{ asset('js/pos/pos.js') }}?v={{ filemtime(public_path('js/pos/pos.js')) }}"></script>
@endif

<script>
    document.getElementById("h3CurrentDate").innerText = daysWeek[f.getDay()] + ", " + f.getDate() + " de " + months[f.getMonth()] + " de " + f.getFullYear();
    if (idTableProcessing != "") {
        $.post(ROUTES.tablesReleaseProcessing, {idTable: idTableProcessing}, function () {
            idTableProcessing = "";
        }).fail(function (xhr, ajaxOptions, thrownError) {
            failErrors(xhr, ajaxOptions, thrownError);
        });
    }
    $('#loading').css('display', 'none');
    if (dining_room == "dining_room") {
        $('#aTables').text("Atención");
    }
    // Un administrador habilita la edición del tipo de cambio en la pantalla de ingreso
    function updateExchangeRate() {
        $.post(ROUTES.authorizeExchangeRate, {email: $('#email').val(), password: $('#password').val()}, function (res) {
            var json = JSON.parse(res);
            if (json.result == "enabled") {
                $('#loginExchangeRate').attr('readonly', false);
                alertify.success("AHORA PUEDE CAMBIAR EL TIPO DE CAMBIO");
            } else {
                alertify.error("NO HABILITADO PARA CAMBIAR EL TIPO DE CAMBIO");
                $('#loginExchangeRate').attr('readonly', true);
            }
        });
    }
    if (dailyClose == "nocierreDaily") {
        $('#btnDailyClose').css('display', 'none');
    }
    @if ($mustOpenCash)
        $(function () {
            $('#ModalOpenCash').modal('show');
        });
    @endif
</script>
</body>
</html>
