$(document).ready(function(){
listarIngreso();
listarTipoingreso();listarCuentacontable();});$("#frmGuardarIngreso").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var nombre = $('#inputNombre').val();var fecha = $('#inputFecha').val();var fecha_registro = $('#inputFecha_registro').val();var tipocambio = $('#inputTipocambio').val();var idMoneda = $('#inputIdMoneda').val();var pagadoA = $('#inputPagadoA').val();var glosa = $('#inputGlosa').val();var idTipoIngreso = $('#inputIdTipoIngreso').val();var idPuntoVenta = $('#inputIdPuntoVenta').val();var idProveedor = $('#inputIdProveedor').val();var con_factura = $('#inputCon_factura').val();var estado = $('#inputEstado').val();var idCuenta = $('#inputIdCuenta').val();if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
                             }if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }if (fecha == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO FECHA NO DEBE ESTAR VACIO.</span><br>";
                             }if (validar("texto y entero", fecha)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO FECHA.</span><br>";
            }if (validar("texto y entero", fecha_registro)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO FECHA_REGISTRO.</span><br>";
            }if (validar("decimal", tipocambio)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO TIPOCAMBIO.</span><br>";
                         }if (validar("decimal", idMoneda)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDMONEDA.</span><br>";
                         }if (validar("texto y entero", pagadoA)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO PAGADOA.</span><br>";
            }if (glosa == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO GLOSA NO DEBE ESTAR VACIO.</span><br>";
                             }if (validar("texto y entero", glosa)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO GLOSA.</span><br>";
            }if (validar("decimal", idPuntoVenta)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDPUNTOVENTA.</span><br>";
                         }if (validar("decimal", idProveedor)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDPROVEEDOR.</span><br>";
                         }if (validar("decimal", con_factura)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO CON_FACTURA.</span><br>";
                         }if (validar("decimal", estado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ESTADO.</span><br>";
                         }if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    } var parametros = new FormData($(this)[0]);
parametros.append("proceso","guardar"); $.ajax({
        type: 'POST',
        url: '../../../CONTROLADORES/ingresoController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalIngresoGuardar').modal('hide');
                $('#frmGuardarIngreso')[0].reset();


            }

            listarIngreso();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});$("#frmEditarIngreso").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var nombre = $('#inputNombreA').val();var fecha = $('#inputFechaA').val();var fecha_registro = $('#inputFecha_registroA').val();var tipocambio = $('#inputTipocambioA').val();var idMoneda = $('#inputIdMonedaA').val();var pagadoA = $('#inputPagadoAA').val();var glosa = $('#inputGlosaA').val();var idTipoIngreso = $('#inputIdTipoIngresoA').val();var idPuntoVenta = $('#inputIdPuntoVentaA').val();var idProveedor = $('#inputIdProveedorA').val();var con_factura = $('#inputCon_facturaA').val();var estado = $('#inputEstadoA').val();var idCuenta = $('#inputIdCuentaA').val();if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
                             }if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }if (fecha == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO FECHA NO DEBE ESTAR VACIO.</span><br>";
                             }if (validar("texto y entero", fecha)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO FECHA.</span><br>";
            }if (validar("texto y entero", fecha_registro)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO FECHA_REGISTRO.</span><br>";
            }if (validar("decimal", tipocambio)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO TIPOCAMBIO.</span><br>";
                         }if (validar("decimal", idMoneda)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDMONEDA.</span><br>";
                         }if (validar("texto y entero", pagadoA)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO PAGADOA.</span><br>";
            }if (glosa == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO GLOSA NO DEBE ESTAR VACIO.</span><br>";
                             }if (validar("texto y entero", glosa)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO GLOSA.</span><br>";
            }if (validar("decimal", idPuntoVenta)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDPUNTOVENTA.</span><br>";
                         }if (validar("decimal", idProveedor)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDPROVEEDOR.</span><br>";
                         }if (validar("decimal", con_factura)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO CON_FACTURA.</span><br>";
                         }if (validar("decimal", estado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ESTADO.</span><br>";
                         }if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    } var parametros = new FormData($(this)[0]);
