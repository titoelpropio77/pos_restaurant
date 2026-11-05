
$(document).ready(function(){
  cerrarCargando();
  obtnerEmpresa();
  listarMesas();
  listarMesero();
  var hoy = new Date();
  var dd = hoy.getDate();
    var mm = hoy.getMonth() + 1; //hoy es 0!
    var yyyy = hoy.getFullYear();
    if (dd < 10) {  dd = '0' + dd }
      if (mm < 10) {  mm = '0' + mm  }
        hoy = dd + '/' + mm + '/' +yyyy ;
      $.fn.datepicker.defaults.format = "dd/mm/yyyy";
      $('#fechaInicial').datepicker({
        autoclose: true,
        dateFormat: 'dd/mm/yyyy'
      }).datepicker("setDate", hoy);
      $('#fechaFinal').datepicker({
        autoclose: true,
        dateFormat: 'dd/mm/yyyy'
      }).datepicker("setDate", hoy);
	// $(function (){ $("#fechaInicial").datepicker({ viewMode: 'days',  format: 'YYYY-MM-DD' }); });
 //    $(function (){ $("#fechaFinal").datepicker({ viewMode: 'days',  format: 'YYYY-MM-DD' }); });

 obteneripCliente();
});
empresa=Array();
    function obtnerEmpresa(){//obtiene los datos de la empresa
      idSucursal=$('#selectSucursal').val();
      $.post('CONTROLADORES/docificacionController.php',{proceso:"obtenerEmpresa",idSucursal:idSucursal},function(res){
        var json = $.parseJSON(res);
        empresa=json.result;
      }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
      });
    }
    tablaExcelVentasDadaFecha="";
    VentasDadaFecha=new Array();
    function listarReporteProductos(){
     fechaInicial=$('#fechaInicial').val();
     fechaFinal=$('#fechaFinal').val();

     tipoPago=$('input:checked').val();
     var primera = Date.parse(fechaInicial); 
     var segunda = Date.parse(fechaFinal); 

     if (fechaFinal=="" || fechaFinal=="" || primera > segunda) {
       alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE COLOCAR LAS 2 FECHAS CORRECTAMENTE", function () {
        alertify.message('OK');

      });
       return;
     }
     var fechaInicial = moment(fechaInicial, 'DD/MM/YYYY');
     var fechaFinal = moment(fechaFinal, 'DD/MM/YYYY');
     fechaFinal = fechaFinal.format('YYYY-MM-DD');
     fechaInicial = fechaInicial.format('YYYY-MM-DD');

     $('#tbodyReporteCaja').empty();
     $.post('CONTROLADORES/cierreProdVendContoller.php',{proceso:'listarReporteProductos',fechaFinal:fechaFinal,fechaInicial:fechaInicial,tipoPago:tipoPago,multiSuc:multiSuc},function(res){
      var json = $.parseJSON(res);
      if (json.error.length > 0) {
        if ("Error Session" === json.error) {
          $('#loading').css('display', 'none');

          alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
            alertify.message('OK');
            window.location="../desconectar.php";
          });


          return;
        }
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
          alertify.message('OK');
        });
        $('#loading').css('display', 'none');
        return;
      }
      VentasDadaFecha=new Array();
      VentasDadaFecha=json.result;
      tablaExcelVentasDadaFecha="<table class='pv tg tc fc fa gastos vd' id='tablaExcelVentasDadaFecha'><tbody>";
      tablaExcelVentasDadaFecha+=
      "<tr><td>CANTIDAD<td>PRODUCTO<td>SUBTOTAL<tbody>";
      TOTAL=0;
      for (var i = 0; i < json.result.length; i++) {
        subtotal=(parseFloat(json.result[i].AGRUPA1)).toFixed(2);
        $('#tbodyReporteCaja').append("<tr>"+
         "<td>"+json.result[i].CANTIDAD+
         "<td>"+json.result[i].NOM_PROD+
         "<td style='text-align:right'>"+subtotal
         );
        TOTAL+=parseFloat(subtotal);
        tablaExcelVentasDadaFecha+="<tr><td>"+json.result[i].CANTIDAD+
        "<td>"+json.result[i].NOM_PROD+
        "<td>"+subtotal;


      }
      tablaExcelVentasDadaFecha+="<tr><td>TOTAL<td><TD>"+TOTAL;
      $('#totalCaja').val(TOTAL.toFixed(2));
    }).fail(function(xhr, ajaxOptions, thrownError){
      failErrors(xhr, ajaxOptions, thrownError); 
    });
  }
  tablaExcelGrupoDadaFecha="";

  $(function () {
    $.contextMenu({
      selector: '#btnMesasMeseros',
      trigger: 'left',
      callback: function (key, options) {
        if (key=="MESAS") {
         $('#ModalMesasHabilitadas').modal('show');
       }
       if (key=="MESEROS") {
         $('#ModalMeseros').modal('show');

       }
       if (key=="ANULADO") {
        listarItemAnulado2Fechas();
      }
      if (key=="MESASANULADOS") {
        listarMesasAnuladas2Fechas();
      }
    },
    items: {
      "MESAS": {name: "MESAS"},
      "MESEROS": {name: "MESEROS"},
      "ANULADO": {name: "ITEM  ANULADOS"},
      "MESASANULADOS": {name: "MESAS ANULADAS"}
    }
  });

  })


