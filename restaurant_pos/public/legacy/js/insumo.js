$(document).ready(function(){
  listarSucursales();
  listarUnidadmedida();
  listarInsumo(1);
  listarAlmacen();
});

proceso=0; 
var fecha = new Date();
vf_Venta = fecha.getFullYear() + "-" + (fecha.getMonth() + 1) + "-" +fecha.getDate() ;
$('input[name=fechaActual]').val(vf_Venta);
function guardarInsumos(proceso){// este proceso se encarga de modificar y guardar
	proceso=proceso;//0 =guardar ; 1= modificar
	error="";
	NombreInsumo=$('#NombreInsumo').val();
	MedidaVenta=$('#MedidaVenta').val();
	StockMinimo=$('#StockMinimo').val();
	StockActual=$('#StockActual').val();
	MedidaMedia=$('#MedidaMedia').val();
	OperadorMedia=$('#OperadorMedia').val();
	EquivalenciaMedia=$('#EquivalenciaMedia').val();
	MedidaMaxima=$('#MedidaMaxima').val();
	OperadorMaxima=$('#OperadorMaxima').val();
	EquivalenciaMaxima=$('#EquivalenciaMaxima').val();
	if (NombreInsumo===""){
		error+="<span style='font-weight: bold;'>EL CAMPO NOMBRE ES OBLIGATORIO.<span style='font-weight: bold;'><br>";
	}
	if (validar('texto',MedidaVenta && MedidaVenta!='')) {
		error+="<span style='font-weight: bold;'>NO INGRESAR CARACTERES ESPECIALES NI NUMEROS EN EL CAMPO MEDIDA VENTA.</span><br>";
	}
	
	if (validar('decimal',StockMinimo) && StockMinimo!='') {
		error+="<span style='font-weight: bold;'>NO INGRESAR CARACTERES ESPECIALES NI LETRAS EN EL CAMPO STOCK MINIMO.</span><br>";
	}
	if (StockMinimo===""){
		error+="<span style='font-weight: bold;'>EL CAMPO STOCK MINIMO ES OBLIGATORIO.<span style='font-weight: bold;'><br>";
	}
	if (validar('decimal',StockActual) && StockActual!='') {
		error+="<span style='font-weight: bold;'>NO INGRESAR CARACTERES ESPECIALES NI LETRAS EN EL CAMPO STOCK MINIMO.<s/pan><br>";
	}
	if (StockActual===""){
		error+="<span style='font-weight: bold;'>EL CAMPO STOCK ACTUAL ES OBLIGATORIO.</span><br>";
	}
	if (validar('decimal',EquivalenciaMedia) && EquivalenciaMedia!='') {
		error+="<span style='font-weight: bold;'>NO INGRESAR CARACTERES ESPECIALES NI LETRAS EN EL CAMPO EQUIVALENCIA.</span><br>";
	}
	if (validar('decimal',EquivalenciaMaxima) && EquivalenciaMaxima!='') {
		error+="<span style='font-weight: bold;'>NO INGRESAR CARACTERES ESPECIALES NI LETRAS EN EL CAMPO EQUIVALENCIA.</span><br>";
	}
	if (error!="") {
		alertify.alert('<span style="font-weight: bold;">ADVERTENCIA</span>',	error, function(){
      alertify.message('OK');
    });
	}
	else{
		
   $('#loading').css('display','block');
   url="../CONTROLADORES/insumoController.php";
   $.ajax({
    url:url,
    type:'POST',
    typedata:'json',
    data:{proceso:"guardar",NombreInsumo:NombreInsumo,MedidaVenta:MedidaVenta,StockMinimo:StockMinimo,StockActual:StockActual
    ,MedidaMedia:MedidaMedia,OperadorMedia:OperadorMedia,EquivalenciaMedia:EquivalenciaMedia,MedidaMaxima:MedidaMaxima
    ,OperadorMaxima:OperadorMaxima,EquivalenciaMaxima:EquivalenciaMaxima,fecha:vf_Venta},
    success:function(res){
      $('#loading').css('display','none');

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
       alertify.success('GUARDADO CORRECTAMENTE');
       location.reload();


     }


   },
 }).fail(function(xhr, ajaxOptions, thrownError){
  failErrors(xhr, ajaxOptions, thrownError);  
});
}

}
reporteStockExcel="";
function listarInsumo(idAlmacen){
  $('#tableInsumo').DataTable().destroy();
  $('#tbodyInsumo').empty();
  $.post('../CONTROLADORES/insumoController.php',{proceso:'listarInsumo',idAlmacen:idAlmacen},function(res){
    var json = JSON.parse(res);
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
      reporteStockExcel="<table id='reporteStockExcel'><tr><td>NOMBRE<td>UNIDAD DE VENTA<td>STOCK ACTUAL<td>MEDIDA MEDIA<td>STOCK ACTUAL";
      reporteStockExcel+="<tbody>";
      for (var i = 0; i < json.result.length; i++) {
      var  stockActualMM=0;
        if (json.result[i].MEDIDAM!="" && json.result[i].MEDIDAM!=null && json.result[i].OPE_MM!=null && json.result[i].OPE_MM!="") {
        stockActualMM=eval(json.result[i].stockActual+json.result[i].OPE_MM+json.result[i].VAL_FOR_MM);
      unidad=json.result[i].MEDIDAM;

      }  
      reporteStockExcel+="<tr>"+
      '<td>'+json.result[i].NOM_INSUMO+
        '<td>'+json.result[i].MEDIDA+
        '<td>'+json.result[i].stockActual+
        '<td>'+json.result[i].MEDIDAM+
        '<td>'+parseFloat(stockActualMM).toFixed(2);

       $('#tbodyInsumo').append('<tr>'+
        '<td>'+json.result[i].COD_INS+
        '<td>'+json.result[i].NOM_INSUMO+
        '<td>'+json.result[i].MEDIDA+
        '<td>'+json.result[i].stockActual+
        '<td>'+json.result[i].MEDIDAM+
        '<td>'+parseFloat(stockActualMM).toFixed(2)+

        '<td>'+json.result[i].STOCK_MIN+
        '<td>'+json.result[i].ESTADO+
        '<td><button title="MODIFICAR INSUMO" onclick="cargarDatos('+json.result[i].ID+')"  class="btn btn-info" title="EDITAR INSUMO" data-toggle="modal" data-target="#ModalModificarInsumos"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button>    <button class="btn btn-danger"  title="ELIMINAR INSUMO"><i class="fa fa-trash-o" aria-hidden="true"></i></button>'
        );
      }
paginatorInsumo();
  }).fail(function(xhr, ajaxOptions, thrownError){
          failErrors(xhr, ajaxOptions, thrownError);  
      });
}