parametros.append("proceso","editar"); $.ajax({
        type: 'POST',
        url: '../../../CONTROLADORES/ingresoController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalIngresoEdit').modal('hide');
                $('#frmEditarIngreso')[0].reset();


            }

            listarIngreso();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});idIngreso='';
function cargarDatosIngreso(idIngreso1) {
    idIngreso=idIngreso1;
    var url = '../../../CONTROLADORES/ingresoController.php';var nombre = $('#inputNombreA');var fecha = $('#inputFechaA');var fecha_registro = $('#inputFecha_registroA');var tipocambio = $('#inputTipocambioA');var idMoneda = $('#inputIdMonedaA');var pagadoA = $('#inputPagadoAA');var glosa = $('#inputGlosaA');var idTipoIngreso = $('#inputIdTipoIngresoA');var idPuntoVenta = $('#inputIdPuntoVentaA');var idProveedor = $('#inputIdProveedorA');var con_factura = $('#inputCon_facturaA');var estado = $('#inputEstadoA');var idCuenta = $('#inputIdCuentaA'); $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idIngreso},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          }
           if (json.result != '') {nombre.val(json.result[0].nombre);fecha.val(json.result[0].fecha);fecha_registro.val(json.result[0].fecha_registro);tipocambio.val(json.result[0].tipocambio);idMoneda.val(json.result[0].idMoneda);pagadoA.val(json.result[0].pagadoA);glosa.val(json.result[0].glosa);idTipoIngreso.val(json.result[0].idTipoIngreso);idPuntoVenta.val(json.result[0].idPuntoVenta);idProveedor.val(json.result[0].idProveedor);con_factura.val(json.result[0].con_factura);estado.val(json.result[0].estado);idCuenta.val(json.result[0].idCuenta);
                $("#ModalIngresoEdit").modal("show");
            }
                 else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                 $("#loading").css("display", "none");
                  }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function paginadorIngreso() {
    $('#tableIngreso').DataTable({
        'pagingType': 'full_numbers',
        'scrollY': '330px',
        'scrollCollapse': true,
        'paging': false,
        retrieve: true

    });
}function listarIngreso(){

   $('#tableIngreso').DataTable().destroy();
$('#tbodyIngreso').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/ingresoController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarIngreso'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#tbodyIngreso').append('<tr>'+
                     '<td>'+json.result[i].nombre+
                     '<td>'+json.result[i].fecha+
                     '<td>'+json.result[i].fecha_registro+
                     '<td>'+json.result[i].tipocambio+
                     '<td>'+json.result[i].idMoneda+
                     '<td>'+json.result[i].pagadoA+
                     '<td>'+json.result[i].glosa+
                     '<td>'+json.result[i].idTipoIngreso+
                     '<td>'+json.result[i].idPuntoVenta+
                     '<td>'+json.result[i].idProveedor+
                     '<td>'+json.result[i].con_factura+
                     '<td>'+json.result[i].estado+
                     '<td>'+json.result[i].idCuenta+'<td><button onclick="cargarDatosIngreso('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarIngreso('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
}
paginadorIngreso();
            $('#loading').css('display', 'none');

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function listarTipoingreso(){

$('#selectIdTipoingreso').empty();
$('#selectIdTipoingresoA').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/tipoingresoController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarTipoingreso'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#selectIdTipoingreso').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
            $('#selectIdTipoingresoA').append('<option value='+json.result[i].id+'>'+json.result[i].nombre); 

        }
        $('#loading').css('display', 'none');
    }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function listarCuentacontable(){

$('#selectIdCuentacontable').empty();
$('#selectIdCuentacontableA').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/cuentacontableController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarCuentacontable'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#selectIdCuentacontable').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
            $('#selectIdCuentacontableA').append('<option value='+json.result[i].id+'>'+json.result[i].nombre); 

        }
        $('#loading').css('display', 'none');
    }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}