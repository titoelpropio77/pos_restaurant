
$(document).ready(function(){
listarGestion();
});

function listarGestion(){
  $('#tbodyGestion').empty();
$.post('../CONTROLADORES/gestionController.php',{proceso:'listarGestion'},function(res){
	var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        for (var i = 0; i < json.result.length; i++) {
          $('#tbodyGestion').append('<tr>'+
            '<td>'+json.result[i].nombre_gestion+
            '<td>'+json.result[i].fecha_inicio+
            '<td>'+json.result[i].fecha_fin+
            '<td>'+json.result[i].estado+
            '<td><button class="btn btn-primary">EDITAR'
            );
        }
}).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}

$("#frmFormulario").submit(function (e) {
    errores = ""
    nombreGestion = $('#nombreGestion').val();
    fechaInicio = $('#fechaInicio').val();
    fechaFin = $('#fechaFin').val();

    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    //ruta la cual recibira nuestro archivo
    // url="@Url.Content("~/Archivo/Recibe")"

    //FormData es necesario para el envio de archivo, 
    //y de la siguiente manera capturamos todos los elementos del formulario
    if (nombreGestion == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("texto y entero", nombreGestion)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    }
    if (fechaInicio == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO FECHA INICIO NO DEBE ESTAR VACIO.</span><br>";

    }
  
    if (fechaFin == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO FECHA FIN NO DEBE ESTAR VACIO.</span><br>";

    }

    if (errores != "") {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', errores, function () {
            alertify.message('OK');
        });
        return;
    }

    var parametros1 = new FormData($(this)[0]);


    //realizamos la petición ajax con la función de jquery
    $.ajax({
        type: "POST",
        url: "../CONTROLADORES/gestionController.php",
        data: parametros1,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
           if (verificarError(json.error)) {
            return;
          }
            alertify.success("GUARDADO CORRECTAMENTE");
            $('#myModalGuardarGestion').modal("hide");

            $('#frmFormulario')[0].reset();
            listarGestion();
        },
        error: function (r) {

            alert("Error del servidor");
            location.reload();
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});
