$(document).ready(function(){
listarProveedores();

listarCiudad();
// listarCuentacontable();
});
$("#frmGuardarProveedores").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
var nombre = $('#inputNombre').val();
var direccion = $('#inputDireccion').val();
var telefono = $('#inputTelefono').val();
var paginaWeb = $('#inputPaginaWeb').val();
var contactoRef = $('#inputContactoRef').val();
var telefonoContacto = $('#inputTelefonoContacto').val();
var correoContacto = $('#inputCorreoContacto').val();
var idCiudad = $('#inputIdCiudad').val();
var idCuenta = $('#inputIdCuenta').val();
var estado = $('#inputEstado').val();
if (validar("texto y entero", nombre) && nombre!="") {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }
if (validar("texto y entero", direccion) && nombre!="") {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO DIRECCION.</span><br>";
            }
if (validar("decimal", telefono) && telefono!="") {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO TELEFONO.</span><br>";
                         }
if (validar("texto y entero", paginaWeb) && paginaWeb!="") {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO PAGINAWEB.</span><br>";
            }
if (validar("texto y entero", contactoRef) && contactoRef!="") {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO CONTACTOREF.</span><br>";
            }
if (validar("texto y entero", telefonoContacto) && telefonoContacto!="") {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO TELEFONOCONTACTO.</span><br>";
                         }
if (validar("decimal", correoContacto) && correoContacto!="") {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO CORREOCONTACTO.</span><br>";
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
        url: '../CONTROLADORES/proveedoresController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalProveedoresGuardar').modal('hide');
                $('#frmGuardarProveedores')[0].reset();


            }

            listarProveedores();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});
$("#frmEditarProveedores").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
var nombre = $('#inputNombreA').val();
var direccion = $('#inputDireccionA').val();
var telefono = $('#inputTelefonoA').val();
var paginaWeb = $('#inputPaginaWebA').val();
var contactoRef = $('#inputContactoRefA').val();
var telefonoContacto = $('#inputTelefonoContactoA').val();
var correoContacto = $('#inputCorreoContactoA').val();
var idCiudad = $('#inputIdCiudadA').val();
var idCuenta = $('#inputIdCuentaA').val();
var estado = $('#inputEstadoA').val();
if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }
if (validar("texto y entero", direccion)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO DIRECCION.</span><br>";
            }
if (validar("decimal", telefono)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO TELEFONO.</span><br>";
                         }
if (validar("texto y entero", paginaWeb)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO PAGINAWEB.</span><br>";
            }
if (validar("texto y entero", contactoRef)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO CONTACTOREF.</span><br>";
            }
if (validar("decimal", telefonoContacto)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO TELEFONOCONTACTO.</span><br>";
                         }
if (validar("decimal", correoContacto)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO CORREOCONTACTO.</span><br>";
                         }
if (validar("decimal", estado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ESTADO.</span><br>";
                         }
if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    }
 var parametros = new FormData($(this)[0]);
parametros.append("proceso","editar");
 $.ajax({
        type: 'POST',
        url: '../CONTROLADORES/proveedoresController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalProveedoresEdit').modal('hide');
                $('#frmEditarProveedores')[0].reset();


            }

            listarProveedores();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});
idProveedores='';
function cargarDatosProveedores(idProveedores1) {
    idProveedores=idProveedores1;
    var url = '../CONTROLADORES/proveedoresController.php';
var nombre = $('#inputNombreA');
var direccion = $('#inputDireccionA');
var telefono = $('#inputTelefonoA');
var paginaWeb = $('#inputPaginaWebA');
var contactoRef = $('#inputContactoRefA');
var telefonoContacto = $('#inputTelefonoContactoA');
var correoContacto = $('#inputCorreoContactoA');
var idCiudad = $('#inputIdCiudadA');
var idCuenta = $('#inputIdCuentaA');
var estado = $('#inputEstadoA');
 $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idProveedores},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          }
           if (json.result != '') {
nombre.val(json.result[0].nombre);
direccion.val(json.result[0].direccion);
telefono.val(json.result[0].telefono);
paginaWeb.val(json.result[0].paginaWeb);
contactoRef.val(json.result[0].contactoRef);
telefonoContacto.val(json.result[0].telefonoContacto);
correoContacto.val(json.result[0].correoContacto);
idCiudad.val(json.result[0].idCiudad);
idCuenta.val(json.result[0].idCuenta);
estado.val(json.result[0].estado);

                $("#ModalProveedoresEdit").modal("show");
            }
                 else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                 $("#loading").css("display", "none");
                  }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}
function paginadorProveedores() {
    $('#tableProveedores').DataTable({
        'pagingType': 'full_numbers',
        'scrollY': '330px',
        'scrollCollapse': true,
        'paging': false,
        retrieve: true

    });
}
function listarProveedores(){

   $('#tableProveedores').DataTable().destroy();
$('#tbodyProveedores').empty();
  
    $.ajax({
        url: '../CONTROLADORES/proveedoresController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarProveedores'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#tbodyProveedores').append('<tr>'+
                     '<td>'+json.result[i].nombre+
                     '<td>'+json.result[i].direccion+
                     '<td>'+json.result[i].telefono+
                     '<td>'+json.result[i].paginaWeb+
                     '<td>'+json.result[i].contactoRef+
                     '<td>'+json.result[i].telefonoContacto+
                     '<td>'+json.result[i].correoContacto+
                     '<td>'+json.result[i].idCiudad+
                     '<td>'+json.result[i].idCuenta+
                     '<td>'+json.result[i].estado+
'<td><button onclick="cargarDatosProveedores('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarProveedores('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
}
paginadorProveedores();
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
        url: '../CONTROLADORES/ciudadController.php',
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
function listarCuentacontable(){

$('#selectIdCuentacontable').empty();
$('#selectIdCuentacontableA').empty();
  
    $.ajax({
        url: '../CONTROLADORES/cuentacontableController.php',
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
