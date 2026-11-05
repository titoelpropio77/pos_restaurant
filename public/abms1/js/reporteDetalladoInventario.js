$(document).ready(function(){
  listarAlmacen();
  listarEmpleado();
  listarMotivoMovimiento();
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
    });
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
      }
    }
  }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
  });
}
function listarMotivoMovimiento(){
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
function listarEmpleado(){
 $.post("../CONTROLADORES/usuarioController.php", {proceso: "listarTodo"}, function (res) {
  var json = JSON.parse(res);
  if (json.error.length > 0) {
    if ("Error Session" === json.error) {
      $('#loading').css('display', 'none');

      alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
        alertify.message('OK');
        window.location = "../desconectar.php";
      });
      return;
    }
    alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
      alertify.message('OK');
    });
    $('#loading').css('display', 'none');
    return;
  }
  for (var i = 0; i < json.result.length; i++) {
    $('#idUsuario').append("<option value='"+json.result[i].ID+"'>"+json.result[i].NOMBRES);

  }
}).fail(function(xhr, ajaxOptions, thrownError){
  failErrors(xhr, ajaxOptions, thrownError);  
});
}
tablaExcelReporteInventario="";
function listarRepInvDet(){
   $('#tableReporteDetallado').DataTable().destroy();
  $('#tbodyReporteDetallado').empty();
  fechaInicial=$('#fechaInicial').val();
  fechaFin=$('#fechaFin').val();
  idUsuario=$('#idUsuario').val();
  idAlmacen=$('#idAlmacen').val();
  tipoInventario=$('#tipoInventario').val();
  idMotivoMovimiento=$('#idMotivoMovimiento').val();
 
  $.post('../CONTROLADORES/kardexController.php',{proceso:'listarRepInvDet',fechaInicial:fechaInicial,fechaFin:fechaFin,idUsuario:idUsuario,
    idAlmacen:idAlmacen,tipoInventario:tipoInventario,idMotivoMovimiento:idMotivoMovimiento},function(res){
     var json = $.parseJSON(res);
     tablaExcelReporteInventario="<table class='pv tg tc fc fa gastos vd' id='tablaExcelReporteInventario'><tbody>";
      tablaExcelReporteInventario+=  
          "<tr style='font-weight: bold;'><td style='font-weight: bold;'>FECHA<td>INSUMO<td>CANTIDAD<td>UNIDAD<td>TIPO DE INVENTARIO<td>ALMACEN ORIGEN<td>ALMACEN DESTINO";
     for (var i = 0; i < json.result.length; i++) {
      tablaExcelReporteInventario+=("<tr><td>"+json.result[i].fecha+
        '<td>'+json.result[i].NOM_INSUMO+
        '<td>'+json.result[i].CANTIDAD+
        '<td>'+json.result[i].abreviatura+
        '<td>'+json.result[i].tipo+
        '<td>'+json.result[i].almacenOrigen+
        '<td>'+json.result[i].almacenDestino);
      $('#tbodyReporteDetallado').append("<tr><td>"+json.result[i].fecha+
        '<td>'+json.result[i].NOM_INSUMO+
        '<td>'+json.result[i].CANTIDAD+
        '<td>'+json.result[i].abreviatura+
        '<td>'+json.result[i].tipo+
        '<td>'+json.result[i].almacenOrigen+
        '<td>'+json.result[i].almacenDestino
        );
     }
   paginadortableReporteDetallado();
 });
}

function paginadortableReporteDetallado(){
   $('#tableReporteDetallado').DataTable({
        'pagingType': 'full_numbers',
        'scrollY': '330px',
        'searching': false,
        'scrollCollapse': true,
        'paging': false,
        retrieve: true

    });
}
function imprimirExcel(){
  $("body").append(tablaExcelReporteInventario);
$('#tablaExcelReporteInventario').tableExport({type:'excel',headings:true,fileName: "Reporte_INVENTARIO"+fechaActualReporte()});
    $('#tablaExcelReporteInventario').remove();

}