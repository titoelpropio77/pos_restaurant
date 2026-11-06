
function colocarId(id){

$('#idProducto').val(id);
$('input[name=producto]').val(id);
}
function habilitarInput(index){
	cantidad=$('#cantidadInsumo'+index).attr('habilitado');
	if (cantidad==='true') {
				$('#cantidadInsumo'+index).attr('disabled',false);
	$('#cantidadInsumo'+index).attr('habilitado',false);



	}else{
	$('#cantidadInsumo'+index).attr('disabled',true);
	$('#cantidadInsumo'+index).attr('habilitado',true);
	$('#cantidadInsumo'+index).val("");
	


	}
}


function habilitarInputA(index){
	cantidad=$('#cantidadInsumoA'+index).attr('habilitado');
	if (cantidad==='true') {
				$('#cantidadInsumoA'+index).attr('disabled',false);
	$('#cantidadInsumoA'+index).attr('habilitado',false);



	}else{
	$('#cantidadInsumoA'+index).attr('disabled',true);
	$('#cantidadInsumoA'+index).val("");
	$('#cantidadInsumoA'+index).attr('habilitado',true);
	


	}
}

function CargarDatosModal(boton){//carga la cantidad y el id al modal ModalModificarCantidadInsumo
cantidad=$(boton).attr('cantidad');
id=$(boton).attr('idrelproins');
nombre=$(boton).attr('nombre');
$('input[name=CantidadRelInsPro]').val(cantidad);
$('input[name=idInsumoProducto]').val(id);
$('#nombreInsumo').text('"'+nombre+'"');

}



function CargarIdEliminarInsumo(boton){
nombre=$(boton).attr('nombreE');
id=$(boton).attr('idrelproinsE');

$('#idInsumoEliminar').val(id);
$('#nombreInsumoE').text('"'+nombre+'"');
}

function modificarCantidadInsumo(){
	$('#loading').css('display','block');

	idInsumo=$('#idInsumoProducto').val();
	CantidadRelInsPro=$('#CantidadRelInsPro').val();
	$.post('CONTROLADORES/insumoProductoController.php',{proceso:"modificarCantidadInsumo",cantidad:CantidadRelInsPro,id:idInsumo},function(res){
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
            	 alertify.success('MODIFICADO CORRECTAMENTE');
 	   listarProducto($('#selectSucursal1').val());
       $('#ModalModificarCantidadInsumo').modal('hide');
            }
 	 
	$('#loading').css('display','none');

	});
}
function eliminarInsumoProducto(){
	idInsumo=$('#idInsumoEliminar').val();
	$('#loading').css('display','block');

$.post('CONTROLADORES/insumoProductoController.php',{proceso:"EliminarInsumoProducto",id:idInsumo},function(res){
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
            	  alertify.success('ELIMINADO CORRECTAMENTE');
 	   listarProducto();
            }
 	
	$('#loading').css('display','none');
		$('#ModalEliminarInsumoProducto').modal('hide');

	});
}


function listarInsumoProducto(id,boton){
  $('input[name=productoInsumo]').val(id);
  nombre=$(boton).attr('nombreproducto');

  $('#tituloInsumoProducto').text('"'+nombre+'"');
	tbodyInsumoProducto=$('#tbodyInsumoProducto');
	tbodyInsumoProducto.empty();
	$('#loading').css('display','block');

	$.post('CONTROLADORES/insumoProductoController.php',{proceso:"listarInsumoProducto",id:id},function(res){
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
            }
	$('#loading').css('display','none');
	for (var i = 0; i < json.result.length; i++) {
		tbodyInsumoProducto.append('<tr><td>'+json.result[i].COD_INS+
			'<td>'+json.result[i].NOM_INSUMO+
			'<td>'+json.result[i].MEDIDA+
			'<td>'+json.result[i].STOCK_MIN+
            '<td><input type="checkbox" name="insumo[]" onclick="habilitarInput('+i+')" value="'+json.result[i].COD_INS+'">'+
			'<td><input type="number" name="cantidad[]" id="cantidadInsumo'+i+'" habilitado="true" disabled="true" class="form-control input-sm">');
	}
paginadorInsumoProducto();
	});
}


function paginadorInsumoProducto() {
    $('#ghatableInsumoProducto').DataTable({
        
         "pagingType": "full_numbers",
        "destroy": true,
        "order": [[0, "asc"]],
        "scrollY": "450px",
        "scrollCollapse": true,
        "paging": false,
        retrieve: true
        
    });
}
