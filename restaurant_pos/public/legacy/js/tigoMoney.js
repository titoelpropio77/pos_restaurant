$(document).ready(function() {
	obtenerDTM();
cerrarCargando();
});


function obtenerDTM() {//obtenerDTM
	$.post('CONTROLADORES/tigoMoneyController.php',{proceso:'obtenerDTM'},function(res){
		var json = JSON.parse(res);
	        if (verificarError(json.error)) {
	            return;
	        }
	        $('#LlaveIdentificacion').val(json.result[0].llaveIdentificacion);
	        $('#LlaveEcriptacion').val(json.result[0].llaveEncriptacion);
	        $('#UrlServicioWeb').val(json.result[0].urlServicioWeb);
	}).fail(function(xhr, ajaxOptions, thrownError){
	        failErrors(xhr, ajaxOptions, thrownError);  
	    });
}

$('#frmGuardarTigoMoney').submit(function (e){
    cargando();
    errores = ""
 e.preventDefault();
LlaveIdentificacion=$('#LlaveIdentificacion').val();
LlaveEcriptacion=$('#LlaveEcriptacion').val();
UrlServicioWeb=$('#UrlServicioWeb').val();
if (LlaveIdentificacion=="") {
        errores += "<span style='font-weight: bold;'>*EL CAMPO LLAVE DE IDENTIFACION NO DEBE ESTAR VACIO.</span><br>";
}
if (LlaveEcriptacion=="") {
        errores += "<span style='font-weight: bold;'>*EL CAMPO LLAVE DE ENCRIPTACION NO DEBE ESTAR VACIO.</span><br>";
}
if (UrlServicioWeb=="") {
        errores += "<span style='font-weight: bold;'>*EL CAMPO URL SEVICIO WEB NO DEBE ESTAR VACIO.</span><br>";
}
if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });

        cerrarCargando();
        return;
    }
 var parametros = new FormData($(this)[0]);
parametros.append("proceso","guardar");
 $.ajax({
 	 type: 'POST',
        url: 'CONTROLADORES/tigoMoneyController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function(res){
        	alertify.success("GUARDADO CORRECTAMENTE");
        	$('textarea').attr('readonly',true);
        	$('#btnGuardar').attr('disabled','disabled');
        cerrarCargando();
            
        },
        error: function (r) {
        cerrarCargando();

            alert('Error del servidor');
        }
 }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
    });
});