function listarMesas() {//lista todas las mensas
  $('#divMesaHabilitadas').empty();
  $.post('CONTROLADORES/mesasController.php', {proceso: 'listarTodo'}, function (res) {
    var json = JSON.parse(res);
    if (json.error.length > 0) {
      if ("Error Session" === json.error) {
        $('#loading').css('display', 'none');

        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
          alertify.message('OK');
          window.location = "desconectar.php";
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

      $('#divMesaHabilitadas').append('<button onclick="listarProductosMesas2Fechas(this)"   idMesa="' + json.result[i].ID + '" class="btn btn-warning btn-lg" style=" background:' + json.result[i].COLORES + ';   margin: 2px;white-space: inherit;">' + json.result[i].NRO_MESA + '</button>');


    }
  }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
  });
}

function listarProductosMesas2Fechas(boton){

  cargando();
  idMesa=$(boton).attr('idMesa');
  nroMesa=$(boton).text();

  fechaInicial=$('#fechaInicial').val();
  fechaFinal=$('#fechaFinal').val();
  var primera = Date.parse(fechaInicial); 
  var segunda = Date.parse(fechaFinal); 

  if (fechaFinal=="" || fechaFinal=="" || primera > segunda) {
   alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE COLOCAR LAS 2 FECHAS CORRECTAMENTE", function () {
    alertify.message('OK');

  });
   cerrarCargando();
   return;
 }
 var fechaInicial = moment(fechaInicial, 'DD/MM/YYYY');
 var fechaFinal = moment(fechaFinal, 'DD/MM/YYYY');
 fechaFinal = fechaFinal.format('YYYY-MM-DD');
 fechaInicial = fechaInicial.format('YYYY-MM-DD');


 $.post('CONTROLADORES/productoMesaController.php',{proceso:'listarProductosMesas2Fechas',fechaInicial:fechaInicial,fechaFinal:fechaFinal,idMesa:idMesa,multiSuc:multiSuc},function(res){
  var json = JSON.parse(res);
  if (json.error.length > 0) {
    if ("Error Session" === json.error) {
      $('#loading').css('display', 'none');

      alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
        alertify.message('OK');
        window.location = "desconectar.php";
      });
      return;
    }
    alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
      alertify.message('OK');
    });
    $('#loading').css('display', 'none');
    return;
  }

  tablaExcelMesas="<table class='pv tg tc fc fa gastos vd' id='tablaExcelMesas'><tbody>";
  tablaExcelMesas+="<tr><td>NRO_MESA<td>"+nroMesa;
  for (var i = 0; i < json.result.length; i++) {
    subtotal=(parseFloat(json.result[i].AGRUPA1)).toFixed(2);
    tablaExcelMesas+="<tr>"+
    "<td>"+json.result[i].CANTIDAD+
    "<td>"+json.result[i].NOM_PROD+
    "<td>"+(parseFloat(subtotal).toFixed(3))
  }
  $("body").append(tablaExcelMesas);
  $('#tablaExcelMesas').tableExport({type:'excel',headings:true,fileName: "Reporte_MESAS"+fechaActualReporte()});
  $('#tablaExcelMesas').remove();
  cerrarCargando();


  $('#ModalMesasHabilitadas').modal('hide');
});
}

