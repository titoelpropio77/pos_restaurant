
<div class="modal fade in" id="ModalAddNote" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-sm">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>AGREGAR NOTA AL PRODUCTO <span id="titleProductNote"></span></b></h4>

            </div>
            <div class="modal-body" style=" overflow-x: auto;">
                <input type="text" name="" class="form-control" id="inputNote">
            </div>
            <div class="modal-footer">
                <button type="submit" id="btnSave" name="btnSaveSupplyProduct" class="btn btn-success" onclick="addNote()">AGREGAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        
            </div>
        </div>
    </div>
</div>
<div class="modal fade in pos-checkout" id="ModalInvoice" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="pos-checkout-head">
                <div>
                    <span class="pos-label">Total a cobrar</span>
                    <div class="pos-checkout-total">Bs <strong id="checkoutTotalBs">0.00</strong></div>
                    <div class="pos-checkout-total-usd">$US <span id="checkoutTotalUsd">0.00</span></div>
                </div>
                <div class="pos-checkout-head-info">
                    <span class="pos-checkout-service-type"><i class="fa fa-cutlery"></i> <span id="labelTakeaway">Llevar</span></span>
                    <span class="pos-checkout-literal" id="labelNumLiteral"></span>
                </div>
            </div>

            <div class="pos-checkout-body">
                <section class="pos-checkout-col">
                    <span class="pos-label">Pago recibido</span>
                    <label class="pos-checkout-amount">
                        <input type="radio" checked value="bs" name="typeCurrencyInvoice" id="radioCurrencyBsInvoice">
                        <span>Bs</span>
                        <input type="text" inputmode="decimal" autocomplete="off" id="totalInvoiceBs" placeholder="0.00" onclick="$('#radioCurrencyBsInvoice').click()" ondblclick="paymentExactCheckout(); saveDetail()" onkeyup="takeChange()">
                    </label>
                    <label class="pos-checkout-amount">
                        <input type="radio" value="dolar" name="typeCurrencyInvoice" id="radioCurrencyDollarInvoice">
                        <span>$US</span>
                        <input type="text" inputmode="decimal" autocomplete="off" id="totalInvoiceUs" placeholder="0.00" onclick="$('#radioCurrencyDollarInvoice').click()" onkeyup="takeChange()">
                    </label>

                    <div class="pos-checkout-billetes">
                        @foreach ([2 => '2boliviano.png', 5 => '5bs.png', 10 => '10BS.JPG', 20 => '20BS.JPG', 50 => '50BS.JPG', 100 => '100BS.JPG', 200 => '200BS.JPG'] as $value => $image)
                            <button type="button" class="{{ $value < 10 ? 'is-currency' : '' }}" onclick="billCheckout({{ $value }})" title="Agregar Bs {{ $value }}">
                                <img src="{{ asset('legacy/images/'.$image) }}" alt="Bs {{ $value }}">
                            </button>
                        @endforeach
                        <button type="button" class="pos-checkout-exact" onclick="paymentExactCheckout()">Exacto</button>
                    </div>
                </section>

                <section class="pos-checkout-col">
                    <div class="pos-checkout-keypad">
                        @for ($i = 1; $i < 10; $i++)
                            <button type="button" onclick="keyCheckout('{{ $i }}')">{{ $i }}</button>
                        @endfor
                        <button type="button" onclick="keyCheckout('.')">.</button>
                        <button type="button" onclick="keyCheckout('0')">0</button>
                        <button type="button" onclick="keyCheckout('delete')"><i class="fa fa-long-arrow-left"></i></button>
                        <button type="button" class="pos-checkout-clear" onclick="keyCheckout('C')">Limpiar</button>
                    </div>

                    <div class="pos-checkout-change" id="checkoutChange">
                        <span class="pos-label">Cambio</span>
                        <div class="pos-checkout-change-montos">
                            <label>Bs <input type="text" id="changeBs" readonly></label>
                            <label>$US <input type="text" id="changeUsd" readonly></label>
                        </div>
                    </div>
                </section>

                <section class="pos-checkout-col">
                    <span class="pos-label">Forma de pago</span>
                    <div class="pos-checkout-methods">
                        <label class="pos-checkout-option-name"><input id="inputCashInvoice" type="radio" name="paymentMethod" value="CONTADO" checked onclick="typePayment(this)"><i class="fa fa-money"></i> Contado</label>
                        <label class="pos-checkout-option-name"><input type="radio" name="paymentMethod" value="CREDITO" onclick="typePayment(this)"><i class="fa fa-credit-card"></i> Crédito</label>
                        <label class="pos-checkout-option-name"><input type="radio" name="paymentMethod" value="CONSUMO" onclick="typePayment(this)"><i class="fa fa-coffee"></i> Consumo interno</label>
                        <label class="pos-checkout-option-name"><input type="radio" name="paymentMethod" value="PREPAGO" onclick="typePayment(this)"><i class="fa fa-clock-o"></i> Pre pago</label>
                        <label class="pos-checkout-option-name"><input type="radio" name="paymentMethod" value="QR" onclick="typePayment(this)"><i class="fa fa-qrcode"></i> QR</label>
                    </div>
                    <div id="divSelectedCredit" style="display: none">
                        <select class="form-control" id="selectTypePaymentCredit" onchange="saveNumberTigoMon(this)">
                            <option value="tarjeta">Tarjeta</option>
                            <option value="direccion">Direccion</option>
                            <option value="tigomon">TigoMon</option>
                        </select>
                    </div>
                    <div id="divInputConsumptionInternal" style="display: none">
                        <input type="text" id="inputConsumptionInternal" class="form-control" placeholder="Detalle del consumo interno">
                    </div>
                    <div id="divSelectedPrePayment" style="display: none">
                        <select class="form-control" id="selectPrepaid">
                            <option value="CONTADO">Contado</option>
                            <option value="CREDITO">Credito</option>
                            <option value="CONSUMO">Consumo</option>
                            <option value="TARJETA">Tarjeta</option>
                            <option value="CORTESIA">Cortesia</option>
                        </select>
                    </div>

                    <label class="pos-checkout-field">
                        <span class="pos-label">Empleado</span>
                        <select class="form-control" id="selectSeller" name="selectSeller">
                            <option value="0">SELECCIONE UN EMPLEADO</option>
                        </select>
                    </label>
                    <label class="pos-checkout-field">
                        <span class="pos-label">N° de orden / mesa</span>
                        <input type="text" class="form-control" id="numberOrder" placeholder="Opcional">
                    </label>
                    <label class="pos-checkout-field">
                        <span class="pos-label">Fecha de venta <small>(sólo si es otro día)</small></span>
                        <input type="date" id="dateRateHis" onchange="changeDate($(this).val())" class="form-control">
                    </label>
                </section>
            </div>

            <div class="pos-checkout-foot">
                <input type="hidden" id="numLiteral">
                <button type="button" class="pos-checkout-volver" data-dismiss="modal" onclick="cancelCheckout()"><i class="fa fa-arrow-left"></i> Volver</button>
                <button type="submit" id="btnSaveDetail" class="pos-btn-invoice pos-checkout-confirmar" onclick="saveDetail(this)"><i class="fa fa-print"></i> COBRAR <small>imprimir factura</small></button>
            </div>
        </div>
    </div>
