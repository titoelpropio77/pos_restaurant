$(document).ready(function(){
  var hoy = new Date();
  var dd = hoy.getDate();
    var mm = hoy.getMonth() + 1; //hoy es 0!
    var yyyy = hoy.getFullYear();
    if (dd < 10) {  dd = '0' + dd }
      if (mm < 10) {  mm = '0' + mm  }
        hoy = yyyy + '-' + mm + '-' +dd ;
      $('#fechaInicial').val(hoy);
      $('#fechaFin').val(hoy);
      $('#inputFecha').val(hoy);
      listarMovimientoDiario();
      listarInsumos_mysql(1);
    //   $('#calendar').fullCalendar({

    //    lang: 'es',
    //    editable: true,
    //    disableDragging: true,
    //   navLinks: false, // can click day/week names to navigate views
    //   selectable: true,
    //   selectHelper: true,

    //   eventMouseover: function( event, jsEvent, view ) { 
    //     background: 'red';
    //     textColor: 'red'
    //   },
    //   select: function(date,start, end) {
    //    var moment = date;
    //    fecha=moment.format('YYYY-MM-DD ');
    //    listarMovimientoDiario(fecha);
    //  },
    //  editable: false,
    //  eventLimit: true,
    //   color: 'yellow',   // an option!
    // textColor: 'red' // an option!
    //   // events:
    // });
    listarMotivomovimiento();
    listarAlmacen();
  });


$("#frmGuardarInventario").submit(function (e) {
  errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    var fecha = $('#inputFecha').val();
// var hora = $('#inputHora').val();
// var importe = $('#inputImporte').val();
// var tipo = $('#inputTipo').val();
// var glosa = $('#inputGlosa').val();
// var idAlmacen = $('#inputIdAlmacen').val();
// var idAlmacenDestino = $('#inputIdAlmacenDestino').val();
// var estado = $('#inputEstado').val();
// var idMotivoMovimiento = $('#inputIdMotivoMovimiento').val();
if (fecha=="")
{
  errores += "<span style='font-weight: bold;'>* EL CAMPO FECHA NO DEBE ESTAR VACIO.</span><br>";
}
// if (validar("texto y entero", hora)) {
//         errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO HORA.</span><br>";
//             }
// if (validar("decimal", importe)) {
//         errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IMPORTE.</span><br>";
//                          }
// if (validar("texto y entero", tipo)) {
//         errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO TIPO.</span><br>";
//             }
// if (validar("texto y entero", glosa)) {
//         errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO GLOSA.</span><br>";
//             }
// if (validar("decimal", idAlmacen)) {
//         errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDALMACEN.</span><br>";
//                          }
// if (validar("decimal", idAlmacenDestino)) {
//         errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDALMACENDESTINO.</span><br>";
//                          }
// if (validar("texto y entero", estado)) {
//         errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO ESTADO.</span><br>";
//             }
// if (validar("decimal", idMotivoMovimiento)) {
//         errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDMOTIVOMOVIMIENTO.</span><br>";
//                          }
if (errores != "") {
  alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
    alertify.message("OK");
  });
  return;
}
var parametros = new FormData($(this)[0]);
parametros.append("proceso","guardar");
$.ajax({
  type: 'POST',
  url: '../CONTROLADORES/inventarioController.php',
  data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
          var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          } else {
            alertify.success('GUARDADO CORRECTAMENTE');
            $('#tbodyDetalleInsumo').empty();
            $('#ModalInventarioGuardar').modal('hide');
            $('#frmGuardarInventario')[0].reset();
            listarInsumos_mysql($('#idAlmacen').val());
            listarMovimientoDiario();

          }
        },
        error: function (r) {

          alert('Error del servidor');
        }
      }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
      });
    });
