ventasDiarias=Array();
turno="";

$(document).ready(function() {
obteneripCliente();
 cerrarCargando();
	var hoy = new Date();
	var dd = hoy.getDate();
    var mm = hoy.getMonth() + 1; //hoy es 0!
    var yyyy = hoy.getFullYear();
    if (dd < 10) {  dd = '0' + dd }
    	if (mm < 10) {  mm = '0' + mm  }
    		hoy = yyyy + '-' + mm + '-' +dd ;
    	cargarCajero(hoy);
    	obtnerEmpresa();
    	$('#calendar').fullCalendar({

    		lang: 'es',
    		editable: true,
    		disableDragging: true,
      navLinks: false, // can click day/week names to navigate views
      selectable: true,
      selectHelper: true,

      eventMouseover: function( event, jsEvent, view ) { 
      	background: 'red';
      	textColor: 'red'
      },
      select: function(date,start, end) {
      	var moment = date;
      	fecha=moment.format('YYYY-MM-DD ');
      	cierreDiario(moment.format('YYYY-MM-DD '));
	
      	listarGrupo(moment.format('YYYY-MM-DD '));
      	listarTOTALCAJACIERRE(fecha);
      	listarFacturasAnuladas(fecha);
      	listarFacturasCredito(fecha);
      	listaGastosFecha(fecha);

      	if (archivarTotales!="") {
      		archivarTotal(fecha);
      	}

      	listarInsumos();
      },

      editable: false,
      eventLimit: true,
      color: 'yellow',   // an option!
    textColor: 'red' // an option!
      // events:
  });

    });
