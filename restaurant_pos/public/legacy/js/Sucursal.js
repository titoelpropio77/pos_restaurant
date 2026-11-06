$(document).ready(function(){
listarSucursal();

listarEmpresa();
listarCiudad();


listarUsuarioContador() ;
});
$("#frmGuardarSucursal").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
var nombre = $('#inputNombre').val();
var direccion = $('#inputDireccion').val();
var telefono = $('#inputTelefono').val();
var estado = $('#inputEstado').val();
var idEmpresa = $('#inputIdEmpresa').val();
var idCiudad = $('#inputIdCiudad').val();
var contador = $('#inputContador').val();
if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }
if (validar("texto y entero", direccion)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO DIRECCION.</span><br>";
            }
if (validar("texto y entero", telefono)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO TELEFONO.</span><br>";
            }
if (validar("texto y entero", estado)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO ESTADO.</span><br>";
            }

if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    }
 var parametros = new FormData($(this)[0]);
parametros.append("proceso","guardar");
 $.ajax({
        type: 'POST',
        url: '../../../CONTROLADORES/sucursalController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalSucursalGuardar').modal('hide');
                $('#frmGuardarSucursal')[0].reset();


            }

            listarSucursal();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});
$("#frmEditarSucursal").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
var nombre = $('#inputNombreA').val();
var direccion = $('#inputDireccionA').val();
var telefono = $('#inputTelefonoA').val();
var estado = $('#inputEstadoA').val();
var idEmpresa = $('#inputIdEmpresaA').val();
var idCiudad = $('#inputIdCiudadA').val();
var contador = $('#inputContadorA').val();
if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }
if (validar("texto y entero", direccion)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO DIRECCION.</span><br>";
            }
if (validar("texto y entero", telefono)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO TELEFONO.</span><br>";
            }
if (validar("texto y entero", estado)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO ESTADO.</span><br>";
            }

if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    }
 var parametros = new FormData($(this)[0]);
parametros.append("proceso","editar");
parametros.append("idSucursal",idSucursal);
idSucursal
 $.ajax({
        type: 'POST',
        url: '../../../CONTROLADORES/sucursalController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalSucursalEdit').modal('hide');
                $('#frmEditarSucursal')[0].reset();


            }

            listarSucursal();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});
idSucursal='';
function cargarDatosSucursal(idSucursal1) {
    idSucursal=idSucursal1;
    var url = '../../../CONTROLADORES/sucursalController.php';
var nombre = $('#inputNombreA');
var direccion = $('#inputDireccionA');
var telefono = $('#inputTelefonoA');
var estado = $('#inputEstadoA');
var idEmpresa = $('#inputIdEmpresaA');
var idCiudad = $('#inputIdCiudadA');
var contador = $('#inputContadorA');
var ipImpresion = $('#ipImpresionA');
 $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idSucursal},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          }
           if (json.result != '') {
nombre.val(json.result[0].nombreSucursal);
direccion.val(json.result[0].direccion);
telefono.val(json.result[0].telefono);
estado.val(json.result[0].estado);
idEmpresa.val(json.result[0].idEmpresa);
idCiudad.val(json.result[0].idCiudad);
contador.val(json.result[0].contador);
ipImpresion.val(json.result[0].ipImpresion);
                $("#ModalSucursalEdit").modal("show");
            }
                 else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                 $("#loading").css("display", "none");
                  }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}
function paginadorSucursal() {
    $('#tableSucursal').DataTable({
        'pagingType': 'full_numbers',
        'scrollY': '330px',
        'scrollCollapse': true,
        'paging': false,
        retrieve: true

    });
}
function listarSucursal(){

   $('#tableSucursal').DataTable().destroy();
$('#tbodySucursal').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/sucursalController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarSucursal'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#tbodySucursal').append('<tr>'+
                     '<td>'+json.result[i].nombre+
                     '<td>'+json.result[i].direccion+
                     '<td>'+json.result[i].telefono+
                     '<td>'+json.result[i].estado+
                     '<td>'+json.result[i].idEmpresa+
                     '<td>'+json.result[i].idCiudad+
                     '<td>'+json.result[i].contador+
'<td><button onclick="cargarDatosSucursal('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarSucursal('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
}
paginadorSucursal();
            $('#loading').css('display', 'none');

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}
function listarEmpresa(){

$('#selectIdEmpresa').empty();
$('#selectIdEmpresaA').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/empresaController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarEmpresa'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#selectIdEmpresa').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
            $('#selectIdEmpresaA').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
 

        }
        $('#loading').css('display', 'none');
    }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}
function listarCiudad(){

$('#selectIdCiudad').empty();
$('#selectIdCiudadA').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/ciudadController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarCiudad'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#selectIdCiudad').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
            $('#selectIdCiudadA').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
 

        }
        $('#loading').css('display', 'none');
    }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}
function listarUsuarioContador() {
    $.ajax({
        url: '../../../CONTROLADORES/usuarioController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarTodo'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#contador').append('<option value='+json.result[i].ID+'>'+json.result[i].NOMBRES);
            $('#contadorA').append('<option value='+json.result[i].ID+'>'+json.result[i].NOMBRES);
 

        }
        $('#loading').css('display', 'none');
    }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}