function cargarDatos(id){
	
  url='../CONTROLADORES/insumoController.php';
  NombreInsumo=$('#NombreInsumoA');
  MedidaVenta=$('#MedidaVentaA');
  StockMinimo=$('#StockMinimoA');
  StockActual=$('#StockActualA');
  MedidaMedia=$('#MedidaMediaA');
  OperadorMedia=$('#OperadorMediaA');
  EquivalenciaMedia=$('#EquivalenciaMediaA');
  MedidaMaxima=$('#MedidaMaximaA');
  OperadorMaxima=$('#OperadorMaximaA');
  EquivalenciaMaxima=$('#EquivalenciaMaximaA');
  $('#idInsumo').val(id);
  $.ajax({
    url:url,
    type:'POST',
    typedata:'json',
    data:{proceso:'cargarDatos',id:id},
    success:function(res){
     var json = $.parseJSON(res);
     if (json.error.length > 0) {
      if ("Error Session" === json.error) {
        $('#loading').css('display', 'none');

        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
          alertify.message('OK');
          window.parent.location = "../../desconectar.php";
        });


        return;
      }
      alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
        alertify.message('OK');
      });
      $('#loading').css('display', 'none');
      return;
    }

    NombreInsumo.val(json.result[0]['NOM_INSUMO']);
    MedidaVenta.val(json.result[0]['MEDIDA']);
    StockMinimo.val(json.result[0]['STOCK_MIN']);
    StockActual.val(json.result[0]['STOCK_ACT']);
    MedidaMedia.val(json.result[0]['MEDIDAM']);
    OperadorMedia.val(json.result[0]['OPE_MM']);


    EquivalenciaMedia.val(json.result[0]['VAL_FOR_MM']);
    MedidaMaxima.val(json.result[0]['MEDIDAX']);
    OperadorMaxima.val(json.result[0]['OPE_MX']);
    EquivalenciaMaxima.val(json.result[0]['VAL_FOR_MX']);