function listarMotivomovimiento(){
 $.ajax({
  url: '../CONTROLADORES/motivomovimientoController.php',
  type: 'POST',
  typeData: 'json',
  data: {proceso: 'listarMotivomovimiento'},
  success: function (res) {
    var json = $.parseJSON(res);
    if (verificarError(json.error)) {
      return;
    }
    for (var i = 0; i < json.result.length; i++) {
      $('#idMotivoMovimiento').append("<option value='"+json.result[i].id+"'>"+json.result[i].nombre);
    }

    $('#loading').css('display', 'none');

  }
}).fail(function(xhr, ajaxOptions, thrownError){
  failErrors(xhr, ajaxOptions, thrownError);  
});
}
function listarMovimientoDiario(){
  fechaInicial=$('#fechaInicial').val();
  fechaFin=$('#fechaFin').val();
  $('#ghatable1').DataTable().destroy();
  $('#tbodyKardex').empty();
  $.post('../CONTROLADORES/kardexController.php',{proceso:'listarMovimientoDiario',fechaInicial:fechaInicial,fechaFin:fechaFin},function(res){
   var json = $.parseJSON(res);
   for (var i = 0; i < json.result.length; i++) {
     $('#tbodyKardex').append("<tr><td style='width: 10%;'>"+json.result[i].id+
      "<td >"+json.result[i].fecha+
      "<td >"+json.result[i].hora+
      "<td >"+json.result[i].tipo+
      "<td >"+json.result[i].estado+
      "<td >"+json.result[i].almacenOrigen+
      "<td >"+json.result[i].almacenDestino+
      "<td >"+json.result[i].motivo+
      "<td ><button class='btn btn-success btn-sm' onclick='listarDetalleInventario("+json.result[i].id+")'>Detalle"
      );
   }
   paginadorInventario();
 });
}
function listarDetalleInventario(idInventario){
  $('#tbodyDetalleInventario').empty();
  $.post('../CONTROLADORES/kardexController.php',{proceso:'listarDetalleInventario',idInventario:idInventario},function(res){
    var json = JSON.parse(res);
    if (verificarError(json.error)) {
      return;
    }
    for (var i = 0; i < json.result.length; i++) {
      $('#tbodyDetalleInventario').append("<tr>"+
        "<td>"+json.result[i].COD_INS+
        "<td>"+json.result[i].NOM_INSUMO+
        "<td>"+json.result[i].CANTIDAD+
        "<td>"+json.result[i].PRE_COSTO+
        "<td>"+json.result[i].nombre
        );
    }
    $('#ModalListarMovimiento').modal('show');
  }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
  });
}
function listarInsumos_mysql(idAlmacen){
 $('#ghatableInsumo').DataTable().destroy();
 $('#tbodyInsumo_mysql').empty();
 $.ajax({
  url: '../CONTROLADORES/insumoController.php',
  type: 'POST',
  typeData: 'json',
  data: {proceso: 'mostrarDadoAlmacen',idAlmacen:idAlmacen},
  success: function (res) {
    var json = $.parseJSON(res);
    if (verificarError(json.error)) {
      return;
    }
    for (var i = 0; i < json.result.length; i++) {
      stockActual=json.result[i].stockActual;
      unidad=json.result[i].MEDIDA;
      if (json.result[i].MEDIDAM!="" && json.result[i].MEDIDAM!=null && json.result[i].OPE_MM!=null && json.result[i].OPE_MM!="") {
        stockActual=eval(json.result[i].stockActual+json.result[i].OPE_MM+json.result[i].VAL_FOR_MM);
      unidad=json.result[i].MEDIDAM;

      }  
      $('#tbodyInsumo_mysql').append('<tr>'+
       '<td>'+json.result[i].COD_INS+
       '<td>'+json.result[i].NOM_INSUMO+
       '<td>'+parseFloat(stockActual).toFixed(3)+
       '<td>'+unidad+
       '<td><button type="button" onclick="agregarInsumoFila(this)" class="btn btn-info btn-sm delete-btn" ><i class="fa fa-plus"></i></button></tr>');
    }
    paginadorInsumos_mysql();
    $('#loading').css('display', 'none');
  }
}).fail(function(xhr, ajaxOptions, thrownError){
  failErrors(xhr, ajaxOptions, thrownError);  
});
}
function paginadorInsumos_mysql() {
  $('#ghatableInsumo').DataTable({
   "pagingType": "full_numbers",
   "destroy": true,
   "order": [[0, "asc"]],
   "scrollY": "350px",
   "scrollCollapse": true,
   "paging": false,
   retrieve: true
 });
}
function paginadorInventario() {
  $('#ghatable1').DataTable({
   "pagingType": "full_numbers",
   "destroy": true,
   "searching": false,
   "order": [[0, "desc"]],
   "scrollY": "350px",
   "scrollCollapse": true,
   "paging": false,
   retrieve: true
 });
}

