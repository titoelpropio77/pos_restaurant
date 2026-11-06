$(document).ready(function(){
    listarEmpresa();
});
$("#frmGuardarEmpresa").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    var nombre = $('#inputNombre').val();
    var imagen = $('#inputImagen').val();
    var web = $('#inputWeb').val();
    var correo = $('#inputCorreo').val();
    var propietario = $('#inputPropietario').val();
    var actividad = $('#inputActividad').val();
    var estado = $('#inputEstado').val();
    var venderSinStock = $('#inputVenderSinStock').val();
    var registroDiario = $('#inputRegistroDiario').val();
    var multilistaPrecio = $('#inputMultilistaPrecio').val();
    if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    }
    if (validar("texto y entero", imagen)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO IMAGEN.</span><br>";
    }
    if (validar("texto y entero", web)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO WEB.</span><br>";
    }
    if (validar("texto y entero", correo)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO CORREO.</span><br>";
    }
    if (validar("texto y entero", propietario)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO PROPIETARIO.</span><br>";
    }
    if (validar("texto y entero", actividad)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO ACTIVIDAD.</span><br>";
    }
    if (validar("decimal", estado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ESTADO.</span><br>";
    }
    if (validar("decimal", venderSinStock)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO VENDERSINSTOCK.</span><br>";
    }
    if (validar("decimal", registroDiario)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO REGISTRODIARIO.</span><br>";
    }
    if (validar("decimal", multilistaPrecio)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO MULTILISTAPRECIO.</span><br>";
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
        url: '../../../CONTROLADORES/empresaController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalEmpresaGuardar').modal('hide');
                $('#frmGuardarEmpresa')[0].reset();


            }

            listarEmpresa();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

});
$("#frmEditarEmpresa").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    var nombre = $('#inputNombreA').val();
    var imagen = $('#inputImagenA').val();
    var web = $('#inputWebA').val();
    var correo = $('#inputCorreoA').val();
    var propietario = $('#inputPropietarioA').val();
    var actividad = $('#inputActividadA').val();
    var estado = $('#inputEstadoA').val();
    var venderSinStock = $('#inputVenderSinStockA').val();
    var registroDiario = $('#inputRegistroDiarioA').val();
    var multilistaPrecio = $('#inputMultilistaPrecioA').val();
    if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    }
    if (validar("texto y entero", imagen)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO IMAGEN.</span><br>";
    }
    if (validar("texto y entero", web)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO WEB.</span><br>";
    }
    if (validar("texto y entero", correo)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO CORREO.</span><br>";
    }
    if (validar("texto y entero", propietario)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO PROPIETARIO.</span><br>";
    }
    if (validar("texto y entero", actividad)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO ACTIVIDAD.</span><br>";
    }
    if (validar("decimal", estado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ESTADO.</span><br>";
    }
    if (validar("decimal", venderSinStock)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO VENDERSINSTOCK.</span><br>";
    }
    if (validar("decimal", registroDiario)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO REGISTRODIARIO.</span><br>";
    }
    if (validar("decimal", multilistaPrecio)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO MULTILISTAPRECIO.</span><br>";
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
        url: '../../../CONTROLADORES/empresaController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalEmpresaEdit').modal('hide');
                $('#frmEditarEmpresa')[0].reset();


            }

            listarEmpresa();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

});
idEmpresa='';
function cargarDatosEmpresa(idEmpresa1) {
    idEmpresa=idEmpresa1;
    var url = '../../../CONTROLADORES/empresaController.php';
    var nombre = $('#inputNombreA');
    var imagen = $('#inputImagenA');
    var web = $('#inputWebA');
    var correo = $('#inputCorreoA');
    var propietario = $('#inputPropietarioA');
    var actividad = $('#inputActividadA');
    var estado = $('#inputEstadoA');
    var venderSinStock = $('#inputVenderSinStockA');
    var registroDiario = $('#inputRegistroDiarioA');
    var multilistaPrecio = $('#inputMultilistaPrecioA');
    $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idEmpresa},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            if (json.result != '') {
                nombre.val(json.result[0].nombre);
                imagen.val(json.result[0].imagen);
                web.val(json.result[0].web);
                correo.val(json.result[0].correo);
                propietario.val(json.result[0].propietario);
                actividad.val(json.result[0].actividad);
                estado.val(json.result[0].estado);
                venderSinStock.val(json.result[0].venderSinStock);
                registroDiario.val(json.result[0].registroDiario);
                multilistaPrecio.val(json.result[0].multilistaPrecio);

                $("#ModalEmpresaEdit").modal("show");
            }
            else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                $("#loading").css("display", "none");
            }
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }
    function paginadorEmpresa() {
        $('#tableEmpresa').DataTable({
            'pagingType': 'full_numbers',
            'scrollY': '330px',
            'scrollCollapse': true,
            'paging': false,
            retrieve: true

        });
    }
    function listarEmpresa(){
        $('#tbodyEmpresa').empty();
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
                    $('#tbodyEmpresa').append('<tr>'+
                       '<td>'+json.result[i].nombre+
                       '<td>'+json.result[i].imagen+
                       '<td>'+json.result[i].web+
                       '<td>'+json.result[i].correo+
                       '<td>'+json.result[i].propietario+
                       '<td>'+json.result[i].actividad+
                       '<td>'+json.result[i].estado+
                       '<td>'+json.result[i].venderSinStock+
                       '<td>'+json.result[i].registroDiario+
                       '<td>'+json.result[i].multilistaPrecio+
                       '<td><button onclick="cargarDatosEmpresa('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarEmpresa('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
                }
                paginadorEmpresa();
                $('#loading').css('display', 'none');

            }
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }
