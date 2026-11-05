
<div class="modal fade in" id="ModalaAgregarNota" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-sm">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>AGREGAR NOTA AL PRODUCTO <span id="tituloProductoNota"></span></b></h4>

            </div>
            <div class="modal-body" style=" overflow-x: auto;">
                <input type="text" name="" class="form-control" id="inputNota">
            </div>
            <div class="modal-footer">
                <button type="submit" id="btnGuardar" name="btnGuardarInsumoProducto" class="btn btn-success" onclick="agregarNota()">AGREGAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        
            </div>
        </div>
    </div>
</div>
<div class="modal fade in pos-cobro" id="ModalFactura" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="pos-cobro-head">
                <div>
                    <span class="pos-label">Total a cobrar</span>
                    <div class="pos-cobro-total">Bs <strong id="cobroTotalBs">0.00</strong></div>
                    <div class="pos-cobro-total-usd">$US <span id="cobroTotalUsd">0.00</span></div>
                </div>
                <div class="pos-cobro-head-info">
                    <span class="pos-cobro-salida"><i class="fa fa-cutlery"></i> <span id="labelLlevar">Llevar</span></span>
                    <span class="pos-cobro-literal" id="labelNumLiteral"></span>
                </div>
            </div>

            <div class="pos-cobro-body">
                <section class="pos-cobro-col">
                    <span class="pos-label">Pago recibido</span>
                    <label class="pos-cobro-monto">
                        <input type="radio" checked value="bs" name="tipoMonedaFactura" id="radioMonedaBsFactura">
                        <span>Bs</span>
                        <input type="text" inputmode="decimal" autocomplete="off" id="totalFacturaBs" placeholder="0.00" onclick="$('#radioMonedaBsFactura').click()" ondblclick="pagoExactoCobro(); guardarDetalle()" onkeyup="sacarCambio()">
                    </label>
                    <label class="pos-cobro-monto">
                        <input type="radio" value="dolar" name="tipoMonedaFactura" id="radioMonedaDolarFactura">
                        <span>$US</span>
                        <input type="text" inputmode="decimal" autocomplete="off" id="totalFacturaUs" placeholder="0.00" onclick="$('#radioMonedaDolarFactura').click()" onkeyup="sacarCambio()">
                    </label>

                    <div class="pos-cobro-billetes">
                        @foreach ([2 => '2boliviano.png', 5 => '5bs.png', 10 => '10BS.JPG', 20 => '20BS.JPG', 50 => '50BS.JPG', 100 => '100BS.JPG', 200 => '200BS.JPG'] as $valor => $imagen)
                            <button type="button" class="{{ $valor < 10 ? 'is-moneda' : '' }}" onclick="billeteCobro({{ $valor }})" title="Agregar Bs {{ $valor }}">
                                <img src="{{ asset('abms1/imagenes/'.$imagen) }}" alt="Bs {{ $valor }}">
                            </button>
                        @endforeach
                        <button type="button" class="pos-cobro-exacto" onclick="pagoExactoCobro()">Exacto</button>
                    </div>
                </section>

                <section class="pos-cobro-col">
                    <div class="pos-cobro-keypad">
                        @for ($i = 1; $i < 10; $i++)
                            <button type="button" onclick="teclaCobro('{{ $i }}')">{{ $i }}</button>
                        @endfor
                        <button type="button" onclick="teclaCobro('.')">.</button>
                        <button type="button" onclick="teclaCobro('0')">0</button>
                        <button type="button" onclick="teclaCobro('borrar')"><i class="fa fa-long-arrow-left"></i></button>
                        <button type="button" class="pos-cobro-limpiar" onclick="teclaCobro('C')">Limpiar</button>
                    </div>

                    <div class="pos-cobro-cambio" id="cobroCambio">
                        <span class="pos-label">Cambio</span>
                        <div class="pos-cobro-cambio-montos">
                            <label>Bs <input type="text" id="cambioBs" readonly></label>
                            <label>$US <input type="text" id="cambioUsd" readonly></label>
                        </div>
                    </div>
                </section>

                <section class="pos-cobro-col">
                    <span class="pos-label">Forma de pago</span>
                    <div class="pos-cobro-formas">
                        <label class="pos-cobro-opcion"><input id="inputContadoFacturar" type="radio" name="formaPago" value="CONTADO" checked onclick="tipoPago(this)"><i class="fa fa-money"></i> Contado</label>
                        <label class="pos-cobro-opcion"><input type="radio" name="formaPago" value="CREDITO" onclick="tipoPago(this)"><i class="fa fa-credit-card"></i> Crédito</label>
                        <label class="pos-cobro-opcion"><input type="radio" name="formaPago" value="CONSUMO" onclick="tipoPago(this)"><i class="fa fa-coffee"></i> Consumo interno</label>
                        <label class="pos-cobro-opcion"><input type="radio" name="formaPago" value="PREPAGO" onclick="tipoPago(this)"><i class="fa fa-clock-o"></i> Pre pago</label>
                    </div>
                    <div id="divSelecCredito" style="display: none">
                        <select class="form-control" id="selectTipoPagoCredito" onchange="guardarNumeroTigoMon(this)">
                            <option value="tarjeta">Tarjeta</option>
                            <option value="direccion">Direccion</option>
                            <option value="tigomon">TigoMon</option>
                        </select>
                    </div>
                    <div id="divInputConsumoInterno" style="display: none">
                        <input type="text" id="inputConsumoInterno" class="form-control" placeholder="Detalle del consumo interno">
                    </div>
                    <div id="divSelecPrePago" style="display: none">
                        <select class="form-control" id="selectPrepago">
                            <option value="CONTADO">Contado</option>
                            <option value="CREDITO">Credito</option>
                            <option value="CONSUMO">Consumo</option>
                            <option value="TARJETA">Tarjeta</option>
                            <option value="CORTESIA">Cortesia</option>
                        </select>
                    </div>

                    <label class="pos-cobro-campo">
                        <span class="pos-label">Empleado</span>
                        <select class="form-control" id="selectVendedor" name="selectVendedor">
                            <option value="0">SELECCIONE UN EMPLEADO</option>
                        </select>
                    </label>
                    <label class="pos-cobro-campo">
                        <span class="pos-label">N° de orden / mesa</span>
                        <input type="text" class="form-control" id="nroOrden" placeholder="Opcional">
                    </label>
                    <label class="pos-cobro-campo">
                        <span class="pos-label">Fecha de venta <small>(sólo si es otro día)</small></span>
                        <input type="date" id="fechaCamHis" onchange="cambiarFecha($(this).val())" class="form-control">
                    </label>
                </section>
            </div>

            <div class="pos-cobro-foot">
                <input type="hidden" id="numLiteral">
                <button type="button" class="pos-cobro-volver" data-dismiss="modal" onclick="cancelarCobro()"><i class="fa fa-arrow-left"></i> Volver</button>
                <button type="submit" id="btnGuardarDetalle" class="pos-btn-facturar pos-cobro-confirmar" onclick="guardarDetalle(this)"><i class="fa fa-print"></i> COBRAR <small>imprimir factura</small></button>
            </div>
        </div>
    </div>
