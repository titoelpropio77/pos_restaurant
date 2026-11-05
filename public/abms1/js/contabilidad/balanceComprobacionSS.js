
function sumas_saldos(verificar) {

    var debe = 0.00;
    var haber = 0.00;
    var total_debe = 0.00;
    var total_haber = 0.00;

    var debeBs = 0.00;
    var haberBs = 0.00;
    var total_debeBs = 0.00;
    var total_haberBs = 0.00;

    var deudor = 0.00;
    var acreedor = 0.00;
    var total_deudor = 0.00;
    var total_acreedor = 0.00;

    var deudorBs = 0.00;
    var acreedorBs = 0.00;
    var total_deudorBs = 0.00;
    var total_acreedorBs = 0.00;

  
        var fecha1 = $('#fecha_in').val()+' 00:00:00';
        var fecha2 = $('#fecha_fi').val()+' 23:59:59';

    var total_objetos = 0;
    var total_filas_objeto = 0;
    $('#tabla').empty();
    var tabla = $('#tabla');
    $('#loading').css("display", "block");
$.post('../CONTROLADORES/reporteContableController.php',{proceso:'balanceComprobacionSS',fecha1:fecha1,
fecha2:fecha2},function(res){
    var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }

        if(json.result.length > 0) {
            total_objetos =json.result.length;

            for (i = 0; i < total_objetos; i++) {
                total_filas_objeto = json.result[i].length;

                for (j = 0; j < total_filas_objeto; j++) {

                    if (json.result[i][j].tipo_detalle == 1) {
                        debe = debe + parseFloat(json.result[i][j].montoSus);
                        debeBs = debeBs + parseFloat(json.result[i][j].montoBs);
                    }
                    else if (json.result[i][j].tipo_detalle == 2) {
                        haber = haber + parseFloat(json.result[i][j].montoSus);
                        haberBs = haberBs + parseFloat(json.result[i][j].montoBs);
                    }
                }

                total_debe = total_debe + debe;
                total_debeBs = total_debeBs + debeBs;
                total_haber = total_haber + haber;
                total_haberBs = total_haberBs + haberBs;

                if (debe > haber) {
                    deudor = debe - haber;
                    deudorBs = debeBs - haberBs;
                    total_deudor = total_deudor + deudor;
                    total_deudorBs = total_deudorBs + deudorBs;

                    if (haber == 0.00) {
                        tabla.append("<tr>\n\
                        <td><b>" + json.result[i][0].codigo + "</b></td>\n\
                        <td><b>" + json.result[i][0].nombre + "<b></td>\n\
                        <td style = 'text-align: right;'>" + debe.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'>" + deudor.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'>" + debeBs.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'>" + deudorBs.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <tr>");
                    }
                    else if (haber > 0.00) {
                        tabla.append("<tr>\n\
                        <td><b>" + json.result[i][0].codigo + "</b></td>\n\
                        <td><b>" + json.result[i][0].nombre + "<b></td>\n\
                        <td style = 'text-align: right;'>" + debe.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'>" + haber.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'>" + deudor.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'>" + debeBs.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'>" + haberBs.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'>" + deudorBs.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <tr>");
                    }
                }
                else if (debe < haber) {
                    acreedor = haber - debe;
                    acreedorBs = haberBs - debeBs;
                    total_acreedor = total_acreedor + acreedor;
                    total_acreedorBs = total_acreedorBs + acreedorBs;

                    if (debe == 0.00) {
                        tabla.append("<tr>\n\
                        <td><b>" + json.result[i][0].codigo + "</b></td>\n\
                        <td><b>" + json.result[i][0].nombre + "<b></td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'>" + haber.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'>" + acreedor.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'>" + haberBs.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'>" + acreedorBs.toFixed(2) + "</td>\n\
                        <tr>");
                    }
                    else if (debe > 0.00) {
                        tabla.append("<tr>\n\
                        <td><b>" + json.result[i][0].codigo + "</b></td>\n\
                        <td><b>" + json.result[i][0].nombre + "<b></td>\n\
                        <td style = 'text-align: right;'>" + debe.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'>" + haber.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'>" + acreedor.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'>" + debeBs.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'>" + haberBs.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'>" + acreedorBs.toFixed(2) + "</td>\n\
                        <tr>");
                    }
                }
                else if (debe == haber) {
                    if (debe == 0.00 && haber == 0.00) {
                        tabla.append("<tr>\n\
                        <td><b>" + json.result[i][0].codigo + "</b></td>\n\
                        <td><b>" + json.result[i][0].nombre + "<b></td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <tr>");
                    }
                    else if (debe > 0.00 && haber > 0.00) {
                        tabla.append("<tr>\n\
                        <td><b>" + json.result[i][0].codigo + "</b></td>\n\
                        <td><b>" + json.result[i][0].nombre + "<b></td>\n\
                        <td style = 'text-align: right;'>" + debe.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'>" + haber.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'>" + debeBs.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'>" + haberBs.toFixed(2) + "</td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <td style = 'text-align: right;'></td>\n\
                        <tr>");
                    }
                }
                debe = 0.00;
                haber = 0.00;
                debeBs = 0.00;
                haberBs = 0.00;
                deudor = 0.00;
                acreedor = 0.00;
                deudorBs = 0.00;
                acreedorBs = 0.00;
            }
        }

        tabla.append("<tr style='background-color: #FC74A6'>\n\
        <td colspan='2'><b>TOTAL</b></td>\n\
        <td style = 'text-align: right; vertical-align: middle;'><b>" + total_debe.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right; vertical-align: middle;'><b>" + total_haber.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right; vertical-align: middle;'><b>" + total_deudor.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right; vertical-align: middle;'><b>" + total_acreedor.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right; vertical-align: middle;'><b>" + total_debeBs.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right; vertical-align: middle;'><b>" + total_haberBs.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right; vertical-align: middle;'><b>" + total_deudorBs.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right; vertical-align: middle;'><b>" + total_acreedorBs.toFixed(2) + "</b></td>\n\
        <tr>");

        $('#loading').css("display", "none");
  }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
