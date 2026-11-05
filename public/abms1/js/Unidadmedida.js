$(document).ready(function(){
    listarUnidadmedida();

});
$("#frmGuardarUnidadmedida").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    var nombre = $('#inputNombre').val();
    var abreviatura = $('#inputAbreviatura').val();
    var estado = $('#inputEstado').val();
    if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    }
    if (abreviatura == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO ABREVIATURA NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("texto y entero", abreviatura)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO ABREVIATURA.</span><br>";
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
        url: '../CONTROLADORES/unidadmedidaController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalUnidadmedidaGuardar').modal('hide');
                $('#frmGuardarUnidadmedida')[0].reset();


            }

            listarUnidadmedida();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

});
$("#frmEditarUnidadmedida").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    var nombre = $('#inputNombreA').val();
    var abreviatura = $('#inputAbreviaturaA').val();
    var estado = $('#inputEstadoA').val();
    if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    }
    if (abreviatura == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO ABREVIATURA NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("texto y entero", abreviatura)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO ABREVIATURA.</span><br>";
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
        url: '../CONTROLADORES/unidadmedidaController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalUnidadmedidaEdit').modal('hide');
                $('#frmEditarUnidadmedida')[0].reset();


            }

            listarUnidadmedida();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

});
idUnidadmedida='';
function cargarDatosUnidadmedida(idUnidadmedida1) {
    idUnidadmedida=idUnidadmedida1;
    var url = '../CONTROLADORES/unidadmedidaController.php';
    var nombre = $('#inputNombreA');
    var abreviatura = $('#inputAbreviaturaA');
    var estado = $('#inputEstadoA');
    $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idUnidadmedida},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            if (json.result != '') {
                nombre.val(json.result[0].nombre);
                abreviatura.val(json.result[0].abreviatura);
                estado.val(json.result[0].estado);

                $("#ModalUnidadmedidaEdit").modal("show");
            }
            else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                $("#loading").css("display", "none");
            }
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }
    function paginadorUnidadmedida() {
        $('#tableUnidadmedida').DataTable({
            'pagingType': 'full_numbers',
            'scrollY': '330px',
            'scrollCollapse': true,
            'paging': false,
            retrieve: true

        });
    }
    function listarUnidadmedida(){
     $('#tableUnidadmedida').DataTable().destroy();
     $('#tbodyUnidadmedida').empty();
     
     $.ajax({
        url: '../CONTROLADORES/unidadmedidaController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarUnidadmedida'},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            for (var i = 0; i < json.result.length; i++) {
                $('#tbodyUnidadmedida').append('<tr>'+
                   '<td>'+json.result[i].nombre+
                   '<td>'+json.result[i].abreviatura+
                   '<td>'+json.result[i].estado+
                   '<td><button onclick="cargarDatosUnidadmedida('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarUnidadmedida('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
            }
            paginadorUnidadmedida();
            $('#loading').css('display', 'none');

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