</div>

<!-- <div class="modal fade in" id="ModalaTipoDeCompra" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-sm">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>AGREGAR NOTA AL PRODUCTO <span id="tituloProductoNota"></span></b></h4>
            </div>
            <div class="modal-body" style=" overflow-x: auto;">
                <input type="text"  class="form-control" id="inputNota">
            </div>
            <div class="modal-footer">
                <button type="submit" id="btnGuardar" name="btnGuardarInsumoProducto" class="btn btn-success" onclick="agregarNota()">AGREGAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

            </div>

        </div>
    </div>

</div>

-->
<div class="modal fade in pos-historico" id="ModalaHistoricoDia" role="dialog" data-backdrop="static">
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
                <div class="input-group date pos-hist-fecha">
                    <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
                    <input type="text" class="form-control datepicker" id="fechaHistorico" autocomplete="off">
                </div>
                <div class="pos-hist-actions">
                    <button type="button" class="pos-hist-btn pos-hist-btn-cierre" id="btnCierreDiario" data-toggle="modal" data-target="#ModalCierreDiario" onclick="listarCajero()"><i class="fa fa-lock"></i> Cierre diario</button>
                    <button type="button" class="pos-hist-btn pos-hist-btn-volver" data-dismiss="modal"><i class="fa fa-arrow-left"></i> Volver</button>
                </div>
            </div>

            <div class="pos-hist-stats">
                <div class="pos-hist-stat"><span class="pos-label">Ventas</span><strong id="histResVentas">0</strong></div>
                <div class="pos-hist-stat is-ok"><span class="pos-label">Activas</span><strong id="histResActivas">0</strong></div>
                <div class="pos-hist-stat is-bad"><span class="pos-label">Anuladas</span><strong id="histResAnuladas">0</strong></div>
                <div class="pos-hist-stat is-total"><span class="pos-label">Total vendido</span><strong id="histResTotal">0.00</strong></div>
            </div>

            <div class="pos-hist-body">
                <section class="pos-hist-card pos-hist-lista">
                    <div class="pos-hist-card-head">
                        <span class="pos-label">Ventas del día</span>
                        <div class="pos-hist-buscar">
                            <i class="fa fa-search"></i>
                            <input type="search" id="buscarHistorico" placeholder="Buscar orden, cajero, forma de pago..." autocomplete="off">
                        </div>
                    </div>
                    <table class="table table-hover" id="tableHistorico" style="width: 100%;">
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
                        <tbody id="tbodyHistorico"></tbody>
                    </table>
                </section>

                <aside class="pos-hist-card pos-hist-detalle">
                    <div class="pos-hist-card-head">
                        <span class="pos-label">Detalle de la venta</span>
                        <span class="pos-hist-hora"><i class="fa fa-clock-o"></i> <span id="spanHistoricoHora">--:--</span></span>
                    </div>
                    <div class="pos-hist-campos">
                        <label><span class="pos-label">Proceso</span><input type="text" class="form-control" id="procesoHistorico" readonly></label>
                        <label class="pos-hist-campo-ancho"><span class="pos-label">Cliente</span><input type="text" class="form-control" id="nombreCliHistorico" readonly></label>
                        <label><span class="pos-label">Folio D</span><input type="text" class="form-control" id="df" placeholder="0" readonly></label>
                        <label><span class="pos-label">Folio N</span><input type="text" class="form-control" id="td" placeholder="0" readonly></label>
                    </div>
                    <div class="pos-hist-items">
                        <table class="table" id="tableHistoricoDetalle">
                            <thead>
                                <tr>
                                    <th class="text-right">Cant.</th>
                                    <th>Descripción</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyHistoricoDetalle">
                                <tr class="pos-hist-vacio"><td colspan="3"><i class="fa fa-hand-pointer-o"></i> Seleccione una venta de la lista</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pos-hist-total">
                        <span>Total</span>
                        <strong id="totalDetalle">0.00</strong>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="ModalSugerencias" role="dialog" data-backdrop="static">
    <div class="modal-dialog">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>OPCIONES PARA: <span id="tituloProductoSugerencia"></span></b></h4>
            </div>
            <div class="modal-body" style=" overflow-x: auto;" id="divSugerencia">

            </div>
            <div class="modal-footer">
                <button type="submit" id="btnGuardar" name="btnGuardarInsumoProducto" class="btn btn-success" onclick="agregarSugerenciaProducto()">AGREGAR</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

            </div>

        </div>
    </div>

