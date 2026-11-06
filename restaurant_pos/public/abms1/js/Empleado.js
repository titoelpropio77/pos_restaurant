$(document).ready(function(){
listarEmpleado();
listarCargo();listarTurno();});$("#frmGuardarEmpleado").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var nombre = $('#inputNombre').val();var fechaNacimiento = $('#inputFechaNacimiento').val();var genero = $('#inputGenero').val();var felefono = $('#inputFelefono').val();var celular = $('#inputCelular').val();var docIdentidad = $('#inputDocIdentidad').val();var correoElectronico = $('#inputCorreoElectronico').val();var idCargo = $('#inputIdCargo').val();var idTurno = $('#inputIdTurno').val();var estado = $('#inputEstado').val();var comision = $('#inputComision').val();if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
                             }if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }if (validar("texto y entero", fechaNacimiento)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO FECHANACIMIENTO.</span><br>";
            }if (validar("texto y entero", genero)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO GENERO.</span><br>";
            }if (validar("decimal", felefono)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO FELEFONO.</span><br>";
                         }if (validar("decimal", celular)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO CELULAR.</span><br>";
                         }if (validar("decimal", docIdentidad)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO DOCIDENTIDAD.</span><br>";
                         }if (validar("texto y entero", correoElectronico)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO CORREOELECTRONICO.</span><br>";
            }if (validar("decimal", estado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ESTADO.</span><br>";
                         }if (validar("decimal", comision)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO COMISION.</span><br>";
                         }if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    } var parametros = new FormData($(this)[0]);
parametros.append("proceso","guardar"); $.ajax({
        type: 'POST',
        url: '../../../CONTROLADORES/empleadoController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalEmpleadoGuardar').modal('hide');
                $('#frmGuardarEmpleado')[0].reset();


            }

            listarEmpleado();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});$("#frmEditarEmpleado").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var nombre = $('#inputNombreA').val();var fechaNacimiento = $('#inputFechaNacimientoA').val();var genero = $('#inputGeneroA').val();var felefono = $('#inputFelefonoA').val();var celular = $('#inputCelularA').val();var docIdentidad = $('#inputDocIdentidadA').val();var correoElectronico = $('#inputCorreoElectronicoA').val();var idCargo = $('#inputIdCargoA').val();var idTurno = $('#inputIdTurnoA').val();var estado = $('#inputEstadoA').val();var comision = $('#inputComisionA').val();if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
                             }if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }if (validar("texto y entero", fechaNacimiento)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO FECHANACIMIENTO.</span><br>";
            }if (validar("texto y entero", genero)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO GENERO.</span><br>";
            }if (validar("decimal", felefono)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO FELEFONO.</span><br>";
                         }if (validar("decimal", celular)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO CELULAR.</span><br>";
                         }if (validar("decimal", docIdentidad)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO DOCIDENTIDAD.</span><br>";
                         }if (validar("texto y entero", correoElectronico)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO CORREOELECTRONICO.</span><br>";
            }if (validar("decimal", estado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ESTADO.</span><br>";
                         }if (validar("decimal", comision)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO COMISION.</span><br>";
                         }if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    } var parametros = new FormData($(this)[0]);
parametros.append("proceso","editar"); $.ajax({
        type: 'POST',
        url: '../../../CONTROLADORES/empleadoController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalEmpleadoEdit').modal('hide');
                $('#frmEditarEmpleado')[0].reset();


            }

            listarEmpleado();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});idEmpleado='';
function cargarDatosEmpleado(idEmpleado1) {
    idEmpleado=idEmpleado1;
    var url = '../../../CONTROLADORES/empleadoController.php';var nombre = $('#inputNombreA');var fechaNacimiento = $('#inputFechaNacimientoA');var genero = $('#inputGeneroA');var felefono = $('#inputFelefonoA');var celular = $('#inputCelularA');var docIdentidad = $('#inputDocIdentidadA');var correoElectronico = $('#inputCorreoElectronicoA');var idCargo = $('#inputIdCargoA');var idTurno = $('#inputIdTurnoA');var estado = $('#inputEstadoA');var comision = $('#inputComisionA'); $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idEmpleado},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          }
           if (json.result != '') {nombre.val(json.result[0].nombre);fechaNacimiento.val(json.result[0].fechaNacimiento);genero.val(json.result[0].genero);felefono.val(json.result[0].felefono);celular.val(json.result[0].celular);docIdentidad.val(json.result[0].docIdentidad);correoElectronico.val(json.result[0].correoElectronico);idCargo.val(json.result[0].idCargo);idTurno.val(json.result[0].idTurno);estado.val(json.result[0].estado);comision.val(json.result[0].comision);
                $("#ModalEmpleadoEdit").modal("show");
            }
                 else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                 $("#loading").css("display", "none");
                  }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function paginadorEmpleado() {
    $('#tableEmpleado').DataTable({
        'pagingType': 'full_numbers',
        'scrollY': '330px',
        'scrollCollapse': true,
        'paging': false,
        retrieve: true

    });
}function listarEmpleado(){

   $('#tableEmpleado').DataTable().destroy();
$('#tbodyEmpleado').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/empleadoController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarEmpleado'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#tbodyEmpleado').append('<tr>'+
                     '<td>'+json.result[i].nombre+
                     '<td>'+json.result[i].fechaNacimiento+
                     '<td>'+json.result[i].genero+
                     '<td>'+json.result[i].felefono+
                     '<td>'+json.result[i].celular+
                     '<td>'+json.result[i].docIdentidad+
                     '<td>'+json.result[i].correoElectronico+
                     '<td>'+json.result[i].idCargo+
                     '<td>'+json.result[i].idTurno+
                     '<td>'+json.result[i].estado+
                     '<td>'+json.result[i].comision+'<td><button onclick="cargarDatosEmpleado('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarEmpleado('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
}
paginadorEmpleado();
            $('#loading').css('display', 'none');

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function listarCargo(){

$('#selectIdCargo').empty();
$('#selectIdCargoA').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/cargoController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarCargo'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#selectIdCargo').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
            $('#selectIdCargoA').append('<option value='+json.result[i].id+'>'+json.result[i].nombre); 

        }
        $('#loading').css('display', 'none');
    }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function listarTurno(){

$('#selectIdTurno').empty();
$('#selectIdTurnoA').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/turnoController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarTurno'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#selectIdTurno').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
            $('#selectIdTurnoA').append('<option value='+json.result[i].id+'>'+json.result[i].nombre); 

        }
        $('#loading').css('display', 'none');
    }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}