</div>

<!-- <div class="modal fade in" id="ModalTypeDePurchase" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-sm">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>AGREGAR NOTA AL PRODUCTO <span id="titleProductNote"></span></b></h4>
            </div>
            <div class="modal-body" style=" overflow-x: auto;">
                <input type="text"  class="form-control" id="inputNote">
            </div>
            <div class="modal-footer">
                <button type="submit" id="btnSave" name="btnSaveSupplyProduct" class="btn btn-success" onclick="addNote()">AGREGAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

            </div>

        </div>
    </div>

</div>

-->
<div class="modal fade in pos-history" id="ModalHistoryDay" role="dialog" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="pos-hist-head">
                <div class="pos-hist-title">
                    <i class="fa fa-history"></i>
                    <div>
                        <h4>Histórico de ventas</h4>
                        <small>Seleccione una venta para ver su detalle</small>
                    </div>
                </div>
                <div class="input-group date pos-hist-date">
                    <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
                    <input type="text" class="form-control datepicker" id="dateHistory" autocomplete="off">
                </div>
                <div class="pos-hist-actions">
                    @can('pos.cash.close')<button type="button" class="pos-hist-btn pos-hist-btn-closing" id="btnDailyClose" data-toggle="modal" data-target="#ModalDailyClose"><i class="fa fa-lock"></i> Cierre diario</button>@endcan
                    <button type="button" class="pos-hist-btn pos-hist-btn-volver" data-dismiss="modal"><i class="fa fa-arrow-left"></i> Volver</button>
                </div>
            </div>

            <div class="pos-hist-stats">
                <div class="pos-hist-stat"><span class="pos-label">Ventas</span><strong id="histResSales">0</strong></div>
                <div class="pos-hist-stat is-ok"><span class="pos-label">Activas</span><strong id="histResActive">0</strong></div>
                <div class="pos-hist-stat is-bad"><span class="pos-label">Anuladas</span><strong id="histResVoided">0</strong></div>
                <div class="pos-hist-stat is-total"><span class="pos-label">Total vendido</span><strong id="histResTotal">0.00</strong></div>
            </div>

            <div class="pos-hist-body">
                <section class="pos-hist-card pos-hist-list">
                    <div class="pos-hist-card-head">
                        <span class="pos-label">Ventas del día</span>
                        <div class="pos-hist-search">
                            <i class="fa fa-search"></i>
                            <input type="search" id="searchHistory" placeholder="Buscar orden, cajero, forma de pago..." autocomplete="off">
                        </div>
                    </div>
                    <table class="table table-hover" id="tableHistory" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Hora</th>
                                <th>Cajero</th>
                                <th>Forma pago</th>
                                <th>Salida</th>
                                <th>Turno</th>
                                <th class="text-right">Total</th>
                                <th>Estado</th>
                                <th>Anulado por</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tbodyHistory"></tbody>
                    </table>
                </section>

                <aside class="pos-hist-card pos-hist-detail">
                    <div class="pos-hist-card-head">
                        <span class="pos-label">Detalle de la venta</span>
                        <span class="pos-hist-time"><i class="fa fa-clock-o"></i> <span id="spanHistoryTime">--:--</span></span>
                    </div>
                    <div class="pos-hist-fields">
                        <label><span class="pos-label">Proceso</span><input type="text" class="form-control" id="processHistory" readonly></label>
                        <label class="pos-hist-field-width"><span class="pos-label">Cliente</span><input type="text" class="form-control" id="nameCustomerHistory" readonly></label>
                        <label><span class="pos-label">Folio D</span><input type="text" class="form-control" id="df" placeholder="0" readonly></label>
                        <label><span class="pos-label">Folio N</span><input type="text" class="form-control" id="td" placeholder="0" readonly></label>
                    </div>
                    <div class="pos-hist-items">
                        <table class="table" id="tableHistoryDetail">
                            <thead>
                                <tr>
                                    <th class="text-right">Cant.</th>
                                    <th>Descripción</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyHistoryDetail">
                                <tr class="pos-hist-empty"><td colspan="3"><i class="fa fa-hand-pointer-o"></i> Seleccione una venta de la lista</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pos-hist-total">
                        <span>Total</span>
                        <strong id="totalDetail">0.00</strong>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="ModalSuggestions" role="dialog" data-backdrop="static">
    <div class="modal-dialog">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>OPCIONES PARA: <span id="titleProductSuggestion"></span></b></h4>
            </div>
            <div class="modal-body" style=" overflow-x: auto;" id="divSuggestion">

            </div>
            <div class="modal-footer">
                <button type="submit" id="btnSave" name="btnSaveSupplyProduct" class="btn btn-success" onclick="addSuggestionProduct()">AGREGAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

            </div>

        </div>
    </div>