fila="";
function agregarFila(){
  $('#tbodyDetalleInsumo').append('<tr><td><button type="button" data-toggle="modal" onclick="fila=this" data-target="#ModalInsumo" type="button" class="btn btn-success btn-sm"><i class="fa fa-plus"></i></button><button  type="button" class="btn btn-danger btn-sm" onclick="removerFila(this)"><i class="fa fa-trash-o" aria-hidden="true"></i></button>'+
    '<td><input name="idInsumo[]" names="idInsumo"  type="text" readonly="" class="form-control">'+
    '<td>'+
    '<td><select style="width: 81px;" names="unidad" class="form-control" name="unidad[]">'+
    '<td><input onchange="calcularSubtotal(this,0)" names="precioIns" step="0.001" name="precioIns[]" class="form-control" type="number" >'+
    '<td><input type="hidden" names="stockActual" name="stockActual[]"><input onchange="calcularSubtotal(this,1)" step="0.001" name="cantidadIns[]" names="cantidadIns" class="form-control" type="number" >'+
    '<td><input step="0.001" names="subTotal" name="subTotal[]" class="form-control" type="number" >'
    );
}
function agregarInsumoFila(insumos){
  $('#ModalInventarioGuardar').css('overflow-y','auto');
  idInsu=  $(insumos).parents("tr").find("td").eq(0).text();
  if (!verificarInsTabla(idInsu)) {
    alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", "YA EXISTE ESE PRODUCTO EN LA LISTA", function () {
      alertify.message("OK");
      return;
    });
    return;
  }
  insuNom=  $(insumos).parents("tr").find("td").eq(1).text();
  inputStockActual=$(fila).parents("tr").find("input[names=stockActual]");
  $('#ModalInsumo').modal('hide');
  select=$(fila).parents("tr").find("select[names=unidad]");
  $(fila).parents("tr").find("input[names=idInsumo]").val(idInsu);
// $(fila).parents("tr").find("td").eq(1).text(idInsu);
$(fila).parents("tr").find("td").eq(2).text(insuNom);
listarUnidadMedida(idInsu,select,inputStockActual);
}