// function listarProductosMesero2Fechas(){

// fechaInicial=$('#fechaInicial').val();
// 	fechaFinal=$('#fechaFinal').val();
// 	idMesa=$(boton).attr('idMesa');
// 	nroMesa=$(boton).attr('nromesa');
//     $('#tbodyReporteCaja').empty();
// 		var fechaInicial = moment(fechaInicial, 'DD/MM/YYYY');
// 	fechaInicial = fechaInicial.format('YYYY-MM-DD');
// 	var fechaFinal = moment(fechaFinal, 'DD/MM/YYYY');
// 	fechaFinal = fechaFinal.format('YYYY-MM-DD');
// 	tipoPago=$('input:checked').val();
// }

function listarMesero() {
  $.post("CONTROLADORES/meseroController.php", {proceso: "listarTodo",multiSuc:multiSuc}, function (res) {
    var json = JSON.parse(res);
    if (json.error.length > 0) {
      if ("Error Session" === json.error) {
        $('#loading').css('display', 'none');

        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
          alertify.message('OK');
          window.location = "desconectar.php";
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
      if (json.result[i].ESTADO=="ACTIVO") {
        $('#divMeseros').append('<button onclick="listarProductosMeseros2Fechas(this)" estado="ocupado"  idMesero="' + json.result[i].ID + '" class="btn btn-warning btn-lg" style="    margin: 2px;     width: 18%;    height: 66px;;white-space: inherit;">' + json.result[i].NOM_EMPL + '</button>');
        $('#idEmpleado').append('<option value="'+json.result[i].ID+'">'+json.result[i].NOM_EMPL);
      }
    }
  }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
  });
}

function listarProductosMeseros2Fechas(boton){


  nomMesero=$(boton).text();
  idMesero=$(boton).attr('idmesero');
  fechaInicial=$('#fechaInicial').val();
  fechaFinal=$('#fechaFinal').val();
  var primera = Date.parse(fechaInicial); 
  var segunda = Date.parse(fechaFinal); 

  if (fechaFinal=="" || fechaFinal=="" || primera > segunda) {
   alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE COLOCAR LAS 2 FECHAS CORRECTAMENTE", function () {
    alertify.message('OK');

  });
   return;
 }
 var fechaInicial = moment(fechaInicial, 'DD/MM/YYYY');
 var fechaFinal = moment(fechaFinal, 'DD/MM/YYYY');
 fechaFinal = fechaFinal.format('YYYY-MM-DD');
 fechaInicial = fechaInicial.format('YYYY-MM-DD');

 $.post('CONTROLADORES/productoMesaController.php',{proceso:'listarProductosMeseros2Fechas',fechaInicial:fechaInicial,fechaFinal:fechaFinal,nomMesero:nomMesero,multiSuc:multiSuc,idMesero:idMesero},function(res){
  var json = JSON.parse(res);
  if (json.error.length > 0) {
    if ("Error Session" === json.error) {
      $('#loading').css('display', 'none');

      alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
        alertify.message('OK');
        window.location = "desconectar.php";
      });
      return;
    }
    alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
      alertify.message('OK');
    });
    $('#loading').css('display', 'none');
    return;
  }
  tablaExcelMesero="<table class='pv tg tc fc fa gastos vd' id='tablaExcelMesero'><tbody>";
  tablaExcelMesero+="<tr><td>EMPLEADO<td>"+nomMesero;
  tablaExcelMesero+="<tr><tr><td>PRODUCTO<td>CANTIDAD<td>SUBTOTAL";
  for (var i = 0; i < json.result.length; i++) {
    subtotal=(parseFloat(json.result[i].AGRUPA1)).toFixed(2);

    tablaExcelMesero+="<tr>"+
    "<td>"+json.result[i].NOM_PROD+
    "<td>"+json.result[i].CANTIDAD+
    "<td>"+subtotal
  }
  $("body").append(tablaExcelMesero);
  $('#tablaExcelMesero').tableExport({type:'excel',headings:true,fileName: "Reporte_MESEROS"+fechaActualReporte()});
  $('#tablaExcelMesero').remove();

  $('#ModalMesero').modal('hide');
}).fail(function(xhr, ajaxOptions, thrownError){
  failErrors(xhr, ajaxOptions, thrownError); });
}

