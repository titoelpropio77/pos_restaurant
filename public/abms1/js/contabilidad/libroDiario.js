
function reporte_libro_diario(verificar) {

    var total_debe = 0.00;
    var total_haber = 0.00;
    var total_total_debe = 0.00;
    var total_total_haber = 0.00;

    var total_debeBs = 0.00;
    var total_haberBs = 0.00;
    var total_total_debeBs = 0.00;
    var total_total_haberBs = 0.00;

    if (verificar == 1) {
        var fecha1 = $('#fecha_in').val()+' 00:00:00';
        var fecha2 = $('#fecha_fi').val()+' 23:59:59';
    }
    else {
        var fecha1 = $('#fecha_inicial').val()+' 00:00:00';
        var fecha2 = $('#fecha_fin').val()+' 23:59:59';
    }

    $('#tabla').empty();
    
    var tabla = $('#tabla');
    var total_filas = 0;
    var tipo_asiento = "";
    $('#loading').css("display", "block");
    $.post('../CONTROLADORES/reporteContableController.php',{proceso:'reporte_libro_diario',fecha1:fecha1,
fecha2:fecha2},function(response){
        var json = JSON.parse(response);
            if (verificarError(json.error)) {
                return;
            }

        if(json.result.length > 0) {
            total_filas = json.result.length;

            for (var i = 0; i < total_filas; i++) {
                    
                if (i < total_filas - 1){

                    if (json.result[i].nro_detalle == 1) {
                        tabla.append("<tr>\n\
                        <td><b>" + json.result[i].fecha_transaccion + "</b></td>\n\
                        <td style = 'text-align: center; vertical-align: middle;'><b>Asiento " + json.result[i].nro_asiento + "</b></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <tr>");

                        if(json.result[i].tipo_detalle == 1){
                            tabla.append("<tr >\n\
                            <td></td>\n\
                            <td>" + json.result[i].nombre + "</td>\n\
                            <td>" + json.result[i].codigo + "</td>\n\
                            <td style = 'text-align: right;'>" + json.result[i].montoSus + "</td>\n\
                            <td></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i].montoBs + "</td>\n\
                            <td></td>\n\
                            <tr>");
                            total_debe = total_debe + parseFloat(json.result[i].montoSus);
                            total_total_debe = total_total_debe + parseFloat(json.result[i].montoSus);
                            total_debeBs = total_debeBs + parseFloat(json.result[i].montoBs);
                            total_total_debeBs = total_total_debeBs + parseFloat(json.result[i].montoBs);
                        }
                        else if(json.result[i].tipo_detalle == 2){
                            tabla.append("<tr >\n\
                            <td></td>\n\
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" + json.result[i].nombre + "</td>\n\
                            <td>" + json.result[i].codigo + "</td>\n\
                            <td></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i].montoSus + "</td>\n\
                            <td></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i].montoBs + "</td>\n\
                            <tr>");
                            total_haber = total_haber + parseFloat(json.result[i].montoSus);
                            total_total_haber = total_total_haber + parseFloat(json.result[i].montoSus);
                            total_haberBs = total_haberBs + parseFloat(json.result[i].montoBs);
                            total_total_haberBs = total_total_haberBs + parseFloat(json.result[i].montoBs);
                        }

                        
                    } 
                    else if (json.result[i].nro_asiento != json.result[i + 1].nro_asiento) {

                        if(json.result[i].tipo_detalle == 1){
                            tabla.append("<tr >\n\
                            <td></td>\n\
                            <td>" + json.result[i].nombre + "</td>\n\
                            <td>" + json.result[i].codigo + "</td>\n\
                            <td style = 'text-align: right;'>" + json.result[i].montoSus + "</td>\n\
                            <td></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i].montoBs + "</td>\n\
                            <td></td>\n\
                            <tr>");
                            total_debe = total_debe + parseFloat(json.result[i].montoSus);
                            total_total_debe = total_total_debe + parseFloat(json.result[i].montoSus);
                            total_debeBs = total_debeBs + parseFloat(json.result[i].montoBs);
                            total_total_debeBs = total_total_debeBs + parseFloat(json.result[i].montoBs);
                        }
                        else if(json.result[i].tipo_detalle == 2){
                            tabla.append("<tr >\n\
                            <td></td>\n\
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" + json.result[i].nombre + "</td>\n\
                            <td>" + json.result[i].codigo + "</td>\n\
                            <td></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i].montoSus + "</td>\n\
                            <td></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i].montoBs + "</td>\n\
                            <tr>");
                            total_haber = total_haber + parseFloat(json.result[i].montoSus);
                            total_total_haber = total_total_haber + parseFloat(json.result[i].montoSus);
                            total_haberBs = total_haberBs + parseFloat(json.result[i].montoBs);
                            total_total_haberBs = total_total_haberBs + parseFloat(json.result[i].montoBs);
                        }

                        if (json.result[i].tipo_asiento == 1) {
                            tipo_asiento = "Ingreso";
                        }
                        else if (json.result[i].tipo_asiento == 2) {
                            tipo_asiento = "Egreso";
                        }
                        else if (json.result[i].tipo_asiento == 3) {
                            tipo_asiento = "Traspaso";
                        }

                        tabla.append("<tr>\n\
                        <td><b>Tipo:</b>&nbsp;" + tipo_asiento + "</td>\n\
                        <td><b>Glosa:</b>&nbsp;" + json.result[i].glosa + "</td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <tr>");

                        tabla.append("<tr style='background-color: #5CD9EF'>\n\
                        <td colspan='3'><b>TOTAL ASIENTO</b></td>\n\
                        <td style = 'text-align: right;'><b>" + total_debe.toFixed(2) + "</b></td>\n\
                        <td style = 'text-align: right;'><b>" + total_haber.toFixed(2) + "</b></td>\n\
                        <td style = 'text-align: right;'><b>" + total_debeBs.toFixed(2) + "</b></td>\n\
                        <td style = 'text-align: right;'><b>" + total_haberBs.toFixed(2) + "</b></td>\n\
                        <tr>");
                        total_debe = 0;
                        total_haber = 0;
                        total_debeBs = 0;
                        total_haberBs = 0;

                    } 
                    else if (json.result[i].nro_asiento == json.result[i + 1].nro_asiento) {

                        if(json.result[i].tipo_detalle == 1){
                            tabla.append("<tr >\n\
                            <td></td>\n\
                            <td>" + json.result[i].nombre + "</td>\n\
                            <td>" + json.result[i].codigo + "</td>\n\
                            <td style = 'text-align: right;'>" + json.result[i].montoSus + "</td>\n\
                            <td></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i].montoBs + "</td>\n\
                            <td></td>\n\
                            <tr>");
                            total_debe = total_debe + parseFloat(json.result[i].montoSus);
                            total_total_debe = total_total_debe + parseFloat(json.result[i].montoSus);
                            total_debeBs = total_debeBs + parseFloat(json.result[i].montoBs);
                            total_total_debeBs = total_total_debeBs + parseFloat(json.result[i].montoBs);
                        }
                        else if(json.result[i].tipo_detalle == 2){
                            tabla.append("<tr >\n\
                            <td></td>\n\
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" + json.result[i].nombre + "</td>\n\
                            <td>" + json.result[i].codigo + "</td>\n\
                            <td></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i].montoSus + "</td>\n\
                            <td></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i].montoBs + "</td>\n\
                            <tr>");
                            total_haber = total_haber + parseFloat(json.result[i].montoSus);
                            total_total_haber = total_total_haber + parseFloat(json.result[i].montoSus);
                            total_haberBs = total_haberBs + parseFloat(json.result[i].montoBs);
                            total_total_haberBs = total_total_haberBs + parseFloat(json.result[i].montoBs);
                        }
                    }
                }
                else if (i == total_filas - 1) {

                    if(json.result[i].tipo_detalle == 1){
                        tabla.append("<tr >\n\
                        <td></td>\n\
                        <td>" + json.result[i].nombre + "</td>\n\
                        <td>" + json.result[i].codigo + "</td>\n\
                        <td style = 'text-align: right;'>" + json.result[i].montoSus + "</td>\n\
                        <td></td>\n\
                        <td style = 'text-align: right;'>" + json.result[i].montoBs + "</td>\n\
                        <td></td>\n\
                        <tr>");
                        total_debe = total_debe + parseFloat(json.result[i].montoSus);
                        total_total_debe = total_total_debe + parseFloat(json.result[i].montoSus);
                        total_debeBs = total_debeBs + parseFloat(json.result[i].montoBs);
                        total_total_debeBs = total_total_debeBs + parseFloat(json.result[i].montoBs);
                    }
                    else if(json.result[i].tipo_detalle == 2){
                        tabla.append("<tr >\n\
                        <td></td>\n\
                        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" + json.result[i].nombre + "</td>\n\
                        <td>" + json.result[i].codigo + "</td>\n\
                        <td></td>\n\
                        <td style = 'text-align: right;'>" + json.result[i].montoSus + "</td>\n\
                        <td></td>\n\
                        <td style = 'text-align: right;'>" + json.result[i].montoBs + "</td>\n\
                        <tr>");
                        total_haber = total_haber + parseFloat(json.result[i].montoSus);
                        total_total_haber = total_total_haber + parseFloat(json.result[i].montoSus);
                        total_haberBs = total_haberBs + parseFloat(json.result[i].montoBs);
                        total_total_haberBs = total_total_haberBs + parseFloat(json.result[i].montoBs);
                    }

                    if (json.result[i].tipo_asiento == 1) {
                        tipo_asiento = "Ingreso";
                    }
                    else if (json.result[i].tipo_asiento == 2) {
                        tipo_asiento = "Egreso";
                    }
                    else if (json.result[i].tipo_asiento == 3) {
                        tipo_asiento = "Traspaso";
                    }

                    tabla.append("<tr>\n\
                    <td><b>Tipo:</b>&nbsp;" + tipo_asiento + "</td>\n\
                    <td><b>Glosa:</b>&nbsp;" + json.result[i].glosa + "</td>\n\
                    <td></td>\n\
                    <td></td>\n\
                    <td></td>\n\
                    <td></td>\n\
                    <td></td>\n\
                    <tr>");

                    tabla.append("<tr style='background-color: #5CD9EF'>\n\
                    <td colspan='3'><b>TOTAL ASIENTO</b></td>\n\
                    <td style = 'text-align: right;'><b>" + total_debe.toFixed(2) + "</b></td>\n\
                    <td style = 'text-align: right;'><b>" + total_haber.toFixed(2) + "</b></td>\n\
                    <td style = 'text-align: right;'><b>" + total_debeBs.toFixed(2) + "</b></td>\n\
                    <td style = 'text-align: right;'><b>" + total_haberBs.toFixed(2) + "</b></td>\n\
                    <tr>");
                }
            }

            tabla.append("<tr style='background-color: #FC74A6'>\n\
            <td colspan='3'><b>TOTAL LIBRO DIARIO</b></td>\n\
            <td style = 'text-align: right; vertical-align: middle;'><b>" + total_total_debe.toFixed(2) + "</b></td>\n\
            <td style = 'text-align: right; vertical-align: middle;'><b>" + total_total_haber.toFixed(2) + "</b></td>\n\
            <td style = 'text-align: right; vertical-align: middle;'><b>" + total_total_debeBs.toFixed(2) + "</b></td>\n\
            <td style = 'text-align: right; vertical-align: middle;'><b>" + total_total_haberBs.toFixed(2) + "</b></td>\n\
            <tr>");
        }

        $('#loading').css("display", "none");
    }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
  

}