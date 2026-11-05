$(document).ready(function(){
listarEgreso();
listarTipoegreso();listarCuentacontable();});$("#frmGuardarEgreso").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var nombre = $('#inputNombre').val();var fecha = $('#inputFecha').val();var fecha_registro = $('#inputFecha_registro').val();var tipocambio = $('#inputTipocambio').val();var idMoneda = $('#inputIdMoneda').val();var pagadoA = $('#inputPagadoA').val();var glosa = $('#inputGlosa').val();var idTipoEgreso = $('#inputIdTipoEgreso').val();var idPuntoVenta = $('#inputIdPuntoVenta').val();var idProveedor = $('#inputIdProveedor').val();var con_factura = $('#inputCon_factura').val();var estado = $('#inputEstado').val();var idCuenta = $('#inputIdCuenta').val();if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
                             }if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
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
        url: '../../../CONTROLADORES/egresoController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalEgresoGuardar').modal('hide');
                $('#frmGuardarEgreso')[0].reset();


            }

            listarEgreso();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});$("#frmEditarEgreso").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var nombre = $('#inputNombreA').val();var fecha = $('#inputFechaA').val();var fecha_registro = $('#inputFecha_registroA').val();var tipocambio = $('#inputTipocambioA').val();var idMoneda = $('#inputIdMonedaA').val();var pagadoA = $('#inputPagadoAA').val();var glosa = $('#inputGlosaA').val();var idTipoEgreso = $('#inputIdTipoEgresoA').val();var idPuntoVenta = $('#inputIdPuntoVentaA').val();var idProveedor = $('#inputIdProveedorA').val();var con_factura = $('#inputCon_facturaA').val();var estado = $('#inputEstadoA').val();var idCuenta = $('#inputIdCuentaA').val();if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
                             }if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
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
        url: '../../../CONTROLADORES/egresoController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalEgresoEdit').modal('hide');
                $('#frmEditarEgreso')[0].reset();


            }

            listarEgreso();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});idEgreso='';
function cargarDatosEgreso(idEgreso1) {
    idEgreso=idEgreso1;
    var url = '../../../CONTROLADORES/egresoController.php';var nombre = $('#inputNombreA');var fecha = $('#inputFechaA');var fecha_registro = $('#inputFecha_registroA');var tipocambio = $('#inputTipocambioA');var idMoneda = $('#inputIdMonedaA');var pagadoA = $('#inputPagadoAA');var glosa = $('#inputGlosaA');var idTipoEgreso = $('#inputIdTipoEgresoA');var idPuntoVenta = $('#inputIdPuntoVentaA');var idProveedor = $('#inputIdProveedorA');var con_factura = $('#inputCon_facturaA');var estado = $('#inputEstadoA');var idCuenta = $('#inputIdCuentaA'); $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idEgreso},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          }
           if (json.result != '') {nombre.val(json.result[0].nombre);fecha.val(json.result[0].fecha);fecha_registro.val(json.result[0].fecha_registro);tipocambio.val(json.result[0].tipocambio);idMoneda.val(json.result[0].idMoneda);pagadoA.val(json.result[0].pagadoA);glosa.val(json.result[0].glosa);idTipoEgreso.val(json.result[0].idTipoEgreso);idPuntoVenta.val(json.result[0].idPuntoVenta);idProveedor.val(json.result[0].idProveedor);con_factura.val(json.result[0].con_factura);estado.val(json.result[0].estado);idCuenta.val(json.result[0].idCuenta);
                $("#ModalEgresoEdit").modal("show");
            }
                 else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                 $("#loading").css("display", "none");
                  }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function paginadorEgreso() {
    $('#tableEgreso').DataTable({
        'pagingType': 'full_numbers',
        'scrollY': '330px',
        'scrollCollapse': true,
        'paging': false,
        retrieve: true

    });
}function listarEgreso(){

   $('#tableEgreso').DataTable().destroy();
$('#tbodyEgreso').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/egresoController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarEgreso'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#tbodyEgreso').append('<tr>'+
                     '<td>'+json.result[i].nombre+
                     '<td>'+json.result[i].fecha+
                     '<td>'+json.result[i].fecha_registro+
                     '<td>'+json.result[i].tipocambio+
                     '<td>'+json.result[i].idMoneda+
                     '<td>'+json.result[i].pagadoA+
                     '<td>'+json.result[i].glosa+
                     '<td>'+json.result[i].idTipoEgreso+
                     '<td>'+json.result[i].idPuntoVenta+
                     '<td>'+json.result[i].idProveedor+
                     '<td>'+json.result[i].con_factura+
                     '<td>'+json.result[i].estado+
                     '<td>'+json.result[i].idCuenta+'<td><button onclick="cargarDatosEgreso('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarEgreso('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
}
paginadorEgreso();
            $('#loading').css('display', 'none');

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function listarTipoegreso(){

$('#selectIdTipoegreso').empty();
$('#selectIdTipoegresoA').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/tipoegresoController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarTipoegreso'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#selectIdTipoegreso').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
            $('#selectIdTipoegresoA').append('<option value='+json.result[i].id+'>'+json.result[i].nombre); 

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