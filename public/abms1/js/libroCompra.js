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
 
 $('#loading').css('display', 'none');
});
function verificarError(error){
    if (error.length > 0) {
        if ("Error Session" === error) {
           $('#loading').css('display', 'none');

           alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
            alertify.message('OK');
            window.location="../desconectar.php";
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

}
tablaExcelLibroCompra="";
tablaTxt="";
function listarLibroCompra(){
    $('#tableLibroCompra').DataTable().destroy();
    $('#tbodyLibroCompra').empty();
    fechaInicial= $('#fechaInicial').val();
    fechaFinal= $('#fechaFinal').val();
    var fechaInicial = moment(fechaInicial, 'DD/MM/YYYY');
    fechaInicial = fechaInicial.format('YYYY-MM-DD');
    var fechaFinal = moment(fechaFinal, 'DD/MM/YYYY');
    fechaFinal = fechaFinal.format('YYYY-MM-DD');
    $.post('CONTROLADORES/gastosController.php',{proceso:'listarLibroCompra',fechaInicial:fechaInicial,
        fechaFinal:fechaFinal},function(res){
            var json = JSON.parse(res);
            if (verificarError(json.error)) {
                return;
            }
            var totalIva=0;
            var total=0;
            tablaTxt="";

            tablaExcelLibroCompra="";
            tablaExcelLibroCompra="<table class='pv tg tc fc fa gastos vd' id='tablaExcelLibroCompra'>"+
            "<thead style='background-color: #00f; color: #fff'><th>ESPECIFICACION <th>Nº <th>FECHA DE LA FACTURA O DUI <th>NIT PROVEEDOR"+
            "<th>NOMBRE Y APELLIDOS/RAZON SOCIAL <th>N° DE LA FACTURA <th>N° DE DUI <th>N° DE AUTORIZACION "+
            "<th>IMPORTE TOTAL DE LA COMPRA <th>IMPORTE NO SUJETO A CREDITO FISCAL <th>SUBTOTAL "+
            "<th>DESCUENTOS, BONIFICACIONES Y REBAJAS SUJETAS AL IVA <th>IMPORTE BASE PARA CREDITO FISCAL"+
            "<th>CREDITO FISCAL <th>CODIGO DE CONTROL <th>TIPO DE COMPRA <tbody>";

            for (var i = 0; i < json.result.length; i++) {
                var fechaFactura = moment(json.result[i].FECHA_FACTURA, 'YYYY-MM-DD');
                var fechaFactura = fechaFactura.format('DD/MM/YYYY');
                totalIva=totalIva+parseFloat(json.result[i].CREDITO_FISCAL);
                total+=parseFloat(json.result[i].MONTO);
                MONTO=(parseFloat(json.result[i].MONTO)).toFixed(2);
                tablaTxt+="1|"+(j=i+1)+"|"+fechaFactura+"|"+json.result[i].NIT_PROVEEDOR+"|"+
                json.result[i].ENTREGADOA+"|"+json.result[i].NROFACTURA+"|0|"+json.result[i].NRO_AUTORIZACION+"|"+
                MONTO+"|0|"+MONTO+"|0|"+MONTO+"|"+json.result[i].CREDITO_FISCAL+"|"+json.result[i].CODIGO_CONTROL +"|1\r\n";

                FACTURA=json.result[i].CON_FACTURA==1?"SI":"NO";
                tablaExcelLibroCompra+="<tr>"+
                "<td>1"+
                "<td>"+(j=i+1) +
                "<td>"+fechaFactura +
                "<td>"+json.result[i].NIT_PROVEEDOR +
                "<td>"+json.result[i].ENTREGADOA +
                "<td>"+json.result[i].NROFACTURA +
                "<td>0"+
                "<td>"+json.result[i].NRO_AUTORIZACION +
                "<td>"+MONTO +
                "<td>0"+
                "<td>"+MONTO +
                "<td>0"+
                "<td>"+MONTO +
                "<td>"+json.result[i].CREDITO_FISCAL +
                "<td>"+json.result[i].CODIGO_CONTROL +
                "<td>1<tr>"
                ;
                $('#tbodyLibroCompra').append("<tr>"+
                    "<td>"+json.result[i].FECHA_FACTURA+
                    "<td>"+json.result[i].CONCEPTO+
                    "<td>"+json.result[i].ENTREGADOA+
                    "<td>"+json.result[i].MONTO+
                    "<td>"+FACTURA+
                    "<td style='text-align:right'>"+json.result[i].CREDITO_FISCAL+
                    "<td><button class='btn btn-success btn-sm'> <i class='fa fa-pencil-square-o' aria-hidden='true'></i><button onclick='eliminarGasto("+json.result[i].ID+")' class='btn btn-danger btn-sm'> <i class='fa fa-trash-o' aria-hidden='true'></i>"
                    );
            }

            $('#total').text(total);
            // $('#totalIva').text((parseFloat(totalIva)).toFixed(2));
            $('#totalIva').text(totalIva.toFixed(2));
            paginatortableLibroCompra();
            $('#loading').css('display', 'none');
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }
    function paginatortableLibroCompra(){
        $('#tableLibroCompra').DataTable({
            "pagingType": "full_numbers",
            "scrollY": "330px",
            "scrollCollapse": true,
            "paging": false,
            retrieve: true

        });
    }

// function descargarArchivo() {
//     var reader = new FileReader();
//     reader.onload = function (event) {
//         var save = document.createElement('a');
//         save.href = event.target.result;
//         save.target = '_blank';
//         save.download = "adsfadf" || 'archivo.dat';
//         var clicEvent = new MouseEvent('click', {
//             'view': window,
//                 'bubbles': true,
//                 'cancelable': true
//         });
//         save.dispatchEvent(clicEvent);
//         (window.URL || window.webkitURL).revokeObjectURL(save.href);
//     };
//     reader.readAsDataURL("1|2|01/02/2018|234234234|adfadf|123|0|123123123|12|0|12|0|12|1.56||1");

// };
// contenidoDeArchivo= Array();
//     contenidoDeArchivo[0] ="123\r\nsfdgsfg";

// var elem = document.getElementById('descargar');

// elem.download = "archivo.txt";
// elem.href = "data:application/octet-stream," 
//                      + encodeURIComponent(tablaTxt);

function imprimirExcel(){
    encodeURIComponent(tablaTxt);
    $("body").append(tablaExcelLibroCompra);
    $('#tablaExcelLibroCompra').tableExport({type:'excel',headings:true,fileName: "Reporte_LIBRODECOMPRA"+fechaActualReporte()});
    $('#tablaExcelLibroCompra').remove();
}
function imprimirTxt(elem){
 
   // var elem = document.getElementById('descargar');

   elem.download = "Reporte_LIBRODECOMPRA"+fechaActualReporte()+".txt";
   elem.href = "data:application/octet-stream," 
   + encodeURIComponent(tablaTxt);

}

function eliminarGasto(id){
    alertify.confirm("ELIMINAR GASTO","¿DESEA ELIMINAR EL GASTO?",
      function(){
         $.post('CONTROLADORES/gastosController.php',{proceso:'eliminarGasto',idGasto:id},function(res){
            var json = JSON.parse(res);
            if (verificarError(json.error)) {
                return;
            }
            alertify.success('Gasto eliminado Corretamente');
            listarLibroCompra();
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });

    },
    function(){
        alertify.error('Cancel');
    });
    
}