</div>

<div class="modal fade in" id="ModalExit" role="dialog" data-backdrop="static">
    <div class="modal-dialog" style="margin-top: 12%;">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>PEDIDOS PARA</b></h4>
            </div>
            <div class="modal-body" style=" overflow-x: auto;    text-align: center;" id="">
                <button class="btn btn-warning btn-lg" data-toggle="modal" data-target="#ModalKeypadTable">MESA</button>
                <button class="btn btn-warning btn-lg" onclick="typeExit(this)">LLEVAR</button>
                <button class="btn btn-warning btn-lg" onclick="typeExit(this)">AUTO</button>
                <button class="btn btn-warning btn-lg" onclick="typeExit(this)">MOTO</button>
            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

            </div>
        </div>
    </div>

</div>
<div class="modal fade in" id="ModalKeypadTable" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-sm" style="margin-top: 12%;">    
        <div class="modal-content">

            <div class="modal-body" style=" overflow-x: auto;    text-align: center;" id="">
                <form name="keypadTable" >

                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 " style=" ">
                        <div class="input-group input-group-sm">
                            <div class="col-sm-9 col-lg-9" style="padding: 0">
                                <input type="number" class="form-control" id="numTable" name="ans">
                            </div>
                            <div class="col-sm-3 col-lg-3" style="padding: 0">
                                <button type="reset"  style="background:  #E8961C;    width: 100%;" type="button" class="btn btn-info">C</button>
                            </div>

                        </div>
                        @for ($i = 1; $i < 10; $i++)
