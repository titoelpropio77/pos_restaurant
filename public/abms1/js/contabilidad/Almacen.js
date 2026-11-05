$(document).ready(function(){
listarAlmacen();
});
$("#frmGuardarAlmacen").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
var nombre = $('#inputNombre').val();
var idSucursal = $('#inputIdSucursal').val();
var estado = $('#inputEstado').val();
var idEmpleadoResponsable = $('#inputIdEmpleadoResponsable').val();
if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
                             }
if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }
if (validar("texto y entero", estado)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO ESTADO.</span><br>";
            }
if (validar("decimal", idEmpleadoResponsable)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDEMPLEADORESPONSABLE.</span><br>";
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
        url: 'CONTROLADORES/almacenController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalAlmacenGuardar').modal('hide');
                $('#frmGuardarAlmacen')[0].reset();


            }

            listarAlmacen();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});
$("#frmEditarAlmacen").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
var nombre = $('#inputNombreA').val();
var idSucursal = $('#inputIdSucursalA').val();
var estado = $('#inputEstadoA').val();
var idEmpleadoResponsable = $('#inputIdEmpleadoResponsableA').val();
if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
                             }
if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }
if (validar("texto y entero", estado)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO ESTADO.</span><br>";
            }
if (validar("decimal", idEmpleadoResponsable)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDEMPLEADORESPONSABLE.</span><br>";
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
        url: 'CONTROLADORES/almacenController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalAlmacenEdit').modal('hide');
                $('#frmEditarAlmacen')[0].reset();


            }

            listarAlmacen();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});
idAlmacen='';
function cargarDatosAlmacen(idAlmacen1) {
    idAlmacen=idAlmacen1;
    var url = 'CONTROLADORES/almacenController.php';
var nombre = $('#inputNombreA');
var idSucursal = $('#inputIdSucursalA');
var estado = $('#inputEstadoA');
var idEmpleadoResponsable = $('#inputIdEmpleadoResponsableA');
 $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idAlmacen},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          }
           if (json.result != '') {
nombre.val(json.result['nombre']);
idSucursal.val(json.result['idSucursal']);
estado.val(json.result['estado']);
idEmpleadoResponsable.val(json.result['idEmpleadoResponsable']);

                $("#ModalAlmacenEdit").modal("show");
            }
                 else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                 $("#loading").css("display", "none");
                  }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}
function paginadorAlmacen() {
    $('#tableAlmacen').DataTable({
        'pagingType': 'full_numbers',
        'scrollY': '330px',
        'scrollCollapse': true,
        'paging': false,
        retrieve: true

    });
}
function listarAlmacen(){

   
$('#tbodyAlmacen').empty();
  
    $.ajax({
        url: 'CONTROLADORES/almacenController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarAlmacen'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#tbodyAlmacen').append('<tr>'+
                     '<td>'+json.result[i].nombre+
                     '<td>'+json.result[i].idSucursal+
                     '<td>'+json.result[i].estado+
                     '<td>'+json.result[i].idEmpleadoResponsable+
'<td><button onclick="cargarDatosAlmacen('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarAlmacen('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
}
paginadorAlmacen();
            $('#loading').css('display', 'none');

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}
