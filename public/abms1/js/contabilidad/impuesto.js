$(document).ready(function (){
	cargarCuentasUtilizables();
	listarImpuestos();
});
function listarImpuestos(){

	$.post('../CONTROLADORES/impuestoController.php',{proceso:'listarImpuesto'},function(res){
		var json = JSON.parse(res);
		$('#tbodyImpuesto').empty();
	        if (verificarError(json.error)) {
	            return;
	        }
	        for (var i = json.result.length - 1; i >= 0; i--) {
	        	$('#tbodyImpuesto').append('<tr>'+
	        		'<td>'+json.result[i].nombre+
	        		'<td>'+json.result[i].valor+
	        		'<td>'+json.result[i].nombreCuenta+
	        		'<td><button class="btn btn-primary">editar</button>'
	        		);
	        }
	        
	}).fail(function(xhr, ajaxOptions, thrownError){
	        failErrors(xhr, ajaxOptions, thrownError);  
	    });
}

function cargarCuentasUtilizables(){
$.post('../CONTROLADORES/cuentaContableController.php',{proceso:'cargarCuentasUtilizables'},function(res){
	var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
        for (var i = json.result.length - 1; i >= 0; i--) {
        	$('#selectCuentaContable').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
        }
        

}).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}

$("#frmFormularioGuardarImpuesto").submit(function (e) {
    errores = ""
  
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();


    if (errores != "") {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', errores, function () {
            alertify.message('OK');
        });
        return;
    }

    var parametros1 = new FormData($(this)[0]);
    parametros1.append('proceso','guardar');
 
    //realizamos la petición ajax con la función de jquery
    $.ajax({
        type: "POST",
        url: "../CONTROLADORES/impuestoController.php",
        data: parametros1,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
          //   var json = $.parseJSON(res);
          //  if (verificarError(json.error)) {
          //   return;
          // }
            alertify.success("GUARDADO CORRECTAMENTE");

            $('#frmFormularioGuardarImpuesto')[0].reset();
            // location.reload();
        },
        error: function (r) {

            alert("Error del servidor");
            location.reload();
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});