$(document).ready(function(){
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
    
 
    cerrarCargando();
});

function listarReporteCajaDada2Fechas(){
  	var totalGastos=0
  	var totalCredito=0
  	var totalCaja=0
  	var totalVentas=0
  	var totalIngresos=0
  cargando();

	fechaInicial=$('#fechaInicial').val();
	fechaFinal=$('#fechaFinal').val();
    $('#tbodyReporteCaja').empty();
	var fechaInicial = moment(fechaInicial, 'DD/MM/YYYY');
	fechaInicial = fechaInicial.format('YYYY-MM-DD');

	var fechaFinal = moment(fechaFinal, 'DD/MM/YYYY');
	fechaFinal = fechaFinal.format('YYYY-MM-DD');
$.post('CONTROLADORES/cierreProdVendContoller.php',{proceso:'listarReporteCajaDada2Fechas',fechaInicial:fechaInicial,fechaFinal:fechaFinal,multiSuc:multiSuc},function(res){
  var json = $.parseJSON(res);
      tablaExcelVentas="<table class='pv tg tc fc fa gastos vd' id='tablaExcelVentas'><tbody>";
      tablaExcelVentas+=  
          "<tr style='font-weight: bold;'><td style='font-weight: bold;'>FECHA<td>T.GASTOS<td>T.Ingresos<td>T.Credito<td>Saldo Caja<td>Venta del Día<td>";

  for (var i = 0; i < json.result.length; i++) {
  	totalcajaT=(parseFloat(json.result[i].facturado)-parseFloat(json.result[i].gastos)-parseFloat(json.result[i].credito)+parseFloat(json.result[i].ingreso)).toFixed(3);

  	totalGastos+=parseFloat(json.result[i].gastos);
  		 totalCredito+=parseFloat(json.result[i].credito);
  	 totalCaja+=parseFloat(totalcajaT);
  	 totalVentas+=parseFloat(json.result[i].facturado);
  	 totalIngresos+=parseFloat(json.result[i].ingreso);
        tablaExcelVentas+='<tr><td style="    border: 1px solid;">'+json.result[i].fecha+'<td>'+(parseFloat(json.result[i].gastos)).toFixed(3)+'<td>'+(parseFloat(json.result[i].ingreso)).toFixed(3)+
        '<td>'+parseFloat(json.result[i].credito)+'<td>'+totalcajaT+'<td>'+(parseFloat(json.result[i].facturado)).toFixed(3);

    $('#tbodyReporteCaja').append("<tr>"+
    	"<td style='    border: 1px solid;width: 11%;'>"+json.result[i].fecha+
    	"<td style='text-align:right;    border: 1px solid;width: 15%;'>"+json.result[i].gastos+
    	"<td style='text-align:right;    border: 1px solid;width: 17%;'>"+json.result[i].ingreso+
    	"<td style='text-align:right;    border: 1px solid;width: 15%;'>"+parseFloat(json.result[i].credito)+
    	"<td style='text-align:right;    border: 1px solid;width: 18%;'>"+totalcajaT+
    	"<td style='text-align:right;    border: 1px solid;'>"+(parseFloat(json.result[i].facturado)).toFixed(3)
    	);
  	
  }
      tablaExcelVentas+="<tr><td><tr><td><td>T.GASTOS<td>T.INGRESOS<td>TOTAL CREDITO<td>TOTAL CAJA<td>TOTAL VENTAS"+
    
      "<tr><td><td>"+totalGastos+"<td>"+totalIngresos+"<td>"+totalCredito+"<td>"+totalCaja+"<td>"+totalVentas+"</tbody></table>";
  $('#inputTotalGastos').val(totalGastos);
  $('#inputTotalIngesos').val(totalIngresos);
  $('#inputTotalCredito').val(totalCredito);
  $('#inputTotalCaja').val(totalCaja);
  $('#inputTotalVentas').val(totalVentas);
    cerrarCargando();
});
}

function imprimirExcel(){
  $("body").append(tablaExcelVentas);
$('#tablaExcelVentas').tableExport({type:'excel',headings:true,fileName: "Reporte_ProductosVendidos"+fechaActualReporte()});
    $('#tablaExcelVentas').remove();

}

function facturasEmitidas(){
  fechaInicial=$('#fechaInicial').val();
  fechaFinal=$('#fechaFinal').val();
var fechaInicial = moment(fechaInicial, 'DD/MM/YYYY');
  fechaInicial = fechaInicial.format('YYYY-MM-DD');
TOTAL_FACT=0;
DEBIDO=0;
  var fechaFinal = moment(fechaFinal, 'DD/MM/YYYY');
  fechaFinal = fechaFinal.format('YYYY-MM-DD');
  $.post('CONTROLADORES/cierreProdVendContoller.php',{proceso:'facturasEmitidas',fechaInicial:fechaInicial,fechaFinal:fechaFinal},function(res){
  var json = $.parseJSON(res);
      tablaExcelVentas="<table class='pv tg tc fc fa gastos vd' id='tablaExcelVentas'><tbody>";
      tablaExcelVentas+=  
          "<tr style='font-weight: bold;'><td style='font-weight: bold;'>NROTRANSAC<td>FAC_NODEC<td>FAC_SIDEC<td>ORDEN<td>F_VENTA<td>NIT_CLI<td>NOM_CLI<td>TOTAL_FACT<td>DEBIDO<td>"+
          "NROAUTORI<td>CIFRADO<td>ESTADO<td>FORMAPAGO<td>LOGIN<td>ELIMINPOR<td>DHORAVEN";

  for (var i = 0; i < json.result.length; i++) {
    if (json.result[i].FORMAPAGO=="CONSUMO" || json.result[i].ESTADO=="INACTIVO") {
     TOTAL_FACT=0;
        DEBIDO= 0;
    }else{
       TOTAL_FACT=(parseFloat(json.result[i].TOTAL_FACT)).toFixed(3);
        DEBIDO= (parseFloat(json.result[i].DEBIDO)).toFixed(3);
    }

       
        tablaExcelVentas+='<tr><td style="border: 1px solid;">'+json.result[i].NROTRANSAC+'<td>'+json.result[i].FAC_NODEC+'<td>'+json.result[i].FAC_SIDEC+
        '<td>'+json.result[i].ORDEN+'<td>'+json.result[i].F_VENTA+'<td>'+json.result[i].NIT_CLI+'<td>'+json.result[i].NOM_CLI+
        '<td>'+TOTAL_FACT+'<td>'+DEBIDO+'<td>'+parseInt(json.result[i].NROAUTORI)+'<td>'+json.result[i].CIFRADO+'<td>'+json.result[i].ESTADO+'<td>'+json.result[i].FORMAPAGO+
       "<td>"+json.result[i].LOGIN+'<td>'+json.result[i].ELIMINPOR+'<td>'+json.result[i].DHORAVEN;

    
    
  }
     $("body").append(tablaExcelVentas);
$('#tablaExcelVentas').tableExport({type:'excel',headings:true,fileName: "Reporte_ProductosVendidos"+fechaActualReporte()});
    $('#tablaExcelVentas').remove();
});
}