// for (var i = 0; i < json.result[0].length; i++) {
// 	alert(res);
// }
}

}).fail(function(xhr, ajaxOptions, thrownError){
  failErrors(xhr, ajaxOptions, thrownError);  
});

}
// function actualizarDatos(){
// 	url='../CONTROLADORES/insumoController.php';
// 	NombreInsumo=$('#NombreInsumoA');
// 	MedidaVenta=$('#MedidaVentaA');
// 	StockMinimo=$('#StockMinimoA');
// 	StockActual=$('#StockActualA');
// 	MedidaMedia=$('#MedidaMediaA');
// 	OperadorMedia=$('#OperadorMediaA');
// 	EquivalenciaMedia=$('#EquivalenciaMediaA');
// 	MedidaMaxima=$('#MedidaMaximaA');
// 	OperadorMaxima=$('#OperadorMaximaA');
// 	EquivalenciaMaxima=$('#EquivalenciaMaximaA');
// 	$.ajax({
// 		url:url,
// 		type:'POST',
// 		datatype:'json',
// 		data:{id:id,NombreInsumo:NombreInsumo,MedidaVenta:MedidaVenta,StockMinimo:StockMinimo,StockActual:StockActual,
// 			MedidaMedia:MedidaMedia,OperadorMaxima:OperadorMaxima,EquivalenciaMaxima:EquivalenciaMaxima}
// 			});
// 		}
// // function listarInsumo(){
// // 	$.post('../CONTROLADORES/insumoController.php',{proceso:})
// // }
$("#frmModificarInsumos").submit(function (e) {
          //esto evita que se haga la petición común, es decir evita que se refresque la pagina
          e.preventDefault();
          //ruta la cual recibira nuestro archivo
          // url="@Url.Content("~/Archivo/Recibe")"
          //FormData es necesario para el envio de archivo, 
          //y de la siguiente manera capturamos todos los elementos del formulario
          var parametros1=new FormData($(this)[0]);
          
          //realizamos la petición ajax con la función de jquery
          $.ajax({
            type: "POST",
            url:"../CONTROLADORES/insumoController.php",
            data: parametros1,
              contentType: false, //importante enviar este parametro en false
              processData: false, //importante enviar este parametro en false
              success: function (res) {
               var json = $.parseJSON(res);

               if (json.error.length > 0) {
                if ("Error Session" === json.error) {
                  $('#loading').css('display', 'none');

                  alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
                    alertify.message('OK');
                    window.parent.location = "../../desconectar.php";
                  });


                  return;
                }
                alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
                  alertify.message('OK');
                });
                $('#loading').css('display', 'none');
                return;
              }else{


              }
              alertify.success("GUARDADO CORRECTAMENTE");
              $('#ModalModificarInsumos').modal("hide");
          // listar();
          $('#frmModificarInsumos')[0].reset();
         // $('#foto').attr('src','imagenes/imagenboton/food.svg');
         location.reload();
       },
       error: function (r) {

        alert("Error del servidor");
      }
    }).fail(function(xhr, ajaxOptions, thrownError){
      failErrors(xhr, ajaxOptions, thrownError);  
    });

  });


$("#frmAumentarStock").submit(function (e) {
          //esto evita que se haga la petición común, es decir evita que se refresque la pagina
          e.preventDefault();
          recepcion=$('input[name=recepcion]').val();
          total=$('#totalStock').val();
          if (recepcion=="" && total=="") {
           alert("DEBE INGRESAR LA CANTIDAD A RECEPCIONAR");
           return false;
         }
          //ruta la cual recibira nuestro archivo
          // url="@Url.Content("~/Archivo/Recibe")"

          //FormData es necesario para el envio de archivo, 
          //y de la siguiente manera capturamos todos los elementos del formulario
          var parametros1=new FormData($(this)[0]);
          
          //realizamos la petición ajax con la función de jquery
          $.ajax({
            type: "POST",
            url:"../CONTROLADORES/insumoController.php",
            data: parametros1,
              contentType: false, //importante enviar este parametro en false
              processData: false, //importante enviar este parametro en false
              success: function (res) {
               var json=  $.parseJSON(res);
               if (json.error.length > 0) {
                if ("Error Session" === json.error) {
                  $('#loading').css('display', 'none');

                  alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
                    alertify.message('OK');
                    window.parent.location = "../../desconectar.php";
                  });
                  return;
                }
                alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
                  alertify.message('OK');
                });
                $('#loading').css('display', 'none');
                return;
              }
              alertify.success("GUARDADO CORRECTAMENTE");
              $('#ModalAumentarStock').modal("hide");
          // listar();
          $('#frmAumentarStock')[0].reset();
         // $('#foto').attr('src','imagenes/imagenboton/food.svg');

         location.reload();
       },
       error: function (r) {

        alert("Error del servidor");
      }
    }).fail(function(xhr, ajaxOptions, thrownError){
      failErrors(xhr, ajaxOptions, thrownError);  
    });

  });


function InsumoRecepcionPerdida(){//ESTA FUNCION HACE LA RESTA ENTRE EL CAMPO RECEPCION MENOS PERDIDA MODAL AUMETAR STOCK
  recepcion=$('input[name=recepcion]').val();
  perdida=$('input[name=perdida]').val();
  total=(parseFloat(recepcion)-parseFloat(perdida)).toFixed(3);

  $('#totalStock').val(total);
}

