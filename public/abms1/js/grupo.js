function GuardarProductoAGrupo(nombre){
	nombreGrupo=nombre;
  $('#loading').css('display','block');
	$.post('CONTROLADORES/grupoController.php',{proceso:"guardar",nombreGrupo:nombreGrupo},function(res){
	  var json = $.parseJSON(res);
	  if (json.error.length > 0) {
                if ("Error Session" === json.error) {
                    $('#loading').css('display', 'none');

                    alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
                        alertify.message('OK');
                        window.location = "../desconectar.php";
                    });


                    return;
                }
                alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
                    alertify.message('OK');
                });
                $('#loading').css('display', 'none');
                return;
            }else{
        	$('#ModalaAgregarGrupo').modal('hide');    	
        	  alertify.success('GUARDADO CORRECTAMENTE');
            }

		listarProductoGrupo(botonProducto);

	}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

function prueba2(){
	alertify.prompt("GUARDAR GRUPO","NOMBRE GRUPO", "",
  function(evt, value ){
   GuardarProductoAGrupo(value);
  
  },
  function(){
    alertify.error('Cancel');
  })  ;
}
