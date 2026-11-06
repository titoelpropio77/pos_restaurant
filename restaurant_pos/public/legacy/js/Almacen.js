$(document).ready(function(){
    listarAlmacen();

    listarSucursal();
    listarCuentacontable();
    listarUsuario();
});
$("#frmGuardarAlmacen").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    var nombre = $('#inputNombre').val();
    var idSucursal = $('#inputIdSucursal').val();
    var estado = $('#inputEstado').val();
    var idEmpleadoResponsable = $('#inputIdEmpleadoResponsable').val();
    var idCuenta = $('#inputIdCuenta').val();
    if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
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
        url: '../../../CONTROLADORES/almacenController.php',
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
    var idCuenta = $('#inputIdCuentaA').val();
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
        url: '../../../CONTROLADORES/almacenController.php',
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
    var url = '../../../CONTROLADORES/almacenController.php';
    var nombre = $('#inputNombreA');
    var idSucursal = $('#inputIdSucursalA');
    var estado = $('#inputEstadoA');
    var idEmpleadoResponsable = $('#inputIdEmpleadoResponsableA');
    var idCuenta = $('#inputIdCuentaA');
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
                nombre.val(json.result[0].nombre);
                idSucursal.val(json.result[0].idSucursal);
                estado.val(json.result[0].estado);
                idEmpleadoResponsable.val(json.result[0].idEmpleadoResponsable);
                idCuenta.val(json.result[0].idCuenta);

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

     $('#tableAlmacen').DataTable().destroy();
     $('#tbodyAlmacen').empty();

     $.ajax({
        url: '../../../CONTROLADORES/almacenController.php',
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
                   '<td>'+json.result[i].idCuenta+
                   '<td><button onclick="cargarDatosAlmacen('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarAlmacen('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
            }
            paginadorAlmacen();
            $('#loading').css('display', 'none');

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
function listarSucursal(){

    $('#selectIdSucursal').empty();
    $('#selectIdSucursalA').empty();

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
                $('#selectIdSucursal').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
                $('#selectIdSucursalA').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);


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

function listarUsuario(){

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
                $('#idEmpleadoResponsable').append('<option value='+json.result[i].ID+'>'+json.result[i].NOMBRES);


            }
            $('#loading').css('display', 'none');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}