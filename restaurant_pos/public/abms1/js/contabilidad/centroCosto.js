$(document).ready(function(){
listarCentroCosto();
});
function listarCentroCosto() {
	$('#tbodyCentroCosto').empty();
	$.post('../CONTROLADORES/centroCostoController.php',{proceso:'listarCentroCosto'},function(res){
		var json = JSON.parse(res);
	        if (verificarError(json.error)) {
	            return;
	        }
	        for (var i = 0; i < json.result.length; i++) {
	        	$('#tbodyCentroCosto').append("<tr>"+
	        		"<td>"+json.result[i].nombre+
	        		"<td>"+json.result[i].abreviatura+
	        		"<td><button onclick=cargarDatos(this,"+json.result[i].id+") class='btn btn-info btn-sm'><i class='fa fa-pencil-square-o' aria-hidden='true'></i>"
	        		);
	        }
	}).fail(function(xhr, ajaxOptions, thrownError){
	        failErrors(xhr, ajaxOptions, thrownError);  
	    });
}

$("#frmFormularioGuardar").submit(function (e) {
    errores = ""
    nombre = $('#nombre').val();
    abreviatura = $('#fechaInicio').val();

    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    //ruta la cual recibira nuestro archivo
    // url="@Url.Content("~/Archivo/Recibe")"

    //FormData es necesario para el envio de archivo, 
    //y de la siguiente manera capturamos todos los elementos del formulario
    if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    }
    if (abreviatura == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO ABREVIATURA NO DEBE ESTAR VACIO.</span><br>";

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
        url: "../CONTROLADORES/centroCostoController.php",
        data: parametros1,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
           if (verificarError(json.error)) {
            return;
          }
            alertify.success("GUARDADO CORRECTAMENTE");
            $('#myModalCentroCostoGuardar').modal("hide");

            $('#frmFormularioGuardar')[0].reset();
            listarCentroCosto();
        },
        error: function (r) {

            alert("Error del servidor");
            location.reload();
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});
idCentroCosto=0;
$("#frmFormularioEditar").submit(function (e) {
    errores = ""
    nombre = $('#nombreA').val();
    abreviatura = $('#fechaInicioA').val();

    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    //ruta la cual recibira nuestro archivo
    // url="@Url.Content("~/Archivo/Recibe")"

    //FormData es necesario para el envio de archivo, 
    //y de la siguiente manera capturamos todos los elementos del formulario
    if (nombre == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("texto y entero", nombre)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    }
    if (abreviatura == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO ABREVIATURA NO DEBE ESTAR VACIO.</span><br>";

    }
  
    

    if (errores != "") {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', errores, function () {
            alertify.message('OK');
        });
        return;
    }

    var parametros1 = new FormData($(this)[0]);

parametros1.append('idCentroCosto',idCentroCosto);
    //realizamos la petición ajax con la función de jquery
    $.ajax({
        type: "POST",
        url: "../CONTROLADORES/centroCostoController.php",
        data: parametros1,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
           if (verificarError(json.error)) {
            return;
          }
            alertify.success("GUARDADO CORRECTAMENTE");
            $('#myModalCentroCostoEditar').modal("hide");

            $('#frmFormularioEditar')[0].reset();
            listarCentroCosto();
        },
        error: function (r) {

            alert("Error del servidor");
            location.reload();
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});

function cargarDatos(boton,id){
idCentroCosto=id;
nombre=$(boton).parents("tr").find("td").eq(0).text();
abreviatura=$(boton).parents("tr").find("td").eq(1).text();
$('#nombreA').val(nombre);
$('#abreviaturaA').val(abreviatura);
$('#myModalCentroCostoEditar').modal("show");
}