</div>

<div class="modal fade in" id="ModalSalida" role="dialog" data-backdrop="static">
    <div class="modal-dialog" style="margin-top: 12%;">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>PEDIDOS PARA</b></h4>
            </div>
            <div class="modal-body" style=" overflow-x: auto;    text-align: center;" id="">
                <button class="btn btn-warning btn-lg" data-toggle="modal" data-target="#ModalTecladoMesa">MESA</button>
                <button class="btn btn-warning btn-lg" onclick="tipoSalida(this)">LLEVAR</button>
                <button class="btn btn-warning btn-lg" onclick="tipoSalida(this)">AUTO</button>
                <button class="btn btn-warning btn-lg" onclick="tipoSalida(this)">MOTO</button>
            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

            </div>
        </div>
    </div>

</div>
<div class="modal fade in" id="ModalTecladoMesa" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-sm" style="margin-top: 12%;">    
        <div class="modal-content">

            <div class="modal-body" style=" overflow-x: auto;    text-align: center;" id="">
                <form name="tecladoMesa" >

                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 " style=" ">
                        <div class="input-group input-group-sm">
                            <div class="col-sm-9 col-lg-9" style="padding: 0">
                                <input type="number" class="form-control" id="numMesa" name="ans">
                            </div>
                            <div class="col-sm-3 col-lg-3" style="padding: 0">
                                <button type="reset"  style="background:  #F93;    width: 100%;" type="button" class="btn btn-info">C</button>
                            </div>

                        </div>
                        @for ($i = 1; $i < 10; $i++)