function listarItemAnulado2Fechas(){
  fechaInicial=$('#fechaInicial').val();
  fechaFinal=$('#fechaFinal').val();
  var fechaInicial = moment(fechaInicial, 'DD/MM/YYYY');
  fechaInicial = fechaInicial.format('YYYY-MM-DD');
  var fechaFinal = moment(fechaFinal, 'DD/MM/YYYY');
  fechaFinal = fechaFinal.format('YYYY-MM-DD');

  $.post('CONTROLADORES/productoMesaController.php',{proceso:'listarItemAnulado2Fechas',fechaInicial:fechaInicial,fechaFinal:fechaFinal,multiSuc:multiSuc},function(res){
    var json = JSON.parse(res);
    if (json.error.length > 0) {
      if ("Error Session" === json.error) {
        $('#loading').css('display', 'none');

        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
          alertify.message('OK');
          window.location = "desconectar.php";
        });
        return;
      }
      alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
        alertify.message('OK');
      });
      $('#loading').css('display', 'none');
      return;
    }
    tablaExcelItemAnulado="<table class='pv tg tc fc fa gastos vd' id='tablaExcelItemAnulado'><tbody>";
    tablaExcelItemAnulado+="<tr style='font-weight: bold;'><td style='font-weight: bold;'>CANTIDAD<td>PRODUCTO<td>SUB_TOTAL";
    TOTAL=0;
    for (var i = 0; i < json.result.length; i++) {
      TOTAL+=parseFloat(json.result[i].CANTIDAD*json.result[i].PRE_VENTA);

      tablaExcelItemAnulado+="<tr>"+
      "<td>"+json.result[i].CANTIDAD+
      "<td>"+json.result[i].NOM_PROD+
      "<td>"+(parseFloat(json.result[i].CANTIDAD*json.result[i].PRE_VENTA)).toFixed(3)
    }
    tablaExcelItemAnulado+="<tr><td><tr><td>TOTAL<td><td>"+(TOTAL).toFixed(3)+"</tbody></table>";

    $("body").append(tablaExcelItemAnulado);
    $('#tablaExcelItemAnulado').tableExport({type:'excel',headings:true,fileName: "Reporte_ITEM_ANULADO"+fechaActualReporte()});
    $('#tablaExcelItemAnulado').remove();

  }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError); });
}

