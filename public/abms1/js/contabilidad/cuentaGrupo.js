
$(document).ready(function(){
	mostrarPadres();
	mostrarGrupos();
});

function mostrarPadres(){
	$.post('../CONTROLADORES/cuentaGrupoController.php',{proceso:'mostrarPadres'},function(res){
		var json = JSON.parse(res);
	        if (verificarError(json.error)) {
	            return;
	        }
	        for (var i = 0; i < json.result.length; i++) {
	        	$('#tbodyCuentaPadre').append('<tr>'+
	        		'<td>'+json.result.codigo+
	        		'<td>'+json.result.nombre+
	        		'<td><button class="btn btn-success">'
	        		);
	        }
	}).fail(function(xhr, ajaxOptions, thrownError){
	        failErrors(xhr, ajaxOptions, thrownError);  
	    });


}

function mostrarGrupos(){
	$.post('../CONTROLADORES/cuentaGrupoController.php',{proceso:'mostrarGrupos'},function(res){
		var json = JSON.parse(res);
	        if (verificarError(json.error)) {
	            return;
	        }
	}).fail(function(xhr, ajaxOptions, thrownError){
	        failErrors(xhr, ajaxOptions, thrownError);  
	    });
}