<button type="button" numero="{{ $i }}" class="btn btn-primary  col-lg-4 col-md-4 col-sm-4 col-xs-4" style="color:black;  font-weight: bold;BACKGROUND-COLOR: #F93;" onClick="document.tecladoMesa.ans.value+={{ $i }}">{{ $i }}</button>
@endfor
                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4" style=" ">
                        </div>
                        <button type ="button"  class="btn btn-primary  col-lg-4 col-md-4 col-sm-4 col-xs-4"  style="color:black;  font-weight: bold;BACKGROUND-COLOR: #F93;  " onClick="document.tecladoMesa.ans.value += 0" >0</button>
                    </div>
                </form>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-success pull-left"  salida="MESA" onclick="pedidoMesa(this)">CONTINUAR</button>                        

                <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#numMesa').val('')">VOLVER</button>                        

            </div>
        </div>
    </div>

</div>


<div class="modal fade in" id="ModalaDetalleMesa" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" style="    width: 94%;
    max-height: 70%;">    
    <div class="modal-content">
        <div class="modal-header">
            <span style="    text-align: left;    font-weight: bold;    font-size: 12pt;" id="spanEmpAtendio"></span><h4 class="modal-title" style="text-align: center"><b>DETALLE  : <SPAN id="spanModalDetalleMesa"></SPAN> -  PERSONA:<SPAN id="spanModalDetalleNroPersona"></SPAN></b></h4>

        </div>
        <div class="modal-body" style=" overflow-x: auto;">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" >
                <table class="table table-responsive" id="tablaDetalleMesa">
                    <thead>
                        <th>CANTIDAD</th>
                        <th>ACCION</th>
                        <th>DETALLE</th>
                        <th style='text-align:right'>PRECIO</th>
                        <th style='text-align:right'>SUBTOTAL</th>
                        <th>OPERACION</th>
                    </thead>
                    <tbody id="tbodyDetalleMesa">
                    </tbody>
                    <tfoot>
                        <td style="font-weight: bold;"> TOTAL</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="font-weight: bold; text-align: right;" id="tdTotalDetalleMesa"></td>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="modal-footer" style="padding: 0; padding-bottom: 6px;    padding-top: 7px;">
            <button type="button" class="btn btn-warning btn-lg pull-left" id="btnAccionItem">ACCION ITEM</button>                        
            <button type="button" style="    background-color: #ff0000;" class="btn btn-info btn-lg "  id="" onclick="anularMesa()">ANULAR PERDIDO</button>                       
            <button type="button" class="btn btn-info btn-lg "  id="" onclick="traspasarItemSelecMesa(1)">TRASPASAR</button>                        
            <button type="button" class="btn btn-success btn-lg " onclick="pagarTodoMesa()" id="">PAGAR</button>                        
            <button type="button" class="btn btn-warning btn-lg " id="" onclick="imprimirProductosMesas('imprimirDetalleM')" >IMPRIMIR</button>                        
            <button type="button" class="btn btn-danger btn-lg" data-dismiss="modal" onclick="opcionItemMesa=0">VOLVER</button>                        

        </div>

    </div>
</div>

</div>



<div class="modal fade in" id="ModalMesasHabilitadas" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>MESAS HABILITADAS<span id="tituloProductoNota"></span></b></h4>

            </div>
            <div class="modal-body" style=" overflow-x: auto;">
             <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" id="divMesaHabilitadas">
                 
             </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

        </div>

    </div>
</div>

</div>

