
function estado_resultado(verificar) {

    var debe = 0.00;
    var haber = 0.00;
    var debeBs = 0.00;
    var haberBs = 0.00;

    var suma = 0.00;
    var sumaBs = 0.00;

    var total_ingresos = 0.00;
    var total_gastos = 0.00;
    var total_ingresosBs = 0.00;
    var total_gastosBs = 0.00;

    var total = 0.00;
    var totalBs = 0.00;

        var fecha1 = $('#fecha_in').val()+' 00:00:00';
        var fecha2 = $('#fecha_fi').val()+' 23:59:59';

    var total_objetos_ingresos = 0;
    var total_objetos_gastos = 0;
    var total_filas_objeto_ingresos = 0;
    var total_filas_objeto_gastos = 0;
    $('#tabla').empty();
    var tabla = $('#tabla');
    $('#loading').css("display", "block");
    $.post('../CONTROLADORES/reporteContableController.php',{proceso:'estado_resultado',fecha1:fecha1,
fecha2:fecha2},function(res){
    var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        if(Object.keys(response).length > 0) {
            total_objetos_gastos = Object.keys(response['Gastos']).length;

            for (i = 0; i < total_objetos_gastos; i++) {
                total_filas_objeto_gastos = Object.keys(response['Gastos'][i]).length;

                for (j = 0; j < total_filas_objeto_gastos; j++) {

                    if (response['Gastos'][i][j].tipo_detalle == 1) {
                        debe = debe + parseFloat(response['Gastos'][i][j].montoSus);
                        debeBs = debeBs + parseFloat(response['Gastos'][i][j].montoBs);
                    }
                    else if (response['Gastos'][i][j].tipo_detalle == 2) {
                        haber = haber + parseFloat(response['Gastos'][i][j].montoSus);
                        haberBs = haberBs + parseFloat(response['Gastos'][i][j].montoBs);
                    }
                }

                suma = debe - haber;
                sumaBs = debeBs - haberBs;
                total_gastos = total_gastos + suma;
                total_gastosBs = total_gastosBs + sumaBs;

                tabla.append("<tr>\n\
                <td><b>" + response['Gastos'][i][0].codigo + "</b></td>\n\
                <td><b>" + response['Gastos'][i][0].nombre + "<b></td>\n\
                <td style = 'text-align: right;'>" + suma.toFixed(2) + "</td>\n\
                <td style = 'text-align: right;'></td>\n\
                <td style = 'text-align: right;'>" + sumaBs.toFixed(2) + "</td>\n\
                <td style = 'text-align: right;'></td>\n\
                <tr>");
                
                debe = 0.00;
                haber = 0.00;
                debeBs = 0.00;
                haberBs = 0.00;
                suma = 0.00;
                sumasBs = 0.00;
            }
            
            tabla.append("<tr style='background-color: #3C8DBC; color: white;'>\n\
            <td colspan='2'><b>TOTAL GASTOS</b></td>\n\
            <td style = 'text-align: right;'><b>" + total_gastos.toFixed(2) + "</b></td>\n\
            <td style = 'text-align: right;'><b></b></td>\n\
            <td style = 'text-align: right;'><b>" + total_gastosBs.toFixed(2) + "</b></td>\n\
            <td style = 'text-align: right;'><b></b></td>\n\
            <tr>");

            total_objetos_ingresos = Object.keys(response['Ingresos']).length;

            for (i = 0; i < total_objetos_ingresos; i++) {
                total_filas_objeto_ingresos = Object.keys(response['Ingresos'][i]).length;

                for (j = 0; j < total_filas_objeto_ingresos; j++) {

                    if (response['Ingresos'][i][j].tipo_detalle == 1) {
                        debe = debe + parseFloat(response['Ingresos'][i][j].montoSus);
                        debeBs = debeBs + parseFloat(response['Ingresos'][i][j].montoBs);
                    }
                    else if (response['Ingresos'][i][j].tipo_detalle == 2) {
                        haber = haber + parseFloat(response['Ingresos'][i][j].montoSus);
                        haberBs = haberBs + parseFloat(response['Ingresos'][i][j].montoBs);
                    }
                }

                suma = haber - debe;
                sumaBs = haberBs - debeBs;
                total_ingresos = total_ingresos + suma;
                total_ingresosBs = total_ingresosBs + sumaBs;

                tabla.append("<tr>\n\
                <td><b>" + response['Ingresos'][i][0].codigo + "</b></td>\n\
                <td><b>" + response['Ingresos'][i][0].nombre + "<b></td>\n\
                <td style = 'text-align: right;'></td>\n\
                <td style = 'text-align: right;'>" + suma.toFixed(2) + "</td>\n\
                <td style = 'text-align: right;'></td>\n\
                <td style = 'text-align: right;'>" + sumaBs.toFixed(2) + "</td>\n\
                <tr>");
                
                debe = 0.00;
                haber = 0.00;
                debeBs = 0.00;
                haberBs = 0.00;
                suma = 0.00;
                sumasBs = 0.00;
            }

            tabla.append("<tr style='background-color: #3C8DBC; color: white;'>\n\
            <td colspan='2'><b>TOTAL INGRESOS</b></td>\n\
            <td style = 'text-align: right;'><b></b></td>\n\
            <td style = 'text-align: right;'><b>" + total_ingresos.toFixed(2) + "</b></td>\n\
            <td style = 'text-align: right;'><b></b></td>\n\
            <td style = 'text-align: right;'><b>" + total_ingresosBs.toFixed(2) + "</b></td>\n\
            <tr>");
        }

        tabla.append("<tr style='background-color: #FC74A6'>\n\
        <td colspan='2'><b>TOTAL</b></td>\n\
        <td style = 'text-align: right;'><b>" + total_gastos.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right;'><b>" + total_ingresos.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right;'><b>" + total_gastosBs.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right;'><b>" + total_ingresosBs.toFixed(2) + "</b></td>\n\
        <tr>");

        total = total_ingresos - total_gastos;
        totalBs = total_ingresosBs - total_gastosBs;
        
        tabla.append("<tr style='background-color: #8CDF33'>\n\
        <td colspan='2'><b>TOTAL UTILIDAD DEL PERIODO</b></td>\n\
        <td style = 'text-align: right;' colspan='2'><b>" + total.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right;' colspan='2'><b>" + totalBs.toFixed(2) + "</b></td>\n\
        <tr>");

        $('#loading').css("display", "none");
  }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}