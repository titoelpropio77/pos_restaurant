function guardarOpProducto(){
	$('#loading').css('display','block');

	nombreSugerencia=$('#NombreSugerencia').val();
	estadoSugerencia=$('#estadoSugerencia').val();
	precioSugerencia=$('#precioSugerencia').val();
	$.post('CONTROLADORES/opProductoController.php',{proceso:'guardar',idProducto:idProducto.val(),nombreProd:nombreProd,nombreSugerencia:nombreSugerencia,estadoSugerencia:estadoSugerencia,
		precioSugerencia:precioSugerencia},function(res,status){
			var json=$.parseJSON(res);
			$('#loading').css('display','none');
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
				$('#NombreSugerencia').val("");
				$('#precioSugerencia').val(0);
				listarOpProducto();

				toastr.success("GUARDADO CORRECTAMENTE");            	
			}


		}).fail(function(xhr, ajaxOptions, thrownError){
			failErrors(xhr, ajaxOptions, thrownError);  
		});
	}

	function listarOpProducto(){
		$('#loading').css('display','block');
		tbodyOpProducto=$('#tbodyOpProducto');
		$('#ghatableOpProducto').DataTable().destroy();
		tbodyOpProducto.empty();
		$.post('CONTROLADORES/opProductoController.php',{proceso:'listar',idProducto:idProducto.val()},function(res,status){
			var json=$.parseJSON(res);
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
			}
			$('#spanOpProducto').text($('#NombreProcA').val());
			for (var i = 0; i < json.result.length; i++) {
				if (json.result[i].ESTADO=="ACTIVO") {
					tbodyOpProducto.append("<tr>"+
						"<td>"+(i+1)+
						"<td>"+json.result[i].OPCION+
						"<td>"+json.result[i].PRE_VENTA+
						"<td><button class='btn btn-danger btn-sm'>INACTIVAR</button>"
						);
				}else{
					tbodyOpProducto.append("<tr>"+
						"<td>"+i+
						"<td>"+json.result[i].OPCION+
						"<td>"+json.result[i].PRE_VENTA+
						"<td><button class='btn btn-success'>ACTIVAR</button>"
						);	
				}
				
			}
			paginatorOpProducto()
			$('#loading').css('display','none');

		}).fail(function(xhr, ajaxOptions, thrownError){
			failErrors(xhr, ajaxOptions, thrownError);  
		});
	}
	function paginatorOpProducto(){
		$('#ghatableOpProducto').DataTable({
			"pagingType": "full_numbers",
			"destroy": true,
			"order": [[0, "asc"]],
			"scrollY": "250px",
			"scrollCollapse": true,
			"paging": false,
			retrieve: true

		});
	}