function listarMesasAnuladas2Fechas(){
  fechaInicial=$('#fechaInicial').val();
  fechaFinal=$('#fechaFinal').val();
  var fechaInicial = moment(fechaInicial, 'DD/MM/YYYY');
  fechaInicial = fechaInicial.format('YYYY-MM-DD');
  var fechaFinal = moment(fechaFinal, 'DD/MM/YYYY');
  fechaFinal = fechaFinal.format('YYYY-MM-DD');

  $.post('CONTROLADORES/productoMesaController.php',{proceso:'listarMesasAnuladas2Fechas',fechaInicial:fechaInicial,fechaFinal:fechaFinal},function(res){
    var json = JSON.parse(res);
    if (json.error.length > 0) {
      if ("Error Session" === json.error) {
        $('#loading').css('display', 'none');

        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
          alertify.message('OK');
          window.location = "desconectar.php";
        });
        return;
      }
      alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
        alertify.message('OK');
      });
      $('#loading').css('display', 'none');
      return;
    }

    tablaExcelMesaAnulado="<table class='pv tg tc fc fa gastos vd' id='tablaExcelMesaAnulado'><tbody>";

    TOTAL=0;
    auxMesa="";
    tablaExcelMesaAnulado+="<tr style='font-weight: bold;'><td style='font-weight: bold;'>CANTIDAD<td>PRODUCTO<td>SUB_TOTAL";
    for (var i = 0; i < json.result.length; i++) {
     NROMESA=json.result[i].NRO_MESA;
     NROMESA2=i!=json.result.length-1?json.result[i+1].NRO_MESA:"";;
     tablaExcelMesaAnulado+="<tr>"+
     "<td>"+json.result[i].CANTIDAD+
     "<td>"+json.result[i].NOM_PROD+
     "<td>"+(parseFloat(json.result[i].CANTIDAD*json.result[i].PRE_VENTA)).toFixed(3);
     TOTAL+=parseFloat(json.result[i].CANTIDAD*json.result[i].PRE_VENTA);




     if (NROMESA!=NROMESA2) {

       tablaExcelMesaAnulado+="<tr style='font-weight: bold;'><td style='font-weight: bold;'>"+json.result[i].SALIDA;   

       tablaExcelMesaAnulado+="<td>TOTAL<td>"+(TOTAL).toFixed(3)+"<tr><td><td>";  
       TOTAL=0;
     }

   }

   $("body").append(tablaExcelMesaAnulado);
   $('#tablaExcelMesaAnulado').tableExport({type:'excel',headings:true,fileName: "Reporte_MESAS_ANULADO"+fechaActualReporte()});
   $('#tablaExcelMesaAnulado').remove();

 }).fail(function(xhr, ajaxOptions, thrownError){
  failErrors(xhr, ajaxOptions, thrownError); });
}
reporteGrupo=Array();
function listarGrupoDada2Fechas(){
  $('#tbodyGrupo').empty();
  fechaInicial=$('#fechaInicial').val();
  fechaFinal=$('#fechaFinal').val();
  var fechaInicial = moment(fechaInicial, 'DD/MM/YYYY');
  fechaInicial = fechaInicial.format('YYYY-MM-DD');
  var fechaFinal = moment(fechaFinal, 'DD/MM/YYYY');
  fechaFinal = fechaFinal.format('YYYY-MM-DD');
  $.post('CONTROLADORES/relProGrupoController.php',{proceso:'listarGrupoDada2Fechas',fechaInicial:fechaInicial,fechaFinal:fechaFinal},function(res){
   var json = JSON.parse(res);
   if (json.error.length > 0) {
    if ("Error Session" === json.error) {
      $('#loading').css('display', 'none');

      alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
        alertify.message('OK');
        window.location = "desconectar.php";
      });
      return;
    }
    alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
      alertify.message('OK');
    });
    $('#loading').css('display', 'none');
    return;
  }
  reporteGrupo=json.result;
  tablaExcelGrupoDadaFecha="<table class='pv tg tc fc fa gastos vd' id='tablaExcelGrupoDadaFecha'><tbody>";
  tablaExcelGrupoDadaFecha+=
  "<tr><td>CANTIDAD<td>GRUPO<tbody>";
  TOTAL=0;
  for (var i = 0; i < json.result.length; i++) {
    $('#tbodyGrupo').append("<tr>"+
      "<td>"+json.result[i].NOM_GRUPO+
      "<td style='text-align:right'>"+json.result[i].CANTIDAD
      );
    tablaExcelGrupoDadaFecha+="<tr>"+
    "<td>"+json.result[i].NOM_GRUPO+
    "<td>"+(parseFloat(json.result[i].CANTIDAD)).toFixed(3);

  }
}).fail(function(xhr, ajaxOptions, thrownError){
  failErrors(xhr, ajaxOptions, thrownError); 
});
}