<button type="button" numero="{{ $i }}" class="btn btn-primary  col-lg-4 col-md-4 col-sm-4 col-xs-4" style="color:black;  font-weight: bold;BACKGROUND-COLOR: #E8961C;" onClick="document.tecladoMesa.ans.value+={{ $i }}">{{ $i }}</button>
@endfor
                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4" style=" ">
                        </div>
                        <button type ="button"  class="btn btn-primary  col-lg-4 col-md-4 col-sm-4 col-xs-4"  style="color:black;  font-weight: bold;BACKGROUND-COLOR: #E8961C;  " onClick="document.tecladoMesa.ans.value += 0" >0</button>
                    </div>
                </form>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-success pull-left"  salida="MESA" onclick="orderTable(this)">CONTINUAR</button>                        

                <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#numTable').val('')">VOLVER</button>                        

            </div>
        </div>
    </div>

</div>


<div class="modal fade in" id="ModalTableItem" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" style="    width: 94%;
    max-height: 70%;">    
    <div class="modal-content">
        <div class="modal-header">
            <span style="    text-align: left;    font-weight: bold;    font-size: 12pt;" id="spanEmpServed"></span><h4 class="modal-title" style="text-align: center"><b>DETALLE  : <SPAN id="spanModalTableItem"></SPAN> -  PERSONA:<SPAN id="spanModalDetailNumberPerson"></SPAN></b></h4>

        </div>
        <div class="modal-body" style=" overflow-x: auto;">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" >
                <table class="table table-responsive" id="tableTableItem">
                    <thead>
                        <th>CANTIDAD</th>
                        <th>ACCION</th>
                        <th>DETALLE</th>
                        <th style='text-align:right'>PRECIO</th>
                        <th style='text-align:right'>SUBTOTAL</th>
                        <th>OPERACION</th>
                    </thead>
                    <tbody id="tbodyTableItem">
                    </tbody>
                    <tfoot>
                        <td style="font-weight: bold;"> TOTAL</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="font-weight: bold; text-align: right;" id="tdTotalTableItem"></td>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="modal-footer" style="padding: 0; padding-bottom: 6px;    padding-top: 7px;">
            <button type="button" class="btn btn-warning btn-lg pull-left" id="btnActionItem">ACCION ITEM</button>                        
            <button type="button" style="    background-color: #ff0000;" class="btn btn-info btn-lg "  id="" onclick="voidTable()">ANULAR PERDIDO</button>                       
            <button type="button" class="btn btn-info btn-lg "  id="" onclick="transferItemSelectedTable(1)">TRASPASAR</button>                        
            <button type="button" class="btn btn-success btn-lg " onclick="payAllTable()" id="">PAGAR</button>                        
            <button type="button" class="btn btn-lg pos-btn-split" onclick="TableService.openSplit()"><i class="fa fa-users"></i> CUENTA COMPARTIDA</button>
            <button type="button" class="btn btn-warning btn-lg " id="" onclick="imprimirProductsTables('imprimirDetalleM')" >IMPRIMIR</button>                        
            <button type="button" class="btn btn-danger btn-lg" data-dismiss="modal" onclick="optionItemTable=0">VOLVER</button>                        

        </div>

    </div>
</div>

</div>