empresa=Array();
    function obtnerEmpresa(){//obtiene los datos de la empresa
    	cargando();
    	$.post('CONTROLADORES/docificacionController.php',{proceso:"obtenerEmpresa"},function(res){
    		var json = $.parseJSON(res);
    		if (verificarError(json.error)) {
    			return;
    		}
    		empresa=json.result;
    		cerrarCargando();
    	}).fail(function(xhr, ajaxOptions, thrownError){
    		failErrors(xhr, ajaxOptions, thrownError);  
    	});
    }
    function verificarError(error){
    	if (error.length > 0) {
    		if ("Error Session" === error) {
    			$('#loading').css('display', 'none');

    			alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
    				alertify.message('OK');
    				window.location="desconectar.php";
    			});


    			return true;
    		}
    		alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', error, function () {
    			alertify.message('OK');
    		});
    		$('#loading').css('display', 'none');
    		return true;
    	}
    }
    function productosVendidos(fecha){
    	// $.post('CONTROLADORES/')
    }
    function cierreDiario(fecha){
    	entrada="";
    	turnoMan=$('#turnoMan');
    	turnoNoc=$('#turnoNoc');
    	if (turnoMan.prop('checked') && turnoNoc.prop('checked')) {
    		entrada="LOS2TURNOS";
    	}else{
    		if (turnoMan.prop('checked')) {
    			entrada="TURNOMAÑANA";
    		}else{
    			if (turnoNoc.prop('checked')) {
    				entrada="TURNONOCHE";
    			}else{
    				alert("DEBE SELECIONAR UN TURNO O AMBOS");
    			}
    		}
    	}

    	switch (entrada){
    		case "LOS2TURNOS":
    		listarVentaDadaFecha(fecha,entrada);
    		turno="MAÑANA Y NOCHE";

    		break;
    		case "TURNOMAÑANA":
    		listarVentaDadaFecha(fecha,entrada);
    		turno="MAÑANA";

    		break;
    		case "TURNONOCHE":
    		turno="NOCHE";

    		listarVentaDadaFecha(fecha,entrada);

    		break;

    	}
// if ($('#dia1').prop('checked')) {}
// 	$.post('CONTROLADORES/cierreProdVendController.php',{proceso:'cierreDiario'},function(res){

// 	})
}
function cargarCajero(fecha){
	$('#selectCajero').empty();	
	$('#selectCajero').append("<option>");
	cargando();
	$.post('CONTROLADORES/usuarioController.php',{proceso:"listarUsuarioVenta",fecha:fecha},function(res){
		var json = $.parseJSON(res);
		if (verificarError(json.error)) {
			return;
		}
		for (var i = 0; i < json.result.length; i++) {

			$('#selectCajero').append("<option value='"+json.result[i].LOGIN+"'>"+json.result[i].LOGIN);
		}
		cerrarCargando();
	}).fail(function(xhr, ajaxOptions, thrownError){
		failErrors(xhr, ajaxOptions, thrownError);  
	});
}
tablaExcelVentasDadaFecha="";
function listarVentaDadaFecha(fecha,entrada){//ENTRADA SON LOS TURNOS CHECKEADOS
	cajero=$('#selectCajero').val();

	$('#tbodyDetalleVenta').empty();
	cargando();

	$.post('CONTROLADORES/cierreProdVendContoller.php',{proceso:'listarVentaDadaFecha',fecha:fecha,entrada:entrada,cajero:cajero,multiSuc:multiSuc},function(res){
		var json = $.parseJSON(res);
		if (verificarError(json.error)) {
			return;
		}
		ventasDiarias=json.result;
		tablaExcelVentasDadaFecha="<table class='pv tg tc fc fa gastos vd' id='tablaExcelVentasDadaFecha'><tbody>";
		tablaExcelVentasDadaFecha+=
		"<tr><td>"+empresa[0]['NOMEMP']+"<td>"+ 
		"<tr><td>"+empresa[0]['SUCURSAL']+"<td>"+ 
		"<tr><td>"+empresa[0]['DIREMP']+"<td>"+ 
		"<tr><td>REPORTE DE CAJA<td>"+ 
		"<tr><td>TURNO: "+turno+"<td>"+ 
		"<tr><td>OPERADOR : "+login+"<td>"+ 
		"<tr><td>FECHA : "+fecha+"<td>"+ 
		"<tr><td>CANTIDAD<td>PRODUCTO<td>SUBTOTAL<tbody>";
		for (var i = 0; i < json.result.length; i++) {

			var subtotal=json.result[i].AGRUPA1;
			$('#tbodyDetalleVenta').append('<tr style="background:#ffe7a2;    font-weight: bold;">'+
				'<td>'+json.result[i].CANTIDAD+
				'<td>'+json.result[i].NOM_PROD+
				'<td style="text-align: right;">'+(parseFloat(subtotal)).toFixed(2)
				);
			tablaExcelVentasDadaFecha+='<tr><td>'+json.result[i].CANTIDAD+'<td>'+json.result[i].NOM_PROD+'<td>'+(parseFloat(subtotal)).toFixed(2);
		}
		tablaExcelVentasDadaFecha+="</tbody></table>";
		cerrarCargando();

		   // paginatorProducto();
		}).fail(function(xhr, ajaxOptions, thrownError){
			failErrors(xhr, ajaxOptions, thrownError);  
		});
	}
	function listarInsumos(){
		$('#tbodyInsumo').empty();
	cargando();

		$.post('CONTROLADORES/insumoController.php',{proceso:'mostrarTodo'},function(res){
			var json = $.parseJSON(res);
			if (verificarError(json.error)) {
				return;
			}
			for (var i = 0; i < json.result.length; i++) {
				medidam="";
				medidax="";
				switch (json.result[i].OPE_MM){
					case '+':
					medidam=(parseFloat(json.result[i].STOCK_ACT)+parseFloat(json.result[i].VAL_FOR_MM)).toFixed(3);
					break;
					case '*':
					medidam=(parseFloat(json.result[i].STOCK_ACT)*parseFloat(json.result[i].VAL_FOR_MM)).toFixed(3);
					break;
					case '-':
					medidam=(parseFloat(json.result[i].STOCK_ACT)-parseFloat(json.result[i].VAL_FOR_MM)).toFixed(3);
					break;
					case '/':
					medidam=(parseFloat(json.result[i].STOCK_ACT)/parseFloat(json.result[i].VAL_FOR_MM)).toFixed(3);
					break;		  		
				}
				switch (json.result[i].OPE_MX){
					case '+':
					medidax=(parseFloat(json.result[i].STOCK_ACT)+parseFloat(json.result[i].VAL_FOR_MX)).toFixed(3);
					break;
					case '*':
					medidax=(parseFloat(json.result[i].STOCK_ACT)*parseFloat(json.result[i].VAL_FOR_MX)).toFixed(3);
					break;
					case '-':
					medidax=(parseFloat(json.result[i].STOCK_ACT)-parseFloat(json.result[i].VAL_FOR_MX)).toFixed(3);
					break;
					case '/':
					medidax=(parseFloat(json.result[i].STOCK_ACT)/parseFloat(json.result[i].VAL_FOR_MX)).toFixed(3);
					break;		  		
				}
				$('#tbodyInsumo').append('<tr style="background:#ffe7a2;  font-weight: bold;">'+
					'<td style="text-align: left;">'+json.result[i].NOM_INSUMO+
					'<td style="text-align: right;">'+json.result[i].STOCK_ACT+
					'<td style="text-align: left;">'+json.result[i].MEDIDA+
					'<td style="text-align: right;">'+medidam+
					'<td style="text-align: left;">'+json.result[i].MEDIDAM+
					'<td style="text-align: right;">'+medidax+
					'<td style="text-align: left;">'+json.result[i].MEDIDAX
				// '<td>'+json.result[i].STOCK_MIN+
				// '<td>'+json.result[i].MEDIDAM+
				// '<td>'+
				);
			}
		cerrarCargando();
		});

	}
	listaGrupo=Array();
	tablaExcelGrupo="";
	function listarGrupo(fecha){
		cargando();

		$('#tbodyGrupo').empty();
		cajero=$('#selectCajero').val();
		$.post('CONTROLADORES/relProGrupoController.php',{proceso:'mostrarFactorGrupoFecha',fecha:fecha,cajero:cajero,turno:turno},function(res){
			var json = $.parseJSON(res);
			if (verificarError(json.error)) {
				return;
			}
			listaGrupo=json.result;
			tablaExcelGrupo="<table class='pv tg tc fc fa gastos vd' id='tablaExcelGrupo'><tbody>";
			tablaExcelGrupo+=
			"<tr><td>"+empresa[0]['NOMEMP']+"<td>"+ 
			"<tr><td>"+empresa[0]['SUCURSAL']+"<td>"+ 
			"<tr><td>"+empresa[0]['DIREMP']+"<td>"+ 
			"<tr><td>REPORTE DE CAJA<td>"+ 
			"<tr><td>TURNO: "+turno+"<td>"+ 
			"<tr><td>OPERADOR : "+login+"<td>"+ 
			"<tr><td>FECHA : "+fecha+"<td>"+ 
			"<tr><td>NOMBRE DEL GRUPO<td>CANTIDAD";
			for (var i = 0; i < json.result.length; i++) {

				$('#tbodyGrupo').append('<tr >'+
					'<tr><td>'+json.result[i].NOM_GRUPO+'<td>'+(parseFloat(json.result[i].CANTIDAD)).toFixed(3)

					);


				tablaExcelGrupo+='<tr><td>'+json.result[i].NOM_GRUPO+'<td>'+json.result[i].CANTIDAD;

			}
			tablaExcelGrupo+="</tbody></table>";
		cerrarCargando();
		}).fail(function(xhr, ajaxOptions, thrownError){
			failErrors(xhr, ajaxOptions, thrownError);  
		});
	}

	function imprimir(){	
		checkProductosVendidos=$('#checkProductosVendidos').prop('checked');
		checkTotaleGrupos=$('#checkTotaleGrupos').prop('checked');
		checkGastos=$('#checkGastos').prop('checked');
		checkTotalCaja=$('#checkTotalCaja').prop('checked');
		if (!checkProductosVendidos) {
			ventasDiarias="";
		}
		checkFacturaCre=$('#checkFacturaCre').prop('checked');
		if (!checkFacturaCre) {
			listaFacturasCredito="";
		}
		if (!checkTotaleGrupos) {
			listaGrupo="";
		}
		checkFacturaAnu=$('#checkFacturaAnu').prop('checked');
		if (!checkFacturaAnu) {
			listaFacturasAnuladas="";
		}
		if (!checkGastos) {
			listarGastos="";
		}


		checkVentasDeli=$('#checkVentasDeli').prop('checked');
		saldoCaja=$('#inputTotalCaja').val();
		vendaDia=$('#inputVentaDia').val();

	// $.post('http://localhost:8080/reportes/imprimirCierreDiario.php',{fecha:fecha,login:login,turno:turno,empresa:empresa,
	// 	listaTOTALCAJACIERRE:listaTOTALCAJACIERRE,listaGrupo:listaGrupo,ventasDiarias:ventasDiarias,
	// 	checkProductosVendidos:checkProductosVendidos,checkTotaleGrupos:checkTotaleGrupos,
	// 	checkTotalCaja:checkTotalCaja,checkFacturaCre:checkFacturaCre,checkFacturaAnu:checkFacturaAnu,
	// 	checkGastos:checkGastos,checkVentasDeli:checkVentasDeli,saldoCaja:saldoCaja,vendaDia:vendaDia,listaFacturasCredito:listaFacturasCredito},function(res){

	// 	});

	if (impresionnube=="impresionnube") {
		if (isMobile.any()) {
		$.post('http://'+ipCliente+':8080/reportes/imprimirCierreDiario.php',{fecha:fecha,login:login,turno:turno,empresa:empresa,
			listaTOTALCAJACIERRE:listaTOTALCAJACIERRE,listaGrupo:listaGrupo,ventasDiarias:ventasDiarias,
			checkProductosVendidos:checkProductosVendidos,checkTotaleGrupos:checkTotaleGrupos,
			checkTotalCaja:checkTotalCaja,checkFacturaCre:checkFacturaCre,checkFacturaAnu:checkFacturaAnu,
			checkGastos:checkGastos,checkVentasDeli:checkVentasDeli,saldoCaja:saldoCaja,vendaDia:vendaDia,
			listaFacturasCredito:listaFacturasCredito,listaFacturasAnuladas:listaFacturasAnuladas,listarGastos:listarGastos},function(res){
			});	
		}else{
		$.post('http://localhost:8080/reportes/imprimirCierreDiario.php',{fecha:fecha,login:login,turno:turno,empresa:empresa,
			listaTOTALCAJACIERRE:listaTOTALCAJACIERRE,listaGrupo:listaGrupo,ventasDiarias:ventasDiarias,
			checkProductosVendidos:checkProductosVendidos,checkTotaleGrupos:checkTotaleGrupos,
			checkTotalCaja:checkTotalCaja,checkFacturaCre:checkFacturaCre,checkFacturaAnu:checkFacturaAnu,
			checkGastos:checkGastos,checkVentasDeli:checkVentasDeli,saldoCaja:saldoCaja,vendaDia:vendaDia,
			listaFacturasCredito:listaFacturasCredito,listaFacturasAnuladas:listaFacturasAnuladas,listarGastos:listarGastos},function(res){
			});	
		}
		

	}else{
		$.post('reportes/imprimirCierreDiario.php',{fecha:fecha,login:login,turno:turno,empresa:empresa,
			listaTOTALCAJACIERRE:listaTOTALCAJACIERRE,listaGrupo:listaGrupo,ventasDiarias:ventasDiarias,
			checkProductosVendidos:checkProductosVendidos,checkTotaleGrupos:checkTotaleGrupos,
			checkTotalCaja:checkTotalCaja,checkFacturaCre:checkFacturaCre,checkFacturaAnu:checkFacturaAnu,
			checkGastos:checkGastos,checkVentasDeli:checkVentasDeli,saldoCaja:saldoCaja,vendaDia:vendaDia,
			listaFacturasCredito:listaFacturasCredito,listaFacturasAnuladas:listaFacturasAnuladas,listarGastos:listarGastos},function(res){
			});
	}
}
function paginatorProducto(){
	$('#tablaVentaProducto').DataTable({
		dom: 'Bfrtip',
		buttons: [
		'copyHtml5',
		'excelHtml5',
		'csvHtml5',
		'pdfHtml5'
		],
		"bInfo" : false,
		"searching": false,
		"pagingType": "full_numbers",
		"scrollY":        "220px",
		"scrollCollapse": true,
		"paging":         false,
		retrieve: true

	})
}
listaTOTALCAJACIERRE=Array();
tablaExcelTotalCaja="";
function listarTOTALCAJACIERRE(fecha){
	empleado=$('#selectCajero').val();
	$.post('CONTROLADORES/venGralController.php',{proceso:'listarTOTALCAJACIERRE',fecha:fecha,turno:turno,empleado:empleado,multiSuc:multiSuc},function(res){
		var json = $.parseJSON(res);
		if (verificarError(json.error)) {
			return;
		}
		listaTOTALCAJACIERRE=json.result;

		if (json.result.length>0) {
			credito=parseFloat(json.result[0].credito);
			consumo=parseFloat(json.result[0].consumo);
			facturado=parseFloat(json.result[0].facturado);
			anulado=parseFloat(json.result[0].anulado);
			gastos=parseFloat(json.result[0].gastos);
			ingreso=parseFloat(json.result[0].ingreso);
			
			totalcaja=( facturado - credito - consumo -anulado-gastos+ingreso).toFixed(3);
			$('#inputTotalCaja').val(totalcaja);
			$('#inputVentaDia').val((parseFloat(json.result[0].facturado)).toFixed(3));
			tablaExcelTotalCaja="";
			tablaExcelTotalCaja="<table id='tablaExcelTotalCaja' class='pv tg tc fc fa gastos vd'><tbody>";
			tablaExcelTotalCaja+=
			"<tr><td>"+empresa[0]['NOMEMP']+"<td>"+ 
			"<tr><td>"+empresa[0]['SUCURSAL']+"<td>"+ 
			"<tr><td>"+empresa[0]['DIREMP']+"<td>"+ 
			"<tr><td>REPORTE DE CAJA<td>"+ 
			"<tr><td>TURNO: "+turno+"<td>"+ 
			"<tr><td>OPERADOR : "+login+"<td>"+ 
			"<tr><td>FECHA : "+fecha+"<td>"+ 
			"<tr><tr><td>TOTAL FACTURADO: <td>"+json.result[0].facturado+
			"<tr><td>TOTAL CREDITO: <td>"+json.result[0].credito+
			"<tr><td>TOTAL CONSUMO INTERNO: <td>"+json.result[0].consumo+
			"<tr><td>TOTAL GASTOS: <td>"+json.result[0].gastos+
			"<tr><td>TOTAL ANULADOS: <td>"+json.result[0].anulado+
			"<tr><td>TOTAL OTROS INGRESOS: <td>"+json.result[0].ingreso+
			"</tr><tr></tr><tr><td>SALDO EN CAJA: <td>"+$('#inputTotalCaja').val()+
			"<tr><td>VENTA DEL DIA : <td>"+$('#inputVentaDia').val()+"</tr></tbody></table>";

		}else{
			$('#inputTotalCaja').val("0");
			$('#inputVentaDia').val("0");
		}
		
	}).fail(function(xhr, ajaxOptions, thrownError){
		failErrors(xhr, ajaxOptions, thrownError);  
	});
}

