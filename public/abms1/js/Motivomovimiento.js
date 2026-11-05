$(document).ready(function(){
    listarMotivomovimiento();
    listarInsumos_mysql();
    listarCuentacontable();
});
$("#frmGuardarMotivomovimiento").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    var nombre = $('#inputNombre').val();
    var estado = $('#inputEstado').val();
    var idCuenta = $('#inputIdCuenta').val();
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
        url: '../CONTROLADORES/motivomovimientoController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalMotivomovimientoGuardar').modal('hide');
                $('#frmGuardarMotivomovimiento')[0].reset();


            }

            listarMotivomovimiento();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

});
$("#frmEditarMotivomovimiento").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    var nombre = $('#inputNombreA').val();
    var estado = $('#inputEstadoA').val();
    var idCuenta = $('#inputIdCuentaA').val();
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
    parametros.append("proceso","editar");
    $.ajax({
        type: 'POST',
        url: '../CONTROLADORES/motivomovimientoController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalMotivomovimientoEdit').modal('hide');
                $('#frmEditarMotivomovimiento')[0].reset();


            }

            listarMotivomovimiento();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

});
idMotivomovimiento='';
function cargarDatosMotivomovimiento(idMotivomovimiento1) {
    idMotivomovimiento=idMotivomovimiento1;
    var url = '../CONTROLADORES/motivomovimientoController.php';
    var nombre = $('#inputNombreA');
    var estado = $('#inputEstadoA');
    var idCuenta = $('#inputIdCuentaA');
    $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idMotivomovimiento},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            if (json.result != '') {
                nombre.val(json.result[0].nombre);
                estado.val(json.result[0].estado);
                idCuenta.val(json.result[0].idCuenta);

                $("#ModalMotivomovimientoEdit").modal("show");
            }
            else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                $("#loading").css("display", "none");
            }
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }
    function paginadorMotivomovimiento() {
        $('#tableMotivomovimiento').DataTable({
            'pagingType': 'full_numbers',
            'scrollY': '330px',
            'scrollCollapse': true,
            'paging': false,
            retrieve: true

        });
    }
    function listarMotivomovimiento(){

       $('#tableMotivomovimiento').DataTable().destroy();
       $('#tbodyMotivomovimiento').empty();

       $.ajax({
        url: '../CONTROLADORES/motivomovimientoController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarMotivomovimiento'},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            for (var i = 0; i < json.result.length; i++) {
                $('#tbodyMotivomovimiento').append('<tr>'+
                 '<td>'+json.result[i].nombre+
                 '<td>'+json.result[i].estado+
                 '<td>'+json.result[i].idCuenta+
                 '<td><button onclick="cargarDatosMotivomovimiento('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarMotivomovimiento('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
            }
            paginadorMotivomovimiento();
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
function agregarFila(){

}
