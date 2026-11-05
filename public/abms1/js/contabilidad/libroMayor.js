
function reporte_libro_mayor(verificar) {
    var total_debe = 0.00;
    var total_haber = 0.00;
    var total_total_debe = 0.00;
    var total_total_haber = 0.00;
    var total_debeBs = 0.00;
    var total_haberBs = 0.00;
    var total_total_debeBs = 0.00;
    var total_total_haberBs = 0.00;
        var fecha1 = $('#fecha_in').val()+' 00:00:00';
        var fecha2 = $('#fecha_fi').val()+' 23:59:59';

    var total_objetos = 0;
    var total_filas_objeto = 0;
    $('#tabla').empty();
    var tabla = $('#tabla');
    $('#loading').css("display", "block");
    $.post('../CONTROLADORES/reporteContableController.php',{proceso:'reporte_libro_mayor',fecha1:fecha1,fecha2:fecha2},function(res){
        var json = JSON.parse(res);
            if (verificarError(json.error)) {
                return;
            }

        if(json.result.length > 0) {
            total_objetos = json.result.length;

            for (i = 0; i < total_objetos; i++) {
                total_filas_objeto =json.result[i].length;

                for (j = 0; j < total_filas_objeto; j++) {

                    if (j == 0 && total_filas_objeto == 1) {
                        tabla.append("<tr style='background-color: #3C8DBC; color: white;'>\n\
                        <td></td>\n\
                        <td style = 'text-align: left;'><b>" + json.result[i][j].codigo + "<b></td>\n\
                        <td style = 'text-align: center;'><b>" + json.result[i][j].nombre + "<b></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <tr>");

                        if(json.result[i][j].tipo_detalle == 1){
                            tabla.append("<tr'>\n\
                            <td style = 'text-align: center;'>" + json.result[i][j].nro_asiento + "</td>\n\
                            <td>" + json.result[i][j].fecha_transaccion + "</td>\n\
                            <td>" + json.result[i][j].glosa + "</td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoSus + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoBs + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <tr>");
                            total_debe = total_debe + parseFloat(json.result[i][j].montoSus);
                            total_total_debe = total_total_debe + parseFloat(json.result[i][j].montoSus);
                            total_debeBs = total_debeBs + parseFloat(json.result[i][j].montoBs);
                            total_total_debeBs = total_total_debeBs + parseFloat(json.result[i][j].montoBs);
                        }
                        else if(json.result[i][j].tipo_detalle == 2){
                            tabla.append("<tr'>\n\
                            <td style = 'text-align: center;'>" + json.result[i][j].nro_asiento + "</td>\n\
                            <td>" + json.result[i][j].fecha_transaccion + "</td>\n\
                            <td>" + json.result[i][j].glosa + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoSus + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoBs + "</td>\n\
                            <tr>");
                            total_haber = total_haber + parseFloat(json.result[i][j].montoSus);
                            total_total_haber = total_total_haber + parseFloat(json.result[i][j].montoSus);
                            total_haberBs = total_haberBs + parseFloat(json.result[i][j].montoBs);
                            total_total_haberBs = total_total_haberBs + parseFloat(json.result[i][j].montoBs);
                        }

                        tabla.append("<tr style='background-color: #5CD9EF'>\n\
                        <td colspan='3'><b>TOTAL CUENTA</b></td>\n\
                        <td style = 'text-align: right;'><b>" + total_debe.toFixed(2) + "</b></td>\n\
                        <td style = 'text-align: right;'><b>" + total_haber.toFixed(2) + "</b></td>\n\
                        <td style = 'text-align: right;'><b>" + total_debeBs.toFixed(2) + "</b></td>\n\
                        <td style = 'text-align: right;'><b>" + total_haberBs.toFixed(2) + "</b></td>\n\
                        <tr>");
                    }
                    else if (j == 0 && total_filas_objeto > 1) {
                        tabla.append("<tr style='background-color: #3C8DBC; color: white;'>\n\
                        <td></td>\n\
                        <td style = 'text-align: left;'><b>" + json.result[i][j].codigo + "<b></td>\n\
                        <td style = 'text-align: center;'><b>" + json.result[i][j].nombre + "<b></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <td></td>\n\
                        <tr>");

                        if(json.result[i][j].tipo_detalle == 1){
                            tabla.append("<tr'>\n\
                            <td style = 'text-align: center;'>" + json.result[i][j].nro_asiento + "</td>\n\
                            <td>" + json.result[i][j].fecha_transaccion + "</td>\n\
                            <td>" + json.result[i][j].glosa + "</td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoSus + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoBs + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <tr>");
                            total_debe = total_debe + parseFloat(json.result[i][j].montoSus);
                            total_total_debe = total_total_debe + parseFloat(json.result[i][j].montoSus);
                            total_debeBs = total_debeBs + parseFloat(json.result[i][j].montoBs);
                            total_total_debeBs = total_total_debeBs + parseFloat(json.result[i][j].montoBs);
                        }
                        else if(json.result[i][j].tipo_detalle == 2){
                            tabla.append("<tr'>\n\
                            <td style = 'text-align: center;'>" + json.result[i][j].nro_asiento + "</td>\n\
                            <td>" + json.result[i][j].fecha_transaccion + "</td>\n\
                            <td>" + json.result[i][j].glosa + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoSus + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoBs + "</td>\n\
                            <tr>");
                            total_haber = total_haber + parseFloat(json.result[i][j].montoSus);
                            total_total_haber = total_total_haber + parseFloat(json.result[i][j].montoSus);
                            total_haberBs = total_haberBs + parseFloat(json.result[i][j].montoBs);
                            total_total_haberBs = total_total_haberBs + parseFloat(json.result[i][j].montoBs);
                        }
                    }
                    else if(j > 0 && j < total_filas_objeto - 1) {
                        if(json.result[i][j].tipo_detalle == 1){
                            tabla.append("<tr'>\n\
                            <td style = 'text-align: center;'>" + json.result[i][j].nro_asiento + "</td>\n\
                            <td>" + json.result[i][j].fecha_transaccion + "</td>\n\
                            <td>" + json.result[i][j].glosa + "</td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoSus + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoBs + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <tr>");
                            total_debe = total_debe + parseFloat(json.result[i][j].montoSus);
                            total_total_debe = total_total_debe + parseFloat(json.result[i][j].montoSus);
                            total_debeBs = total_debeBs + parseFloat(json.result[i][j].montoBs);
                            total_total_debeBs = total_total_debeBs + parseFloat(json.result[i][j].montoBs);
                        }
                        else if(json.result[i][j].tipo_detalle == 2){
                            tabla.append("<tr'>\n\
                            <td style = 'text-align: center;'>" + json.result[i][j].nro_asiento + "</td>\n\
                            <td>" + json.result[i][j].fecha_transaccion + "</td>\n\
                            <td>" + json.result[i][j].glosa + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoSus + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoBs + "</td>\n\
                            <tr>");
                            total_haber = total_haber + parseFloat(json.result[i][j].montoSus);
                            total_total_haber = total_total_haber + parseFloat(json.result[i][j].montoSus);
                            total_haberBs = total_haberBs + parseFloat(json.result[i][j].montoBs);
                            total_total_haberBs = total_total_haberBs + parseFloat(json.result[i][j].montoBs);
                        }
                    }
                    else if(j == total_filas_objeto - 1) {
                        if(json.result[i][j].tipo_detalle == 1){
                            tabla.append("<tr'>\n\
                            <td style = 'text-align: center;'>" + json.result[i][j].nro_asiento + "</td>\n\
                            <td>" + json.result[i][j].fecha_transaccion + "</td>\n\
                            <td>" + json.result[i][j].glosa + "</td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoSus + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoBs + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <tr>");
                            total_debe = total_debe + parseFloat(json.result[i][j].montoSus);
                            total_total_debe = total_total_debe + parseFloat(json.result[i][j].montoSus);
                            total_debeBs = total_debeBs + parseFloat(json.result[i][j].montoBs);
                            total_total_debeBs = total_total_debeBs + parseFloat(json.result[i][j].montoBs);
                        }
                        else if(json.result[i][j].tipo_detalle == 2){
                            tabla.append("<tr'>\n\
                            <td style = 'text-align: center;'>" + json.result[i][j].nro_asiento + "</td>\n\
                            <td>" + json.result[i][j].fecha_transaccion + "</td>\n\
                            <td>" + json.result[i][j].glosa + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoSus + "</td>\n\
                            <td style = 'text-align: right;'></td>\n\
                            <td style = 'text-align: right;'>" + json.result[i][j].montoBs + "</td>\n\
                            <tr>");
                            total_haber = total_haber + parseFloat(json.result[i][j].montoSus);
                            total_total_haber = total_total_haber + parseFloat(json.result[i][j].montoSus);
                            total_haberBs = total_haberBs + parseFloat(json.result[i][j].montoBs);
                            total_total_haberBs = total_total_haberBs + parseFloat(json.result[i][j].montoBs);
                        }

                        tabla.append("<tr style='background-color: #5CD9EF'>\n\
                        <td colspan='3'><b>TOTAL CUENTA</b></td>\n\
                        <td style = 'text-align: right;'><b>" + total_debe.toFixed(2) + "</b></td>\n\
                        <td style = 'text-align: right;'><b>" + total_haber.toFixed(2) + "</b></td>\n\
                        <td style = 'text-align: right;'><b>" + total_debeBs.toFixed(2) + "</b></td>\n\
                        <td style = 'text-align: right;'><b>" + total_haberBs.toFixed(2) + "</b></td>\n\
                        <tr>");
                    }
                }
                total_debe = 0;
                total_haber = 0;
                total_debeBs = 0;
                total_haberBs = 0;
            }

            tabla.append("<tr style='background-color: #FC74A6'>\n\
            <td colspan='3'><b>TOTAL LIBRO MAYOR</b></td>\n\
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
