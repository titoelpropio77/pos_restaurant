$(document).ready(function(){
listarCajacuenta();
listarCuentacontable();});$("#frmGuardarCajacuenta").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var nombre = $('#inputNombre').val();var idCuenta = $('#inputIdCuenta').val();var estado = $('#inputEstado').val();var eliminado = $('#inputEliminado').val();var moneda = $('#inputMoneda').val();if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }if (validar("decimal", estado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ESTADO.</span><br>";
                         }if (validar("decimal", eliminado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ELIMINADO.</span><br>";
                         }if (validar("texto y entero", moneda)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO MONEDA.</span><br>";
            }if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    } var parametros = new FormData($(this)[0]);
parametros.append("proceso","guardar"); $.ajax({
        type: 'POST',
        url: '../../../CONTROLADORES/cajacuentaController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalCajacuentaGuardar').modal('hide');
                $('#frmGuardarCajacuenta')[0].reset();


            }

            listarCajacuenta();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});$("#frmEditarCajacuenta").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var nombre = $('#inputNombreA').val();var idCuenta = $('#inputIdCuentaA').val();var estado = $('#inputEstadoA').val();var eliminado = $('#inputEliminadoA').val();var moneda = $('#inputMonedaA').val();if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }if (validar("decimal", estado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ESTADO.</span><br>";
                         }if (validar("decimal", eliminado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ELIMINADO.</span><br>";
                         }if (validar("texto y entero", moneda)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO MONEDA.</span><br>";
            }if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    } var parametros = new FormData($(this)[0]);
parametros.append("proceso","editar"); $.ajax({
        type: 'POST',
        url: '../../../CONTROLADORES/cajacuentaController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalCajacuentaEdit').modal('hide');
                $('#frmEditarCajacuenta')[0].reset();


            }

            listarCajacuenta();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});idCajacuenta='';
function cargarDatosCajacuenta(idCajacuenta1) {
    idCajacuenta=idCajacuenta1;
    var url = '../../../CONTROLADORES/cajacuentaController.php';var nombre = $('#inputNombreA');var idCuenta = $('#inputIdCuentaA');var estado = $('#inputEstadoA');var eliminado = $('#inputEliminadoA');var moneda = $('#inputMonedaA'); $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idCajacuenta},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          }
           if (json.result != '') {nombre.val(json.result[0].nombre);idCuenta.val(json.result[0].idCuenta);estado.val(json.result[0].estado);eliminado.val(json.result[0].eliminado);moneda.val(json.result[0].moneda);
                $("#ModalCajacuentaEdit").modal("show");
            }
                 else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                 $("#loading").css("display", "none");
                  }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function paginadorCajacuenta() {
    $('#tableCajacuenta').DataTable({
        'pagingType': 'full_numbers',
        'scrollY': '330px',
        'scrollCollapse': true,
        'paging': false,
        retrieve: true

    });
}function listarCajacuenta(){

   $('#tableCajacuenta').DataTable().destroy();
$('#tbodyCajacuenta').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/cajacuentaController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarCajacuenta'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#tbodyCajacuenta').append('<tr>'+
                     '<td>'+json.result[i].nombre+
                     '<td>'+json.result[i].idCuenta+
                     '<td>'+json.result[i].estado+
                     '<td>'+json.result[i].eliminado+
                     '<td>'+json.result[i].moneda+'<td><button onclick="cargarDatosCajacuenta('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarCajacuenta('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
}
paginadorCajacuenta();
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