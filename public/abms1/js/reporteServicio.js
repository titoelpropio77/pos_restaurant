$(document).ready(function(){
	var hoy = new Date();
    cerrarCargando();
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
    
 
});

function listarReporteServicioDada2Fechas(){
    var totalGastos=0
    var totalCredito=0
    var totalCaja=0
    var totalVentas=0
    var totalIngresos=0

    fechaInicial=$('#fechaInicial').val();
    fechaFinal=$('#fechaFinal').val();
    $('#tbodyReporteCaja').empty();
    var fechaInicial = moment(fechaInicial, 'DD/MM/YYYY');
    fechaInicial = fechaInicial.format('YYYY-MM-DD');

    var fechaFinal = moment(fechaFinal, 'DD/MM/YYYY');
    fechaFinal = fechaFinal.format('YYYY-MM-DD');
    $('#inputCtdMesa').val("");
            $('#inputBsMesa').val("");
      
            $('#inputCtdLlevar').val("");
            $('#inputBsLlevar').val("");
        
            $('#inputCtdMoto').val("");
            $('#inputBsMoto').val("");
        
            $('#inputCtdAuto').val("");
            $('#inputBsAuto').val("");
$.post('CONTROLADORES/cierreProdVendContoller.php',{proceso:'listarReporteServicioDada2Fechas',fechaInicial:fechaInicial,fechaFinal:fechaFinal},function(res){
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

      tablaExcelVentas="<table class='pv tg tc fc fa gastos vd' id='tablaExcelVentas'><tbody>";
      tablaExcelVentas+=  
          "<tr style='font-weight: bold;'><td style='font-weight: bold;'>FECHA INICAL<td>"+fechaInicial+" <td>FECHA FIN<td>"+fechaFinal+"<td>";
      tablaExcelVentas+=  
          "<tr style='font-weight: bold;'><td style='font-weight: bold;'>CANTIDAD<td>NOM <td>SUBTOTAL<td>MESA<td>LLEVAR<td>AUTO<td>MOTO";

  for (var i = 0; i < json.result.length; i++) {
    // totalcajaT=(parseFloat(json.result[i].facturado)-parseFloat(json.result[i].gastos)-parseFloat(json.result[i].credito)+parseFloat(json.result[i].ingreso)).toFixed(3);

    // totalGastos+=parseFloat(json.result[i].gastos);
    //      totalCredito+=parseFloat(json.result[i].credito);
    //  totalCaja+=parseFloat(totalcajaT);
    //  totalVentas+=parseFloat(json.result[i].facturado);
    //  totalIngresos+=parseFloat(json.result[i].ingreso);
    //     tablaExcelVentas+='<tr><td style="    border: 1px solid;">'+json.result[i].fecha+'<td>'+(parseFloat(json.result[i].gastos)).toFixed(3)+'<td>'+(parseFloat(json.result[i].ingreso)).toFixed(3)+
    //     '<td>'+parseFloat(json.result[i].credito)+'<td>'+totalcajaT+'<td>'+(parseFloat(json.result[i].facturado)).toFixed(3);
subTotal=json.result[i].AGRUPA3=="0"?json.result[i].SUBTOTAL:json.result[i].AGRUPA3;
    $('#tbodyReporteCaja').append("<tr>"+
        "<td style='     text-align: right;   border: 1px solid;'>"+json.result[i].CANTIDAD+
        "<td style='    border: 1px solid;'>"+json.result[i].NOM_PROD+
        "<td style=' text-align: right;    border: 1px solid;'>"+subTotal+
        "<td style=' text-align: right;   border: 1px solid;'>"+json.result[i].MESA+
        "<td style=' text-align: right;  border: 1px solid;'>"+json.result[i].LLEVAR+
        "<td style=' text-align: right; border: 1px solid;'>"+json.result[i].AUTO+
        "<td style=' text-align: right;  border: 1px solid;'>"+json.result[i].MOTO
        );

     tablaExcelVentas+=  
          "<tr style='font-weight: bold;'><td style='font-weight: bold;'>"+json.result[i].CANTIDAD+"<td>"+json.result[i].NOM_PROD+"<td>"+json.result[i].SUBTOTAL+
          "<td>"+json.result[i].MESA+"<td>"+json.result[i].LLEVAR+"<td>"+json.result[i].AUTO+"<td>"+json.result[i].MOTO;
    
  }

  for (var i = 0; i < json.result2.length; i++) {
     switch(json.result2[i].SALIDA){
        case 'MESA':
            $('#inputCtdMesa').val(json.result2[i].CANTIDAD);
            $('#inputBsMesa').val(json.result2[i].SUBTOTAL);

        break;
        case 'LLEV':
            $('#inputCtdLlevar').val(json.result2[i].CANTIDAD);
            $('#inputBsLlevar').val(json.result2[i].SUBTOTAL);
        break;
        case 'MOTO':
            $('#inputCtdMoto').val(json.result2[i].CANTIDAD);
            $('#inputBsMoto').val(json.result2[i].SUBTOTAL);
        break;
        case 'AUTO':
            $('#inputCtdAuto').val(json.result2[i].CANTIDAD);
            $('#inputBsAuto').val(json.result2[i].SUBTOTAL);
        break;
        
     }
  }
  tablaExcelVentas+=  
          "<tr><td><tr style='font-weight: bold;'><td>Ctd. MESA<td>LLEVAR<td>AUTO<td>MOTO";
          tablaExcelVentas+=  
          "<tr style='font-weight: bold;'><td>"+$('#inputCtdMesa').val()+"<td>"+$('#inputCtdLlevar').val()+"<td>"+$('#inputCtdAuto').val()+"<td>"+$('#inputCtdMoto').val();
          tablaExcelVentas+=  
          "<tr><td>$bs<tr style='font-weight: bold;'><td>"+$('#inputBsMesa').val()+"<td>"+$('#inputBsLlevar').val()+"<td>"+$('#inputBsAuto').val()+"<td>"+$('#inputBsMoto').val();
  //     tablaExcelVentas+="<tr><td><tr><td><td>T.GASTOS<td>T.INGRESOS<td>TOTAL CREDITO<td>TOTAL CAJA<td>TOTAL VENTAS"+
    
  //     "<tr><td><td>"+totalGastos+"<td>"+totalIngresos+"<td>"+totalCredito+"<td>"+totalCaja+"<td>"+totalVentas+"</tbody></table>";
  // $('#inputTotalGastos').val(totalGastos);
  // $('#inputTotalIngesos').val(totalIngresos);
  // $('#inputTotalCredito').val(totalCredito);
  // $('#inputTotalCaja').val(totalCaja);
  // $('#inputTotalVentas').val(totalVentas);
});

}

function imprimirExcel(){
  $("body").append(tablaExcelVentas);
$('#tablaExcelVentas').tableExport({type:'excel',headings:true,fileName: "Reporte_ProductosVendidos"+fechaActualReporte()});
    $('#tablaExcelVentas').remove();

}