listaFacturasCredito=Array();
tablaExcelCredito="";
function listarFacturasCredito(fecha){

	cajero=$('#selectCajero').val();
	$.post('CONTROLADORES/cierreProdVendContoller.php',{proceso:'listaFacturasCredito',fecha:fecha,cajero:cajero,turno:turno},function(res){
		var json = $.parseJSON(res);
		if (verificarError(json.error)) {
			return;
		}
		tablaExcelCredito="<table id='tablaExcelCredito' class='pv tg tc fc fa gastos vd'><tbody>";
		tablaExcelCredito+=
		"<tr><td style='    width: 225px;'>"+empresa[0]['NOMEMP']+"<td>"+ 
		"<tr><td style='    width: 225px;'>"+empresa[0]['SUCURSAL']+"<td>"+ 
		"<tr><td style='    width: 225px;'>"+empresa[0]['DIREMP']+"<td>"+ 
		"<tr><td>REPORTE DE CAJA<td>"+ 
		"<tr><td>TURNO: "+turno+"<td>"+ 
		"<tr><td>OPERADOR : "+login+"<td>"+ 
		"<tr><td>FECHA : "+fecha+"<td><tr><td><td><td></tr>"+ 
		"<tr><td>NºFAC<td>CLIENTE<td>MONTO</tr>";
		if (json.result.length>0) {
			listaFacturasCredito=json.result;


			for (var i = 0; i < json.result.length; i++) {
				tablaExcelCredito+="<tr><td>"+json.result[i].FACTURA+"<td>"+json.result[i].NOM_CLI+"<td>"+json.result[i].TOTAL_FACT;
			}
		}else{
			listaFacturasCredito="0";

		}
		tablaExcelCredito+="</tbody></table>";
		
	}).fail(function(xhr, ajaxOptions, thrownError){
		failErrors(xhr, ajaxOptions, thrownError);  
	});
}//lista facturas a credito
listaFacturasAnuladas=Array();
tablaExcelFacturasAnuladas="";
function listarFacturasAnuladas($fecha){

	$.post('CONTROLADORES/cierreProdVendContoller.php',{proceso:'listaFacturasAnuladas',fecha:fecha},function(res){
		var json = $.parseJSON(res);
		if (verificarError(json.error)) {
			return;
		}
		tablaExcelFacturasAnuladas="<table id='tablaExcelFacturasAnuladas' class='pv tg tc fc fa gastos vd'><tbody>";
		tablaExcelFacturasAnuladas+=
		"<tr><td>"+empresa[0]['NOMEMP']+"<td>"+ 
		"<tr><td>"+empresa[0]['SUCURSAL']+"<td>"+ 
		"<tr><td>"+empresa[0]['DIREMP']+"<td>"+ 
		"<tr><td>REPORTE DE CAJA<td>"+ 
		"<tr><td>TURNO: "+turno+"<td>"+ 
		"<tr><td>OPERADOR : "+login+"<td>"+ 
		"<tr><td>FECHA : "+fecha+"<td><tr><td><td><td></tr>"+ 
		"<tr><td>NºFAC<td>VENDEDOR<td>MONTO</tr>";
		if (json.result.length>0) {
			listaFacturasAnuladas=json.result;
			for (var i = 0; i < json.result.length; i++) {
				tablaExcelFacturasAnuladas+="<tr><td>"+json.result[i].FACTURA+"<td>"+json.result[i].LOGIN+"<td>"+json.result[i].TOTAL_FACT;
			}
		}else{
			listaFacturasAnuladas="0";

		}
		tablaExcelFacturasAnuladas+="</tbody></table>";
		
	}).fail(function(xhr, ajaxOptions, thrownError){
		failErrors(xhr, ajaxOptions, thrownError);  
	});
}
archivarTotales="";
function archivarTotalesChecked(checked){
	check=$(checked).prop('checked');

	if (check) {
		chekeado=$(checked).val();
		switch(chekeado){
			case 'MAÑANA':
			if ($('#turnoNoc').prop('checked')) {
				if (multiSuc==1) {
					return;	
				}
				alertify.confirm("<b color='yellow'>ADVERTENCIA</b>","<b>SI SELECCIONA LOS 2 TURNOS EJECUTARA EL CIERRE. ¿DESEA CONTINUAR?.</b>",
					function(){
						$('#archivarTotales').prop('checked',true);
						archivarTotales="archivar";
						alertify.success('Ok');
					},
					function(){
						archivarTotales="";
						alertify.error('Cancel');
					});
			}
			break;
			case 'NOCHE':
			if ($('#turnoMan').prop('checked')) {
				if (multiSuc==1) {
					return;	
				}
				alertify.confirm("<b color='yellow'>ADVERTENCIA</b>","<b>SI SELECCIONA LOS 2 TURNOS EJECUTARA EL CIERRE. ¿DESEA CONTINUAR?.</b>",
					function(){
						$('#archivarTotales').prop('checked',true);
						archivarTotales="archivar";
						alertify.success('Ok');
					},
					function(){
						archivarTotales="";
						alertify.error('Cancel');
					});
			}
			break;
			case 'archivarTotales':
			archivarTotales="archivar";
			break;

		}
	}
}

