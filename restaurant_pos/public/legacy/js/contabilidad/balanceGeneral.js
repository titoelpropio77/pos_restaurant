

function balance_general(verificar) {

    var debe = 0.00;
    var debeBs = 0.00;
    var haber = 0.00;
    var haberBs = 0.00;

    var suma = 0.00;
    var sumaBs = 0.00;

    var total_activo = 0.00;
    var total_activoBs = 0.00;
    var total_pasivo = 0.00;
    var total_pasivoBs = 0.00;
    var total_patrimonio = 0.00;
    var total_patrimonioBs = 0.00;

    var total = 0.00;
    var totalBs = 0.00;

    if (verificar == 1) {
        var fecha1 = $('#fecha_in').val()+' 00:00:00';
        var fecha2 = $('#fecha_fi').val()+' 23:59:59';
    }
    else {
        var fecha1 = $('#fecha_inicial').val()+' 00:00:00';
        var fecha2 = $('#fecha_fin').val()+' 23:59:59';
    }

    var total_objetos_activo = 0;
    var total_objetos_pasivo = 0;
    var total_objetos_patrimonio = 0;
    var total_filas_objeto_activo = 0;
    var total_filas_objeto_pasivo = 0;
    var total_filas_objeto_patrimonio = 0;
    $('#tabla').empty();
    var tabla = $('#tabla');
    $('#loading').css("display", "block");
   $.post('../CONTROLADORES/reporteContableController.php',{proceso:'balanceGeneral',fecha1:fecha1,
fecha2:fecha2},function(res){
    var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        if(Object.keys(response).length > 0) {
            total_objetos_activo = Object.keys(response['Activo']).length;

            for (i = 0; i < total_objetos_activo; i++) {
                total_filas_objeto_activo = Object.keys(response['Activo'][i]).length;

                for (j = 0; j < total_filas_objeto_activo; j++) {

                    if (response['Activo'][i][j].tipo_detalle == 1) {
                        debe = debe + parseFloat(response['Activo'][i][j].montoSus);
                        debeBs = debeBs + parseFloat(response['Activo'][i][j].montoBs);
                    }
                    else if (response['Activo'][i][j].tipo_detalle == 2) {
                        haber = haber + parseFloat(response['Activo'][i][j].montoSus);
                        haberBs = haberBs + parseFloat(response['Activo'][i][j].montoBs);
                    }
                }

                suma = debe - haber;
                sumaBs = debeBs - haberBs;
                total_activo = total_activo + suma;
                total_activoBs = total_activoBs + sumaBs;

                tabla.append("<tr>\n\
                <td><b>" + response['Activo'][i][0].codigo + "</b></td>\n\
                <td><b>" + response['Activo'][i][0].nombre + "<b></td>\n\
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
            <td colspan='2'><b>TOTAL ACTIVO</b></td>\n\
            <td style = 'text-align: right;'><b>" + total_activo.toFixed(2) + "</b></td>\n\
            <td style = 'text-align: right;'><b></b></td>\n\
            <td style = 'text-align: right;'><b>" + total_activoBs.toFixed(2) + "</b></td>\n\
            <td style = 'text-align: right;'><b></b></td>\n\
            <tr>");

            total_objetos_pasivo = Object.keys(response['Pasivo']).length;

            for (i = 0; i < total_objetos_pasivo; i++) {
                total_filas_objeto_pasivo = Object.keys(response['Pasivo'][i]).length;

                for (j = 0; j < total_filas_objeto_pasivo; j++) {

                    if (response['Pasivo'][i][j].tipo_detalle == 1) {
                        debe = debe + parseFloat(response['Pasivo'][i][j].montoSus);
                        debeBs = debeBs + parseFloat(response['Pasivo'][i][j].montoBs);
                    }
                    else if (response['Pasivo'][i][j].tipo_detalle == 2) {
                        haber = haber + parseFloat(response['Pasivo'][i][j].montoSus);
                        haberBs = haberBs + parseFloat(response['Pasivo'][i][j].montoBs);
                    }
                }

                suma = haber - debe;
                sumaBs = haberBs - debeBs;
                total_pasivo = total_pasivo + suma;
                total_pasivoBs = total_pasivoBs + sumaBs;

                tabla.append("<tr>\n\
                <td><b>" + response['Pasivo'][i][0].codigo + "</b></td>\n\
                <td><b>" + response['Pasivo'][i][0].nombre + "<b></td>\n\
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
            <td colspan='2'><b>TOTAL PASIVO</b></td>\n\
            <td style = 'text-align: right;'><b></b></td>\n\
            <td style = 'text-align: right;'><b>" + total_pasivo.toFixed(2) + "</b></td>\n\
            <td style = 'text-align: right;'><b></b></td>\n\
            <td style = 'text-align: right;'><b>" + total_pasivoBs.toFixed(2) + "</b></td>\n\
            <tr>");

            total_objetos_patrimonio = Object.keys(response['Patrimonio']).length;

            for (i = 0; i < total_objetos_patrimonio; i++) {
                total_filas_objeto_patrimonio = Object.keys(response['Patrimonio'][i]).length;

                for (j = 0; j < total_filas_objeto_patrimonio; j++) {

                    if (response['Patrimonio'][i][j].tipo_detalle == 1) {
                        debe = debe + parseFloat(response['Patrimonio'][i][j].montoSus);
                        debeBs = debeBs + parseFloat(response['Patrimonio'][i][j].montoBs);
                    }
                    else if (response['Patrimonio'][i][j].tipo_detalle == 2) {
                        haber = haber + parseFloat(response['Patrimonio'][i][j].montoSus);
                        haberBs = haberBs + parseFloat(response['Patrimonio'][i][j].montoBs);
                    }
                }

                suma = haber - debe;
                sumaBs = haberBs - debeBs;
                total_patrimonio = total_patrimonio + suma;
                total_patrimonioBs = total_patrimonioBs + sumaBs;

                tabla.append("<tr>\n\
                <td><b>" + response['Patrimonio'][i][0].codigo + "</b></td>\n\
                <td><b>" + response['Patrimonio'][i][0].nombre + "<b></td>\n\
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
            <td colspan='2'><b>TOTAL PATRIMONIO</b></td>\n\
            <td style = 'text-align: right;'><b></b></td>\n\
            <td style = 'text-align: right;'><b>" + total_patrimonio.toFixed(2) + "</b></td>\n\
            <td style = 'text-align: right;'><b></b></td>\n\
            <td style = 'text-align: right;'><b>" + total_patrimonioBs.toFixed(2) + "</b></td>\n\
            <tr>");
        }

        total = total_pasivo + total_patrimonio;
        totalBs = total_pasivoBs + total_patrimonioBs;

        tabla.append("<tr style='background-color: #FC74A6'>\n\
        <td colspan='2'><b>TOTAL</b></td>\n\
        <td style = 'text-align: right;'><b>" + total_activo.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right;'><b>" + total.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right;'><b>" + total_activoBs.toFixed(2) + "</b></td>\n\
        <td style = 'text-align: right;'><b>" + totalBs.toFixed(2) + "</b></td>\n\
        <tr>");

        $('#loading').css("display", "none");
     }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}