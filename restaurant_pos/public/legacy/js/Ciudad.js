$(document).ready(function(){
listarCiudad();
listarPais();});$("#frmGuardarCiudad").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var nombre = $('#inputNombre').val();var idPais = $('#inputIdPais').val();if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    } var parametros = new FormData($(this)[0]);
parametros.append("proceso","guardar"); $.ajax({
        type: 'POST',
        url: '../../../CONTROLADORES/ciudadController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalCiudadGuardar').modal('hide');
                $('#frmGuardarCiudad')[0].reset();


            }

            listarCiudad();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});$("#frmEditarCiudad").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var nombre = $('#inputNombreA').val();var idPais = $('#inputIdPaisA').val();if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
            }if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    } var parametros = new FormData($(this)[0]);
parametros.append("proceso","editar"); $.ajax({
        type: 'POST',
        url: '../../../CONTROLADORES/ciudadController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalCiudadEdit').modal('hide');
                $('#frmEditarCiudad')[0].reset();


            }

            listarCiudad();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});idCiudad='';
function cargarDatosCiudad(idCiudad1) {
    idCiudad=idCiudad1;
    var url = '../../../CONTROLADORES/ciudadController.php';var nombre = $('#inputNombreA');var idPais = $('#inputIdPaisA'); $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idCiudad},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          }
           if (json.result != '') {nombre.val(json.result[0].nombre);idPais.val(json.result[0].idPais);
                $("#ModalCiudadEdit").modal("show");
            }
                 else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                 $("#loading").css("display", "none");
                  }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function paginadorCiudad() {
    $('#tableCiudad').DataTable({
        'pagingType': 'full_numbers',
        'scrollY': '330px',
        'scrollCollapse': true,
        'paging': false,
        retrieve: true

    });
}function listarCiudad(){

   $('#tableCiudad').DataTable().destroy();
$('#tbodyCiudad').empty();
  
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
            $('#tbodyCiudad').append('<tr>'+
                     '<td>'+json.result[i].nombre+
                     '<td>'+json.result[i].idPais+'<td><button onclick="cargarDatosCiudad('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarCiudad('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
}
paginadorCiudad();
            $('#loading').css('display', 'none');

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function listarPais(){

$('#selectIdPais').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/paisController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarPais'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#selectIdPais').append('<option value='+json.result[i].id+'>'+json.result[i].nombre); 

        }
        $('#loading').css('display', 'none');
    }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}