tipoCambio = 0;
botonProducto = "";
URLactual = "";
salidaProducto = "";
ipCliente = "";
contador = 1;//ESTE PARA CONTROLAR EL DATAPPICKER
fechaHoy = "";
function verificarError(error){
    if (error.length > 0) {
        if ("Error Session" === error) {
          $('#loading').css('display', 'none');

          alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
            alertify.message('OK');
            window.location = "/";
        });

          return true;
      }
      alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', error, function () {
        alertify.message('OK');
    });
      $('#loading').css('display', 'none');
      return true;
  }
}
$(document).ready(function () {
 $(this).bind("contextmenu", function(e) {

                // e.preventDefault();
            });
 var d = new Date();
 fechaHoy = d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate();
 listartEntregadoGasto();
 listarMesero();
 obtnerEmpresa();
 verificarDocificacion();
 verificarBackup();
 var URLactual = window.location;
 toastr.options = {
    "closeButton": false,
    "debug": false,
    "newestOnTop": false,
    "progressBar": false,
    "positionClass": "toast-bottom-center",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "100",
    "hideDuration": "1000",
    "timeOut": "100",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut" 
}
obteneripCliente();
listarMenuGrupo();
listarMesas();
listarSucursal();
var d = new Date();
var strDate = d.getFullYear() + "/" + (d.getMonth() + 1) + "/" + d.getDate();
tipoCambio = $('#tipoCambio').val();


paginadortbodyHistorico();
tableHistorico = $('#tableHistorico').DataTable();
   // verificarBackup();
});
indexDetalle = 0;
totalBs = 0;
numeros = "";
filaNombre = "";
filaNombreSpan = "";
idProd = 0;
fechaHoy = "";
$('body').on('hidden.bs.modal', '.modal', function () {
    $(this).removeData('bs.modal');
});
function failErrors(xhr, ajaxOptions, thrownError){
    console.log(xhr.status)
    console.log(xhr.responseText)
    console.log(thrownError)
    error="";
    if (xhr.status === 0) {
        error="<span style='font-weight:bold'>* Error de conexión, verifica tu instalación de RED.</span><br>";

    } else if (xhr.status == 302) {
       error="<span style='font-weight:bold'>* La página ha sido movida. error 302.</span><br>";
   } else if (xhr.status == 404) {
       error="<span style='font-weight:bold'>* La página no ha sido encontrada. error 404.</span><br>";
   } else if (xhr.status == 500) {
       error="<span style='font-weight:bold'>* Internal Server Error error 500.</span><br>";
   } else if (thrownError === 'parsererror') {
    error="<span style='font-weight:bold'>* Error parse JSON.</span><br>";
} else if (thrownError === 'timeout') {
    error="<span style='font-weight:bold'>* Exceso tiempo.</span><br>";
} else if (thrownError === 'abort') {
    error="<span style='font-weight:bold'>* Petición post abortada.</span><br>";
} else {
    error="<span style='font-weight:bold'>* ERROR desconocido:  "+ xhr.responseText+".</span><br>";
}    
if (error!="") {
    alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', error, function () {
        alertify.message('OK');
    });
}  
$('#btnGuardarDetalle').removeAttr('disabled'); 
}
function listarSucursal(){
    $.ajax({
        url: RUTAS.sucursales,
        type: 'GET',
        typeData: 'json',
        data: {},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            for (var i = 0; i < json.result.length; i++) {
                if (ventaMultialmacen==1) {
                    $('#selectSucursal').append('<option value="'+json.result[i].id+'">'+json.result[i].nombre+'</option>');
                }
            }
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

}
function cambiarSucursal(idSuc){
    $.post(RUTAS.cambiarSucursal, {idSuc:idSuc},function(res){
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        location.reload();
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
function tipoSalida(botonSalida) {
    salidaProducto = $(botonSalida).text();
    $('#ModalSalida').modal('hide');
    $('#labelLlevar').text(salidaProducto);
    $('#ModalFactura').modal('show');
    $('#ModalFactura').css('overflow-y', 'scroll');
    total = $("#totalBs").val();
    var valor = LITERAL(total);
    $('#numLiteral').val(valor);
    $('#labelNumLiteral').text(valor);
    if (notifFechaLimite!="") {
        alertify.success(notifFechaLimite);
    }
}
function maximizarPagina(URLactual) {
    window.open(URLactual, '', 'fullscreen=yes, scrollbars=no');
}

function listarMenuGrupo() {
    idprimero = 0;

    $.get(RUTAS.categorias, {idSucursal:idSucursal}, function (res) {
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        for (var i = 0; i < json.result.length; i++) {
            $('#divMenuGrupo').append('<button onclick="listarProducto(' + json.result[i].id + ',this)" name="btnMenu" class=" btn  btn-lg botonCategoriaMobil"  id="botonMenu' + json.result[i].id + '">' + json.result[i].nom_grupo + '</button>');
            $('#divBotonProducto').append('<button onclick="moverProductoMenu(' + json.result[i].id + ')" name="btnMenu" class=" btn  btn-lg botonCategoriaMobil"  >' + json.result[i].nom_grupo + '</button>');
            if (i == 0) {
                idprimero = json.result[i].id;

            }
            // $('#loading').css('display','none');
        }

        $('#botonMenu'+idprimero).click();
        // listarProducto(idprimero,$('#botonMenu'+idprimero));
        despuesDeCargar();

    });
}
function listarMesas() {//lista todas las mensas
    $('#divMesa').empty();
    $('#divMesaHabilitadas').empty();
    $.get(RUTAS.mesas, {idSucursal:idSucursal}, function (res) {
        var json = JSON.parse(res);
        var total=0;
        if (verificarError(json.error)) {
            return;
        }
        for (var i = 0; i < json.result.length; i++) {
            if (json.result[i].PROCESANDO != "") {
                total++;
                if (salon == "nosalon") {
                    $('#divMesa').append('<button disabled="" nroMesa="'+json.result[i].NRO_MESA+'" nroPersona="' + json.result[i].NROPERSONA + '" idMesa="' + json.result[i].ID + '" class="aumentarPM btn btn-warning btn-lg" style=" background:' + json.result[i].COLORES + ';   margin: 2px;    width: 10%;      color: black;  height: 52px;white-space: inherit;"><i class="fa fa-cutlery" aria-hidden="true"></i>' + json.result[i].NRO_MESA + '</button>');
                    $('#divMesaHabilitadas').append('<button onclick="traspasarItemMesa(this)" disabled=""  idMesa="' + json.result[i].ID + '" class="btn btn-warning btn-lg" style=" background:' + json.result[i].COLORES + ';   margin: 2px;white-space: inherit;">' + json.result[i].NRO_MESA + '</button>');
                } else {
                    nomcli=json.result[i].NOM_CLI==""?json.result[i].NRO_MESA:json.result[i].NOM_CLI;
                    $('#divMesa').append('<button disabled="" nroMesa="'+json.result[i].NRO_MESA+'"  nroPersona="' + json.result[i].NROPERSONA + '" idMesa="' + json.result[i].ID + '" class="aumentarPM btn btn-warning btn-lg" style="margin: 2px;white-space: inherit; background:' + json.result[i].COLORES + ';   margin: 2px;     color: black;   width: 10%;   height: 46PX;"><i class="fa fa-cutlery" aria-hidden="true"></i>' + nomcli + '</button>');
                    $('#divMesaHabilitadas').append('<button onclick="traspasarItemMesa(this)" disabled=""  idMesa="' + json.result[i].ID + '" class="btn btn-warning btn-lg" style="    color: black; background:' + json.result[i].COLORES + ';   margin: 2px;white-space: inherit;">' + nomcli + '</button>');

                }
                // $('#divMesa').append('<button  idMesa="'+json.result[i].ID+'" class="aumentarPM btn btn-warning btn-lg" style=" background:'+json.result[i].COLORES+';   margin: 2px;">' + json.result[i].NRO_MESA + '</button>');
            }
            if (json.result[i].USO != "" && json.result[i].PROCESANDO == "") {
                total++;
                if (salon == "nosalon") {
                    $('#divMesa').append('<button  idMesa="' + json.result[i].ID + '" nroMesa="'+json.result[i].NRO_MESA+'" nroPersona="' + json.result[i].NROPERSONA + '" class="aumentarPM btn btn-warning btn-lg" style=" background:' + json.result[i].COLORES + ';   margin: 2px; width: 10%;      color: black;  height: 52px;white-space: inherit;"><i class="fa fa-users" aria-hidden="true"></i>' + json.result[i].NRO_MESA + '</button>');
                    $('#divMesaHabilitadas').append('<button onclick="traspasarItemMesa(this)" estado="ocupado"  idMesa="' + json.result[i].ID + '" class="btn btn-warning btn-lg" style=" background:' + json.result[i].COLORES + ';   margin: 2px; width: 10%;    height: 25%;white-space: inherit;"><i class="fa fa-users" aria-hidden="true"></i>' + json.result[i].NRO_MESA + '</button>');
                } else {
                    nomcli=json.result[i].NOM_CLI==""?json.result[i].NRO_MESA:json.result[i].NOM_CLI;
                    $('#divMesa').append('<button  idMesa="' + json.result[i].ID + '" nroMesa="'+json.result[i].NRO_MESA+'" nroPersona="' + json.result[i].NROPERSONA + '" class="aumentarPM btn btn-warning btn-lg" style=" background:' + json.result[i].COLORES + ';   margin: 2px; width: 10%;     color: black;   height: 52px;white-space: inherit;padding: 0;"><i class="fa fa-users" aria-hidden="true"></i>' + nomcli + '</button>');
                    $('#divMesaHabilitadas').append('<button onclick="traspasarItemMesa(this)" estado="ocupado"  idMesa="' + json.result[i].ID + '" class="btn btn-warning btn-lg" style=" background:' + json.result[i].COLORES + ';   margin: 2px; width: 10%;   height: 46PX;white-space: inherit;padding: 0;"><i class="fa fa-users" aria-hidden="true"></i>' + nomcli + '</button>');
                }
            } else {
                if (json.result[i].USO == "" && json.result[i].PROCESANDO == "") {
                    $('#divMesa').append("<button  idMesa='" + json.result[i].ID + "' nroMesa='"+json.result[i].NRO_MESA+"' nroPersona='" + json.result[i].NROPERSONA + "' class='agregarPM btn btn-warning  btn-lg' style='    margin: 2px; width: 10%;       height: 52px;white-space: inherit;'>" + json.result[i].NRO_MESA + "</button>");
                    $('#divMesaHabilitadas').append("<button nroMesa='"+json.result[i].NRO_MESA+"' onclick='traspasarItemMesa(this)' estado='abierta'  idMesa='" + json.result[i].ID + "'' class='btn btn-warning  btn-lg' style='    margin: 2px; width: 10%;       height: 25%;white-space: inherit;'>" + json.result[i].NRO_MESA + "</button>");
                }
            }

        }
        $('#spanCantMesa').text(total);
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}

idBotonMenu="";
auxBotonMenu="";
function listarProducto(id,boton) {
    if (boton!=auxBotonMenu) {
     $(boton).removeClass();
     $(boton).addClass('clickMenuGrupo');

     auxBotonMenu=auxBotonMenu==""?"":$(auxBotonMenu).removeClass(); $(auxBotonMenu).addClass('btn  btn-lg botonCategoria');
     auxBotonMenu=boton;
 }
 idBotonMenu=id;
 json="";
 
 $('#divProducto').empty();
 $.get(RUTAS.productos, {id: id,idSucursal:idSucursal}, function (res,estado) {
    var json = JSON.parse(res);

    if (verificarError(json.error)) {
        return;
    }
    for (var i = 0; i < json.result.length; i++) {

       habilitado= productoHabilitado(json,i);
       if (habilitado!="") {
        if (json.result[i].presa == "") {
            if (json.result[i].imgnormal == "" || json.result[i].imgnormal.charAt(0) != "a") {
                $('#divProducto').append('<button  imppunto4="'+json.result[i].imppunt4+'" imppunto3="'+json.result[i].imppunt3+'" imppunto2="'+json.result[i].imppunt2+'" imppunto1="'+json.result[i].imppunt1+'" impCoc="'+json.result[i].impcoc+'"  tipoProducto="'+json.result[i].tiempo+'" idProducto="'+json.result[i].id+'" cantidad="' + json.result[i].cantidad + '" name="btnProducto" presa="' + json.result[i].presa + '" title="Precio: ' + json.result[i].pre_venta + ' , ' + json.result[i].nom_prod + '" precio="' + json.result[i].pre_venta + '" nombre="' + json.result[i].nom_prod + '"  onclick="listarDetalleVenta(this,' + json.result[i].id + ')" class="btnMenu btn btn-primary " style="border: none;    font-size: 12px;\n\
                    font-weight: bold;       width: 95px;text-shadow: 2px 2px black;    height: 52px; text-shadow: 2px 2px black; font-size: 8pt; box-shadow: 1px 1px #999;    margin-right: 4px;\n\
                    margin-bottom: 3px;    margin-left: 1px;    padding: 0;      margin-top: 1px; white-space: inherit; background:' + json.result[i].colores + ' " >' + json.result[i].nom_prod + '</button>');
            } else {
                $('#divProducto').append('<a  imppunto4="'+json.result[i].imppunt4+'" imppunto3="'+json.result[i].imppunt3+'" imppunto2="'+json.result[i].imppunt2+'" impPunto1="'+json.result[i].imppunt1+'" impCoc="'+json.result[i].impcoc+'"  tipoProducto="'+json.result[i].tiempo+'" idProducto="'+json.result[i].id+'"  cantidad="' + json.result[i].cantidad + '" class="btnMenu" href="#" name="btnProducto" presa="" title="' + json.result[i].pre_venta + ' , ' + json.result[i].nom_prod + '" precio="' + json.result[i].pre_venta + '" nombre="' + json.result[i].nom_prod + '" onclick="listarDetalleVenta(this,' + json.result[i].id + ')">' +
                    '<div class="" style="width: max-content;padding-left: 0;padding-right: 0;overflow: hidden;display: inline-table; ' +
                    'padding-right: 0;    overflow: hidden;"> ' +
                    '<div class="btn btn-primary" style="border: none;    font-size: 12px;' +
                    'font-weight: bold;       width: 95px;    height: 52px;   box-shadow: 1px 1px #999;    margin-right: 4px;' +
                    'margin-bottom: 3px;    margin-left: 1px;     padding: 0;      margin-top: 1px; white-space: inherit; background:; ">' +
                    '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 " >' +
                    '<img src="' + json.result[i].imgnormal + '" style="width: 36px;height: 56px;    position: absolute;    left: 1px;">' +
                    '</div>' +
                    ' <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 "  style="padding: 0;">' +
                    '<span style="position: relative;' +
                    'top: 1px;' +
                    'word-wrap: break-word;' +
                    'text-shadow: 2px 2px black;' +
                    'left: -5px;' +
                    'font-size: 8pt;">' + json.result[i].nom_prod + '</span>' +
                    '</div></div></div></a>');
            }
        } else {
            if (json.result[i].imgnormal == "" || json.result[i].imgnormal.charAt(0) != "a") {
                $('#divProducto').append('<button  imppunto4="'+json.result[i].imppunt4+'" imppunto3="'+json.result[i].imppunt3+'" imppunto2="'+json.result[i].imppunt2+'" imppunto1="'+json.result[i].imppunt1+'" impCoc="'+json.result[i].impcoc+'"  tipoProducto="'+json.result[i].tiempo+'"  idProducto="'+json.result[i].id+'" cantidad="' + json.result[i].cantidad + '"  name="btnProducto"  title="Precio: ' + json.result[i].pre_venta + ' , ' + json.result[i].nom_prod + '" precio="' + json.result[i].pre_venta + '" nombre="' + json.result[i].nom_prod + '"  onclick="cargarSugerencia(this,' + json.result[i].id + ')" class="btnMenu btn btn-primary " style="border: none;    font-size: 12px;\n\
                    font-weight: bold;       width: 95px; text-shadow: 2px 2px black;   height: 52px;   box-shadow: 1px 1px #999;    margin-right: 4px;\n\
                    margin-bottom: 3px;    margin-left: 1px;     padding: 0;      margin-top: 1px; white-space: inherit; background:' + json.result[i].colores + ' " >' + json.result[i].nom_prod + '</button>');
            } else {
                $('#divProducto').append('<a  imppunto4="'+json.result[i].imppunt4+'" imppunto3="'+json.result[i].imppunt3+'" imppunto2="'+json.result[i].imppunt2+'" impPunto1="'+json.result[i].imppunt1+'" impCoc="'+json.result[i].impcoc+'"  tipoProducto="'+json.result[i].tiempo+'" idProducto="'+json.result[i].id+'"  cantidad="' + json.result[i].cantidad + '"  class="btnMenu" href="#" name="btnProducto" presa="" title="Precio: ' + json.result[i].pre_venta + ' , ' + json.result[i].nom_prod + '" precio="' + json.result[i].pre_venta + '"  nombre="' + json.result[i].nom_prod + '" onclick="cargarSugerencia(this,' + json.result[i].id + ')">' +
                    '<div class="" style="width: max-content;display:inline-table;    padding-left: 0;' +
                    'padding-right: 0;    overflow: hidden;"> ' +
                    '<div class="btn btn-primary" style="border: none;    font-size: 12px;' +
                    'font-weight: bold;       width: 95px;    height: 52px;   box-shadow: 1px 1px #999;    margin-right: 4px;' +
                    'margin-bottom: 3px;    margin-left: 1px;     padding: 0;      margin-top: 1px; white-space: inherit; background:; ">' +
                    '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 " >' +
                    '<img src="' + json.result[i].imgnormal + '" style="width: 36px;height: 56px;    position: absolute;    left: 1px;">' +
                    '</div>' +
                    ' <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 "  style="padding: 0;">' +
                    '<span style="position: relative;' +
                    'top: 1px;' +
                    'word-wrap: break-word;' +
                    'text-shadow: 2px 2px black;' +
                    'left: -5px;' +
                    'font-size: 8pt;">' + json.result[i].nom_prod + '</span>' +
                    '</div></div></div></a>');
            }
        }
    }
    habilitado="";
}
        // $('#loading').css('display','none');

    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

}
i = 0;
function listarDetalleVenta(boton, id) {

    var tabla=$('#tablaDetalle tbody tr');
    i++;
    contar = 0;
    subTotal = 0;
    nombre = $(boton).text();
    precio = parseFloat($(boton).attr('precio'));
    cantidad = (parseFloat($(boton).attr('cantidad'))).toFixed(2);
    tipoProducto=$(boton).attr('tipoproducto');
    cant = $('#cant');
    id = id;
    tbody = $('#tbdoyDetalle');

    if (parseFloat(cant.val()) > 0) {
        cantidad = parseFloat(cant.val());
        cant.val("");
    }
    var targetInput = $('#tablaDetalle span[nombreProdu="' + nombre + '"]');

    if (targetInput.length > 0) {
      var  total=0;
      var inputCant = $(targetInput).closest("tr").find("input[name=cantidad]");
      var inputPrecio = $(targetInput).closest("tr").find("input[name=precio]");
      var inputSubtotal = $(targetInput).closest("tr").find("input[name=subTotal]");
      var spansubTotal = $(targetInput).closest("tr").find("#spansubTotal");
      var spanCantidad = $(targetInput).closest("tr").find("#spanCantidad");
      var spanPrecio = $(targetInput).closest("tr").find("#spanPrecio");
        //     alert(inputCant.val());
        // cantidad=parseFloat(cantidad+inputCant.val());
        $(tabla).each(function (e) {
            total+=parseFloat($(this).closest("tr").find("input[name=subTotal]").val());
            
        });
        
        if ( tipoProducto=="TIEMPO"){
         aux=parseFloat($('#botonOculto').attr('subTotal'));
         $('#botonOculto').attr('subTotal','');
     }else{
       if (CEL_CLI!="" && CEL_CLI!=null) {
        porcentajeDescuento=precio * cantidad*0.1;
        aux = precio * cantidad-porcentajeDescuento;

    }else{
        aux = precio * cantidad;

    }
}

totalBs = (total + aux).toFixed(2);

subTotal = parseFloat(inputSubtotal.val()) + aux;
        // totalBs = totalBs + parseFloat(precio);
        $('#totalBs').val(totalBs);

        $('#totalUsd').val((totalBs / tipoCambio).toFixed(2));
        inputSubtotal.val(subTotal.toFixed(2));
        contar = (parseFloat(inputCant.val()) + parseFloat(cantidad)).toFixed(2);
        inputCant.val(contar);
        spansubTotal.text(subTotal.toFixed(2));

        spanCantidad.text(contar);

    } else {
        total=0;
        

        $(tabla).each(function (e) {
            total+=parseFloat($(this).closest("tr").find("input[name=subTotal]").val());
            
        });
        if ( tipoProducto=="TIEMPO"){
         aux=parseFloat($('#botonOculto').attr('subTotal'));
         $('#botonOculto').attr('subTotal','');
     }else{
        if (CEL_CLI!="" && CEL_CLI!=null) {
            porcentajeDescuento=precio * cantidad*0.1;
            aux = precio * cantidad-porcentajeDescuento;

        }else{
            aux = precio * cantidad;

        }
        
    }
    totalBs = (total + aux).toFixed(2);
    var  qr="";
    if ($('#checkCodigoQr').prop('checked')) {
        $('#checkCodigoQr').prop('checked',false);
        qr="x";
    }
    $('#totalBs').val(totalBs);
    $('#totalUsd').val((totalBs / tipoCambio).toFixed(2));
    imppunto4=$(boton).attr('imppunto4')==undefined?"":$(boton).attr('imppunto4');
    imppunto3=$(boton).attr('imppunto3')==undefined?"":$(boton).attr('imppunto3');
    imppunto2=$(boton).attr('imppunto2')==undefined?"":$(boton).attr('imppunto2');
    imppunto1=$(boton).attr('imppunto1')==undefined?"":$(boton).attr('imppunto1');
    impCoc=$(boton).attr('impcoc')==undefined?"":$(boton).attr('impcoc');
    
    tbody.append('<tr class="colorhover" style="     font-weight: bold;    " ><td  style="    border-top: 1px solid #333;width: 9.5%;    text-align: right;"><button style="display:none" name="gestionImpresion"  imppunto4="'+imppunto4+'" imppunto3="'+imppunto3+'" imppunto2="'+imppunto2+'" imppunto1="'+imppunto1+'" impcoc="'+impCoc+'"></button><input readonly="" type="hidden" value="' + id + '" name="inputIdProducto" class="form-control"><input readonly="" type="hidden" value="' + cantidad + '" name="cantidad" class="form-control"><span id="spanCantidad">' + cantidad + '</span>' +
        '<td style="     border-top: 1px solid #333;      width: 52.5%;"><input name="productoQr" type="hidden" value="' + qr + '"><input id="inputProductoId' + i + '" name="nombreProducto" type="hidden" value="' + nombre + '"><span nombreProdu="' + nombre + '" id="spanProd' + id + '" name="nombreProducto" >' + nombre + '</span><input class="form-control" type="hidden" name="notaProducto" onchange="agregarNota(this,' + i + ')" onblur="agregarNota(this,' + i + ')"  id="notaProducto' + i + '">' +
        '<td  style="     text-align: right;  border-top: 1px solid #333;    width: 11%;  text-align: right;"><input readonly="" name="precio" type="hidden" value="' + precio + '" class="form-control"><span id="spanPrecio">' + precio.toFixed(2) + '</span>' +
        '<td style="     text-align: right;    border-top: 1px solid #333;    width: 11%;  text-align: right;"><input name="tipoProducto" type="hidden" value="'+$(boton).attr("tipoproducto")+'"><input readonly="" type="hidden"  value="' + (aux).toFixed(2) + '" name="subTotal" class="form-control col-xs-3"><span id="spansubTotal">' + (aux).toFixed(2) + '</span>' +
        '<td style="    padding: 0px 3%; border-top: 1px solid #333;"><button class="btn btn-danger btn-sm delete-btn"><i class="fa fa-trash-o" aria-hidden="true"></i></button>' +
        '<button class="menuNota btn btn-warning btn-sm" onclick="cargarModalNota(this)" >Nota</button>'
        );
}
toastr["success"](nombre);
return i;

}

function limpiarDetalle() {
    tbody = $('#tbdoyDetalle');
    tbody.empty();
    $('#totalBs').val("0.00");
    $('#totalUsd').val("0.00");
    $('#totalFacturaBs').val("");
    $('#totalFacturaUs').val("");
    $('#nombreCli').val("");
    $('#nitCli').val("");
    $('#inputContadoFacturar').click();
    $('#btnFacturar').attr('onclick', 'llenarDatosModalF()');
    $('#nroOrden').val('');
    if (idMesa != null && idMesa!="") {
        hablilitarMesaProcesando(idMesa);
    }
    idMesa=null;

    $('#spanProcesando').text("");
    i = null;
    totalBs = null;
    CEL_CLI=null;

    
    json=[];
    tabla=null;
    error=null;
    subTotal=null;
    opcionItemMesa=0;
    total=null;
    tito=null;
    imPresion=null;
    idDet = [];
    cantidadMesaProd = [];
    nombrePdto = [];
    idProdMes = [];
    contador2 = null;
    idProducto=[];
    cantidadA=[];
    nombrePdto=[];
    arrayNotaProducto=[];
    precioA=[];
    NombreProdc=[];
    subtotalA=[];
    numCelularTigo=null;
    transaccionTigoMoney=null;
    $('#selectTipoPagoCredito').prop('selectedIndex',0);
}

$(document).on("click", ".delete-btn", function () {

    $(this).closest("tr").remove();
    var subtotal = 0;
    var targetInput = $('#tablaDetalle input[name=cantidad]');
    if (targetInput.length > 0) {
        $(targetInput).each(function (e) {
            var gridNum = $(this).closest("tr").find("input[name=cantidad]");
            var itemPrice = $(this).closest("tr").find("input[name=precio]");
            subtotal += $(gridNum).val() * $(itemPrice).val()
        });
    }
    tota_bs = document.getElementById("totalBs").value = parseFloat(subtotal).toFixed(2);
    totalBs = parseFloat(tota_bs);
    $('#totalUsd').val((tota_bs / tipoCambio).toFixed(2));


    // document.getElementById("cantext-total_us").value = parseFloat(subtotal).toFixed(2)/parseFloat(vcam_of).toFixed(3);             
});
filaNombreinput = "";
function cargarModalNota(input) {

    filaNotaProducto = $(input).closest("tr").find("input[name=notaProducto]");
    filaNotaProducto.attr('type', 'text');
    filaNotaProducto.focus();

    // $('#tituloProductoNota').text('"' + filaNombreinput.val() + '"');

    // $('#inputNota').val(filaNombre)
}
function agregarNota(input, index) {
    filaNombre = $(input).closest("tr").find("span[name=nombreProducto]");
    filaNombreinput = $(input).closest("tr").find("input[name=nombreProducto]");
    nota = $(input).val();
    if (nota != "") {
        filaNombre.attr('nombreprodu', nota);
        filaNombre.text(filaNombreinput.val() + "(" + nota + ")");
    } else {
        filaNombre.text(filaNombreinput.val());
    }
    $(input).attr('type', 'hidden');
}

CEL_CLI="";
$("#telefonoNitCliente").autocomplete({


    serviceUrl: RUTAS.clientesSugerenciasDomicilio,
    params: {campo: "nit"},
    minLength: 3,
    cache: false,
    width: 450,
    caheLength: 1,
    noCache: true,
    
    select: function (event, ui) {
        console.log(ui.item);
        
        $("#telefonoCliente").val(ui.item.TEL_CLI);
        $('#NombreClienteDomicilio').val(ui.item.nomCli);
        $('#direccionCliente').val(ui.item.DIRECCION);
        $(this).data();

    }

});

$("#NombreClienteDomicilio").autocomplete({
    minChars:3,
    maxHeight:200,
    lookupLimit:50,
    lookup: function (query, done) {

        $.ajax({
           url: RUTAS.clientesSugerenciasDomicilio,
           type: 'get',
           dataType: 'JSON',
           async: true,
           data: { term: query, campo: "nombre" },
           success: function(data) {
             done(data);
         }
     }).done(function(data){
        done( data );
        
    });
 },
 onSelect: function (suggestion) {
        // alert('You selected: ' + suggestion.value + ', ' + suggestion.nit);
        $("#telefonoCliente").val(suggestion.TEL_CLI);
        $('#NombreClienteDomicilio').val(suggestion.nomCli);
        $('#direccionCliente').val(suggestion.DIRECCION);
        $('#telefonoNitCliente').val(suggestion.nitCli);

        $(this).autocomplete('clear');
    }

});
$("#telefonoCliente").autocomplete({
 minChars:3,
 maxHeight:200,
 lookupLimit:50,
 width:300,
 lookup: function (query, done) {
    $.ajax({
       url: RUTAS.clientesSugerenciasDomicilio,
       type: 'get',
       dataType: 'JSON',
       async: true,
       data: { term: query, campo: "telefono" },
       success: function(data) {
         done(data);
     }
 }).done(function(data){
    done( data );
});
},
onSelect: function (suggestion) {
        // alert('You selected: ' + suggestion.value + ', ' + suggestion.nit);
        $(this).val(suggestion.label);
        $('#direccionCliente').val(suggestion.DIRECCION);
        $('#telefonoNitCliente').val(suggestion.nitCli);
        $('#NombreClienteDomicilio').val(suggestion.nomCli);
        
        $(this).autocomplete('clear');
    }

    
});

$("#nombreCli").autocomplete({
    minChars:3,
    maxHeight:200,
    lookupLimit:50,
    width:300,
    lookup: function (query, done) {

        $.ajax({
           url: RUTAS.clientesSugerenciasNombre,
           type: 'get',
           dataType: 'JSON',
           async: true,
           data: { term: query },
           success: function(data) {
             done(data);
         }
     }).done(function(data){
        done( data )
        
    });
 },
 onSelect: function (suggestion) {
        // alert('You selected: ' + suggestion.value + ', ' + suggestion.nit);
        $('#nombreCli').val(suggestion.label);
        $('#nitCli').val(suggestion.number);
        CEL_CLI=suggestion.CEL_CLI;
        $(this).autocomplete('clear');
    }

});

$("#nitCli").autocomplete({
 minChars:3,
 maxHeight:200,
 lookupLimit:50,
 width:300,
 lookup: function (query, done) {

    $.ajax({
       url: RUTAS.clientesSugerenciasNit,
       type: 'get',
       dataType: 'JSON',
       async: true,
       data: { term: query },
       success: function(data) {
         done(data);
     }
 }).done(function(data){
    done( data )
    
});
},
onSelect: function (suggestion) {
 $('#nombreCli').val(suggestion.cliente);
 $("#nitCli").val(suggestion.label);
 CEL_CLI=suggestion.CEL_CLI;
 
        // alert('You selected: ' + suggestion.value + ', ' + suggestion.nit);
        
    }
    
});


function llenarDatosModalF() {
    if ($('#totalBs').val() != '0.00') {
        $('#cambioBs').val("-" + $('#totalBs').val());
        $('#cambioUsd').val("-" + $('#totalUsd').val());
        $('#ModalSalida').modal('show');

    } else {
        alert("SELECCIONE ITEM POR FAVOR");
    }

    // $('#cambioUsd')
}


var isMobile = {
    Android: function () {
        return navigator.userAgent.match(/Android/i);
    },
    BlackBerry: function () {
        return navigator.userAgent.match(/BlackBerry/i);
    },
    iOS: function () {
        return navigator.userAgent.match(/iPhone|iPad|iPod/i);
    },
    Opera: function () {
        return navigator.userAgent.match(/Opera Mini/i);
    },
    Windows: function () {
        return navigator.userAgent.match(/IEMobile/i);
    },
    any: function () {
        return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
    }
};


function despuesDeCargar() {
    if (isMobile.any())
    {
        $('#labelUsuario').removeClass();
        $('#labelUsuario').addClass('labelUMobil');
        $('#labelTipoCambio').removeClass();
        $('#labelTipoCambio').addClass('labelTMobil');

        $('#labelTurno').removeClass();
        $('#labelTurno').addClass('labelTurnoMobil');

        $('#h1reloj').removeClass();
        $('#h1reloj').addClass('relojMobil');
        $('#h3FechaActual').removeClass();
        $('#h3FechaActual').addClass('fechaMobilH3');

    } else {

        $('button[name=btnMenu]').removeClass("botonCategoriaMobil");
        $('button[name=btnMenu]').addClass("botonCategoria");
        $('#divProducto').removeClass('divProductoMobil');
        $('#divProducto').addClass('divProducto');

        $('#divMenuGrupo').removeClass('divCategoriaMobil');
        $('#divMenuGrupo').addClass('divCategoria');

    }

}


function ponerMoneda(moneda) {
    if ($('#totalFacturaBs').val()) {
        totalFacturaBs = parseFloat($('#totalFacturaBs').val());
        totalFacturaBs += moneda;
        totalFacturaUsd = totalFacturaBs / tipoCambio;
        $('#totalFacturaBs').val(totalFacturaBs);
        // $('#totalFacturaUs').val((totalFacturaUsd).toFixed(2));
    } else {
        $('#totalFacturaBs').val(moneda);
        // $('#totalFacturaUs').val((moneda/tipoCambio).toFixed(2));
    }
    sacarCambio();
}

function sacarCambio() {

    verificar=(parseFloat($('#totalFacturaBs').val()));
    tipoMonedaFactura=$('input[name=tipoMonedaFactura]:checked').val();
    if (tipoMonedaFactura=="bs") {
        totalFacturaBs =$('#totalFacturaBs').val();    
    }else{
        totalFacturaBs="0";
    }
    
    // if (validar('decimal')) {}
    // alert(verificar);
    if (totalFacturaBs != "") {
        if (tipoMonedaFactura=="dolar") {
         totalFacturaBs=parseFloat($('#totalFacturaUs').val()*tipoCambio);
         $('#totalFacturaBs').val(totalFacturaBs);
     }
     totalDebe = parseFloat($('#totalBs').val());
     cambioBs = $('#cambioBs').val();
     totalCambioBs = parseFloat(totalFacturaBs) - totalDebe;
     $('#cambioBs').val((totalCambioBs).toFixed(2));
     $('#cambioUsd').val((totalCambioBs / tipoCambio).toFixed(2));
     $('#totalFacturaUs').val(parseFloat((totalFacturaBs / tipoCambio).toFixed(2)));
        // $('#totalFacturaUs').val((totalFacturaBs/tipoCambio).toFixed(2));

    } else {

        $('#cambioBs').val("-" + $('#totalBs').val());
        $('#cambioUsd').val("-" + $('#totalUsd').val());
        $('#totalFacturaBs').val("");
        $('#totalFacturaUs').val("");
        // $('#totalFacturaUs').val("");

    }
    estadoCambioCobro();

}
function teclaCobro(tecla) {
    var input = $('#radioMonedaDolarFactura').is(':checked') ? $('#totalFacturaUs') : $('#totalFacturaBs');
    var valor = input.val();
    if (tecla == 'C') {
        valor = '';
    } else if (tecla == 'borrar') {
        valor = valor.slice(0, -1);
    } else if (tecla == '.') {
        if (valor.indexOf('.') == -1) {
            valor = (valor == '' ? '0' : valor) + '.';
        }
    } else {
        valor += tecla;
    }
    input.val(valor);
    if (valor == '' || valor == '0.') {
        $('#totalFacturaBs, #totalFacturaUs').val(valor);
        $('#cambioBs').val("-" + $('#totalBs').val());
        $('#cambioUsd').val("-" + $('#totalUsd').val());
        estadoCambioCobro();
        return;
    }
    sacarCambio();
}

function billeteCobro(moneda) {
    $('#radioMonedaBsFactura').prop('checked', true);
    ponerMoneda(moneda);
}

function pagoExactoCobro() {
    $('#radioMonedaBsFactura').prop('checked', true);
    $('#totalFacturaBs').val(parseFloat($('#totalBs').val()) || 0);
    sacarCambio();
}

function estadoCambioCobro() {
    var cambio = parseFloat($('#cambioBs').val());
    $('#cobroCambio').toggleClass('is-falta', isNaN(cambio) || cambio < 0);
}

function cancelarCobro() {
    $('#totalFacturaBs').val('');
    $('#totalFacturaUs').val('');
    $('#inputContadoFacturar').click();
    $('#selectTipoPagoCredito').prop('selectedIndex', 0);
    $('#nroOrden').val('');
}

$('#ModalFactura').on('show.bs.modal', function () {
    $('#cobroTotalBs').text($('#totalBs').val());
    $('#cobroTotalUsd').text($('#totalUsd').val());
    $('#radioMonedaBsFactura').prop('checked', true);
    estadoCambioCobro();
});

function  pedidoMesa(botonMesa) {
    if ($('#totalBs').val() != '0.00') {
        $('#cambioBs').val("-" + $('#totalBs').val());
        $('#cambioUsd').val("-" + $('#totalUsd').val());

        $('#labelLlevar').text("MESA");
    } else {
        alert("SELECCIONE ITEM POR FAVOR");
        return;
    }
    if (notifFechaLimite!="") {
        alertify.success(notifFechaLimite);
    }
    numMesa = $('#numMesa').val();
    // var nroOrden=numMesa==''
    $('#nroOrden').val(numMesa);
    botonMensa = $(botonMesa).attr('salida');
    // salidaProducto = botonMensa + ":" + numMesa;
    salidaProducto = botonMensa;
    $('#ModalTecladoMesa').modal('hide');

    $('#ModalSalida').modal('hide');
    $('#ModalFactura').modal('show');
    $('#ModalFactura').css('overflow-y', 'scroll');
    total = $("#totalBs").val();
    var valor = LITERAL(total);
    $('#numLiteral').val(valor);
    $('#labelNumLiteral').text(valor);
    $('#numMesa').val("");
}

procesoRefrescar=0;
function guardarDetalle() {//guarda la venta en geneneral1
    procesoRefrescar++;
    $('#btnGuardarDetalle').attr('disabled','disabled');
    // $('#loading').css('display','block');
    var  tabla = $('#tablaDetalle input[name=cantidad]');
    var  selectPrepago ="";

    var nombreCli = $('#nombreCli').val();
    var nitCli = $('#nitCli').val();
    var  cambioBs = $('#cambioBs').val();
    var  cambioUsd = $('#cambioUsd').val();
    var  totalFacturaBs = $('#totalFacturaBs').val();
    var  totalFacturaUs = $('#totalFacturaUs').val();
    // formaPago = $('input[name=formaPago]').val();
    var  totalBs = $('#totalBs').val();
    var  totalUsd = $('#totalUsd').val();
    var  formaPago = $('input:radio[name=formaPago]:checked').val();
    var selectTipoPagoCredito=$('#selectTipoPagoCredito').val();

    if (formaPago == "CREDITO" && selectTipoPagoCredito!="tigomon") {
        if (nombreCli == "SIN NOMBRE" || nombreCli == "") {
            alert("EL CAMPO NOMBRE DE CLIENTE NO DEBE ESTAR VACIO");
            $('#btnGuardarDetalle').removeAttr('disabled');
            return;
        }
    }
    if (formaPago=="PREPAGO") {
     selectPrepago=$('#selectPrepago ').val();
 }
 var  fechaVenta = vf_Venta;
 var  turno = $('#vturno').text();
 var valor = LITERAL(totalBs);
    // alert(valor);
    if (parseFloat(cambioBs) < 0) {
     $('#btnGuardarDetalle').removeAttr('disabled');
     alertify.error('LA CANTIDAD TIENE QUE SER MAYOR AL TOTAL');

     return;
 }
 var  selectVendedor = $('#selectVendedor').val();
 var  idProducto = new Array();
 var cantidadA = new Array();
 var precioA = new Array();
 var arrayNotaProducto = new Array();
 var NombreProdc=new Array();
 var subtotalA=new Array();
 var gQr=new Array();
   var nombrePdto=new Array();//este array se encarga d mandar a cocina el producto con la nota

   var contador=0;
   var  imPresion='{"imp":[';
   if (tabla.length > 0) {
    $(tabla).each(function (e) {
        var id = $(this).closest("tr").find("input[name=inputIdProducto]").val();
        var cantidad = $(this).closest("tr").find("input[name=cantidad]").val();
        var precio = $(this).closest("tr").find("input[name=precio]").val();
        var notaPducto = $(this).closest("tr").find("input[name=notaProducto]").val();
        var nompdo = $(this).closest("tr").find("input[name=nombreProducto]").val();
        var subTotal = $(this).closest("tr").find("#spansubTotal").text();
        var qr = $(this).closest("tr").find("input[name=productoQr]").val();
        var imppunto4A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto4');
        var imppunto3A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto3');
        var imppunto2A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto2');
        var imppunto1A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto1');
        var impCocA=$(this).closest("tr").find("button[name=gestionImpresion]").attr('impcoc');
        contador++;
        if(tabla.length!=contador){
            imPresion+='{"impCoc":"'+impCocA+'","imppunto4":"'+imppunto4A+'","imppunto3":"'+imppunto3A+'","imppunto2":"'+imppunto2A+'","imppunto1":"'+imppunto1A+'"},';
        }else{
            imPresion+='{"impCoc":"'+impCocA+'","imppunto4":"'+imppunto4A+'","imppunto3":"'+imppunto3A+'","imppunto2":"'+imppunto2A+'","imppunto1":"'+imppunto1A+'"}';

        }
        
        idProducto.push(id);
        arrayNotaProducto.push(notaPducto);
        NombreProdc.push(nompdo);
        subtotalA.push(subTotal);
        gQr.push(qr);
        precioA.push(precio);
        cantidadA.push(cantidad);
        if (notaPducto == "") {
            nombrePdto.push(nompdo);
        } else {
            nombrePdto.push(nompdo + "(" + notaPducto + ")");
        }
    });
    imPresion+=']}';
    JSON.stringify(imPresion);
    var tito=JSON.parse(imPresion);
    nroOrden=$('#nroOrden').val()==""?"":':'+$('#nroOrden').val();
    salidaProducto+=nroOrden;
    $.post(RUTAS.facturar, {nota: arrayNotaProducto, salidaProducto: salidaProducto, totalUsd: totalUsd, totalBs: totalBs, vendedor: selectVendedor, cantidad: cantidadA, idProducto: idProducto, nombreCli: nombreCli, nitCli: nitCli,
        cambioBs: cambioBs,direccionCli:direccionCli,telefonoCli:telefonoCli ,cambioUsd: cambioUsd, totalFacturaBs: totalFacturaBs, totalFacturaUs: totalFacturaUs, formaPago: formaPago, fechaVenta: fechaVenta,
        turno: turno,selectPrepago:selectPrepago,precio:precioA,nombreProduc:NombreProdc,subTotal:subtotalA,billar:billar,qr:gQr,transaccionTigoMoney:transaccionTigoMoney}, function (res) {
            var json = JSON.parse(res);
            var fac=json.nroFac;
            if (verificarError(json.error)) {
                return;
            }
             $('#fechaCamHis').val("");
            cambiarFechaPersonalizada=0;
            $('#ModalFactura').modal('hide');
            $('#orden').val(json.result2);
            // $('#loading').css('display','none');
            
            $.post(RUTAS.impresionVenta,{fechaHoy:fechaVenta , totalBs: totalBs,literal:valor,idVenta:json.result,Fac:json.nroFac}, function (resi) {
                var jsoni = JSON.parse(resi);
                // printJS('abms1/REPORTEPHP/'+json.result);
                if (jsoni.error != "") {
                    if (isMobile.any())
                    {
                        if (impresionnube=="impresionnube") {
                         $.ajax({
                            url:"http://" + ipCliente + ":8080/reportes/imprimir.php",
                            datatype:"jsonp",
                            data:{idVenta:json.result,cambioBs:jsoni.listaVenta[0].CAMBIO_BS,direccionCli:direccionCli,telefonoCli:telefonoCli, totalBs: totalBs,nombreCli:nombreCli,nitCli:nitCli,fechaHoy:fechaHoy,fac:fac,codigoqu: jsoni.codigoqr,  listaDVenta: jsoni.listaDVenta, literal: valor, listaEmpresa: jsoni.listaEmpresa},
                            type:"post",
                            success:function(res){

                            }
                        });
                     }else{
                         $.ajax({
                            url:"abms1/reportes/imprimir.php",
                            datatype:"jsonp",
                            data:{idVenta:json.result,cambioBs:jsoni.listaVenta[0].CAMBIO_BS,direccionCli:direccionCli,telefonoCli:telefonoCli, totalBs: totalBs,nombreCli:nombreCli,nitCli:nitCli,fechaHoy:fechaHoy,fac:fac,codigoqu: jsoni.codigoqr,  listaDVenta: jsoni.listaDVenta, literal: valor, listaEmpresa: jsoni.listaEmpresa},
                            type:"post",
                            success:function(res){

                            }
                        });
                     }
                     
                        // $.post("http://" + ipCliente + ":8080/reportes/imprimir.php", {fechaHoy:fechaHoy,fac:fac,codigoqu: jsoni.codigoqr, listaVenta: jsoni.listaVenta, listaDVenta: jsoni.listaDVenta, literal: valor, listaEmpresa: jsoni.listaEmpresa}, function (res) {}); //aqui hay q descomentar para que imprima desde el celular
                        // window.open("abms1/REPORTEPHP/prueba4.php?idVenta="+json.result+"&&literal="+valor);                   

                    } else {
                        
                        if (impresionnube=="impresionnube") {
                            $.ajax({
                                url:"http://" + ipCliente + ":8080/reportes/imprimir.php",
                                datatype:"jsonp",
                                data:{idVenta:json.result,cambioBs:jsoni.listaVenta[0].CAMBIO_BS,direccionCli:direccionCli,telefonoCli:telefonoCli, totalBs: totalBs,nombreCli:nombreCli,nitCli:nitCli,fechaHoy:fechaHoy,fac:fac,codigoqu: jsoni.codigoqr,  listaDVenta: jsoni.listaDVenta, literal: valor, listaEmpresa: jsoni.listaEmpresa},
                                type:"post",
                                success:function(res){

                                }
                            });
                        }else{
                         $.ajax({
                            url:"abms1/reportes/imprimir.php",
                            datatype:"jsonp",
                            data:{idVenta:json.result,cambioBs:jsoni.listaVenta[0].CAMBIO_BS,direccionCli:direccionCli,telefonoCli:telefonoCli, totalBs: totalBs,nombreCli:nombreCli,nitCli:nitCli,fechaHoy:fechaHoy,fac:fac,codigoqu: jsoni.codigoqr,  listaDVenta: jsoni.listaDVenta, literal: valor, listaEmpresa: jsoni.listaEmpresa},
                            type:"post",
                            success:function(res){

                            }
                        });
                     }
                       // $.post("http://localhost:8080/reportes/imprimir.php", {fechaHoy:fechaHoy,fac:fac,codigoqu: jsoni.codigoqr, listaVenta: jsoni.listaVenta, listaDVenta: jsoni.listaDVenta, literal: valor, listaEmpresa: jsoni.listaEmpresa}, function (res) { });//  imprimir a la factura 
                        //para imprimir factura desde ordenador
                    }
                    
                    // alert("impresora no conectada");
                    // ventana=window.open("abms1/REPORTEPHP/prueba4.php?idVenta="+json.result+"&&literal="+valor); 
                    if (opcionItemMesa==0) {
                        var nroOrden=$('#nroOrden').val();
                        if (impresionnube=="impresionnube") {
                            $.ajax({
                                url:"http://" + ipCliente + ":8080/reportes/printCocina.php",
                                datatype:"jsonp",
                                data:{nroOrden:nroOrden,cantidad:cantidadA,subTotal:subtotalA,imPresion:tito.imp, nombrePdto: nombrePdto, listaVenta: jsoni.listaVenta, listaDVenta: jsoni.listaDVenta},
                                type:"post",
                                success:function(res){
                                    if (procesoRefrescar==10) {
                                        location.reload();
                                    }
                                }
                        });//Imprimir cocina
                        }else{
                            $.ajax({
                                url:"abms1/reportes/printCocina.php",
                                datatype:"jsonp",
                                data:{nroOrden:nroOrden,cantidad:cantidadA,subTotal:subtotalA,imPresion:tito.imp, nombrePdto: nombrePdto, listaVenta: jsoni.listaVenta, listaDVenta: jsoni.listaDVenta},
                                type:"post",
                                success:function(res){
                                    if (procesoRefrescar==10) {
                                        location.reload();
                                    }
                                }
                        });//Imprimir cocina
                        }
                        limpiarDetalle();
                    }
                }
                if (opcionItemMesa == 1) {//solo paga los items selecionados 

                    habilitarActualizarMesa(opcionItemMesa);
                    $('#numMesa').val("");
                    $('#btnFacturar').attr('onclick', 'llenarDatosModalF()');
                    $('#spanProcesando').text("");
                    opcionItemMesa = 0;
                }
            if (opcionItemMesa == 2) {//paga toda la mesa y la habilita para el siguiente uso

                habilitarActualizarMesa(opcionItemMesa);
                $('#numMesa').val("");
                $('#btnFacturar').attr('onclick', 'llenarDatosModalF()');
                $('#spanProcesando').text("");
                opcionItemMesa = 0;
            }
            $('#btnGuardarDetalle').removeAttr('disabled');


            direccionCli="";
            telefonoCli="";
            idTarjeta="";
            // TODO migración: PDF de factura (REPORTEPHP/prueba4.php) pendiente de portar a Laravel
          //  window.open("abms1/REPORTEPHP/prueba4.php?idVenta="+json.result+"&&literal="+valor+"&&Fac="+fac+"&&fechaHoy="+fechaVenta);
      });
            // ventana=window.open("abms1/REPORTEPHP/prueba4.php?idVenta="+json.result+"&&literal="+valor);    
            // ventana.close();
            alertify.success("GUARDADO CORRECTAMENTE");



            

        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    } else {
        alertify.error("coloque los datos ");
    }

}


function sinNombre() {


    if (!$('#nombreCli').val()) {
        $('#nombreCli').val("SIN NOMBRE");
    }


}
habilitardtp = 1;
fechaAnterior = "";
function listarHistorico() {

//     if (habilitardtp == 1) {
//         $.fn.datepicker.defaults.format = "yyyy-mm-dd";
//         $('.datepicker').datepicker({
//             autoclose: true,
//             dateFormat: 'yy-mm-dd'
//         }).datepicker("setDate", f_Venta);
//         habilitardtp = 0;
//         $('#fechaHistorico').attr('onchange', 'listarHistorico()');
// //        $('.datepicker').datepicker({
// //         autoclose: true,
// //         dateFormat: 'yy-mm-dd'
// //     }).on("change", function (e) {
// //     //console.log("Date changed: ", e.target.value);
// //         alert("holi");

// // });
//     }


$('#loading').css('display', 'block');
var fechaHistorico = moment($('#fechaHistorico').val(), 'DD/MM/YYYY');

fechaHistorico = fechaHistorico.format('YYYY-MM-DD');
    fechaAnterior = fechaHistorico;
    tbodyHistorico = $('#tbodyHistorico');
    limpiarDetalleHistorico();
    $('.modal-backdrop').css("height", '100%');
    $.get(RUTAS.historico, {fechaHistorico: fechaHistorico}, function (res, status) {
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        $('#tableHistorico').DataTable().destroy();
        tbodyHistorico.empty();
        for (var i = 0; i < json.result.length; i++) {
            tbodyHistorico.append(filaHistorico(json.result[i]));
        }

        paginadortbodyHistorico();
        resumenHistorico();

        $('#loading').css('display', 'none');
        if (status != "success") {
            alert("LO SIENTO A SUCEDIDO UN ERROR")
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);
    });

}

function escHistorico(valor) {
    return $('<div>').text(valor == null ? '' : valor).html();
}

function filaHistorico(venta) {
    var activa = venta.ESTADO == "ACTIVO";
    var total = parseFloat(venta.TOTAL_FACT) || 0;
    var hora = (venta.DHORAVEN || '').substring(0, 5);
    return "<tr class='colorhover" + (activa ? "" : " is-anulada") + "' data-total='" + total + "'>" +
        "<td data-order='" + (parseInt(venta.ORDEN, 10) || 0) + "'><input name='idVenta' type='hidden' value='" + venta.ID + "'><strong>#" + escHistorico(venta.ORDEN) + "</strong></td>" +
        "<td>" + escHistorico(hora) + "</td>" +
        "<td>" + escHistorico(venta.LOGIN) + "</td>" +
        "<td>" + escHistorico(venta.FORMAPAGO) + "</td>" +
        "<td>" + escHistorico(venta.SALIDA) + "</td>" +
        "<td>" + escHistorico(venta.TURNO) + "</td>" +
        "<td class='text-right' data-order='" + total + "'>" + total.toFixed(2) + "</td>" +
        "<td class='js-estado'>" + badgeEstadoHistorico(venta.ESTADO) + "</td>" +
        "<td class='js-eliminpor'>" + escHistorico(venta.ELIMINPOR) + "</td>" +
        "<td class='text-right'>" + (activa
            ? "<button class='pos-hist-accion is-anular' idProduc='" + venta.ID + "' onclick='anularVenta(this,1)'>ANULAR</button>"
            : "<button class='pos-hist-accion is-activar' idProduc='" + venta.ID + "' onclick='anularVenta(this,1)'>ACTIVAR</button>") +
        "</td></tr>";
}

function badgeEstadoHistorico(estado) {
    var activa = estado == "ACTIVO";
    return "<span class='pos-hist-badge " + (activa ? "is-ok" : "is-bad") + "'>" + (activa ? "Activa" : "Anulada") + "</span>";
}

function resumenHistorico() {
    var filas = $('#tbodyHistorico tr').has('input[name=idVenta]');
    var anuladas = filas.filter('.is-anulada');
    var total = 0;
    filas.not('.is-anulada').each(function () {
        total += parseFloat($(this).attr('data-total')) || 0;
    });
    $('#histResVentas').text(filas.length);
    $('#histResActivas').text(filas.length - anuladas.length);
    $('#histResAnuladas').text(anuladas.length);
    $('#histResTotal').text(total.toFixed(2));
}

function limpiarDetalleHistorico() {
    $('#tbodyHistoricoDetalle').html("<tr class='pos-hist-vacio'><td colspan='3'><i class='fa fa-hand-pointer-o'></i> Seleccione una venta de la lista</td></tr>");
    $('#spanHistoricoHora').text('--:--');
    $('#totalDetalle').text('0.00');
    $('#nombreCliHistorico, #procesoHistorico, #df, #td').val('');
}

$("#tableHistorico tbody").on('click', 'tr', function () {    // function_tr
    var data = $(this).find("input[name=idVenta]").val();
    if (!data) {
        return;
    }
    $('#tbodyHistorico tr.is-selected').removeClass('is-selected');
    $(this).addClass('is-selected');
    totalDetalle = 0;
    tbodyHistoricoDetalle = $('#tbodyHistoricoDetalle');
    $.get(RUTAS.detalleVenta, {id: data}, function (res) {
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        tbodyHistoricoDetalle.empty();
        for (var i = 0; i < json.result.length; i++) {
            var subTotal=parseFloat(json.result[i].AGRUPA1) || 0;
            totalDetalle +=subTotal;
            tbodyHistoricoDetalle.append("<tr>" +
                "<td class='text-right'>" + escHistorico(json.result[i].CANTIDAD) + "</td>" +
                "<td>" + escHistorico(json.result[i].NOM_PROD) + "</td>" +
                "<td class='text-right'>" + subTotal.toFixed(2) + "</td></tr>"
                );
        }
        if (json.result.length == 0) {
            tbodyHistoricoDetalle.append("<tr class='pos-hist-vacio'><td colspan='3'>La venta no tiene productos</td></tr>");
        }
        $('#spanHistoricoHora').text(json.result2[0].DHORAVEN || '--:--');
        $('#totalDetalle').text(totalDetalle.toFixed(2));
        $('#nombreCliHistorico').val(json.result2[0].NOM_CLI);
        $('#procesoHistorico').val(json.result2[0].ID);
        $('#df').val(json.result2[0].FORTAX);
        $('#td').val(json.result2[0].TOT_DES);
    }).fail(function (res) {
        $('#loading').css('display', 'none');
        alert("ERROR INTENTE NUEVAMENTE");
    });
});
function paginadortbodyHistorico() {
    $('#tableHistorico').DataTable({
        "destroy": true,
        "order": [[0, "asc"]],
        "scrollY": "calc(100vh - 330px)",
        "scrollCollapse": true,
        "paging": false,
        "dom": "t<'pos-hist-info'i>",
        "columnDefs": [{"orderable": false, "targets": [9]}],
        "language": {
            "emptyTable": "No hay ventas registradas en esta fecha",
            "zeroRecords": "Ninguna venta coincide con la búsqueda",
            "info": "Mostrando _TOTAL_ ventas",
            "infoEmpty": "Sin ventas",
            "infoFiltered": "(filtradas de _MAX_)"
        },
        retrieve: true

    });
    var buscar = $('#buscarHistorico').val();
    if (buscar) {
        $('#tableHistorico').DataTable().search(buscar).draw();
    }
}
$('#buscarHistorico').on('input', function () {
    $('#tableHistorico').DataTable().search(this.value).draw();
});
$('#ModalaHistoricoDia').on('shown.bs.modal', function () {
    $('#tableHistorico').DataTable().columns.adjust();
});

function anularVenta(boton, index) {
    id = $(boton).attr('idProduc');
    estado = $(boton).text();
    $('#loading').css('display', 'block');
    $.post(RUTAS.anularVenta, {id: id, estado: estado}, function (res, status) {
        var json = JSON.parse(res);
        var fila = $(boton).parents("tr");
        if (verificarError(json.error)) {
            return;
        }
        if (estado == "ANULAR") {
            $(boton).removeClass('is-anular').addClass('is-activar').text("ACTIVAR");
            fila.addClass('is-anulada');
            fila.find('td.js-estado').html(badgeEstadoHistorico("INACTIVO"));
        } else {
            $(boton).removeClass('is-activar').addClass('is-anular').text("ANULAR");
            fila.removeClass('is-anulada');
            fila.find('td.js-estado').html(badgeEstadoHistorico("ACTIVO"));
        }
        resumenHistorico();

        $('#loading').css('display', 'none');

    }).fail(function () {
        alert("ERROR INTENTE NUEVAMENTE");
    });
}

function cargarSugerencia(boton, id) {
    botonProducto = boton;//variable global

    idProd = id;//variable global
    spanNombre = $('#tablaDetalle span[id="spanProd' + idProd + '"]');
// if (spanNombre.length!=0) {
// listarDetalleVenta(boton,id);
// }else{
    $('#tituloProductoSugerencia').val($(boton).attr('nombre'));
    // $('#loading').css('display','block');
    $('#divSugerencia').empty();
    $.get(RUTAS.opcionesProducto, {idProducto: id}, function (res) {
        json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        if (json.result.length != 0) {
            for (var i = 0; i < json.result.length; i++) {
                $('#divSugerencia').append("<button style='    margin-top: 4px;min-width: 100px;max-width: 100px; margin-right: 3px; white-space: inherit;font-size: 14px; padding: 0;    min-height: 41px;' onclick='cargarSugerenciaProducto(this)' class='btn btn-lg'>" + json.result[i].OPCION);
            }

            $('#ModalSugerencias').modal('show');
            $('#tituloProductoSugerencia').text($(boton).attr('nombre'));

        } else {
            listarDetalleVenta(boton, id,"");
        }
        // $('#loading').css('display','none');

    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
// }
}
detalle = new Array();
cont = 0;
function cargarSugerenciaProducto(boton) {

    $(boton).css('background', '#96f1bf');
    $(boton).attr('disabled', true);
    sugerencia = $(boton).text();

    detalle[cont] = sugerencia;
    cont++;
}
function agregarSugerenciaProducto() {
    nombreProdu = $(botonProducto).text();
    suge = "";
    if (detalle.length == 0) {//si no selecciona ninguna sugerencia entra por verdadero
        listarDetalleVenta(botonProducto, idProd,"");
        $('#ModalSugerencias').modal('hide');
        return;
    }
    for (var i = 0; i < detalle.length; i++) {
        // if (i==detalle.length-1) {

        // }
        suge += detalle[i] + ", ";
    }
    target = $('#tablaDetalle span[id="spanProd' + idProd + '"]');
    if (target.length == 0) {//cuando es por primara vez
        index = listarDetalleVenta(botonProducto, idProd,"");

        spanNombre = $('#tablaDetalle span[id="spanProd' + idProd + '"]');
        inputProductoId = $('#tablaDetalle input[id="inputProductoId' + index + '"]');
        nota = $('#tablaDetalle input[id="notaProducto' + index + '"]');
//aqui
nota.val(suge);
sugerencia = inputProductoId.val() + "(" + suge + ")";
spanNombre.attr('nombreprodu', sugerencia);

// alert(spanNombre.text());
        // alert(sugerencia);
        if (sugerencia == spanNombre.text()) {
// alert("existe");
} else {
    spanNombre.text(spanNombre.text() + "(" + suge + ")");
}
} else {
    inputProductoId = $(botonProducto).text();
    nmprod = $(botonProducto).text();
    sugerencia = nmprod + "(" + suge + ")";
    spanNombre = $('#tablaDetalle span[id="spanProd' + idProd + '"]');
        // inputProductoId = $('#tablaDetalle input[id="inputProductoId' + idProd + '"]');
        nombre = $(botonProducto).attr('nombre');
        precio = $(botonProducto).attr('precio');
        cantidadpro = $(botonProducto).attr('cantidad');

        if (sugerencia == spanNombre.text()) {//si es igual a la sugerencia no agrega al detalle solo cuenta a la cantidad

            $('#botonOculto').attr('nombre', nombre + sugerencia);
            $('#botonOculto').attr('precio', precio);
            $('#botonOculto').attr('cantidad', cantidadpro);
            $('#botonOculto').text(sugerencia);

            index = listarDetalleVenta($('#botonOculto'), idProd,"");
            $('#inputProductoId' + index).val($(botonProducto).text());//renombra nombre del producto del input ocultado
            nota = $('#tablaDetalle input[id="notaProducto' + index + '"]');
            nota.val(suge);
        } else {


            $('#botonOculto').attr('nombre', sugerencia);
            $('#botonOculto').attr('precio', precio);
            $('#botonOculto').attr('cantidad', cantidadpro);

            $('#botonOculto').text(sugerencia);
            index = listarDetalleVenta($('#botonOculto'), idProd,"");
            $('#inputProductoId' + index).val($(botonProducto).text());//renombra nombre del producto del input ocultado
            nota = $('#tablaDetalle input[id="notaProducto' + index + '"]');
            nota.val(suge);

        }

    }
    $('#ModalSugerencias').modal('hide');
    detalle = new Array();
    cont = 0;

}
//Ocultamos el men� al cargar la p�gina
$("#menu").hide();

tipoGasto = "";
idMesa = "";
nroMesa = "";
opcionItemMesa = 0;
nombreMesa = "";//esta variable es para guardar el nombre del boton de la mesa
$(function () {
    $.contextMenu({
        selector: '.btnMenu',
        callback: function (key, options) {
            switch(key){
                case 'INSUMO':
                var idProducto=$(this).attr('idProducto');
                listarInsumoDadoProducto(idProducto);
                break;
                case 'DESACTIVAR':
                var idProducto=$(this).attr('idProducto');
                desactivarProducto(idProducto);
                break;
                case 'MOVER':
                var idProducto=$(this).attr('idProducto');
                $('#ModalBotonProducto').modal('show');
                $('#idProductoParaMenu').val(idProducto);
                // moverProductoMenu(idProducto); 
                break;
            }
        },
        items: {
            "INSUMO": {name: "INSUMOS QUE LLEVA ESTE PRODUCTO", icon: "fa-edit"},
            "DESACTIVAR": {name: "DESACTIVAR EL BOTON", icon: "fa-cloud-download"},
            "MOVER": {name: "MOVER A OTRA PAGINA O BOTON", icon: "fa-certificate"}
        }
    });

    $('.context-menu-one').on('click', function (e) {
        console.log('clicked', this);
    })


    $.contextMenu({
        selector: '#btnGastosNuevo',
        trigger: 'left',
        callback: function (key, options) {
            tipoGasto = key;
            $('#btnGastosNuevo').text(tipoGasto);
            $('#inputGastosEntregado').attr("readonly", false);
            $('#inputGastosConcepto').attr("readonly", false);
            $('#inputGastosMonto').attr("readonly", false);
            $('#inputFacturaEgreso').attr("readonly", false);
            listartGastos(key);
        },
        items: {
            "INGRESO": {name: "INGRESO"},
            "EGRESO": {name: "COMPRA-EGRESO"}
        }
    });
    $.contextMenu({
        selector: '.menuNota',
        trigger: 'left',
        callback: function (key, options) {
         filaNombre = $(this).closest("tr").find("span[name=nombreProducto]");
         filaNombreinput = $(this).closest("tr").find("input[name=nombreProducto]");
         notaProducto= $(this).closest("tr").find("input[name=notaProducto]");
         notaProducto.val(notaProducto.val()+","+key+",");
         filaNombre = $(this).closest("tr").find("span[name=nombreProducto]");
         filaNombre.attr('nombreprodu', notaProducto.val());
         filaNombre.text(filaNombreinput.val() + "(" + notaProducto.val() + ")");
         
     },
     items: {
        "MESA": {name: "MESA"},
        "LLEVAR": {name: "LLEVAR"},
        "ABAJO": {name: "ABAJO"}
    }
});



    $.contextMenu({
        selector: '.agregarPM',
        trigger: 'left',
        callback: function (key, options) {

            if ($('#selectMeseroMesas').val()=="0") {
             alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "* DEBE DE SELECCIONAR EL MESERO PRIMERO", function () {
                alertify.message('OK');
            });
         }else{

            idMesa = $(this).attr('idmesa');
            nroMesa = $(this).attr('nroMesa');
            verificarEmpleado($(this));   
        }
            // agregarProductoMesa(idMesa,nroMesa);
        },
        items: {
            "AGREGAR": {name: "ABRIR"}

        }
    });


    $.contextMenu({
        selector: '.aumentarPM',
        trigger: 'left',
        callback: function (key, options) {
            if ($('#spanProcesando').text()!="") {
                alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "EXISTEN PRODUCTOS EN PROCESO DE FACTURACION.", function () {
                    alertify.message('OK');
                });
            }else{
                if (key === "AUMENTAR") {
                    idMesa = $(this).attr('idmesa');
                    nroPersona = $(this).attr('nroPersona');
                    nroMesa = $(this).text() == "" ? "0" :  $(this).attr('nroMesa');
                    nombreMesa = $(this).text();
                    aumentarProductoMesa(idMesa, nroMesa);
                } else {


                 idMesa = $(this).attr('idmesa');
                 nroPersona = salon=="nosalon"?$(this).attr('nroPersona'):$(this).text();
                 nroMesa = $(this).text() == "" ? "0" : $(this).attr('nroMesa');

                 nombreMesa = $(this).text();
                 listarDetalleMesa(idMesa, nroMesa, nroPersona);

             }
         }
            // agregarProductoMesa(idMesa,nroMesa);
        },
        items: {
            "AUMENTAR": {name: "AUMENTAR"},
            "DETALLE": {name: "VER DETALLE"}
        }
    });

    $.contextMenu({
        selector: '#btnAccionItem',
        trigger: 'left',
        callback: function (key, options) {

            switch (key) {
                case "PAGAR":
                pagarItemSelecMesa();
                opcionItemMesa = 1;

                break;
                case "TRASPASAR":
                traspasarItemSelecMesa(2);
                opcionItemMesa = 3;
                break;
                case "ELIMINARITEM":
                eliminarItemCant();
                break;
            }
            // agregarProductoMesa(idMesa,nroMesa);
        },
        items: {
            "PAGAR": {name: "PAGAR ITEMS"},
            "TRASPASAR": {name: "TRASPASAR ITEMS A OTRA MESA"},
            "ELIMINARITEM": {name: "ELIMINAR ITEM"}


        }
    });


});
// $(document).bind("contextmenu ", function(e){
//                   $("#menu").css({'display':'block', 'left':e.pageX, 'top':e.pageY});
//                   return false;
//   });

//  $(document).click(function(e){
//                   if(e.button == 0){
//                         $("#menu").css("display", "none");
//                   }
// });
//        


function obteneripCliente() {
    $.get(RUTAS.impresionSucursal, {idSucursal:idSucursal}, function (res) {
        var jsonip = JSON.parse(res);
        ipCliente = jsonip.result[0].ipImpresion;
        
    });
}

function tipoPago(radio) {
    tipopago = $(radio).val();
    switch (tipopago) {
        case "CREDITO":
        $('#divSelecCredito').css('display', 'block');
        $('#divInputConsumoInterno').css('display', 'none');
        $('#divSelecPrePago').css('display', 'none');

        break;
        case "CONTADO":
        $('#divSelecCredito').css('display', 'none');
        $('#divInputConsumoInterno').css('display', 'none');
        $('#divSelecPrePago').css('display', 'none');


        break;
        case "CONSUMO":
        $('#divSelecCredito').css('display', 'none');
        $('#divInputConsumoInterno').css('display', 'block');

        $('#divSelecPrePago').css('display', 'none');

        break;
        case "PREPAGO":
        $('#divSelecCredito').css('display', 'none');
        $('#divInputConsumoInterno').css('display', 'none');
        $('#divSelecPrePago').css('display', 'block');

        break;
    }
}
function listartEntregadoGasto() {
    $.get(RUTAS.entregados, {}, function (res) {
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        var availableTagsV = json.result;

        $("#inputGastosEntregado").autocomplete({
            lookup: availableTagsV
        });
    });
}
function listartGastos(tipoTrasanccion) {
    $('#tbodyGastos').empty();
    var fecha = vf_Venta;
    $.get(RUTAS.gastos, {fecha: fecha,tipoTrasanccion:tipoTrasanccion}, function (res) {
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        for (var i = 0; i < json.result.length; i++) {
            $('#tbodyGastos').append("<tr><td>" + json.result[i].NROGASTO +
                "<td>" + json.result[i].ENTREGADOA +
                "<td>" + json.result[i].CONCEPTO +
                "<td>" + json.result[i].MONTO +
                "<td>" + json.result[i].FECHAGASTO +
                "<td>" + json.result[i].LOGIN
                );
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
function guardarGasto() {
    if ($('#btnGastosNuevo').text()=="NUEVO") {
     alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>',
      "DEBE PRESIONAR EL BOTON NUEVO  Y ELEGIR EL TIPO DE TRANSACCION", function () {
        alertify.message('OK');

    });
     return;
 }
 entregado = $('#inputGastosEntregado').val();
 concepto = $('#inputGastosConcepto').val();
 monto = $('#inputGastosMonto').val();
 turno = $('#vturno').text();
 factura = $('#inputFacturaEgreso').text();
 fechaFactura=$('#fechaFactura').val();
 nitProveedor= $('#nitProveedor').val();
 nroAutorizacion= $('#nroAutorizacion').val();
 codigoControl= $('#codigoControl').val();
 nroFactura= $('#inputNroFactura').val();
 conCreditoFiscal=$('#checkboxCreditoFiscal').prop('checked')?1:0;
 conFactura=$('#checkboxIncluyeFactura').prop('checked')?1:0;
 salioCaja=$('#checkboxSalioCaja').prop('checked')?1:0;

 var fechaFactura = moment(fechaFactura, 'DD/MM/YYYY');

 fechaFactura = fechaFactura.format('YYYY-MM-DD');
 ivaCreditoFiscal= $('#inputDebitoFiscal').val();
 $.post(RUTAS.guardarGasto, {fecha: vf_Venta, 
    entregado: entregado, concepto: concepto,
    monto: monto, turno: turno,factura:factura,tipoGasto:tipoGasto,fechaFactura:fechaFactura,
    nitProveedor:nitProveedor,nroAutorizacion:nroAutorizacion,codigoControl:codigoControl,
    nroFactura:nroFactura,ivaCreditoFiscal:ivaCreditoFiscal,conCreditoFiscal:conCreditoFiscal,
    conFactura:conFactura,salioCaja:salioCaja
}, function (res) {
    var json = JSON.parse(res);

    if (verificarError(json.error)) {
        return;
    }
    if (impresionnube=="impresionnube") {
     
       $.post('http://'+ ipCliente + ':8080/reportes/ingreso_egreso.php',{empresa:empresa,fecha:vf_Venta,tipoGasto:tipoGasto,monto:monto,concepto:concepto,entregado:entregado,usuario:$('#spanUsuario').text()},function(res){
          
       });
   }else{
       $.post('abms1/reportes/ingreso_egreso.php',{empresa:empresa,fecha:vf_Venta,tipoGasto:tipoGasto,monto:monto,concepto:concepto,entregado:entregado,usuario:$('#spanUsuario').text()},function(res){
          
       });
   }
   
   $('#btnGastosNuevo').text("NUEVO");
   $('#inputGastosEntregado').attr("readonly", true);
   $('#inputGastosConcepto').attr("readonly", true);
   $('#inputGastosMonto').attr("readonly", true);
   $('#inputFacturaEgreso').attr("readonly", true);

   $('#inputGastosEntregado').val("");
   $('#inputGastosConcepto').val("");
   $('#inputGastosMonto').val("");
   $('#inputFacturaEgreso').val("");
   $('#fechaFactura').val("");
   $('#nitProveedor').val("");
   $('#nroAutorizacion').val("");
   $('#codigoControl').val("");
   $('#inputNroFactura').val("");
   $('#checkboxCreditoFiscal').prop('checked',false);
   $('#checkboxIncluyeFactura').prop('checked',false);
   $('#inputDebitoFiscal').val("");
   $('#ModalCompraIva').modal('hide');

});
}

function agregarProductoMesa(persona) {//parametro de entra cantidad de clientes en la mesa
    idMesa = idMesa;//la trae de una variable publica
    nroMesa = nroMesa;//la trae de una variable publica
    idMesero = idMesero;//la trae de una variable publica

    tabla = $('#tablaDetalle input[name=cantidad]');

    selectVendedor = $('#selectVendedor').val();
    var idProducto = new Array();
    var cantidadA = new Array();
    var nombrePdto = new Array();
    var arrayNotaProducto = new Array();
    var   precioA = new Array();
    var NombreProdc=new Array();
    var precioA=new Array();
    var subtotalA=new Array();
    var contador=0;
    var imPresion='{"imp":[';

    if (tabla.length > 0) {
        $(tabla).each(function (e) {
            var id = $(this).closest("tr").find("input[name=inputIdProducto]").val();
            var cantidad = $(this).closest("tr").find("input[name=cantidad]").val();
            var nombrePducto = $(this).closest("tr").find("input[name=nombreProducto]").val();
            var notaPducto = $(this).closest("tr").find("input[name=notaProducto]").val();
            var precio = $(this).closest("tr").find("input[name=precio]").val();
            var nompdo = $(this).closest("tr").find("input[name=nombreProducto]").val();
            var subTotal = $(this).closest("tr").find("#spansubTotal").text();
            var imppunto4A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto4');
            var imppunto3A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto3');
            var imppunto2A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto2');
            var imppunto1A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto1');
            var impCocA=$(this).closest("tr").find("button[name=gestionImpresion]").attr('impcoc');
            cantidadA.push(cantidad);
            idProducto.push(id);
            arrayNotaProducto.push(notaPducto);
            NombreProdc.push(nompdo);
            subtotalA.push(subTotal);
            precioA.push(precio);
            contador++;
            if(tabla.length!=contador){
                imPresion+='{"impCoc":"'+impCocA+'","imppunto4":"'+imppunto4A+'","imppunto3":"'+imppunto3A+'","imppunto2":"'+imppunto2A+'","imppunto1":"'+imppunto1A+'"},';
            }else{
                imPresion+='{"impCoc":"'+impCocA+'","imppunto4":"'+imppunto4A+'","imppunto3":"'+imppunto3A+'","imppunto2":"'+imppunto2A+'","imppunto1":"'+imppunto1A+'"}';

            }
            if (notaPducto == "") {
                nombrePdto.push(nombrePducto);
            } else {
                nombrePdto.push(nombrePducto + "(" + notaPducto + ")");
            }
        });
        imPresion+=']}';
        JSON.stringify(imPresion);
        tito=JSON.parse(imPresion);
        if (billar=="billar") {
           cantidadA.push("1");
           idProducto.push("99999");
           arrayNotaProducto.push("");
           NombreProdc.push("Atencion");
           subtotalA.push("0");
           precioA.push("0");

       }

       

       var fechaVenta = vf_Venta;
       var turno = $('#vturno').text();

       $.post(RUTAS.abrirMesa, {cantCli: persona, nota: arrayNotaProducto
        , cantidad: cantidadA, idMesero: idMesero, turno: turno, idProducto: idProducto, idMesa: idMesa
        , nroMesa: nroMesa, fechaVenta: fechaVenta,salon:salon,meserosc:meserosc,NombrePro:NombreProdc,precio:precioA,subtotal
        :subtotalA,billar:billar,porcRest:porcRest}, function (res) {
            var json = JSON.parse(res);
            if (verificarError(json.error)) {
                return;
            }
            var horaA=horaActualSistemaOp();
            var NOM_PROD=$('#selectMeseroMesas option:selected').text();
            if (impresionnube=="impresionnube") {
                $.post("http://" + ipCliente + ":8080/reportes/imprimirMesa.php", {proceso:'imprimeAbrirMesa',nroMesa:nroMesa,hora:horaA,fechaVenta: fechaVenta,imPresion:tito.imp,  cantidad: cantidadA,nombrePdto:nombrePdto,subTotal:subtotalA,NOM_EMPL:NOM_PROD,orden:json.result}, function (res) {
                });
            }else{
                $.post("abms1/reportes/imprimirMesa.php", {proceso:'imprimeAbrirMesa',nroMesa:nroMesa,hora:horaA,fechaVenta: fechaVenta,imPresion:tito.imp,  cantidad: cantidadA,nombrePdto:nombrePdto,subTotal:subtotalA,NOM_EMPL:NOM_PROD,orden:json.result}, function (res) {
                }); 
            }
            limpiarDetalle();
            alertify.success('PRODUCTOS AGREGADOS A MESA');

            $('#aFacturacion').click();
            idMesa = "";
            nroMesa = "";

        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });

    } else {
        alertify.alert('<span style="font-weight: bold;    color: red;">ADVERTENCIA</span>', "DEBE SELECCIONAR AL MENOS UN ITEM", function () {

        });
    }
}
function listarMesero() {
    $.get(RUTAS.meseros, {}, function (res) {
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        for (var i = 0; i < json.result.length; i++) {
            if (json.result[i].ESTADO=="ACTIVO") {
               var codigo=meserosc=="meserosc"?json.result[i].ID:json.result[i].ID;
               $('#selectMeseroMesas').append("<option value=" + codigo + " >" + json.result[i].NOM_EMPL);
               $('#selectVendedor').append("<option value=" + json.result[i].ID + " >" + json.result[i].NOM_EMPL);
           }
       }
   }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

// function verificarEmpleado(){
//  $.get(RUTAS.meseros, {},function(res){
//        var json = JSON.parse(res);
//        for (var i = 0; i < json.result.length; i++) {
//             $('#selectMeseroMesas').append("<option value="+json.result[i].ID+" >"+json.result[i].NOM_EMPL);
//         }

//     })
// }
idMesero = "";
function verificarEmpleado(btnMesa) {
    validado = false;
    if (sinvistaempleado=="sinvistaempleado") {
       idMesero = $('#selectMeseroMesas').val();
       agregarProductoMesa("");
   }else{
    alertify.prompt("CODIGO EMPLEADO", "CODIGO", "",
        function (evt, value) {
            idMesero = $('#selectMeseroMesas').val();

            codigo = value.trim();
            if (idMesero == codigo || meserosc=="meserosc") {
                if (salon == "nosalon") {
                    persona = prompt("POR FAVOR COLOQUE LA CANTIDAD DE PERSONAS", "");


                } else {
                    persona = prompt("POR FAVOR COLOQUE EL NOMBRE DEL CLIENTE", "");

                }

                agregarProductoMesa(persona);



            } else {
                alertify.error('CODIGO ERRONEO INTENTE NUEVAMENTE');
                validado = false;
            }
                // GuardarProductoAGrupo(value);
                //  alertify.success('GUARDADO CORRECTAMENTE');
            },
            function () {
                // alertify.error('Cancel');
            });
}


}
horaA="";
nombreEmpleadoAtendio="";
function listarDetalleMesa(idMesa, nroMesa, nroPersona) {
    fechaVenta = vf_Venta;
    $('#tbodyDetalleMesa').empty();
    
    $.get(RUTAS.mesaDetalle, {idMesa: idMesa, nroMesa: nroMesa,
       fechaVenta: fechaVenta}, function (res) {
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return false;
        }
        total=0;
        if (json.result=="") {
            alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "NO ES DE LA FECHA ACTUAL", function () {
                alertify.message('OK');

            });
            return false;
        }
        for (var i = 0; i < json.result.length; i++) {
            // if (json.result[i].) {}
            if (parseInt(json.result[i].CANTIDAD) != 0) {
                nota = json.result[i].NOTA == "" ? "" : "(" + json.result[i].NOTA + ")";
                var tipoProducto=json.result[i].TIEMPO=="T"?"TIEMPO":"";
                if(json.result[i].TIEMPO=="T"){
                // var fecha1 = moment('2016-07-12');
                // var fecha2 = moment('2016-08-1');
                // console.log(fecha2.diff(fecha1, 'hours'), ' horas de diferencia');

                cantidad=obtenerTiempoBillar(json.result[i].TIEMPO_INI,json.result[i].TIEMPO_FIN);
                
                horaA=horaActualSistemaOp();
                subTotal=obtenerSubtotalBillar(cantidad,json.result[i].PRE_VENTA);
                total=parseFloat(total)+parseFloat(subTotal);
                botonTiempo=json.result[i].TIEMPO_FIN==""?"<button title='PARAR TIEMPO' class='btn btn-success btn-sm '  onclick='pararTiempoBillar("+json.result[i].ID+",this)'>"+ json.result[i].TIEMPO_INI:"<button disabled='' title='PARAR TIEMPO' class='btn btn-success btn-sm '  onclick='pararTiempoBillar("+json.result[i].ID+",this)'>"+ json.result[i].TIEMPO_INI+" / "+json.result[i].TIEMPO_FIN;
                $('#tbodyDetalleMesa').append("<tr><td><input type='hidden' name='idDetalleMesa' value='" + json.result[i].ID + "' class='form-control'>" + cantidad +
                    "<td><input  style='width: 90px;' onchange='validarCantidadDetMesa(this)' type='number' name='inputAccion' class='form-control'>" +
                    "<td><input type='hidden' name='tipoProducto' value='"+tipoProducto+"'><input type='hidden' name='idProductoMesa' value='" + json.result[i].COD_PROD + "' >" + json.result[i].NOM_PROD + nota +
                    "<td style='text-align:right'>" + json.result[i].PRE_VENTA +
                    "<td style='text-align:right'>" + subTotal +
                    "<td>"+botonTiempo
                    );
            }else{
                botonEliminarItem="";
                botonEliminarItem=json.result[i].COD_PROD=="99999"?"":"<button class='btn btn-danger btn-sm delete-btn' onclick='eliminarItem("+json.result[i].ID+",this)'><i class='fa fa-trash-o' aria-hidden='true'></i></button>";
                $('#tbodyDetalleMesa').append("<tr><td><input type='hidden' name='idDetalleMesa' value='" + json.result[i].ID + "' class='form-control'>" + json.result[i].CANTIDAD +
                    "<td><input  style='width: 90px;' onchange='validarCantidadDetMesa(this)' type='number' name='inputAccion' class='form-control'>" +
                    "<td><input type='hidden' name='tipoProducto' value='"+tipoProducto+"'><input type='hidden' name='idProductoMesa' value='" + json.result[i].COD_PROD + "' >" + json.result[i].NOM_PROD + nota +
                    "<td style='text-align:right'>" + json.result[i].PRE_VENTA +
                    "<td style='text-align:right'>" + (parseFloat(json.result[i].PRE_VENTA * json.result[i].CANTIDAD)).toFixed(3) +
                    "<td>"+botonEliminarItem
                    );

                total=parseFloat(total)+parseFloat(json.result[i].PRE_VENTA * json.result[i].CANTIDAD);
            }

        }
        nombreEmpleadoAtendio=json.result[i].NOM_EMPL;

    }
    $('#spanEmpAtendio').text(nombreEmpleadoAtendio);
    if (billar=="billar") {
       var targetInput = $('#tbodyDetalleMesa input[value="99999"]');

       precioAtencion=targetInput.val()!=undefined?obtenerPrecioAtencion(total):0;
       total=precioAtencion+total;

       $(targetInput).closest("tr").find("input[name=inputAccion]");

       $(targetInput).parents("tr").find("td").eq(3).text(precioAtencion.toFixed(2));
       $(targetInput).parents("tr").find("td").eq(4).text(precioAtencion.toFixed(2));

   }

   $('#tdTotalDetalleMesa').text((total).toFixed(3));
   $('#ModalaDetalleMesa').modal('show');
   $('#spanModalDetalleMesa').text(nroMesa);
   $('#spanModalDetalleNroPersona').text(nroPersona);
   return true;
}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

function validarCantidadDetMesa(input) {
    cantidad = parseFloat($(input).val());
    var data = $(input).parents("tr").find("td").eq(0);
    cantidad2 = parseFloat(data.text());
    if (cantidad > cantidad2) {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "* LA CANTIDAD ES MAYOR A LA ORIGINAL", function () {
            alertify.message('OK');
        });
        $(input).val("");

    }

}
mesaPagar = "";
function pagarItemSelecMesa() {
    tabla = $('#tablaDetalleMesa input[name=idDetalleMesa]');
    var  contador= 0;
    error = "";
    mesaPagar = idMesa;
    idDet = new Array();
    cantidadMesaProd = new Array();
    nombrePdto = new Array();
    idProdMes = new Array();
    contador2 = 0;
    if (tabla.length > 0) {
        tbody = $('#tbdoyDetalle');
        tbody.empty();
        $('#nombreCli').val(nombreMesa);
        $(tabla).each(function (e) {
            var idDetalle = $(this).closest("tr").find("input[name=idDetalleMesa]").val();
            var idProductoMesa = $(this).closest("tr").find("input[name=idProductoMesa]").val();
            var cantidad = $(this).closest("tr").find("input[name=inputAccion]").val();
            var idProductoMesa = $(this).closest("tr").find("input[name=idProductoMesa]").val();
            var cantidad2 = $(this).closest("tr").find("input[name=inputAccion]");
            var cantidadDetalle = $(cantidad2).parents("tr").find("td").eq(0);
            var nombreProduto = $(cantidad2).parents("tr").find("td").eq(2).text();
            var precio = $(cantidad2).parents("tr").find("td").eq(3).text();
            var tipoProducto=$(this).closest("tr").find("input[name=tipoProducto]").val();
            var subtotal=$(cantidad2).parents("tr").find("td").eq(4).text();
            if (tipoProducto=="TIEMPO" && cantidad!="") {
              cantidad=cantidadDetalle.text();
          }

          if (parseFloat(cantidadDetalle.text()) == parseFloat(cantidad)) {
            contador++;
        }
        if (cantidad != "" && cantidad != "0") {

            cantidadMesaProd.push(cantidad);
            idDet.push(idDetalle);
            idProdMes.push(idProductoMesa);
            $('#botonOculto').attr('subtotal', subtotal);
            $('#botonOculto').attr('nombre', nombreProduto);
            $('#botonOculto').attr('precio', precio);
            $('#botonOculto').attr('cantidad', cantidad);
            $('#botonOculto').attr('tipoProducto', tipoProducto);
            $('#botonOculto').text(nombreProduto);
            listarDetalleVenta($('#botonOculto'), idProductoMesa);

        } else {
            contador2++;
        }

        if (contador == tabla.length || contador2 == tabla.length) {
            alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "<span style='font-weight: bold; '>* DEBE PAGAR MESA COMPLETA O DEBE PONER ALGUNA CANTIDAD EN LA COLUMNA ACCION</span><br>", function () {
                alertify.message('OK');
            });
            limpiarDetalle();
            error = "error";
        }
    });
        if (error == "") {
            $('#ModalaDetalleMesa').modal('hide');
            $('#aFacturacion').click();
            mesaProcesando();
            $('#numMesa').val(nroMesa);
            $('#btnFacturar').attr('onclick', 'pedidoMesa(this)');//el boton facturar le agrego esa funcion para q no muestre el modal de opcion salida
            $('#btnFacturar').attr('salida', 'MESA');
        }


    }
}
function mesaProcesando(opcion) {

    $.post(RUTAS.mesasProcesando, {idMesa: mesaPagar}, function (res) {
        $('#spanProcesando').css('display', 'inline-block');
        $('#spanProcesando').text($('#spanProcesando').text() + "PROCESANDO: " + nroMesa);



    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}

habilitarMesa = 0;//0 = no se da de baja, 1 se da baja
function traspasarItemSelecMesa(opcion) {//opcion =1 traspasa todo, opcion 2 traspasa item por item
    var tabla = $('#tablaDetalleMesa input[name=idDetalleMesa]');
    var contadorMesaTraspaso = 0;
    var contadorVerificar = 0;
    idDet = new Array();
    cantidadA = new Array();
    nombrePdto = new Array();
    idProdMes = new Array();
    if (tabla.length > 0) {


        $(tabla).each(function (e) {

            var idDetalle = $(this).closest("tr").find("input[name=idDetalleMesa]").val();
            var idProductoMesa = $(this).closest("tr").find("input[name=idProductoMesa]").val();
            var cantidad = $(this).closest("tr").find("input[name=inputAccion]").val();
            var idProductoMesa = $(this).closest("tr").find("input[name=idProductoMesa]").val();
            var cantidad2 = $(this).closest("tr").find("input[name=inputAccion]");
            var cantidadDetalle = $(cantidad2).parents("tr").find("td").eq(0);
            var nombreProduto = $(cantidad2).parents("tr").find("td").eq(2).text();
            var precio = $(cantidad2).parents("tr").find("td").eq(3).text();
            if (opcion==1) {
                cantidad=cantidadDetalle.text();
            }
            if ((parseFloat(cantidadDetalle.text()) - parseFloat(cantidad)) == 0) {
                contadorVerificar++;
            }
            if (parseFloat(cantidadDetalle.text()) == parseFloat(cantidad)) {
                contador++;
            }
            if (cantidad != "" && cantidad != "0") {
                cantidadA.push(cantidad);
                idDet.push(idDetalle);
                idProdMes.push(idProductoMesa);

            } else {
                contadorMesaTraspaso++;
            }
        });
        if (contadorVerificar == tabla.length) {
            habilitarMesa = 1;
        } else {
            habilitarMesa = 0;
        }
        if (contadorMesaTraspaso == tabla.length) {
            alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "<span style='font-weight: bold; '>* DEBE COLOCAR AL MENOS UNA CANTIDAD A TRASPASAR</span><br>", function () {
                alertify.message('OK');
            });
        } else {
            $('#ModalMesasHabilitadas').modal('show');
        }


    }
}

function traspasarItemMesa(boton) {
    idMesaTraspaso = $(boton).attr('idmesa');
    estadoMesa = $(boton).attr('estado');
    nroMesaTraspaso = $(boton).text();
    idMesaDisminuir = idMesa;
    fecha = vf_Venta;
    $.post(RUTAS.traspasarMesa, {salon:salon, estadoMesa: estadoMesa, habilitarMesa: habilitarMesa, nroMesaTraspaso: nroMesaTraspaso, fecha: fecha, idDet: idDet, cantidad: cantidadA, idMesaTraspaso: idMesaTraspaso,
        idMesaDisminuir: idMesaDisminuir}, function (res) {
            var json = JSON.parse(res);
            if (verificarError(json.error)) {
                return;
            }
            $('#ModalMesasHabilitadas').modal('hide');
            $('#ModalaDetalleMesa').modal('hide');
            listarMesas();

        });
}
function aumentarProductoMesa(idMesa, nroMesa) {
    tabla = $('#tablaDetalle input[name=cantidad]');
    idProducto = new Array();
    cantidadA = new Array();
    nombrePdto = new Array();
    arrayNotaProducto = new Array();
    fechaVenta = vf_Venta;
    var tiempoHabilitado="";
    var contador=0;
    var imPresion='{"imp":[';
    var subtotalA=new Array();

    if (tabla.length > 0) {
        var  tipoProducto1=$(tabla).closest("tr").find("input[value=T]").val()!=undefined?"T":"";
        $(tabla).each(function (e) {
            var id = $(this).closest("tr").find("input[name=inputIdProducto]").val();
            var cantidad = $(this).closest("tr").find("input[name=cantidad]").val();
            var nombrePducto = $(this).closest("tr").find("input[name=nombreProducto]").val();
            var notaPducto = $(this).closest("tr").find("input[name=notaProducto]").val();
            var tipoProducto = $(this).closest("tr").find("input[name=tipoProducto]").val();
            var subTotal = $(this).closest("tr").find("#spansubTotal").text();

            cantidadA.push(cantidad);
            idProducto.push(id);
            subtotalA.push(subTotal);
            arrayNotaProducto.push(notaPducto);
            var imppunto4A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto4');
            var imppunto3A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto3');
            var imppunto2A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto2');
            var imppunto1A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto1');
            var impCocA=$(this).closest("tr").find("button[name=gestionImpresion]").attr('impcoc');
            contador++;
            if(tabla.length!=contador){
                imPresion+='{"impCoc":"'+impCocA+'","imppunto4":"'+imppunto4A+'","imppunto3":"'+imppunto3A+'","imppunto2":"'+imppunto2A+'","imppunto1":"'+imppunto1A+'"},';
            }else{
                imPresion+='{"impCoc":"'+impCocA+'","imppunto4":"'+imppunto4A+'","imppunto3":"'+imppunto3A+'","imppunto2":"'+imppunto2A+'","imppunto1":"'+imppunto1A+'"}';

            }
            if (notaPducto == "") {
                nombrePdto.push(nombrePducto);
            } else {
                nombrePdto.push(nombrePducto + "(" + notaPducto + ")");
            }

        });
        imPresion+=']}';
        JSON.stringify(imPresion);
        tito=JSON.parse(imPresion);
        
        $.post(RUTAS.agregarMesa, {idMesa: idMesa, idProducto: idProducto,
            cantidad: cantidadA, notaProducto: arrayNotaProducto, fechaVenta: fechaVenta, nroMesa: nroMesa,tipoProducto:tipoProducto1}, function (res) {
                var json = JSON.parse(res);
                if (verificarError(json.error)) {
                    return;
                }
                var horaA=horaActualSistemaOp();
                var NOM_PROD=$('#selectMeseroMesas option:selected').text();
                if (impresionnube=="impresionnube") {
                    $.post("http://" + ipCliente + ":8080/reportes/imprimirMesa.php", {proceso:'imprimeAbrirMesa',nroMesa:nroMesa,hora:horaA,fechaVenta: fechaVenta,imPresion:tito.imp,  cantidad: cantidadA,nombrePdto:nombrePdto,subTotal:subtotalA,NOM_EMPL:NOM_PROD,orden:json.result}, function (res) {
                    });
                }else{
                    $.post("abms1/reportes/imprimirMesa.php", {proceso:'imprimeAbrirMesa',nroMesa:nroMesa,hora:horaA,fechaVenta: fechaVenta,imPresion:tito.imp,  cantidad: cantidadA,nombrePdto:nombrePdto,subTotal:subtotalA,NOM_EMPL:NOM_PROD,orden:json.result}, function (res) {
                    });
                }
                limpiarDetalle();
                alertify.success('PRODUCTOS AGREGADOS A MESA');
                listarMesas();
                $('#aFacturacion').click();

                idMesa = "";
                nroMesa = "";


            }).fail(function(xhr, ajaxOptions, thrownError){
                failErrors(xhr, ajaxOptions, thrownError);  
            });
            // }
        } else {
            alertify.alert('<span style="font-weight: bold;    color: red;">ADVERTENCIA</span>', "DEBE SELECCIONAR AL MENOS UN ITEM", function () {
                alertify.message('OK');
            });
        }
    }

    function pagarTodoMesa() {
        tabla = $('#tablaDetalleMesa input[name=idDetalleMesa]');
        contador = 0;
        error = "";
        mesaPagar = idMesa;
        idDet = new Array();
        cantidadMesaProd = new Array();
        nombrePdto = new Array();
        idProdMes = new Array();
        opcionItemMesa = 2;

        if (tabla.length > 0) {
            $('#nombreCli').val(nombreMesa);
            tbody = $('#tbdoyDetalle');
            tbody.empty();
            $(tabla).each(function (e) {

                var idDetalle = $(this).closest("tr").find("input[name=idDetalleMesa]").val();
                var idProductoMesa = $(this).closest("tr").find("input[name=idProductoMesa]").val();
                var cantidad2 = $(this).closest("tr").find("input[name=inputAccion]");
                var tipoProducto=$(this).closest("tr").find("input[name=tipoProducto]").val();
                var cantidadDetalle = $(cantidad2).parents("tr").find("td").eq(0);
                var nombreProduto = $(cantidad2).parents("tr").find("td").eq(2).text();
                var precio = $(cantidad2).parents("tr").find("td").eq(3).text();
                var subtotal=$(cantidad2).parents("tr").find("td").eq(4).text();


                cantidadMesaProd.push(cantidadDetalle.text());
                idDet.push(idDetalle);
                idProdMes.push(idProductoMesa);


                $('#botonOculto').attr('subtotal', subtotal);


                $('#botonOculto').attr('nombre', nombreProduto);
                $('#botonOculto').attr('nombre', nombreProduto);
                $('#botonOculto').attr('precio', precio);
                $('#botonOculto').attr('cantidad', cantidadDetalle.text());
                $('#botonOculto').attr('tipoProducto', tipoProducto);
                $('#botonOculto').text(nombreProduto);
                listarDetalleVenta($('#botonOculto'), idProductoMesa,tipoProducto);
            });
            if (error == "") {

                $('#ModalaDetalleMesa').modal('hide');
                $('#aFacturacion').click();
                mesaProcesando();
                $('#numMesa').val(nroMesa);
            $('#btnFacturar').attr('onclick', 'pedidoMesa(this)');//el boton facturar le agrego esa funcion para q no muestre el modal de opcion salida
            $('#btnFacturar').attr('salida', 'MESA');
        }


    }
}
function hablilitarMesaProcesando(idMesa) {

    $.post(RUTAS.mesasLiberarProcesando, {idMesa: idMesa}, function (res) {
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }

        idMesa = null;
        listarMesas();
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}

function habilitarActualizarMesa(opcionItemMesa) {//opcion =1 solo paga los items seleccionados, 2 paga toda la mesa


    $.post(RUTAS.mesaCobrada, {idMesa: idMesa, opcionItemMesa: opcionItemMesa
        , cantidad: cantidadMesaProd, idProducto: idProdMes, idDetalle: idDet}, function (res) {
            var json = JSON.parse(res);
            if (verificarError(json.error)) {
                return;
            }
            $('#spanProcesando').val("");
            $('#spanProcesando').css("display", 'none');
            limpiarDetalle();
            listarMesas();
            if (procesoRefrescar==10) {
                location.reload();
            }
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }

    function imprimirProductosMesas(operacion){
        tabla = $('#tablaDetalleMesa input[name=idDetalleMesa]');
        error = "";
        cantidadA = new Array();
        nombrePdto = new Array();
        precioA = new Array();
        subTotal = new Array();
        opcionItemMesa = 2;
        total=$('#tdTotalDetalleMesa').text();
        if (tabla.length > 0) {

            $(tabla).each(function (e) {

                var cantidad2 = $(this).closest("tr").find("input[name=inputAccion]");
                var cantidadDetalle = $(cantidad2).parents("tr").find("td").eq(0).text();
                var nombreProducto = $(cantidad2).parents("tr").find("td").eq(2).text();
                var precio = $(cantidad2).parents("tr").find("td").eq(3).text();
                var subtotalA = $(cantidad2).parents("tr").find("td").eq(4).text();
                cantidadA.push(cantidadDetalle);
                nombrePdto.push(nombreProducto);
                precioA.push(precio);
                subTotal.push(subtotalA);
            });
            horaA=horaActualSistemaOp();
            if (impresionnube=="impresionnube") {
                $.post("http://" + ipCliente + ":8080/reportes/imprimirMesa.php", {proceso:operacion,cantidad:cantidadA,
                    nombreEmpleadoAtendio:nombreEmpleadoAtendio,nombreProducto:nombrePdto,hora:horaA,precioA:precioA,nroMesa:nroMesa,fecha:vf_Venta,subTotal:subTotal,total:total,operacion:operacion}, function (res) {
                    });
            }
            else{
                $.post("abms1/reportes/imprimirMesa.php", {proceso:operacion,cantidad:cantidadA,
                    nombreEmpleadoAtendio:nombreEmpleadoAtendio,nombreProducto:nombrePdto,hora:horaA,precioA:precioA,nroMesa:nroMesa,fecha:vf_Venta,subTotal:subTotal,total:total,operacion:operacion}, function (res) {
                    });
            }
        }


    }


    function anularMesa(){
     $.post(RUTAS.anularMesa, {idMesa: idMesa, fechaVenta: fechaVenta, nroMesa: nroMesa}, function (res) {
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }

        imprimirProductosMesas("ANULAR MESA");
        alertify.success('MESA ANULADA CORRECTAMENTE');
        $('#ModalaDetalleMesa').modal('hide');
        $('#aFacturacion').click();
        idMesa = "";
        nroMesa = "";
        listarMesas();
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}

function eliminarItem(id,boton){
    tabla = $('#tablaDetalleMesa input[name=idDetalleMesa]');
    var cantidadDetalle = $(boton).parents("tr").find("td").eq(0).text();
    var nombreProducto = $(boton).parents("tr").find("td").eq(2).text();
    var precio = $(boton).parents("tr").find("td").eq(3).text();
    var subtotalA = $(boton).parents("tr").find("td").eq(4).text();
    $('#tdTotalDetalleMesa').text(subtotalA);

    totalDetalle=tabla.length==1?"abrirmesa":"";
    if (totalDetalle=="abrirmesa") {
       alertify.alert('<span style="font-weight: bold;    color: blue;">INFORMACION</span>', "* TOME EN CUENTA QUE LE QUEDA SOLO UN ITEM, LA MESA SE ANULARA POR DEFECTO", function () {
        alertify.message('OK');
    }); 
   }
   
   var idDetalle = id;

   $.post(RUTAS.eliminarItemMesa, {idDetalle: idDetalle,totalDetalle:totalDetalle,idMesa:idMesa}, function (res) {
    var json = JSON.parse(res);
    if (verificarError(json.error)) {
        return;
    }
    $('#tbodyDetalleMesa').empty();
    $('#tbodyDetalleMesa').append("<tr><td><input type='hidden' name='idDetalleMesa' value='" + idDetalle + "' class='form-control'>" + cantidadDetalle +
        "<td><input  style='width: 90px;' onchange='validarCantidadDetMesa(this)' type='number' name='inputAccion' class='form-control'>" +
        "<td><input type='hidden' name='idProductoMesa' value='" + nombreProducto + "' >" +nombreProducto +
        "<td>" + precio +
        "<td>" + subtotalA +
        "<td><button class='btn btn-danger btn-sm delete-btn' onclick='eliminarItem("+idDetalle+",this)'><i class='fa fa-trash-o' aria-hidden='true'></i></button> "
        );
    imprimirProductosMesas("ANULAR ITEM");
    listarMesas();
    alertify.success('ITEM ANULADO CORRECTAMENTE');
    
    if (totalDetalle=="abrirmesa") {
        $('#aFacturacion').click();
        $('#ModalaDetalleMesa').modal('hide');

    }else{
     listarDetalleMesa(idMesa,idMesa,"");
 }
 
        // idMesa = "";
        // nroMesa = "";

    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });  
}

function removeDefaultFunction()
{
    window.onhelp = function () { return false; }
}
        /** use keydown event and trap only the F-key, 
        but not combinations with SHIFT/CTRL/ALT **/
        $(window).bind('keydown', function(e) {
            //This is the F1 key code, but NOT with SHIFT/CTRL/ALT
            var keyCode = e.keyCode || e.which;
            //&& 
              //  !(event.altKey ||event.ctrlKey || event.shiftKey || event.metaKey)
              if((keyCode == 123 || e.key == 'F12') )
              {
                // prevent code starts here:
                removeDefaultFunction();
                e.cancelable = true;
                e.stopPropagation();
                e.preventDefault();
                e.returnValue = false;
                // Open help window here instead of alert
                llenarDatosModalF();
            }else{
        //          var reportStr   =
        // "The " +
        // ( e.ctrlKey  ? "Control " : "" ) +
        // ( e.shiftKey ? "Shift "   : "" ) +
        // ( e.altKey   ? "Alt "     : "" ) +
        // ( e.metaKey  ? "Meta "    : "" ) +
        // e.code + " " +
        // "key was pressed.";
        
    // $("#statusReport").text (reportStr);

    //--- Was a Ctrl-Alt-E combo pressed?
    if (event.shiftKey  && event.keyCode == 121) {
        // this.hitCnt = ( this.hitCnt || 0 ) + 1;
        alert('adfadf');
        
        
    }
    
}

            // Add other F-keys here:
            // else if((keyCode == 113 || e.key == 'F2') && 
            //         !(event.altKey ||event.ctrlKey || event.shiftKey || event.metaKey))
            //  {
            //     // prevent code starts here:
            //     removeDefaultFunction();
            //     e.cancelable = true;
            //     e.stopPropagation();
            //     e.preventDefault();
            //     e.returnValue = false;
            //     // Do something else for F2
            //     alert('F2 key opened, ' + keyCode);
            //     }
        });

        function listarCajero(){
            fecha = vf_Venta;
            $('#selectCajero').empty();
            $('#selectCajero').append("<option value=''>");

            $.get(RUTAS.cajeros, {fecha:fecha}, function (res) {
                var json = JSON.parse(res);
                if (verificarError(json.error)) {
                    return;
                }
                for (var i = 0; i < json.result.length; i++) {
                   $('#selectCajero').append("<option value='"+json.result[i].LOGIN+"'>"+json.result[i].LOGIN);

               }

           }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });  
       }
       empresa=Array();
    function obtnerEmpresa(){//obtiene los datos de la empresa
        $.get(RUTAS.dosificacion, {idSucursal,idSucursal },function(res){
          var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
        }
        empresa=json.result;
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}



function cierreCaja(){
    var ventasDiarias=new Array();
    cajero=$('#selectCajero').val();
    entrada="";
    fecha = vf_Venta;
    turnoMan=$('#checkboxMañana');
    turnoNoc=$('#checkboxNoche');
    cierre=$('#checkboxCierre');
    if (cajero=="") {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE SELECCIONAR UN CAJERO", function () {
        });
        return; 
    }
    if (!cierre.prop('checked')) {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE SELECCIONAR CIERRE", function () {
        });
        return; 
    }
    if (turnoMan.prop('checked') && turnoNoc.prop('checked')) {
        entrada="LOS2TURNOS";
    }else{
        if (turnoMan.prop('checked')) {
            entrada="TURNOMAÑANA";
        }else{
            if (turnoNoc.prop('checked')) {
                entrada="TURNONOCHE";
            }else{
                alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE SELECCIONAR UN TURNO O AMBOS", function () {
                });
                return; 
            }
        }
    }

    switch (entrada){
        case "LOS2TURNOS":
        turno="MAÑANA Y NOCHE";

        break;
        case "TURNOMAÑANA":
        turno="MAÑANA";

        break;
        case "TURNONOCHE":
        turno="NOCHE";


        break;
        
    }


    $.post(RUTAS.cierreCaja, {fecha:fecha,empleado:cajero,entrada:entrada},function(res){
      var json = $.parseJSON(res);
      if (verificarError(json.error)) {
        return;
    }
    ventasDiarias=json.result;
    alertify.success('CIERRE DIARIO CORRECTAMENTE FECHA:'+fecha);
    listarTOTALCAJACIERRE(fecha,turno,cajero);
    imprimirItemElim(fecha,turno,cajero);
    $('#ModalCierreDiario').modal('hide');


           // paginatorProducto();
       }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
   }

   function listarTOTALCAJACIERRE(fecha,turno,cajero){

    $.get(RUTAS.totalesCierre, {fecha:fecha,turno:turno,empleado:cajero,entrada:turno},function(res){
        var json = $.parseJSON(res);
        if (verificarError(json.error)) {
            return;
        }
        if (json.result.length>0) {
            credito=parseFloat(json.result[0].credito);
            consumo=parseFloat(json.result[0].consumo);
            facturado=parseFloat(json.result[0].facturado);
            anulado=parseFloat(json.result[0].anulado);
            gastos=parseFloat(json.result[0].gastos);
            ingreso=parseFloat(json.result[0].ingreso);
            
            totalcaja=(facturado - credito - consumo -anulado-gastos+ingreso).toFixed(3);


            $.post('http://localhost:8080/reportes/imprimirCierre.php',{proceso:'listaTOTALCAJACIERRE',fecha:fecha,login:login,turno:turno,empresa:empresa,
                listaTOTALCAJACIERRE:json.result,saldoCaja:totalcaja,vendaDia:(parseFloat(json.result[0].facturado)).toFixed(3)},function(res){
                });

        }else{

        }
        
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
function imprimirItemElim(fecha,turno,cajero){//esta funcion imprime desde el historico
    $.get(RUTAS.itemsEliminados, {fecha:fecha,empleado:cajero
        ,entrada:turno},function(res){
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            if (json.result.length>0) {

                $.post('http://localhost:8080/reportes/imprimirCierre.php',{proceso:'listarItemElimDadaFecha',fecha:fecha,login:login,turno:turno,listaItemEli:json.result},function(res){
                });

            }else{

            }
            listarMesas();
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }
    notifFechaLimite="";
    function verificarDocificacion(){
        $.get(RUTAS.verificarDosificacion, {fecha:vf_Venta},function(res){
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            notifFechaLimite=json.result;
            if (notifFechaLimite!="") {
              alertify.success(notifFechaLimite);

          }

      });
    }
function productoHabilitado(json,i){//esta funcion verifica si el producto esta habilitado o no para ser mostrado
 var d = new Date();

 diaHoy=d.getDay();
 diaActual = f_Venta.getDay();


 var hora = f_Venta.getHours();
 var hora = ("0" + hora).slice(-2);
 var minuto = f_Venta.getMinutes();
 var minuto = ("0" + minuto).slice(-2);
 var segundo = d.getSeconds();
 var sliceegundo = ("0" + segundo).slice(-2);

 horaActual = hora + ":" + minuto;
 lun=json.result[i].lun!=""?"1":json.result[i].lun;
 mar=json.result[i].mar!=""?"2":json.result[i].mar;
 mier=json.result[i].mier!=""?"3":json.result[i].mier;
 jue=json.result[i].jue!=""?"4":json.result[i].jue;
 vie=json.result[i].vie!=""?"5":json.result[i].vie;
 sab=json.result[i].sab!=""?"6":json.result[i].sab;
 dom=json.result[i].dom!=""?"7":json.result[i].do;
            habilitado="";//si la variable habilitado esta vacia no esta disponible caso contrario lo esta
            switch(diaActual){
                case 1:
                if (lun!="") {

                    if (json.result[i].hora_inicio>json.result[i].hora_fin) {
                        if (diaHoy==diaActual) {
                            if (horaActual>=json.result[i].hora_inicio) {
                                habilitado="x";
                            }
                        }
                        else{
                            if (horaActual<=json.result[i].hora_fin) {
                                habilitado="x";
                            }
                        }
                    }
                    else{
                       if (horaActual>=json.result[i].hora_inicio &&  horaActual<=json.result[i].hora_fin || json.result[i].hora_inicio==json.result[i].hora_fin) {
                        habilitado="x"
                    }
                }
            }
            break;
            case 2:
            if (mar!="") {
               if (json.result[i].hora_inicio>json.result[i].hora_fin) {
                if (diaHoy==diaActual) {
                    if (horaActual>=json.result[i].hora_inicio) {
                        habilitado="x";
                    }
                }
                else{
                    if (horaActual<=json.result[i].hora_fin) {
                        habilitado="x";
                    }
                }
            }
            else{
               if (horaActual>=json.result[i].hora_inicio &&  horaActual<=json.result[i].hora_fin || json.result[i].hora_inicio==json.result[i].hora_fin) {
                habilitado="x"
            }
        }
    }
    break;
    case 3:
    if (mier!="") {
       if (json.result[i].hora_inicio>json.result[i].hora_fin) {
        if (diaHoy==diaActual) {
            if (horaActual>=json.result[i].hora_inicio) {
                habilitado="x";
            }
        }
        else{
            if (horaActual<=json.result[i].hora_fin) {
                habilitado="x";
            }
        }
    }
    else{
       if (horaActual>=json.result[i].hora_inicio &&  horaActual<=json.result[i].hora_fin || json.result[i].hora_inicio==json.result[i].hora_fin) {
        habilitado="x"
    }
}
}
break;
case 4:
if (jue!="") {
   if (json.result[i].hora_inicio>json.result[i].hora_fin) {
    if (diaHoy==diaActual) {
        if (horaActual>=json.result[i].hora_inicio) {
            habilitado="x";
        }
    }
    else{
        if (horaActual<=json.result[i].hora_fin) {
            habilitado="x";
        }
    }
}
else{
   if (horaActual>=json.result[i].hora_inicio &&  horaActual<=json.result[i].hora_fin || json.result[i].hora_inicio==json.result[i].hora_fin) {
    habilitado="x"
}
}
}
break;
case 5:
if (vie!="") {
   if (json.result[i].hora_inicio>json.result[i].hora_fin) {
    if (diaHoy==diaActual) {
        if (horaActual>=json.result[i].hora_inicio) {
            habilitado="x";
        }
    }
    else{
        if (horaActual<=json.result[i].hora_fin) {
            habilitado="x";
        }
    }
}
else{
   if (horaActual>=json.result[i].hora_inicio &&  horaActual<=json.result[i].hora_fin || json.result[i].hora_inicio==json.result[i].hora_fin) {
    habilitado="x"
}
}
}
break;
case 6:

if (sab!="") {

   if (json.result[i].hora_inicio>json.result[i].hora_fin) {
    if (diaHoy==diaActual) {
        if (horaActual>=json.result[i].hora_inicio) {
            habilitado="x";
        }
    }
    else{
        if (horaActual<=json.result[i].hora_fin) {
            habilitado="x";
        }
    }
}
else{
   if (horaActual>=json.result[i].hora_inicio &&  horaActual<=json.result[i].hora_fin || json.result[i].hora_inicio==json.result[i].hora_fin) {
    habilitado="x"
}
}

}
break;
case 0:
if (dom!="") {
   if (json.result[i].hora_inicio>json.result[i].hora_fin) {
    if (diaHoy==diaActual) {
        if (horaActual>=json.result[i].hora_inicio) {
            habilitado="x";
        }
    }
    else{
        if (horaActual<=json.result[i].hora_fin) {
            habilitado="x";
        }
    }
}
else{
   if (horaActual>=json.result[i].hora_inicio &&  horaActual<=json.result[i].hora_fin || json.result[i].hora_inicio==json.result[i].hora_fin) {
    habilitado="x"
}
}
}
break;
}
if (json.result[i].estado!="ACTIVO") {
    habilitado="";
}
return habilitado;
}
function buscarProductoKeyUp(input){
    input=$(input).val();
    if (input.length>=3) {
        $('#divProducto').empty();
        $.get(RUTAS.buscarProducto, {nomProd:input},function(res){
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }

            for (var i = 0; i < json.result.length; i++) {
               if (json.result[i].estado=="ACTIVO") {
                habilitado="";
                habilitado= productoHabilitado(json,i);
                if (habilitado!="") {
                    if (json.result[i].presa == "") {
                        if (json.result[i].imgnormal == "" || json.result[i].imgnormal.charAt(0) != "a") {
                            $('#divProducto').append('<button  imppunto4="'+json.result[i].imppunt4+'" imppunto3="'+json.result[i].imppunt3+'" imppunto2="'+json.result[i].imppunt2+'" imppunto1="'+json.result[i].imppunt1+'" impCoc="'+json.result[i].impcoc+'"  tipoProducto="'+json.result[i].tiempo+'" idProducto="'+json.result[i].id+'" cantidad="' + json.result[i].cantidad + '" name="btnProducto" presa="' + json.result[i].presa + '" title="Precio: ' + json.result[i].pre_venta + ' , ' + json.result[i].nom_prod + '" precio="' + json.result[i].pre_venta + '" nombre="' + json.result[i].nom_prod + '"  onclick="listarDetalleVenta(this,' + json.result[i].id + ')" class="btnMenu btn btn-primary " style="border: none;    font-size: 12px;\n\
                                font-weight: bold;       width: 95px;text-shadow: 2px 2px black;    height: 52px; text-shadow: 2px 2px black; font-size: 8pt; box-shadow: 1px 1px #999;    margin-right: 4px;\n\
                                margin-bottom: 3px;    margin-left: 1px;    padding: 0;      margin-top: 1px; white-space: inherit; background:' + json.result[i].colores + ' " >' + json.result[i].nom_prod + '</button>');
                        } else {
                            $('#divProducto').append('<a  imppunto4="'+json.result[i].imppunt4+'" imppunto3="'+json.result[i].imppunt3+'" imppunto2="'+json.result[i].imppunt2+'" impPunto1="'+json.result[i].imppunt1+'" impCoc="'+json.result[i].impcoc+'"  tipoProducto="'+json.result[i].tiempo+'" idProducto="'+json.result[i].id+'"  cantidad="' + json.result[i].cantidad + '" class="btnMenu" href="#" name="btnProducto" presa="" title="' + json.result[i].pre_venta + ' , ' + json.result[i].nom_prod + '" precio="' + json.result[i].pre_venta + '" nombre="' + json.result[i].nom_prod + '" onclick="listarDetalleVenta(this,' + json.result[i].id + ')">' +
                                '<div class="" style="width: max-content;padding-left: 0;padding-right: 0;overflow: hidden;display: inline-table; ' +
                                'padding-right: 0;    overflow: hidden;"> ' +
                                '<div class="btn btn-primary" style="border: none;    font-size: 12px;' +
                                'font-weight: bold;       width: 95px;    height: 52px;   box-shadow: 1px 1px #999;    margin-right: 4px;' +
                                'margin-bottom: 3px;    margin-left: 1px;     padding: 0;      margin-top: 1px; white-space: inherit; background:; ">' +
                                '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 " >' +
                                '<img src="' + json.result[i].imgnormal + '" style="width: 36px;height: 56px;    position: absolute;    left: 1px;">' +
                                '</div>' +
                                ' <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 "  style="padding: 0;">' +
                                '<span style="position: relative;' +
                                'top: 1px;' +
                                'word-wrap: break-word;' +
                                'text-shadow: 2px 2px black;' +
                                'left: -5px;' +
                                'font-size: 8pt;">' + json.result[i].nom_prod + '</span>' +
                                '</div></div></div></a>');
                        }
                    } else {
                        if (json.result[i].imgnormal == "" || json.result[i].imgnormal.charAt(0) != "a") {
                            $('#divProducto').append('<button  imppunto4="'+json.result[i].imppunt4+'" imppunto3="'+json.result[i].imppunt3+'" imppunto2="'+json.result[i].imppunt2+'" imppunto1="'+json.result[i].imppunt1+'" impCoc="'+json.result[i].impcoc+'"  tipoProducto="'+json.result[i].tiempo+'"  idProducto="'+json.result[i].id+'" cantidad="' + json.result[i].cantidad + '"  name="btnProducto"  title="Precio: ' + json.result[i].pre_venta + ' , ' + json.result[i].nom_prod + '" precio="' + json.result[i].pre_venta + '" nombre="' + json.result[i].nom_prod + '"  onclick="cargarSugerencia(this,' + json.result[i].id + ')" class="btnMenu btn btn-primary " style="border: none;    font-size: 12px;\n\
                                font-weight: bold;       width: 95px; text-shadow: 2px 2px black;   height: 52px;   box-shadow: 1px 1px #999;    margin-right: 4px;\n\
                                margin-bottom: 3px;    margin-left: 1px;     padding: 0;      margin-top: 1px; white-space: inherit; background:' + json.result[i].colores + ' " >' + json.result[i].nom_prod + '</button>');
                        } else {
                            $('#divProducto').append('<a  imppunto4="'+json.result[i].imppunt4+'" imppunto3="'+json.result[i].imppunt3+'" imppunto2="'+json.result[i].imppunt2+'" impPunto1="'+json.result[i].imppunt1+'" impCoc="'+json.result[i].impcoc+'"  tipoProducto="'+json.result[i].tiempo+'" idProducto="'+json.result[i].id+'"  cantidad="' + json.result[i].cantidad + '"  class="btnMenu" href="#" name="btnProducto" presa="" title="Precio: ' + json.result[i].pre_venta + ' , ' + json.result[i].nom_prod + '" precio="' + json.result[i].pre_venta + '"  nombre="' + json.result[i].nom_prod + '" onclick="cargarSugerencia(this,' + json.result[i].id + ')">' +
                                '<div class="" style="width: max-content;display:inline-table;    padding-left: 0;' +
                                'padding-right: 0;    overflow: hidden;"> ' +
                                '<div class="btn btn-primary" style="border: none;    font-size: 12px;' +
                                'font-weight: bold;       width: 95px;    height: 52px;   box-shadow: 1px 1px #999;    margin-right: 4px;' +
                                'margin-bottom: 3px;    margin-left: 1px;     padding: 0;      margin-top: 1px; white-space: inherit; background:; ">' +
                                '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 " >' +
                                '<img src="' + json.result[i].imgnormal + '" style="width: 36px;height: 56px;    position: absolute;    left: 1px;">' +
                                '</div>' +
                                ' <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 "  style="padding: 0;">' +
                                '<span style="position: relative;' +
                                'top: 1px;' +
                                'word-wrap: break-word;' +
                                'text-shadow: 2px 2px black;' +
                                'left: -5px;' +
                                'font-size: 8pt;">' + json.result[i].nom_prod + '</span>' +
                                '</div></div></div></a>');
                        }
                    }
                }
                habilitado="";
            }
        }
    });
};

}
nomProdCodBarra="";
function verificarCodBarraProd(input){

    codProdBarra=$(input).val();
    $.get(RUTAS.buscarCodigoBarra, {codProdBarra:codProdBarra},function(res){
        var json = $.parseJSON(res);
        if (verificarError(json.error)) {
            return;
        }
        if(json.result==""){
            alertify.error("NO EXISTE ESE ITEM CON ESE CODIGO DE BARRA");
            return;
        }
        for (var i = 0; i < json.result.length; i++) {
           if (json.result[i].estado=="ACTIVO") {
            habilitado="";
            habilitado= productoHabilitado(json,i);
            if (habilitado!="") {
                $('#botonOculto').attr('nombre', json.result[i].nom_prod);
                $('#botonOculto').attr('precio', json.result[i].pre_venta);
                $('#botonOculto').attr('cantidad', 0);
                $('#botonOculto').text(json.result[i].nom_prod);
                listarDetalleVenta($('#botonOculto'), json.result[i].id,"");
                nomProdCodBarra=json.result[i].nom_prod;
                $('#inputCantCodBarra').focus();
            }
        }
        habilitado="";
    }
});
}

function agregarCantProdCodBarra(input){//esta funcion agrega la cantidad al producto agregado en el detalle de venta y por ende modifica el subtotal y el total
    cantidad=parseFloat($(input).val());
    targetInput = $('#tablaDetalle span[nombreProdu="' + nomProdCodBarra + '"]');
    var tabla=$('#tablaDetalle tbody tr');
    if (targetInput.length>0) {
     var inputCant = $(targetInput).closest("tr").find("input[name=cantidad]");
     var inputPrecio = $(targetInput).closest("tr").find("input[name=precio]");
     var inputSubtotal = $(targetInput).closest("tr").find("input[name=subTotal]");
     var spansubTotal = $(targetInput).closest("tr").find("#spansubTotal");
     var spanCantidad = $(targetInput).closest("tr").find("#spanCantidad");
     var spanPrecio = $(targetInput).closest("tr").find("#spanPrecio");
        //     alert(inputCant.val());
        // cantidad=parseFloat(cantidad+inputCant.val());
        total=0;
        $(tabla).each(function (e) {
            total+=parseFloat($(this).closest("tr").find("input[name=subTotal]").val());
            
        });
        aux = precio * cantidad;
        subTotal = parseFloat(inputSubtotal.val()) + aux;
        
        totalBs=(parseFloat(total+aux)).toFixed(2);
        
        

        $('#totalBs').val(totalBs);

        $('#totalUsd').val((totalBs / tipoCambio).toFixed(2));
        inputSubtotal.val(subTotal.toFixed(2));
        contar = (parseFloat(inputCant.val()) + parseFloat(cantidad)).toFixed(2);
        inputCant.val(contar);
        spansubTotal.text(subTotal.toFixed(2));

        spanCantidad.text(contar);
        $(input).val("");
        $('#inputCodigoBarra').val("");
        $('#inputCodigoBarra').focus();
    }
}

function listarInsumoDadoProducto(idProducto){

    $('#tbodyInsumoProducto').empty();
    $.get(RUTAS.insumosProducto, {idProducto: idProducto}, function (res) {
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }

        for (var i = 0; i < json.result.length; i++) {
            $('#tbodyInsumoProducto').append('<tr>'+
                '<td>'+json.result[i].NOM_INSUMO+
                '<td>'+json.result[i].STOCK_ACT
                );
        }
        $('#ModalInsumoProducto').modal('show');

    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}


function desactivarProducto(idProducto){


  $.post(RUTAS.desactivarProducto, {idProducto: idProducto}, function (res) {
    var json = JSON.parse(res);
    if (verificarError(json.error)) {
        return;
    }
    listarProducto(idBotonMenu,auxBotonMenu);

    alertify.success("PRODUCTO DESACTIVADO CORRECTAMENTE");

}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

function moverProductoMenu(grupo){
    var idProducto=$('#idProductoParaMenu').val();
    $.post(RUTAS.moverProducto, {idProducto: idProducto,grupo:grupo}, function (res) {
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        $('#botonMenu'+grupo).click()
        $('#ModalBotonProducto').modal('hide');
        alertify.success("PRODUCTO MOVIDO CORRECTAMENTE");

    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

}

direccionCli="";
telefonoCli="";
nombreClieDomicilio="";
function obtenerDireccionTelf(){
    nombreClieDomicilio=$('#NombreClienteDomicilio').val();
    direccionCli=$('#direccionCliente').val().trim();
    telefonoCli=$('#telefonoCliente').val();
    nit=$('#telefonoNitCliente').val();

    if (nombreClieDomicilio=="" || direccionCli=="" || telefonoCli=="") {
     alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "LOS CAMPOS TELEFONO, NOMBRE DEL CLIENTE Y DIRECCION NO DEBEN ESTAR VACIOS", function () {
        alertify.message('OK');
    });
 }else{
    $('#nombreCli').val(nombreClieDomicilio);
    $('#nitCli').val(nit);
    $('#aFacturacion').click();
    $('#NombreClienteDomicilio').val("");
    $('#direccionCliente').val("");
    $('#telefonoCliente').val("");
    $('#telefonoNitCliente').val("");
}
}

function cargarTG(){
    var tabla=$('#tablaDetalle tbody tr');
    if (tabla.length>0) {
        $('#ModalTarjetasGif').modal('show');
    }else{
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE HABER AL MENOS 1 ITEM", function () {
            alertify.message('OK');
        });
        $('#inputTarjeta').prop('checked',false);

    }

}
idTarjeta="";
function buscarTarjetaGift(){
    tarjeta=$('#inputTarjetaGif').val();
    
    $.get(RUTAS.tarjetas, {tarjeta: tarjeta}, function (res) {
        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        if (parseFloat(json.result[0].PRE_PAGO)==0) {
           alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "YA NO TIENE SALDO DISPONIBLE", function () {
            alertify.message('OK');
        });
           idTarjeta="";
           return;
       }
       if (json.result.length>0 && json.result!="") {
        idTarjeta=tarjeta;
        totalBsTar=$('#totalBs').val();
        Saldo=(parseFloat(json.result[0].PRE_PAGO-totalBsTar)).toFixed(2);
        $('#divTajertaGif').css('display','block');
        $("#spanNombreTajertas").text(json.result[0].NOM_CLI);
        $("#spanTotalTajertas").text(json.result[0].TOTAL_FACT);
        $("#spanConsumoTajertas").text(totalBsTar);
        $("#spanUltimoSaldoTajertas").text(json.result[0].PRE_PAGO);
        $("#spanNuevoSaldoTajertas").text(Saldo);
        $("#spanFormaPagoTajertas").text(json.result[0].NUMORDEN);
    }
    $('#tableTarjetasGift').DataTable().destroy();
    $('#tbodyTarjetasGif').empty();
    if (json.result2.length>0 && json.result!="") {
        for (var i = 0; i < json.result2.length; i++) {
           $('#tbodyTarjetasGif').append("<tr>"+
            "<td>"+i+
            "<td>"+json.result2[i].CANTIDAD+
            "<td>"+json.result2[i].NOM_PROD+
            "<td>"+parseFloat(json.result2[i].CANTIDAD*json.result2[i].PRE_VENTA)+
            "<td>"+json.result2[i].F_VENTA

            );
           
       }
       paginatortableTarjetasGift();
   }

}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

function guardarDetalleTarjetaGif(){
    if (idTarjeta=="") {
       alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "YA NO TIENE SALDO DISPONIBLE", function () {
        alertify.message('OK');
    });

       return;
   }
    // $('#loading').css('display','block');
    tabla = $('#tablaDetalle input[name=cantidad]');
    // formaPago = $('input[name=formaPago]').val();
    totalBs = $('#totalBs').val();
    totalUsd = $('#totalUsd').val();
    
    nomCli=$("#spanNombreTajertas").text();
    nuevoSaldo=parseFloat($("#spanNuevoSaldoTajertas").text())<0?"0":$("#spanNuevoSaldoTajertas").text();
    fechaVenta = vf_Venta;
    turno = $('#vturno').text();
    var valor = LITERAL(totalBs);
    // alert(valor);

    var  selectVendedor = $('#selectVendedor').val();
    var  idProducto = new Array();
    var cantidadA = new Array();
    var precioA = new Array();
    var arrayNotaProducto = new Array();
    var NombreProdc=new Array();
    var subtotalA=new Array();
    var gQr=new Array();
   var nombrePdto=new Array();//este array se encarga d mandar a cocina el producto con la nota

   var contador=0;
   var  imPresion='{"imp":[';
   if (tabla.length > 0) {
    $(tabla).each(function (e) {
        var id = $(this).closest("tr").find("input[name=inputIdProducto]").val();
        var cantidad = $(this).closest("tr").find("input[name=cantidad]").val();
        var precio = $(this).closest("tr").find("input[name=precio]").val();
        var notaPducto = $(this).closest("tr").find("input[name=notaProducto]").val();
        var nompdo = $(this).closest("tr").find("input[name=nombreProducto]").val();
        var subTotal = $(this).closest("tr").find("#spansubTotal").text();
        var qr = $(this).closest("tr").find("input[name=productoQr]").val();
        var imppunto4A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto4');
        var imppunto3A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto3');
        var imppunto2A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto2');
        var imppunto1A=$(this).closest("tr").find("button[name=gestionImpresion]").attr('imppunto1');
        var impCocA=$(this).closest("tr").find("button[name=gestionImpresion]").attr('impcoc');
        contador++;
        if(tabla.length!=contador){
            imPresion+='{"impCoc":"'+impCocA+'","imppunto4":"'+imppunto4A+'","imppunto3":"'+imppunto3A+'","imppunto2":"'+imppunto2A+'","imppunto1":"'+imppunto1A+'"},';
        }else{
            imPresion+='{"impCoc":"'+impCocA+'","imppunto4":"'+imppunto4A+'","imppunto3":"'+imppunto3A+'","imppunto2":"'+imppunto2A+'","imppunto1":"'+imppunto1A+'"}';

        }
        
        idProducto.push(id);
        arrayNotaProducto.push(notaPducto);
        NombreProdc.push(nompdo);
        subtotalA.push(subTotal);
        gQr.push(qr);
        precioA.push(precio);
        cantidadA.push(cantidad);
        if (notaPducto == "") {
            nombrePdto.push(nompdo);
        } else {
            nombrePdto.push(nompdo + "(" + notaPducto + ")");
        }
    });
    imPresion+=']}';
    JSON.stringify(imPresion);
    var tito=JSON.parse(imPresion);
    var ventastr='{"NOM_CLI":"","NIT_CLI":"","ORDEN":"0","SALIDA":"MESA","DHORAVEN":"'+horaActualSistemaOp()+'","USUARIO":"'+USUARIOACTUAL+'"}';
    JSON.stringify(ventastr);
    var ventar=JSON.parse(ventastr);
    nroOrden="0";
    salidaProducto+=nroOrden;
    $.post(RUTAS.consumoTarjeta, {tarjeta: idTarjeta,idProducto:idProducto,notaPducto:arrayNotaProducto,
        cantidad:cantidadA,fechaVenta:fechaVenta,turno:turno,totalBs:totalBs,nuevoSaldo:nuevoSaldo,nomCli:nomCli}, function (res) {
            var json = JSON.parse(res);
            if (verificarError(json.error)) {
                return;
            }
            $('#tbdoyDetalle').empty();
            if (parseFloat($("#spanNuevoSaldoTajertas").text())<0) {
                nuevoSaldo=parseFloat(nuevoSaldo)*-1;
                $('#botonOculto').attr('nombre', "Consumo Adicional de Tarjeta");
                $('#botonOculto').attr('precio', parseFloat($("#spanNuevoSaldoTajertas").text())*-1);
                $('#botonOculto').attr('cantidad', '1');
                $('#botonOculto').text("Consumo Adicional de Tarjeta");
                listarDetalleVenta($('#botonOculto'), 0,"");
                $('#btnFacturar').click();
            }
            $('#tbodyTarjetasGif').empty();
            $('#divTajertaGif').css('display','none');
            $('#inputTarjetaGif').val("");
            $("#spanNombreTajertas").text("");
            $("#spanTotalTajertas").text("");
            $("#spanConsumoTajertas").text("");
            $("#spanUltimoSaldoTajertas").text("");
            $("#spanNuevoSaldoTajertas").text("");
            $("#spanFormaPagoTajertas").text("");
            $('#ModalTarjetasGif').modal('hide');
            $('#inputTarjeta').prop('checked',false);
            alertify.success('GUARDADO CORRECTAMENTE');

            if (impresionnube=="impresionnube") {
                $.ajax({
                    url:"http://" + ipCliente + ":8080/reportes/printCocina.php",
                    datatype:"jsonp",
                    data:{nroOrden:nroOrden,cantidad:cantidadA,subTotal:subtotalA,imPresion:tito.imp,
                     nombrePdto: nombrePdto, listaVenta: ventar},
                     type:"post",
                     success:function(res){
                        if (procesoRefrescar==10) {
                            location.reload();
                        }
                    }
                        });//Imprimir cocina
            }else{
                $.ajax({
                    url:"abms1/reportes/printCocina.php",
                    datatype:"jsonp",
                    data:{nroOrden:nroOrden,cantidad:cantidadA,subTotal:subtotalA,imPresion:tito.imp, 
                        nombrePdto: nombrePdto, listaVenta: ventar},
                        type:"post",
                        success:function(res){
                            if (procesoRefrescar==10) {
                                location.reload();
                            }
                        }
                        });//Imprimir cocina
            }
            limpiarDetalle();
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }

}

function obtenerTiempoBillar(HORAInicio,HORAfIN){
 var horaActual = mueveReloj();

 horita=HORAfIN==""?horaActual:HORAfIN+":00";
// hora_inicio=HORAfIN!=""?HORAInicio:
var hora1 = (horita).split(":"),//tiempo mayor
    hora2 = (HORAInicio+":00").split(":"),//tiempo menor
   // var hora1 = ("04:29:01").split(":"),
   //  hora2 = ("03:28:56").split(":"), 

   t1 = new Date(),
   t2 = new Date();
   var t3 = new Date();
 // t2.setDate(fechaE);

 t1.setHours(hora1[0], hora1[1], hora1[2]);
 t2.setHours(hora2[0], hora2[1], hora2[2]);
//Aquí hago la resta
// alert(t2.getMinutes());
// alert(t1.getMinutes() - t2.getMinutes());
t3.setHours(t1.getHours() - t2.getHours(), t1.getMinutes() - t2.getMinutes(), t1.getSeconds() - t2.getSeconds());
// t3.setHours(t1.getHours() - hora2[0], t1.getMinutes() - hora2[1], t1.getSeconds() - t2.getSeconds());
hora=("0" + t3.getHours()).slice(-2);

minutos=("0" + t3.getMinutes()).slice(-2);

//Imprimo el resultado

return (hora+"." + minutos );

// alert ("La diferencia es de: " + (t1.getHours() ? t1.getHours() + (t1.getHours() > 1 ? " horas" : " hora") : "") + (t1.getMinutes() ? ", " + t1.getMinutes() + (t1.getMinutes() > 1 ? " minutos" : " minuto") : "") + (t1.getSeconds() ? (t1.getHours() || t1.getMinutes() ? " y " : "") + t1.getSeconds() + (t1.getSeconds() > 1 ? " segundos" : " segundo") : ""));
}
function obtenerSubtotalBillar(cantidad,precio){
var preh=parseFloat(cantidad.substring(0, 2)*precio);//precio hora
var prem=cantidad.substring(3, 5);
// alert(cantidad.substring(0, 2));
// alert(prem);
vvalm=0;
switch(true){
    case (prem>=1 && prem<=5) :                                        
    vvalm = 2;
    break;
    case (prem>=6 && prem<=10):                
    vvalm = 3;
    break;
    case (prem>=11 && prem<=15):               
    vvalm = 5;
    break;
    case (prem>=16 && prem<=20):   
    vvalm = 6;
    break;
    case (prem>=21 && prem<=25):      
    vvalm = 7;
    break;
    case (prem>=26 && prem<=30):                
    vvalm = 8;
    break;
    case (prem>=31 && prem<=35):                
    vvalm = 10;
    break;
    case (prem>=36 && prem<=40):                
    vvalm = 11;
    break;
    case (prem>=41 && prem<=45):                
    vvalm = 13;
    break;
    case (prem>=46 && prem<=50):                
    vvalm = 14;
    break;
    case (prem>=51 && prem<=55):                
    vvalm = 15;
    break;
    case (prem>=56 && prem<=59):                 
    vvalm = 16;
    break;
    default:
    break;
}

return (preh+vvalm).toFixed(3);
}

function obtenerPrecioAtencion(total){
    vtotalsercub=0;
    switch(true){
       case  (total>=1 && total<=50):
       vtotalsercub = 0
       break;
       case  (total>=51 && total<=100) :
       vtotalsercub = 5
       break;
       case  (total>=101 && total<=500) :
       vtotalsercub = 10
       break;
       case (total>=501 && total<=1000):
       vtotalsercub = 12
       break;
       case (total>=1001 && total<=2000):
       vtotalsercub = 15
       break;
       case (total>=2001 && total<=3000):
       vtotalsercub = 20           
       break;
   }
   return vtotalsercub;
}

function pararTiempoBillar(idDetalle,boton){

   $.post(RUTAS.pararTiempo, {idDetalle: idDetalle,horaA:horaA,idMesa:idMesa}, function (res) {
    var json = JSON.parse(res);
    if (verificarError(json.error)) {
        return;
    }
    $(boton).attr('disabled','disabled');
    alertify.success('GUARDADO CORRECTAMENTE');
    horaA="";
}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}
numCelularTigo=0;
transaccionTigoMoney=null;
function guardarNumeroTigoMon(select){
    if ($(select).val()=="tigomon") {
        alertify.prompt("NUMERO DE CELULAR", "NUMERO", "",
            function (evt, value) {
                numCelularTigo=value;
            // if (numCelularTigo!="" && numCelularTigo!=null && selectTipoPagoCredito=="tigomon" && formaPago == "CREDITO") {
                $('#loading').css('display','block');
                var  totalBs=$('#totalBs').val();
                $.ajax({
                    url:"abms1/CONTROLADORES/tigoMoneyController.php",
                    datatype:"jsonp",
                    cache:false,
                    data:{proceso:"tigomon",tel:numCelularTigo,monto: totalBs},
                    type:"post",
                    success:function(res){
                        var json = JSON.parse(res);
                        if (verificarError(json.error)) {
                            $('#loading').css('display','none');
                            return;
                        }else{
                            $('#totalFacturaBs').dblclick();
                            alertify.alert('<span style="font-weight: bold;    color: blue;">TRANSACCION</span>', "TRANSACCION EJECUTADA SATIFACTORIAMENTE", function () {
                                alertify.message('OK');
                            });
                            $('#loading').css('display','none');
                            transaccionTigoMoney=json.result;
                        }
                    }   
                }).fail(function(xhr, ajaxOptions, thrownError){
                    failErrors(xhr, ajaxOptions, thrownError);  
                });
  // }
},
function () {
                // alertify.error('Cancel');
            });
    }
}


function buscarPorNombre(){
 $('#tableCliente').DataTable().destroy();
 $('#tbodyListaCliente').empty();
 nombre=$('#buscarNomNitCli').val();
 $.ajax({
    url: RUTAS.clientesBuscarNombre,
    type: 'GET',
    dataType: 'JSON',
    async: true,
    data: {nombre:nombre },
    success: function(res) {
        for (var i = 0; i < res.result.length; i++) {        
         $('#tbodyListaCliente').append("<tr>"+
            "<td style='width: 42%'>"+res.result[i].NOM_CLI+
            "<td style='width: 13%;'>"+res.result[i].NIT_CLI+
            "<td  style='width: 14%;'>"+res.result[i].TEL_CLI+
            "<td style='    width: 19%;'>"+res.result[i].DIR_CLI+
            "<td style='    width: 5%'>"+res.result[i].CEL_CLI+
            "<td><button  class='btn btn-info' title='EDITAR CLIENTE' onclick='cargarCliente("+res.result[i].ID+",this)'><i class='fa fa-pencil-square-o' aria-hidden='true'></i></button><button  class='btn btn-danger' title='ELIMINAR CLIENTE' onclick='eliminarCliente("+res.result[i].ID+",this)'><i class='fa fa-trash-o' aria-hidden='true'></i></button>"

            );
     }
     paginadortbodyCliente();
     
 }

});
}
function buscarPorNit(){
 $('#tableCliente').DataTable().destroy();
 $('#tbodyListaCliente').empty();
 nit=$('#buscarNomNitCli').val();
 $.ajax({
    url: RUTAS.clientesBuscarNit,
    type: 'GET',
    dataType: 'JSON',
    async: true,
    data: {nit:nit },
    success: function(res) {
        for (var i = 0; i < res.result.length; i++) {        
         $('#tbodyListaCliente').append("<tr>"+
            "<td style='width: 42%'>"+res.result[i].NOM_CLI+
            "<td style='width: 13%;'>"+res.result[i].NIT_CLI+
            "<td  style='width: 14%;'>"+res.result[i].TEL_CLI+
            "<td style='    width: 19%;'>"+res.result[i].DIR_CLI+
            "<td style='    width: 5%'>"+res.result[i].CEL_CLI+
            "<td><button  class='btn btn-info' title='EDITAR CLIENTE' onclick='cargarCliente("+res.result[i].ID+",this)'><i class='fa fa-pencil-square-o' aria-hidden='true'></i></button><button  class='btn btn-danger' title='ELIMINAR CLIENTE' onclick='eliminarCliente("+res.result[i].ID+",this)'><i class='fa fa-trash-o' aria-hidden='true'></i></button>"
            );
     }
     paginadortbodyCliente();
     
 }

});
}
function paginadortbodyCliente() {
    $('#tableCliente').DataTable({
        "pagingType": "full_numbers",
        "destroy": true,
        "order": [[0, "asc"]],
        "scrollY": "418px",
        "scrollCollapse": true,
        "paging": false,
        "search": false,
        retrieve: true

    });
}

function paginatortableTarjetasGift() {
    $('#tableTarjetasGift').DataTable({
        "pagingType": "full_numbers",
        "destroy": true,
        "order": [[0, "asc"]],
        "scrollY": "418px",
        "scrollCollapse": true,
        "paging": false,
        retrieve: true

    });
}
idCliente=null;
function cargarCliente(id,boton){
    idCliente=id;
    var nomCli = $(boton).parents("tr").find("td").eq(0).text();
    var nitCli = $(boton).parents("tr").find("td").eq(1).text();
    var telCli = $(boton).parents("tr").find("td").eq(2).text();
    var dirCli = $(boton).parents("tr").find("td").eq(3).text();
    var celCli = $(boton).parents("tr").find("td").eq(4).text();
    $('#nombreCliA').val(nomCli);
    $('#nitCliA').val(nitCli);
    $('#dirCliA').val(dirCli);
    $('#telCliA').val(telCli);
    $('#celCliA').val(celCli);

    $('#ModalModificarCliente').modal('show');

}
function modificarCliente(){
    var nomCli = $('#nombreCliA').val();
    var nitCli = $('#nitCliA').val();
    var  dirCliA = $('#dirCliA').val();
    var telCli = $('#telCliA').val();
    var celCli = $('#celCliA').val();
    $.ajax({
        url: RUTAS.clientesActualizar,
        type: 'POST',
        dataType: 'JSON',
        cache:false,
        async: true,
        data: {nomCli:nomCli,nitCli:nitCli,telCli:telCli,celCli:celCli,dirCliA:dirCliA,idCliente:idCliente},
        success: function(res) {
            if (verificarError(res.error)) {
               
             return;
         }
         buscarPorNombre();
         $('#ModalListarCliente').css('overflow-y','auto');
         $('#ModalModificarCliente').modal('hide');
     }

 }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

function eliminarCliente(id,boton){
    $.ajax({
        url: RUTAS.clientesEliminar,
        type: 'POST',
        dataType: 'JSON',
        cache:false,
        async: true,
        data: {idCliente:id},
        success: function(res) {
            if (verificarError(res.error)) {
               
             return;
         }
         buscarPorNombre();
         
     }

 }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

function eliminarItemCant(){
   tabla = $('#tablaDetalleMesa input[name=idDetalleMesa]');
   var  contador= 0;
   error = "";
   mesaPagar = idMesa;
   idDet = new Array();
   cantidadMesaProd = new Array();
   nombrePdto = new Array();
   idProdMes = new Array();
   contador2 = 0;
   if (tabla.length > 0) {
    tbody = $('#tbdoyDetalle');
    tbody.empty();
    $('#nombreCli').val(nombreMesa);
    $(tabla).each(function (e) {
        var idDetalle = $(this).closest("tr").find("input[name=idDetalleMesa]").val();
        var idProductoMesa = $(this).closest("tr").find("input[name=idProductoMesa]").val();
        var cantidad = $(this).closest("tr").find("input[name=inputAccion]").val();
        var idProductoMesa = $(this).closest("tr").find("input[name=idProductoMesa]").val();
        var cantidad2 = $(this).closest("tr").find("input[name=inputAccion]");
        var cantidadDetalle = $(cantidad2).parents("tr").find("td").eq(0);
        var nombreProduto = $(cantidad2).parents("tr").find("td").eq(2).text();
        var precio = $(cantidad2).parents("tr").find("td").eq(3).text();
        var tipoProducto=$(this).closest("tr").find("input[name=tipoProducto]").val();
        var subtotal=$(cantidad2).parents("tr").find("td").eq(4).text();
        if (tipoProducto=="TIEMPO" && cantidad!="") {
         alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "<span style='font-weight: bold; '>* NO PUEDE ELIMINAR UN PRODUCTO DE TIEMPO</span><br>", function () {
            alertify.message('OK');
        });
         return;
     }

     if (parseFloat(cantidadDetalle.text()) == parseFloat(cantidad)) {
        contador++;
    }
    if (cantidad != "" && cantidad != "0") {
        cantidadMesaProd.push(cantidad);
        idDet.push(idDetalle);
        idProdMes.push(idProductoMesa);
    } else {
        contador2++;
    }
    if (contador == tabla.length || contador2 == tabla.length) {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "<span style='font-weight: bold; '>* DEBE ANULAR MESA</span><br>", function () {
            alertify.message('OK');
        });
        limpiarDetalle();
        error = "error";
        return;
    }
});
    if (error == "") {
        fechaVenta = vf_Venta;
        $.post(RUTAS.eliminarCantidadMesa, {idDetalle: idDet,idMesa:idMesa,cantidad:cantidadMesaProd,fechaVenta:fechaVenta}, function (res) {
            var json = JSON.parse(res);
            if (verificarError(json.error)) {
                return;
            }
            alertify.success('GUARDADO CORRECTAMENTE');
            idMesa=null;
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }
}
}
function verificarBackup(){
    if (backup!="nobackup") {
     JSON.stringify(backup);
     var obj=JSON.parse(backup);
     var fecha = new Date();

     if(obj.DIA==f_Venta.getDate() && obj.ESTADO=="0" || obj.PENDIENTE=="1"){
 // if(navigator.onLine){//verifica si hay internet, caso contrario queda pendiente el backup


    $('#loading').css('display','block');
    $.post('abms1/Google-Drive/index.php', {proceso:"BACKUP",dia:obj.DIA,pendiente:obj.PENDIENTE}, function (res) {

        var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }

        alert("BACKUP ENVIADO CORRECTAMENTE");
        $('#loading').css('display','none');
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
// }else{
//     $.post('abms1/Google-Drive/index.php', {proceso:"backupPendiente",dia:obj.DIA,pendiente:obj.PENDIENTE}, function (res) {
   
// }).fail(function(xhr, ajaxOptions, thrownError){
//     failErrors(xhr, ajaxOptions, thrownError);  
// });
// }
}else{

    if (obj.DIA!=f_Venta.getDate()  && obj.ESTADO=="1") {
        $.post('abms1/Google-Drive/index.php', {proceso:"cambiarEstado",dia:obj.DIA,pendiente:obj.PENDIENTE}, function (res) {
           
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }
}
}
}
function activarIval(input){
    if ($(input).prop('checked')) {
        $('#ModalCompraIva').modal('show');
    }
}
function convertirQr(value){
    if (value!="") {
     var str = value;
     var res = str.split("|");
     nitProveedor= res[0];
     nroFactura= res[1];
     autorizacion= res[2];
     fecha= res[3];
     importe= res[4];
     codigoControl= res[6];
     ivaCreditoFiscal= res[5];
     $('#fechaFactura').val(fecha);
     $('#nitProveedor').val(nitProveedor);
     $('#nroAutorizacion').val(autorizacion);
     $('#codigoControl').val(codigoControl);
     $('#inputNroFactura').val(nroFactura);
     $('#inputDebitoFiscal').val(ivaCreditoFiscal);
     $('#inputGastosMonto').val(importe);
 }
}
// var targArea = document.getElementById ("keyPrssInp");
// targArea.addEventListener ('keydown',  reportKeyEvent);

// function reportKeyEvent (zEvent) {
//     var reportStr   =
//         "The " +
//         ( zEvent.ctrlKey  ? "Control " : "" ) +
//         ( zEvent.shiftKey ? "Shift "   : "" ) +
//         ( zEvent.altKey   ? "Alt "     : "" ) +
//         ( zEvent.metaKey  ? "Meta "    : "" ) +
//         zEvent.code + " " +
//         "key was pressed.";

//     // $("#statusReport").text (reportStr);

//     //--- Was a Ctrl-Alt-E combo pressed?
//     if (zEvent.ctrlKey  &&  zEvent.altKey  &&  zEvent.code === "KeyE") {
//         this.hitCnt = ( this.hitCnt || 0 ) + 1;
//     alert(reportStr);

//         // $("#statusReport").after (
//         //     '<p>Bingo! cnt: ' + this.hitCnt + '</p>'
//         // );
//     }
//     zEvent.stopPropagation ();
//     zEvent.preventDefault ()
// }

cambiarFechaPersonalizada=0;
function cambiarFecha(fecha){

if (fecha!="" && cambiofecha) {
vf_Venta=fecha;
cambiarFechaPersonalizada=1;
}
}