function archivarTotal(fecha){

	if ($('#archivarTotales').prop('checked')) {
		entrada="";
		turnoMan=$('#turnoMan');
		turnoNoc=$('#turnoNoc');
		if (turnoMan.prop('checked') && turnoNoc.prop('checked')) {
			entrada="LOS2TURNOS";
		}else{
			if (turnoMan.prop('checked')) {
				entrada="TURNOMAÑANA";
			}else{
				if (turnoNoc.prop('checked')) {
					entrada="TURNONOCHE";
				}else{
					alert("DEBE SELECIONAR UN TURNO O AMBOS");
					return;
				}
			}
		}
		empleado=$('#selectCajero').val();
		$.post('CONTROLADORES/venGralController.php',{proceso:'archivarTotales',fecha:fecha,empleado:empleado,entrada:entrada},function(res){
			$('#archivarTotales').prop('checked',false);
			archivarTotales="";
			alertify.success('SE HA CERRADO LAS VENTAS CORRECTAMENTE FECHA: '+fecha);
		});
	}
}

function imprimirExcel(tabla){

		// $('#tableDetalleVenta').tableExport({type:'excel',headings:true,fileName: "Reporte_ProductosVendidos"+fechaActualReporte()});
		$("body").append(tablaExcelVentasDadaFecha);
		$("body").append(tablaExcelTotalCaja);
		$("body").append(tablaExcelCredito);
		$("body").append(tablaExcelGrupo);
		$("body").append(tablaExcelFacturasAnuladas);
		$("body").append(tablaExcelGastos);
		

		$('.pv').tableExport({type:'excel',
			fileName: "CIERRE DIARIO"+fechaActualReporte(),
			excelFileFormat:'xmlss',
			worksheetName: ['PRODUCTO VENDIDOS','REPORTE DE CAJA', 'REPORTE DE VENTAS A CREDITO','GRUPOS','FACTURAS ANULADAS','GASTOS']});
		$('#tablaExcelCredito').remove();
		$('#tablaExcelGrupo').remove();
		$('#tablaExcelTotalCaja').remove();
		$('#tablaExcelFacturasAnuladas').remove();
		$('#tablaExcelVentasDadaFecha').remove();
		$('#tablaExcelGastos').remove();

	}
	function imprimirPdf(){
		$('#tablaExcelCredito').tableExport({type:'pdf',pdfmake:{enabled:true}});
	}
	listarGastos=Array();
	tablaExcelGastos="";
	function listaGastosFecha(fecha){
		$.post('CONTROLADORES/gastosController.php',{proceso:'listaroGastos',fecha:fecha,tipoTrasanccion:"EGRESO"},function(res){
			var json = $.parseJSON(res);
			if (verificarError(json.error)) {
				return;
			}
			listarGastos=json.result;
			tablaExcelGastos="<table id='tablaExcelGastos' class='pv tg tc fc fa gastos vd'><tbody>";
			tablaExcelGastos+=
			"<tr><td>"+empresa[0]['NOMEMP']+"<td>"+ 
			"<tr><td>"+empresa[0]['SUCURSAL']+"<td>"+ 
			"<tr><td>"+empresa[0]['DIREMP']+"<td>"+ 
			"<tr><td>REPORTE DE CAJA<td>"+ 
			"<tr><td>TURNO: "+turno+"<td>"+ 
			"<tr><td>OPERADOR : "+login+"<td>"+ 
			"<tr><td>FECHA : "+fecha+"<td><tr><td><td><td></tr>"+ 
			"<tr><td>NRO<td>ENTREGADO A<td>CONCEPTO<td>MONTO</tr>";
			if (json.result.length>0) {
				listaFacturasAnuladas=json.result;
				for (var i = 0; i < json.result.length; i++) {
					tablaExcelGastos+="<tr><td>"+json.result[i].ID+"<td>"+json.result[i].ENTREGADOA+"<td>"+json.result[i].CONCEPTO+"<td>"+json.result[i].MONTO;
				}
			}else{
				listarGastos="0";

			}
			tablaExcelGastos+="</tbody></table>";
		}).fail(function(xhr, ajaxOptions, thrownError){
			failErrors(xhr, ajaxOptions, thrownError);  
		});
	}
