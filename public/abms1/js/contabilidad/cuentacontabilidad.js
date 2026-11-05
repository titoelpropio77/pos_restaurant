$(document).ready(function(){
    $('#loading').css('display','none');
    listarCuenta();
    mostrarMoneda();
});

function mostrarMoneda(){
  $.post('../CONTROLADORES/monedaController.php',{proceso:'mostrarMoneda'},function(res){
    var json = JSON.parse(res);
          if (verificarError(json.error)) {
              return;
          }
          for (var i = 0; i < json.result.length; i++) {
            $('#selectMoneda').append('<option value="'+json.result[i].id+'" >'+json.result[i].nombre    );
          }
  }).fail(function(xhr, ajaxOptions, thrownError){
          failErrors(xhr, ajaxOptions, thrownError);  
      });
}
function listarCuenta(){
  $.post('../CONTROLADORES/cuentaContableController.php',{proceso:'cargarPlan'},function(res){
    var json = JSON.parse(res);
          if (verificarError(json.error)) {
              return;
          }
      $('#contenedorCuenta').append(json.result2);
       $(".file-tree").filetree();
  }).fail(function(xhr, ajaxOptions, thrownError){
          failErrors(xhr, ajaxOptions, thrownError);  
      });
}
$(function () {
    $.contextMenu({
        selector: '.Menu-Context',
        callback: function (key, options) {
            switch(key){

                case 'AGREGAR':
                   var idMenu=$(this).attr('id');
                   $.post('../CONTROLADORES/cuentaContableController.php',{proceso:'buscarcuenta',idCuenta:idMenu},function(res){
                     var json = JSON.parse(res);
                           if (verificarError(json.error)) {
                               return;
                           }
                            var cod = "";
                        var codigonuevo = "";
                        // if(typeof(json.result2[0].codigo) != "undefined"){
                        if(json.result2.length>0){
                          cod = json.result2[0].codigo;

                        }
                        if(cod != ""){
                          var codigo = cod.split(".");
                            codigo[codigo.length-2] = parseInt(codigo[codigo.length-2]) + 1;
                            codigonuevo = unir(codigo);

                        }
                        else{
                          codigonuevo = json.result[0].codigo + "1.";
                        }
                        $("#codigo").val(codigonuevo);
                        $("#padre").val(json.result[0].nombre);
                        $("#idpadre").val(json.result[0].id);
                        $("#estadohijo").val(json.result[0].hijo);
                        $("#myModalCuenta").modal('show');

                   }).fail(function(xhr, ajaxOptions, thrownError){
                           failErrors(xhr, ajaxOptions, thrownError);  
                       });

                  
                break;

                case 'MODIFICAR':
                    var idMenu=$(this).attr('id');
                break;

                case 'ELIMINAR':
                    var idMenu=$(this).attr('id');
                break;

                case 'EXPANDIR':
                    $(".folder-root").removeClass("closed").addClass("open");
                break;

                case 'CONTRAER':
                    $(".folder-root").removeClass("open").addClass("closed");
                break;
            }
        },
        items: {
            "AGREGAR": {name: "Agregar cuenta", icon: "fa-plus-circle"},
            "MODIFICAR": {name: "Modificar cuenta", icon: "fa-pencil-square-o"},
            "ELIMINAR": {name: "Eliminar cuenta", icon: "fa-trash"},
            "EXPANDIR": {name: "Expandir todo", icon: "fa-expand"},
            "CONTRAER": {name: "Contraer todo", icon: "fa-compress"},
            "LINEA": "---------",
            "CERRAR": {name: "Cerrar", icon: function(){
                return 'context-menu-icon context-menu-icon-quit';
            }}
        }
    });

    $('.Menu-Context').on('click', function (e) {
        console.log('clicked', this);
    })
});

function unir(array)
{
	var codigo="";
	for (var i = 0; i < array.length-1; i++) {
       codigo = codigo + array[i] + ".";
    }
    return codigo;
}

$("#utilizable").change(function(event) {
	    if($("#utilizable").val() == "1"){
	    	$("#hijo").val("NO");
	    	$("#valorhijo").val("0");
	    }
        else{
        	$("#hijo").val("SI");
        	$("#valorhijo").val("1");
        }
})



$("#frmFormularioGuardar").submit(function (e) {
    errores = ""
    // nombreGestion = $('#nombreGestion').val();
    // fechaInicio = $('#fechaInicio').val();
    // fechaFin = $('#fechaFin').val();

    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    //ruta la cual recibira nuestro archivo
    // url="@Url.Content("~/Archivo/Recibe")"

    //FormData es necesario para el envio de archivo, 
    //y de la siguiente manera capturamos todos los elementos del formulario
    // if (nombreGestion == "") {
    //     errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    // }
    // if (validar("texto y entero", nombreGestion)) {
    //     errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    // }
    // if (fechaInicio == "") {
    //     errores += "<span style='font-weight: bold;'>* EL CAMPO FECHA INICIO NO DEBE ESTAR VACIO.</span><br>";

    // }
  
    // if (fechaFin == "") {
    //     errores += "<span style='font-weight: bold;'>* EL CAMPO FECHA FIN NO DEBE ESTAR VACIO.</span><br>";

    // }

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
        url: "../CONTROLADORES/cuentaContableController.php",
        data: parametros1,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
           if (verificarError(json.error)) {
            return;
          }
            alertify.success("GUARDADO CORRECTAMENTE");
            $('#myModalCuenta').modal("hide");

            $('#frmFormularioGuardar')[0].reset();
            $('#contenedorCuenta').empty();
            listarCuenta();
           
        },
        error: function (r) {

            alert("Error del servidor");
            location.reload();
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});

function buscarultimacuenta(){
$.post('../CONTROLADORES/cuentaContableController.php',{proceso:'buscarultimacuenta'},function(res){
  var json = JSON.parse(res);
        if (verificarError(json.error)) {
            return;
        }
          var cod = "";
      var codigonuevo = "";
      if(typeof(json.result[0].codigo) != "undefined"){
        cod = res[0].codigo;
      }
      if(cod != ""){
        codigonuevo = (parseInt((json.result[0].codigo.replace(".", ""))) + 1) + ".";
      }
      else{
        codigonuevo = "1.";
      }
      $("#codigo").val(codigonuevo);
      $("#padre").val("NINGUNO");
      $("#idpadre").val("0");
      $("#estadohijo").val("");
      $('#myModalCuenta').modal('show');
}).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

}

