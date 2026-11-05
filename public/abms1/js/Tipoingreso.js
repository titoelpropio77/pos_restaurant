$(document).ready(function(){
listarTipoingreso();
listarCuentacontable();});$("#frmGuardarTipoingreso").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var nombre = $('#inputNombre').val();var idCuenta = $('#inputIdCuenta').val();var estado = $('#inputEstado').val();if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
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
        url: '../../../CONTROLADORES/tipoingresoController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalTipoingresoGuardar').modal('hide');
                $('#frmGuardarTipoingreso')[0].reset();


            }

            listarTipoingreso();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});$("#frmEditarTipoingreso").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var nombre = $('#inputNombreA').val();var idCuenta = $('#inputIdCuentaA').val();var estado = $('#inputEstadoA').val();if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
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
        url: '../../../CONTROLADORES/tipoingresoController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalTipoingresoEdit').modal('hide');
                $('#frmEditarTipoingreso')[0].reset();


            }

            listarTipoingreso();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});idTipoingreso='';
function cargarDatosTipoingreso(idTipoingreso1) {
    idTipoingreso=idTipoingreso1;
    var url = '../../../CONTROLADORES/tipoingresoController.php';var nombre = $('#inputNombreA');var idCuenta = $('#inputIdCuentaA');var estado = $('#inputEstadoA'); $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idTipoingreso},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          }
           if (json.result != '') {nombre.val(json.result[0].nombre);idCuenta.val(json.result[0].idCuenta);estado.val(json.result[0].estado);
                $("#ModalTipoingresoEdit").modal("show");
            }
                 else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                 $("#loading").css("display", "none");
                  }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function paginadorTipoingreso() {
    $('#tableTipoingreso').DataTable({
        'pagingType': 'full_numbers',
        'scrollY': '330px',
        'scrollCollapse': true,
        'paging': false,
        retrieve: true

    });
}function listarTipoingreso(){

   $('#tableTipoingreso').DataTable().destroy();
$('#tbodyTipoingreso').empty();
  
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
            $('#tbodyTipoingreso').append('<tr>'+
                     '<td>'+json.result[i].nombre+
                     '<td>'+json.result[i].idCuenta+
                     '<td>'+json.result[i].estado+'<td><button onclick="cargarDatosTipoingreso('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarTipoingreso('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
}
paginadorTipoingreso();
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