function listarUnidadMedida(idInsumo,select,inputStockActual){
  cargando();
  $(select).empty();
  idAlmacen=$('#idAlmacen').val();
  $.post('../CONTROLADORES/insumoController.php',{proceso:'listarUnidadInsumo',idInsumo:idInsumo,idAlmacen:idAlmacen},function(res){
    var json = JSON.parse(res);
    cerrarCargando();
    if (verificarError(json.error)) {
      return;
    }
    if (json.result.length>1  && json.result[1].OPE_MM!="") {
     stockActual=eval(json.result[1].stockActual+json.result[1].OPE_MM+json.result[1].VAL_FOR_MM);
     for (var i = 0; i < json.result.length; i++) {
       if (json.result[i].idUnidadMedad==json.result[i].MEDIDAM) 
      {
        (select).append("<option value='"+json.result[i].MEDIDAM+","+json.result[i].OPE_MM+"'>"+json.result[i].abreviatura); 
      }
      if (json.result[i].idUnidadMedad==json.result[i].MEDIDA) 
      {
       $(select).append("<option value='"+json.result[i].MEDIDA+",0'>"+json.result[i].abreviatura);  
     }

   // if (json.result[i].OPE_MX) {
   //   $(select).append("<option value='"+json.result[2].MEDIDAX+","+json.result[i].OPE_MX+"'>"+json.result[i].abreviatura); 
   // }
 }


}else{
  stockActual=eval(json.result[0].stockActual+json.result[0].OPE_MM+json.result[0].VAL_FOR_MM);
  

}
$(inputStockActual).val(stockActual);
}).fail(function(xhr, ajaxOptions, thrownError){
  failErrors(xhr, ajaxOptions, thrownError);  
});
}
function calcularSubtotal(index,opcion){//opcion 1 = cantidad, 0=precio
 cantidad=   $(index).parents("tr").find("input[names=cantidadIns]").val();
 stockActual=$(index).parents("tr").find("input[names=stockActual]").val();
 if (opcion==0) {
  subTotal=$(index).val()*cantidad;
  if (isNaN(subTotal)) {
    $(index).parents("tr").find("input[names=subTotal]").val("");
  }else{
    $(index).parents("tr").find("input[name=ssubTotal]").val(subTotal);
  }
}else{

 //  if (parseFloat(cantidad)>parseFloat(stockActual) ) {
 //    if($('select[name=tipo]').val()=="EGRESO" || $('select[name=tipo]').val()=="TRASPASO"){
 //     alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", "NO EXISTE ESE CANTIDAD EN ALMACEN SELECCIONADO", function () {
 //      alertify.message("OK");
 //      $(index).parents("tr").find("input[names=cantidadIns]").val("");
 //      return;
 //    });
 //     return;
 //   }
 // }
 precioIns= $(index).parents("tr").find("input[names=precioIns]").val();
  // if (cantidad!="" && $(index).val()!=""  ) {}
  subTotal=($(index).val()*precioIns)
  if (isNaN(subTotal)) {
    $(index).parents("tr").find("input[names=subTotal]").val("");
  }else{
    $(index).parents("tr").find("input[names=subTotal]").val(subTotal);
  }
}
calcularTotal();
}
function listarAlmacen(){
  $.ajax({
    url: '../CONTROLADORES/almacenController.php',
    type: 'POST',
    typeData: 'json',
    data: {proceso: 'listarAlmacen'},
    success: function (res) {
      var json = $.parseJSON(res);
      if (verificarError(json.error)) {
        return;
      }
      for (var i = 0; i < json.result.length; i++) {
        $('#idAlmacen').append("<option value='"+json.result[i].id+"'>"+json.result[i].nombre);
        $('#idAlmacenDestino').append("<option value='"+json.result[i].id+"'>"+json.result[i].nombre);
      }
    }
  }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
  });
}

function calcularTotal(){
  var tabla = $('#ghatableInsumoProducto input[name=precioIns]');
  total=0;
  if (tabla.length > 0) {


    $(tabla).each(function (e) {
      total=total+parseFloat($(this).closest('tr').find("input[names=subTotal]").val());
    });
  }
  $('input[name=totalImporte]').val(total.toFixed(2));
}
function verificarInsTabla(codigo){
  var tabla = $('#ghatableInsumoProducto input[names=precioIns]');
  var total=0;
  var aux=0;
  if (tabla.length > 0) {
    $(tabla).each(function (e) {
      var cod=$(this).closest('tr').find("input[names=idInsumo]").val();
      if (cod==codigo) {
        aux=1;
      }
    });
  }
  if (aux==0) {
    return true;
  }  else{
    return false;
  }
}
function removerFila(fila){
  $(fila).closest('tr').remove();
  calcularTotal();
}
function verficarTipoInventario(valor)
{

  if (valor=="TRASPASO") {
    $('#divAlmacenDestino').css('display','block');
  }else{
    $('#divAlmacenDestino').css('display','none');

  }
}