function obtenerSeleccion(){
  $('#tbodyInsumosSelect').empty();
  $('#ghatable').DataTable().$('input[type="checkbox"]').each(function () {
            // If checkbox doesn't exist in DOM

                // If checkbox is checked
                if (this.checked) {

                     // var gridNum = $(this).closest("tr").find("input[name=factorProducto]");
//                     Create a hidden element 
var codigo = $(this).closest("tr").find("td:eq(0)").text();
var nombre = $(this).closest("tr").find("td:eq(1)").text();
$('#tbodyInsumosSelect').append('<tr>'+
  '<td>'+codigo+
  '<td>'+nombre+
  '<td><input type="number" class="form-control" name="cantInsumo">'
  );
}
});
  $('#ModalInsumosSelect').modal('show');
}
function listarSucursales(){
  $('#divSucursales').empty();
  $.post('../CONTROLADORES/ipSucursalController.php',{proceso:'listarSucursales'},function(res){
    var json=  $.parseJSON(res);
    if (json.error.length > 0) {
      if ("Error Session" === json.error) {
        $('#loading').css('display', 'none');

        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
          alertify.message('OK');
          window.parent.location = "../../desconectar.php";
        });
        return;
      }
      alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
        alertify.message('OK');
      });
      $('#loading').css('display', 'none');
      return;
    }
    for (var i = 0; i < json.result.length; i++) {
      $('#divSucursales').append("<label><input nombre='"+json.result[i].nombreSucursal+"' value='"+json.result[i].ipSucursal+"' name='ipSucursales' type='radio'>"+json.result[i].nombreSucursal+"</label>");
    }
  });
}

function realizarTraspaso(){
  var ipSucursal=$('input[name=ipSucursales]:checked');
  var nombreSuc=ipSucursal.attr("nombre");
  if (idSucursal.val()==undefined) {
   alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE SELECCIONAR UNA SUCURSAL", function () {
    alertify.message('OK');
  });
   return;
 }
 var  tabla = $('#tablaInsumosSelect input[name=cantInsumo]');
 var codigoA=Array();
 var cantidadA=Array();
 var nombreInsumoA=Array();
 if (tabla.length > 0) {
  $(tabla).each(function (e) {
    var cantidad = $(this).closest("tr").find("input[name=cantInsumo]").val();
    var codigo = $(this).parents("tr").find("td").eq(0).text();
    var insumo = $(this).parents("tr").find("td").eq(2).text();
    codigoA.push(codigo);
    cantidadA.push(cantidad);
    nombreInsumoA.push(insumo);
    if (cantidad=="") {
      alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "LA CANTIDAD NO DEBE ESTAR VACIA", function () {
        alertify.message('OK');
      });
      return;
    }
  });

  $.post('../CONTROLADORES/kardexController.php',{proceso:'traspasarInsumos',fecha:vf_Venta,nombreInsumo:nombreInsumoA,cantidad:cantidadA,idInsumo:codigoA,ipSucursal:ipSucursal,nombreSucursal:nombreSucursal},function(res){

  });
}
}


function listarUnidadmedida(){
  $.ajax({
    url: '../CONTROLADORES/unidadmedidaController.php',
    type: 'POST',
    typeData: 'json',
    data: {proceso: 'listarUnidadmedida'},
    success: function (res) {
      var json = $.parseJSON(res);
      if (verificarError(json.error)) {
        return;
      }
      for (var i = 0; i < json.result.length; i++) {
        $('.medidaVenta').append('<option value="'+json.result[i].id+'">'+json.result[i].abreviatura);
      }
      $('#loading').css('display', 'none');
    }
  }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
  });
}


function listarAlmacen(){
  $.ajax({
    url: '../CONTROLADORES/almacenController.php',
    type: 'POST',
    typeData: 'json',
    data: {proceso: 'listarAlmacen'},
    success: function (res) {
      var json = $.parseJSON(res);
      if (verificarError(json.error)) {
        return;
      }
      for (var i = 0; i < json.result.length; i++) {
        $('#idAlmacen').append("<option value='"+json.result[i].id+"'>"+json.result[i].nombre);
      }
    }
  }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
  });
}

function paginatorInsumo(){
$('#tableInsumo').DataTable({
        'pagingType': 'full_numbers',
        'scrollY': '330px',
        'scrollCollapse': true,
        'paging': false,
        retrieve: true

    });
}

function reporteExcelInsumo() {
    $("body").append(reporteStockExcel);
$('#reporteStockExcel').tableExport({type:'excel',headings:true,fileName: "Reporte_INSUMOS"+fechaActualReporte()});
    $('#reporteStockExcel').remove();
}