<div class="modal fade in" id="ModalCierreDiario" role="dialog" data-backdrop="static">
    <div class="modal-dialog ">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>CIERRE DE CAJA</b></h4>

            </div>
            <div class="modal-body" style=" overflow-x: auto;">
             <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" id="">
                <label for="inputEmail3" class="col-sm-3 control-label">CAJERO:</label>
                <div class="col-sm-9">
                    <select class="form-control" id="selectCajero" name="selectCajero">
                        <option value="0"></option>
                    </select>
                </div>
                <label for="inputEmail3" class="col-sm-3 control-label">TURNOS:</label>
                <label><input type="checkbox" name="checkboxMañana" id="checkboxMañana" value="MAÑANA">MAÑANA</label>
                <label><input type="checkbox" name="checkboxNoche" id="checkboxNoche" value="NOCHE">NOCHE</label><br><br>
                <label><input type="checkbox" name="checkboxCierre" id="checkboxCierre">CIERRE</label>


            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-success" onclick="cierreCaja()">ACEPTAR</button>                        
            <button type="button" class="btn btn-danger" data-dismiss="modal">CANCELAR</button>                        

        </div>

    </div>
</div>

</div>



<div class="modal fade in" id="ModalInsumoProducto" role="dialog" data-backdrop="static">
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
                            <tbody id="tbodyInsumoProducto"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

                </div>

            </div>
        </div>
    </div>

    <div class="modal fade in" id="ModalBotonProducto" role="dialog" data-backdrop="static">
        <div class="modal-dialog ">    
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" style="text-align: center"><b>CATEGORIAS HABILITADAS</b></h4>

                </div>
                <div class="modal-body" style=" overflow-x: auto;">
                    <input type="hidden" id="idProductoParaMenu" name="">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" id="divBotonProducto">
                        
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

                </div>

            </div>
        </div>
    </div>

    <div class="modal fade in" id="ModalTarjetasGif" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-lg">    
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" style="text-align: center"><b>TARJETAS</b></h4>

                </div>
                <div class="modal-body" style=" overflow-x: auto;">
                    
                 <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="font-weight: bold">
                    <div class="form-group">
                        <label>Nro. Tarjeta</label>
                        <input type="text" name="" id="inputTarjetaGif">
                        <button class="btn btn-success" id="btnBuscarTarjetaGif" onclick="buscarTarjetaGift()">BUSCAR</button>
                    </div>
                    <div id="divTajertaGif" style="display: none">
                        <label>Nombre Cliente: </label><span id="spanNombreTajertas"></span><br>
                        <label>Total Tarjeta: </label><span id="spanTotalTajertas"></span><br>
                        <label>Consumo Actual: </label><span id="spanConsumoTajertas"></span><br>
                        <label>Ultimo Saldo: </label><span id="spanUltimoSaldoTajertas"></span><br>
                        <label>Su nuevo saldo: </label><span id="spanNuevoSaldoTajertas"></span><br>
                        <label>FORMA PREPAGO: </label><span id="spanFormaPagoTajertas"></span><br>
                    </div>
                    <table id="tableTarjetasGift" class="table display table table-bordered table-stripe table-hover table-responsive">
                       <thead>
                           <th>cont</th>
                           <th>Cantidad</th>
                           <th>Producto</th>
                           <th>Total</th>
                           <th>Fechas</th>
                       </thead>
                       <tbody id="tbodyTarjetasGif">
                           
                       </tbody>
                   </table>               
               </div>
           </div>
           <div class="modal-footer">
            <button type="button" class="btn btn-success" onclick="guardarDetalleTarjetaGif()">CONTINUAR</button>                        
            <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#inputTarjeta').prop('checked',false);">VOLVER</button>                        

        </div>

    </div>
</div>
</div>