<!-- Abrir mesa: mesero, código, personas o cliente y resumen de la primera comanda -->
<div class="modal fade in pos-checkout pos-dialog" id="ModalOpenTable" role="dialog" data-backdrop="static">
    <div class="modal-dialog">
        <form class="modal-content" onsubmit="event.preventDefault(); TableService.submitOpen();" autocomplete="off">
            <div class="pos-checkout-head">
                <div>
                    <span class="pos-label">Abrir mesa</span>
                    <div class="pos-dialog-title">Mesa <span id="openTableNumber"></span></div>
                </div>
                <span class="pos-checkout-service-type"><i class="fa fa-cutlery"></i> Salón</span>
            </div>

            <div class="pos-dialog-body">
                <label class="pos-checkout-field">
                    <span class="pos-label">Mesero</span>
                    <select id="openTableWaiter" class="form-control"></select>
                </label>
                <label class="pos-checkout-field" id="openTableCodeField">
                    <span class="pos-label">Código del mesero</span>
                    <input type="password" inputmode="numeric" id="openTableCode" class="form-control" placeholder="Ingrese su código">
                </label>
                <div class="pos-checkout-field" id="openTablePeopleField">
                    <span class="pos-label">Personas</span>
                    <div class="pos-stepper">
                        <button type="button" onclick="TableService.stepPeople(-1)" aria-label="Menos"><i class="fa fa-minus"></i></button>
                        <input type="number" min="1" max="99" id="openTablePeople" value="2">
                        <button type="button" onclick="TableService.stepPeople(1)" aria-label="Más"><i class="fa fa-plus"></i></button>
                    </div>
                </div>
                <label class="pos-checkout-field" id="openTableCustomerField">
                    <span class="pos-label">Nombre del cliente</span>
                    <input type="text" id="openTableCustomer" class="form-control" maxlength="60" placeholder="Opcional">
                </label>

                <div class="pos-open-summary" id="openTableSummary"></div>
            </div>

            <div class="pos-checkout-foot">
                <button type="button" class="pos-checkout-volver" data-dismiss="modal"><i class="fa fa-arrow-left"></i> Volver</button>
                <button type="submit" id="btnOpenTable" class="pos-btn-invoice pos-checkout-confirmar"><i class="fa fa-check"></i> ABRIR MESA</button>
            </div>
        </form>
    </div>
</div>