function imprimirExcel(tabla){

        // $('#tableDetalleVenta').tableExport({type:'excel',headings:true,fileName: "Reporte_ProductosVendidos"+fechaActualReporte()});
        $("body").append(tablaExcelVentasDadaFecha);
        $("body").append(tablaExcelGrupoDadaFecha);
        $('.pv').tableExport({type:'excel',
          fileName: "REPORTE PRODUCTOS"+fechaActualReporte(),
          excelFileFormat:'xmlss',
          worksheetName: ['PRODUCTO VENDIDOS','REPORTE GRUPO']});
        $('#tablaExcelGrupoDadaFecha').remove();
        $('#tablaExcelVentasDadaFecha').remove();
      }
      function imprimirReporte(){
        fechaInicial=$('#fechaInicial').val();
        fechaFinal=$('#fechaFinal').val();
        if (impresionnube=="impresionnube") {
          if (isMobile.any()) {
           $.post('http://'+ipCliente+':8080/reportes/imprimirReporteProducto.php',{VentasDadaFecha:VentasDadaFecha,empresa:empresa,reporteGrupo:reporteGrupo,fechaInicial:fechaInicial,
            fechaFinal:fechaFinal,login:login},function(res){
            });
         }else{
          $.post('http://localhost:8080/reportes/imprimirReporteProducto.php',{VentasDadaFecha:VentasDadaFecha,empresa:empresa,reporteGrupo:reporteGrupo,fechaInicial:fechaInicial,
            fechaFinal:fechaFinal,login:login},function(res){

            });
        }
      }else{
        $.post('reportes/imprimirReporteProducto.php',{VentasDadaFecha:VentasDadaFecha,empresa:empresa,reporteGrupo:reporteGrupo,fechaInicial:fechaInicial,
          fechaFinal:fechaFinal,login:login},function(res){
          });
      }
    }

    function rankingdeventa() {

     idEmpleado=$('#idEmpleado').val();
     fechaInicial=$('#fechaInicial').val();
     fechaFinal=$('#fechaFinal').val();

     $.post('CONTROLADORES/venGralController.php',{proceso:'rankingdeventa',fechaFinal:fechaFinal,fechaInicial:fechaInicial,idEmpleado:idEmpleado},function(res){
      var json = JSON.parse(res);
      if (verificarError(json.error)) {
        return;
      }
    var  TOTAL=0;
 tablaExcelMesaAnulado="<table class='pv tg tc fc fa gastos vd' id='tablaExcelMesaAnulado'><tbody>";
   tablaExcelMesaAnulado+="<tr><tr><td>PRODUCTO<td>CANTIDAD<td>SUBTOTAL";

  for (var i = 0; i < json.result.length; i++) {
    subtotal=(parseFloat(json.result[i].AGRUPA1)).toFixed(2);
   
    tablaExcelMesaAnulado+="<tr>"+
    "<td>"+json.result[i].NOM_PROD+
    "<td>"+json.result[i].CANTIDAD+
    "<td>"+subtotal
     TOTAL=parseFloat(TOTAL)+parseFloat(subtotal);
  }
  $('#ModalaRankingVenta').modal('hide');
  tablaExcelMesaAnulado+="<tr><td><td><td>"+TOTAL;
  $("body").append(tablaExcelMesaAnulado);
  $('#tablaExcelMesaAnulado').tableExport({type:'excel',headings:true,fileName: "Reporte_Empleado"+fechaActualReporte()});
  $('#tablaExcelMesaAnulado').remove();
    }).fail(function(xhr, ajaxOptions, thrownError){
      failErrors(xhr, ajaxOptions, thrownError);  
    });
  }