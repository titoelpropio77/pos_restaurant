$(document).ready(function(){
listarSucursal();
  
});
$("#formEmpresa").submit(function (e) {
    errores = ""
   

    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();

    //ruta la cual recibira nuestro archivo
    // url="@Url.Content("~/Archivo/Recibe")"

    //FormData es necesario para el envio de archivo, 
    //y de la siguiente manera capturamos todos los elementos del formulario
    // if (nombreProd == "") {
    //     errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    // }
    // if (validar("texto y entero", nombreProd)) {
    //     errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    // }
    

    if (errores != "") {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', errores, function () {
            alertify.message('OK');
        });
        return;
    }
var fecha = new Date();
 vf_Venta = fecha.getFullYear() + "-" + (fecha.getMonth() + 1) + "-" +fecha.getDate() ;
    var parametros1 = new FormData($(this)[0]);
 
        var fechaFinal = moment($('input[name=fechaLimite]').val(), 'DD/MM/YYYY');
    fechaFinal = fechaFinal.format('YYYY-MM-DD');
    parametros1.append('proceso','guardar');
    parametros1.append('fecha',vf_Venta);
    parametros1.append('fechaLimite',fechaFinal);
    //realizamos la petición ajax con la función de jquery
    $.ajax({
        type: "POST",
        url: "CONTROLADORES/docificacionController.php",
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
            alertify.success("GUARDADO CORRECTAMENTE");
           

           $('.form-control').attr('readonly',true);$('#llave').attr('readonly',true)
            

        },
        error: function (r) {

            alert("Error del servidor");
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});
function listarSucursal(){

    $.ajax({
        url: 'CONTROLADORES/sucursalController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarSucursal'},
        success: function (res) {
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
totalSucursal= json.result.length;
            for (var i = 0; i < json.result.length; i++) {
                $('#selectSucursalDoc').append('<option value="'+json.result[i].id+'">'+json.result[i].nombre
                    );
            }

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}

function obtenerEmpresa(id){

$.post('CONTROLADORES/docificacionController.php',{proceso:"obtenerEmpresa",idSucursal:id},function(res){
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
                
            }
        
if (json.result!="") {
   var fechaFinal = moment(json.result[0].FECHA_LIM, 'YYYY-MM-DD');
    fechaFinal = fechaFinal.format('DD/MM/YYYY');
    $('input[name=nombreEmp]').val(json.result[0].NOMEMP);
    $('input[name=nombreSuc]').val(json.result[0].SUCURSAL);
    $('input[name=telefono]').val(json.result[0].TELEMP);
    $('input[name=direccion]').val(json.result[0].DIREMP);
    $('input[name=paisCiudad]').val(json.result[0].CIUPAI);
    $('input[name=nit]').val(json.result[0].NITRUC);
    $('input[name=numOrden]').val(json.result[0].NUMORDEN);
    $('input[name=alfNum]').val(json.result[0].ALFA);
    $('input[name=InicioFactura]').val(json.result[0].INIFAC);
    $('input[name=finFactura]').val(json.result[0].FINFAC);
    $('input[name=facturaActual]').val(json.result[0].FACACT);
    $('input[name=numAutorizacion]').val(json.result[0].NROAUTORI);
    $('input[name=fechaLimite]').val(fechaFinal);
    $('#llave').val(json.result[0].LLAVE);
    $('input[name=turnoIni]').val(json.result[0].HINITM);
    $('input[name=turnoFin]').val(json.result[0].HFINTM);
    $('#textareaLeyenda1').val(json.result[0].LEYENDA1);
    $('#textareaLeyenda2').val(json.result[0].LEYENDA2);
  }else{
    $('input[name=nombreEmp]').val("");
    $('input[name=nombreSuc]').val("");
    $('input[name=telefono]').val("");
    $('input[name=direccion]').val("");
    $('input[name=paisCiudad]').val("");
    $('input[name=nit]').val("");
    $('input[name=numOrden]').val("");
    $('input[name=alfNum]').val("");
    $('input[name=InicioFactura]').val("");
    $('input[name=finFactura]').val("");
    $('input[name=facturaActual]').val("");
    $('input[name=numAutorizacion]').val("");
    $('input[name=fechaLimite]').val("");
    $('#llave').val("");
    $('input[name=turnoIni]').val("");
    $('input[name=turnoFin]').val("");
    $('#textareaLeyenda1').val("");
    $('#textareaLeyenda2').val("");
  }
    $('#loading').css('display','none');
     

    });
}
function leerArchivo(e) {
 nombreArchivo=$(this).val();
  extension = (nombreArchivo.substring(nombreArchivo.lastIndexOf("."))).toLowerCase();
  var archivo = e.target.files[0];

  if (!archivo) {
    return;
  }
 if (extension!=".txt") {
     alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "TIPO DE ARCHIVO NO PERMITIDO", function () {
                    alertify.message('OK');
                });
     return;
 }
  var lector = new FileReader();
  lector.onload = function(e) {
    var contenido = e.target.result;
      var lineas = contenido.split('\n');
      var contador=0;
     for(var linea of lineas) {
    console.log('[linea]', linea)

 
       if (contador==0) {
     
         $('input[name=fechaLimite]').val(linea);
            
            
       }
       if (contador==1) {
          $('input[name=numAutorizacion]').val(linea);
          
       }
       if (contador==2) {
          $('#llave').val(linea.trim());
          
       }
        contador++;
    

  }
    
    
  };
    lector.readAsText(archivo);
}
function mostrarContenido(contenido) {
 
 
}

function imprimirPrueba(){
 textareaLeyenda1=  $('#textareaLeyenda1').val();
    textareaLeyenda2=$('#textareaLeyenda2').val();
    if (impresionnube=="impresionnube") {
$.post('"http://localhost:8080/reportes/imprimirPruebaLeyenda.php',{textareaLeyenda1:textareaLeyenda1,textareaLeyenda2:textareaLeyenda2},function(res){
    });
    }
   else{
       $.post('reportes/imprimirPruebaLeyenda.php',{textareaLeyenda1:textareaLeyenda1,textareaLeyenda2:textareaLeyenda2},function(res){
    });
    }
}

document.getElementById('fotocargar')
  .addEventListener('change', leerArchivo, false);