<!-- Cuenta compartida: la mesa se cobra en partes, cada una con su nombre, NIT y forma de pago -->
<div class="modal fade in pos-checkout pos-split" id="ModalSplitBill" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="pos-checkout-head">
                <div>
                    <span class="pos-label">Cuenta compartida · Mesa <span id="splitTableNumber"></span></span>
                    <div class="pos-checkout-total">Saldo Bs <strong id="splitPending">0.00</strong></div>
                </div>
                <div class="pos-checkout-head-info">
                    <span class="pos-checkout-service-type" id="splitPartLabel">Parte 1</span>
                    <span class="pos-checkout-literal" id="splitPaidSummary"></span>
                </div>
            </div>

            <div class="pos-split-body">
                <section class="pos-checkout-col">
                    <span class="pos-label">¿Qué paga este cliente?</span>
                    <div class="pos-segmented" role="tablist">
                        <label><input type="radio" name="splitMode" value="equal" checked onchange="TableService.setMode(this.value)"><span><i class="fa fa-pie-chart"></i> Partes iguales</span></label>
                        <label><input type="radio" name="splitMode" value="items" onchange="TableService.setMode(this.value)"><span><i class="fa fa-list-ul"></i> Por ítems</span></label>
                    </div>

                    <div id="splitEqual" class="pos-split-equal">
                        <span>Dividir entre</span>
                        <div class="pos-stepper">
                            <button type="button" onclick="TableService.stepParts(-1)" aria-label="Menos"><i class="fa fa-minus"></i></button>
                            <input type="number" min="1" max="50" id="splitParts" value="2" onchange="TableService.setParts(this.value)">
                            <button type="button" onclick="TableService.stepParts(1)" aria-label="Más"><i class="fa fa-plus"></i></button>
                        </div>
                        <span>personas</span>
                    </div>

                    <div class="pos-split-items">
                        <table class="table pos-split-table">
                            <thead>
                                <tr><th>Producto</th><th class="text-right">Precio</th><th class="text-center" id="splitQtyHead">Cantidad</th><th class="text-right">Subtotal</th></tr>
                            </thead>
                            <tbody id="splitItems"></tbody>
                        </table>
                    </div>

                    <div class="pos-split-part-total">
                        <span>Total de esta parte</span>
                        <strong>Bs <span id="splitPartTotal">0.00</span></strong>
                    </div>
                </section>

                <section class="pos-checkout-col">
                    <span class="pos-label">Datos del cliente</span>
                    <label class="pos-checkout-field">
                        <span class="pos-label">Nombre / razón social</span>
                        <input type="text" id="splitName" class="form-control" maxlength="100" placeholder="SIN NOMBRE">
                    </label>
                    <label class="pos-checkout-field">
                        <span class="pos-label">NIT / CI</span>
                        <input type="text" id="splitTaxId" class="form-control" inputmode="numeric" maxlength="20" placeholder="0">
                    </label>

                    <span class="pos-label">Forma de pago</span>
                    <div class="pos-checkout-methods">
                        <label class="pos-checkout-option-name"><input type="radio" name="splitPayment" value="CONTADO" checked onchange="TableService.setPayment(this.value)"><i class="fa fa-money"></i> Efectivo</label>
                        <label class="pos-checkout-option-name"><input type="radio" name="splitPayment" value="QR" onchange="TableService.setPayment(this.value)"><i class="fa fa-qrcode"></i> QR</label>
                        <label class="pos-checkout-option-name"><input type="radio" name="splitPayment" value="CREDITO" onchange="TableService.setPayment(this.value)"><i class="fa fa-credit-card"></i> Tarjeta / crédito</label>
                        <label class="pos-checkout-option-name"><input type="radio" name="splitPayment" value="CONSUMO" onchange="TableService.setPayment(this.value)"><i class="fa fa-coffee"></i> Consumo interno</label>
                    </div>

                    <div id="splitCash" class="pos-split-cash">
                        <label class="pos-checkout-amount">
                            <span>Bs</span>
                            <input type="text" inputmode="decimal" id="splitReceived" placeholder="Recibido" oninput="TableService.updateChange()">
                        </label>
                        <button type="button" class="pos-split-exact" onclick="TableService.exact()">Exacto</button>
                        <div class="pos-checkout-change" id="splitChangeBox">
                            <span class="pos-label">Cambio</span>
                            <strong id="splitChange">0.00</strong>
                        </div>
                    </div>

                    <div class="pos-split-paid" id="splitPaidList"></div>
                </section>
            </div>

            <div class="pos-checkout-foot">
                <button type="button" class="pos-checkout-volver" onclick="TableService.closeSplit()"><i class="fa fa-arrow-left"></i> Volver</button>
                <button type="button" id="btnSplitCharge" class="pos-btn-invoice pos-checkout-confirmar" onclick="TableService.chargePart()"><i class="fa fa-print"></i> COBRAR PARTE <small>Bs <span id="splitChargeTotal">0.00</span></small></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="ModalTablesEnabled" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>MESAS HABILITADAS<span id="titleProductNote"></span></b></h4>

            </div>
            <div class="modal-body" style=" overflow-x: auto;">
             <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" id="divTableEnabled">
                 
             </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

        </div>

    </div>
</div>

</div>