<div class="modal fade in" id="ModalListarCliente" role="dialog" data-backdrop="static">
    <div class="modal-dialog modal-lg" style="    width: 97%;">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>LISTA DE CLIENTES</b></h4>

            </div>
            <div class="modal-body" style=" overflow-x: auto;">
                
             <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="font-weight: bold">
                <div>
                   <table style="margin: 0;" class="table display table table-bordered table-stripe table-hover table-responsive" id="tableCliente">
                       <thead>
                           <th style="    width: 36%;">NOMBRE Y APELLIDO</th>
                           <th style="width: 13%;">NIT</th>
                           <th>TELEFONO</th>
                           <th>DIRECCION</th>
                           <th>CEL_CLI</th>
                           <th style="width: 5%;">OPERACION</th>
                       </thead>
                       <tbody id="tbodyListaCliente">
                           
                       </tbody>
                   </table>  
               </div>
               <!-- <div style="height: 300px; overflow: auto;"> -->
                         <!-- <table class="" border="1" style="    width: 100%;">
                              
                              <tbody id="tbodyListaCliente">
                                 
                             </tbody>
                         </table>       -->       
                         <!-- </div> -->
                     </div>
                 </div>
                 <div class="modal-footer">
                   <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6" style="text-align: left;">
                    <label>TEXTO</label>
                    <div class="input-group margin">
                        <input type="text" class="form-control" id="buscarNomNitCli">
                        <span class="input-group-btn">
                          <button type="button" class="btn btn-info btn-flat" onclick="buscarPorNombre()">NOMBRE!</button>
                          <button type="button" class="btn btn-success btn-flat" onclick="buscarPorNit()">NIT</button>
                      </span>
                  </div>

              </div>
              <button type="button" class="btn btn-danger" data-dismiss="modal">VOLVER</button>                        

          </div>

      </div>
  </div>
</div>

<div class="modal fade in" id="ModalModificarCliente" role="dialog" data-backdrop="static">
    <div class="modal-dialog">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>MODIFICAR CLIENTE</b></h4>

            </div>
            <div class="modal-body" style=" overflow-x: auto;">
                
             <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" >
               <div class="form-group">
                   <label>NOMBRE</label>
                   <input type="text" name="" id="nombreCliA" class="form-control" >
               </div>
               <div class="form-group">
                   <label>NIT</label>
                   <input type="text" name="" id="nitCliA" class="form-control" >
               </div>
               <div class="form-group">
                   <label>DIRECCION</label>
                   <input type="text" name="" id="dirCliA" class="form-control" >
               </div>
               <div class="form-group">
                   <label>TELEFONO</label>
                   <input type="text" name="" id="telCliA" class="form-control" >
               </div>
               <div class="form-group">
                   <label>CEL_CLI</label>
                   <input type="text" name="" id="celCliA" class="form-control" >
               </div>
           </div>
       </div>
       <div class="modal-footer">
        
        <button type="button" class="btn btn-success"  onclick="modificarCliente()">GUARDAR</button>                        
        <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#ModalListarCliente').css('overflow-y','auto')" >VOLVER</button>                        

    </div>

</div>
</div>
</div>

<div class="modal fade in" id="ModalCompraIva" role="dialog" data-backdrop="static">
    <div class="modal-dialog">    
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" style="text-align: center"><b>DATOS DE LA FACTURA</b></h4>

            </div>
            <div class="modal-body" style=" overflow-x: auto;">
                
             <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" >
               <div class="form-group">
                   <label>FECHA</label>
                   <input type="text" class="form-control pull-right datepicker" id="fechaFactura">
               </div>
               <div class="form-group">
                   <label>NIT PROVEEDOR</label>
                   <input type="text" name="" id="nitProveedor" class="form-control" >
               </div>
               <div class="form-group">
                   <label>NRO AUTORIZACION</label>
                   <input type="text" name="" id="nroAutorizacion" class="form-control" >
               </div>
               <div class="form-group">
                   <label>CODIGO CONTROL</label>
                   <input type="text" name="" id="codigoControl" class="form-control" >
               </div>
               <div class="form-group" >
                <label>NRO FACTURA.</label>                                    
                <input type="number" name="" id="inputNroFactura" class="form-control"  >

            </div>
            <div class="form-group">
               <label>INCLUYE IVA</label>
               <input type="checkbox" name="" id="checkboxCreditoFiscal" class="form-control" >
           </div>

           <div class="form-group">
               <label>CREDITO FISCAL</label>
               <input type="text" name="" id="inputDebitoFiscal" readonly="" class="form-control" >
           </div>
       </div>
   </div>
   <div class="modal-footer">
       <button type="button" class="btn btn-info pull-left"  onclick="alertify.prompt('GENERADOR QR', 'CODIGO QR', '',
        function (evt, value) {
            convertirQr(value);
        },
        function () {
        });">DESDE GENERADOR QR</button>
        <button type="button" class="btn btn-success"  onclick="guardarGasto()">GUARDAR</button>                        
        <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#ModalListarCliente').css('overflow-y','auto')" >VOLVER</button>                        

    </div>

</div>
</div>
</div>

