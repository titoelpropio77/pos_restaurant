$(document).ready(function(){
    listarInventario();

});
$("#frmGuardarInventario").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    var fecha = $('#inputFecha').val();
    var hora = $('#inputHora').val();
    var importe = $('#inputImporte').val();
    var tipo = $('#inputTipo').val();
    var glosa = $('#inputGlosa').val();
    var idAlmacen = $('#inputIdAlmacen').val();
    var idAlmacenDestino = $('#inputIdAlmacenDestino').val();
    var estado = $('#inputEstado').val();
    var idMotivoMovimiento = $('#inputIdMotivoMovimiento').val();
    if (validar("texto y entero", fecha)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO FECHA.</span><br>";
    }
    if (validar("texto y entero", hora)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO HORA.</span><br>";
    }
    if (validar("decimal", importe)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IMPORTE.</span><br>";
    }
    if (validar("texto y entero", tipo)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO TIPO.</span><br>";
    }
    if (validar("texto y entero", glosa)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO GLOSA.</span><br>";
    }
    if (validar("decimal", idAlmacen)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDALMACEN.</span><br>";
    }
    if (validar("decimal", idAlmacenDestino)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDALMACENDESTINO.</span><br>";
    }
    if (validar("texto y entero", estado)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO ESTADO.</span><br>";
    }
    if (validar("decimal", idMotivoMovimiento)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDMOTIVOMOVIMIENTO.</span><br>";
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
        url: '../../../CONTROLADORES/inventarioController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalInventarioGuardar').modal('hide');
                $('#frmGuardarInventario')[0].reset();


            }

            listarInventario();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

});
$("#frmEditarInventario").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    var fecha = $('#inputFechaA').val();
    var hora = $('#inputHoraA').val();
    var importe = $('#inputImporteA').val();
    var tipo = $('#inputTipoA').val();
    var glosa = $('#inputGlosaA').val();
    var idAlmacen = $('#inputIdAlmacenA').val();
    var idAlmacenDestino = $('#inputIdAlmacenDestinoA').val();
    var estado = $('#inputEstadoA').val();
    var idMotivoMovimiento = $('#inputIdMotivoMovimientoA').val();
    if (validar("texto y entero", fecha)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO FECHA.</span><br>";
    }
    if (validar("texto y entero", hora)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO HORA.</span><br>";
    }
    if (validar("decimal", importe)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IMPORTE.</span><br>";
    }
    if (validar("texto y entero", tipo)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO TIPO.</span><br>";
    }
    if (validar("texto y entero", glosa)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO GLOSA.</span><br>";
    }
    if (validar("decimal", idAlmacen)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDALMACEN.</span><br>";
    }
    if (validar("decimal", idAlmacenDestino)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDALMACENDESTINO.</span><br>";
    }
    if (validar("texto y entero", estado)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO ESTADO.</span><br>";
    }
    if (validar("decimal", idMotivoMovimiento)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO IDMOTIVOMOVIMIENTO.</span><br>";
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
        url: '../../../CONTROLADORES/inventarioController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalInventarioEdit').modal('hide');
                $('#frmEditarInventario')[0].reset();


            }

            listarInventario();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

});
idInventario='';
function cargarDatosInventario(idInventario1) {
    idInventario=idInventario1;
    var url = '../../../CONTROLADORES/inventarioController.php';
    var fecha = $('#inputFechaA');
    var hora = $('#inputHoraA');
    var importe = $('#inputImporteA');
    var tipo = $('#inputTipoA');
    var glosa = $('#inputGlosaA');
    var idAlmacen = $('#inputIdAlmacenA');
    var idAlmacenDestino = $('#inputIdAlmacenDestinoA');
    var estado = $('#inputEstadoA');
    var idMotivoMovimiento = $('#inputIdMotivoMovimientoA');
    $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idInventario},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            if (json.result != '') {
                fecha.val(json.result[0].fecha);
                hora.val(json.result[0].hora);
                importe.val(json.result[0].importe);
                tipo.val(json.result[0].tipo);
                glosa.val(json.result[0].glosa);
                idAlmacen.val(json.result[0].idAlmacen);
                idAlmacenDestino.val(json.result[0].idAlmacenDestino);
                estado.val(json.result[0].estado);
                idMotivoMovimiento.val(json.result[0].idMotivoMovimiento);

                $("#ModalInventarioEdit").modal("show");
            }
            else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                $("#loading").css("display", "none");
            }
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }
    function paginadorInventario() {
        $('#tableInventario').DataTable({
            'pagingType': 'full_numbers',
            'scrollY': '330px',
            'scrollCollapse': true,
            'paging': false,
            retrieve: true

        });
    }
    function listarInventario(){
     $('#tableInventario').DataTable().destroy();
     $('#tbodyInventario').empty();
     $.ajax({
        url: '../../../CONTROLADORES/inventarioController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarInventario'},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            for (var i = 0; i < json.result.length; i++) {
                $('#tbodyInventario').append('<tr>'+
                   '<td>'+json.result[i].fecha+
                   '<td>'+json.result[i].hora+
                   '<td>'+json.result[i].importe+
                   '<td>'+json.result[i].tipo+
                   '<td>'+json.result[i].glosa+
                   '<td>'+json.result[i].idAlmacen+
                   '<td>'+json.result[i].idAlmacenDestino+
                   '<td>'+json.result[i].estado+
                   '<td>'+json.result[i].idMotivoMovimiento+
                   '<td><button onclick="cargarDatosInventario('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarInventario('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
            }
            paginadorInventario();
            $('#loading').css('display', 'none');

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