<div class="modal fade in" id="ModalOpenCash" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>APERTURA DE CAJA</b></h4>
            </div>
            <div class="modal-body">
                @can('pos.cash.open')
                    <label for="inputOpeningAmount">MONTO INICIAL (Bs):</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="inputOpeningAmount">
                @else
                    <p>SU USUARIO NO TIENE PERMISO PARA ABRIR CAJA.</p>
                @endcan
            </div>
            <div class="modal-footer">
                @can('pos.cash.open')
                    <button type="button" class="btn btn-success" onclick="openCashRegister()">ABRIR CAJA</button>
                @endcan
                <button type="button" class="btn btn-danger" onclick="$('#fromLogin').submit()">SALIR</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="ModalDailyClose" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>CIERRE DE CAJA</b></h4>
            </div>
            <div class="modal-body">
                <label for="inputCountedCash">EFECTIVO CONTADO (Bs):</label>
                <input type="number" step="0.01" min="0" class="form-control" id="inputCountedCash">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btnClosingCash" onclick="closingCashRegister()">ACEPTAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">CANCELAR</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="ModalClosingResult" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>CAJA CERRADA</b></h4>
            </div>
            <div class="modal-body">
                <p>ESPERADO: <b id="spanClosingExpected"></b></p>
                <p>CONTADO: <b id="spanClosingCounted"></b></p>
                <p>DIFERENCIA: <b id="spanClosingDifference"></b></p>
                <div id="divClosingNote" style="display: none">
                    <label for="inputClosingNote">EXPLIQUE LA DIFERENCIA:</label>
                    <textarea class="form-control" id="inputClosingNote" maxlength="500" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" onclick="finishClosing()">ACEPTAR</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="ModalSupplyProduct" role="dialog" data-backdrop="static">
    <div class="modal-dialog ">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>INSUMOS</b></h4>

            </div>
            <div class="modal-body" style=" overflow-x: auto;">
             <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" id="">
                
                <table class="table display table table-bordered table-stripe table-hover table-responsive">
                    <thead>
                        <th>INSUMO
                            <th>STOCK ACTUAL
                            </thead>
                            <tbody id="tbodySupplyProduct"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

                </div>

            </div>
        </div>
    </div>

    <div class="modal fade in" id="ModalButtonProduct" role="dialog" data-backdrop="static">
        <div class="modal-dialog ">    
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" style="text-align: center"><b>CATEGORIAS HABILITADAS</b></h4>

                </div>
                <div class="modal-body" style=" overflow-x: auto;">
                    <input type="hidden" id="idProductForMenu" name="">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" id="divButtonProduct">
                        
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

                </div>

            </div>
        </div>
    </div>

    <div class="modal fade in" id="ModalGiftCards" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg">    
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" style="text-align: center"><b>TARJETAS</b></h4>

                </div>
                <div class="modal-body" style=" overflow-x: auto;">
                    
                 <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="font-weight: bold">
                    <div class="form-group">
                        <label>Nro. Tarjeta</label>
                        <input type="text" name="" id="inputGiftCard">
                        <button class="btn btn-success" id="btnSearchGiftCard" onclick="searchGiftCard()">BUSCAR</button>
                    </div>
                    <div id="divCardGif" style="display: none">
                        <label>Nombre Cliente: </label><span id="spanNameCards"></span><br>
                        <label>Total Tarjeta: </label><span id="spanTotalCards"></span><br>
                        <label>Consumo Actual: </label><span id="spanConsumptionCards"></span><br>
                        <label>Ultimo Saldo: </label><span id="spanLastBalanceCards"></span><br>
                        <label>Su nuevo saldo: </label><span id="spanNewBalanceCards"></span><br>
                        <label>FORMA PREPAGO: </label><span id="spanPaymentMethodCards"></span><br>
                    </div>
                    <table id="tableGiftCards" class="table display table table-bordered table-stripe table-hover table-responsive">
                       <thead>
                           <th>cont</th>
                           <th>Cantidad</th>
                           <th>Producto</th>
                           <th>Total</th>
                           <th>Fechas</th>
                       </thead>
                       <tbody id="tbodyGiftCards">
                           
                       </tbody>
                   </table>               
               </div>
           </div>
           <div class="modal-footer">
            <button type="button" class="btn btn-success" onclick="saveDetailGiftCard()">CONTINUAR</button>                        
            <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#inputCard').prop('checked',false);">VOLVER</button>                        

        </div>

    </div>
</div>
</div>

<div class="modal fade in" id="ModalListCustomer" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" style="    width: 97%;">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>LISTA DE CLIENTES</b></h4>

            </div>
            <div class="modal-body" style=" overflow-x: auto;">
                
             <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="font-weight: bold">
                <div>
                   <table style="margin: 0;" class="table display table table-bordered table-stripe table-hover table-responsive" id="tableCustomer">
                       <thead>
                           <th style="width: 24%;">NOMBRE Y APELLIDO</th>
                           <th>NIT</th>
                           <th>CI</th>
                           <th>TELEFONO</th>
                           <th>CELULAR</th>
                           <th>CORREO</th>
                           <th style="width: 20%;">DIRECCION</th>
                           <th style="width: 5%;">OPERACION</th>
                       </thead>
                       <tbody id="tbodyListCustomer">
                           
                       </tbody>
                   </table>  
               </div>
               <!-- <div style="height: 300px; overflow: auto;"> -->
                         <!-- <table class="" border="1" style="    width: 100%;">
                              
                              <tbody id="tbodyListCustomer">
                                 
                             </tbody>
                         </table>       -->       
                         <!-- </div> -->
                     </div>
                 </div>
                 <div class="modal-footer">
                   <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6" style="text-align: left;">
                    <label>TEXTO</label>
                    <div class="input-group margin">
                        <input type="text" class="form-control" id="searchNameCustomerTaxId">
                        <span class="input-group-btn">
                          <button type="button" class="btn btn-info btn-flat" onclick="searchPorName()">NOMBRE!</button>
                          <button type="button" class="btn btn-success btn-flat" onclick="searchPorTaxId()">NIT</button>
                      </span>
                  </div>

              </div>
              @can('pos.customers.create')
              <button type="button" class="btn btn-success" onclick="newCustomer()"><i class="fa fa-plus"></i> NUEVO CLIENTE</button>
              @endcan
              <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

          </div>

      </div>
  </div>
</div>

<div class="modal fade in" id="ModalModifyCustomer" role="dialog" data-backdrop="static">
    <div class="modal-dialog">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b id="titleCustomerForm">MODIFICAR CLIENTE</b></h4>

            </div>
            <div class="modal-body" style=" overflow-x: auto;">
                <div class="row">
                    <div class="col-sm-12 form-group">
                        <label>NOMBRE Y APELLIDO *</label>
                        <input type="text" id="nameCustomerA" class="form-control" maxlength="100">
                    </div>
                    <div class="col-sm-6 form-group">
                        <label>NIT</label>
                        <input type="text" id="customerTaxIdA" class="form-control" maxlength="20">
                    </div>
                    <div class="col-sm-6 form-group">
                        <label>CI</label>
                        <input type="text" id="customerIdNumberA" class="form-control" maxlength="15">
                    </div>
                    <div class="col-sm-6 form-group">
                        <label>TELEFONO</label>
                        <input type="text" id="customerPhoneA" class="form-control" maxlength="20">
                    </div>
                    <div class="col-sm-6 form-group">
                        <label>CELULAR</label>
                        <input type="text" id="customerMobileA" class="form-control" maxlength="20">
                    </div>
                    <div class="col-sm-6 form-group">
                        <label>CORREO</label>
                        <input type="email" id="customerEmailA" class="form-control" maxlength="100">
                    </div>
                    <div class="col-sm-6 form-group">
                        <label>FECHA DE NACIMIENTO</label>
                        <input type="date" id="birthDateA" class="form-control">
                    </div>
                    <div class="col-sm-12 form-group">
                        <label>DIRECCION</label>
                        <input type="text" id="customerAddressA" class="form-control" maxlength="500">
                    </div>
                </div>
            </div>
       <div class="modal-footer">
        
        <button type="button" class="btn btn-success"  onclick="saveCustomer()">GUARDAR</button>                        
        <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#ModalListCustomer').css('overflow-y','auto')" >VOLVER</button>                        

    </div>

</div>
</div>
</div>

<div class="modal fade in" id="ModalPurchaseVat" role="dialog" data-backdrop="static">
    <div class="modal-dialog">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>DATOS DE LA FACTURA</b></h4>

            </div>
            <div class="modal-body" style=" overflow-x: auto;">
                
             <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" >
               <div class="form-group">
                   <label>FECHA</label>
                   <input type="text" class="form-control pull-right datepicker" id="dateInvoice">
               </div>
               <div class="form-group">
                   <label>NIT PROVEEDOR</label>
                   <input type="text" name="" id="taxIdSupplier" class="form-control" >
               </div>
               <div class="form-group">
                   <label>NRO AUTORIZACION</label>
                   <input type="text" name="" id="numberAuthorization" class="form-control" >
               </div>
               <div class="form-group">
                   <label>CODIGO CONTROL</label>
                   <input type="text" name="" id="controlCode" class="form-control" >
               </div>
               <div class="form-group" >
                <label>NRO FACTURA.</label>                                    
                <input type="number" name="" id="inputNumberInvoice" class="form-control"  >

            </div>
            <div class="form-group">
               <label>INCLUYE IVA</label>
               <input type="checkbox" name="" id="checkboxTaxCredit" class="form-control" >
           </div>

           <div class="form-group">
               <label>CREDITO FISCAL</label>
               <input type="text" name="" id="inputDebitFiscal" readonly="" class="form-control" >
           </div>
       </div>
   </div>
   <div class="modal-footer">
       <button type="button" class="btn btn-info pull-left"  onclick="alertify.prompt('GENERADOR QR', 'CODIGO QR', '',
        function (evt, value) {
            convertQr(value);
        },
        function () {
        });">DESDE GENERADOR QR</button>
        <button type="button" class="btn btn-success"  onclick="saveExpense()">GUARDAR</button>                        
        <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#ModalListCustomer').css('overflow-y','auto')" >VOLVER</button>                        

    </div>

</div>
</div>
</div>

