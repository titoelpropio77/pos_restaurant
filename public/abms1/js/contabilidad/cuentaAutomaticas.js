
$(document).ready(function(){
  cargarCuentasUtilizables();
  cargarCuentasEnTablas();
});
tipo="";
modulo="";
function  llamarCuenta(option,tipo1,modulo1){
  $('#myModalCuenta').modal('show');
  tipo=tipo1;
  modulo=modulo1;
}

function cargarCuentasEnTablas(){
  $.post('../CONTROLADORES/cuentaAutomaticaController.php',{proceso:'cargarCuentasEnTablas'},function(res){
    var json = JSON.parse(res);
    if (verificarError(json.error)) {
      return;
    }
    for (var i = json.result.length - 1; i >= 0; i--) {
      switch(json.result[i].nombreCA){
        case 'Costo Venta':

        $('#CodigoCuentaCV').text(json.result[i].codigo);
        $('#NombreCuentaCV').text(json.result[i].nombre);
        $('#btnCostoVentaGuardar').css('display','none');
        $('#btnCostoVentaEditar').css('display','block');
        break;
        case 'CC Ingreso por Ventas':
        $('#CodigoCuentaIV').text(json.result[i].codigo);
        $('#NombreCuentaIV').text(json.result[i].nombre);
        $('#btnIVGuardar').css('display','none');
        $('#btnIVEditar').css('display','block');
        break;
        case 'CC Ingreso por Servicios':
        $('#CodigoCuentaIS').text(json.result[i].codigo);
        $('#NombreCuentaIS').text(json.result[i].nombre);
        $('#btnISGuardar').css('display','none');
        $('#btnISEditar').css('display','block');
        break;
        case 'CC Dev. En Ventas':
        $('#CodigoDevEnVentas').text(json.result[i].codigo);
        $('#NombreDevEnventas').text(json.result[i].nombre);
        $('#btnDVGuardar').css('display','none');
        $('#btnDVEditar').css('display','block');
        break;
        case 'Cuenta Mercaderia en Transito':
        $('#CodigoCuentaMT').text(json.result[i].codigo);
        $('#NombreCuentaMT').text(json.result[i].nombre);
        $('#btnCuentaMTGuardar').css('display','none');
        $('#btnCuentaMTEditar').css('display','block');
        break;
        case 'Cuenta Devolucion de Compras':
        $('#CodigoCuentaDC').text(json.result[i].codigo);
        $('#NombreCuentaDC').text(json.result[i].nombre);
        $('#btnCuentaDCGuardar').css('display','none');
        $('#btnCuentaDCEditar').css('display','block');
        break;
        case 'Cuenta CF devolucion Compra':
        $('#CodigoCuentaCFDC').text(json.result[i].codigo);
        $('#NombreCuentaCFDC').text(json.result[i].nombre);
        $('#btnCuentaCFDCGuardar').css('display','none');
        $('#btnCuentaCFDCEditar').css('display','block');
        break;
        case 'Cuenta descuento Compra':
        $('#CodigoCuentaDescuentoC').text(json.result[i].codigo);
        $('#NombreCuentaDescuentoC').text(json.result[i].nombre);
        $('#btnCuentaDescuentoCGuardar').css('display','none');
        $('#btnCuentaDescuentoCEditar').css('display','block');
        break;
        case 'CC Ajuste Inventario':
        $('#CodigoAjusteInv').text(json.result[i].codigo);
        $('#NombreAjusteInv').text(json.result[i].nombre);
        $('#btnCuentaAjusteInvGuardar').css('display','none');
        $('#btnCuentaAjusteInvEditar').css('display','block');
        break;
        case 'CC Ingreso Sobrante':
        $('#CodigoIngresoSob').text(json.result[i].codigo);
        $('#NombreIngresoSob').text(json.result[i].nombre);
        $('#btnCuentaEgresoSobGuardar').css('display','none');
        $('#btnEgresoSobEditar').css('display','block');
        break;
        case 'CC Egreso Faltante':
        $('#CodigoEgresoFal').text(json.result[i].codigo);
        $('#NombreEgresoFal').text(json.result[i].nombre);
        $('#btnCuentaEgresoFalGuardar').css('display','none');
        $('#btnCuentaEgresoFalEditar').css('display','block');
        break;
              // case 'Ingreso por Ventas'
              // break;
            }
          }
          
        }).fail(function(xhr, ajaxOptions, thrownError){
          failErrors(xhr, ajaxOptions, thrownError);  
        });
      }
      function cargarCuentasUtilizables(){
        $('#tbodyCuenta').empty();

        $.post('../CONTROLADORES/cuentaContableController.php',{proceso:'cargarCuentasUtilizables'},function(res){
          var json = JSON.parse(res);
          if (verificarError(json.error)) {
           return;
         }
         for (var i = 0; i < json.result.length; i++) {

          $('#tbodyCuenta').append('<tr>'+
           ' <td id="idcuenta_'+(i+1)+'" style="display: none">'+json.result[i].id+'</td>'+
           '<td id="codigocuenta_'+(i+1)+'">'+json.result[i].codigo+
           '<td id="nombrecuenta_'+(i+1)+'">'+json.result[i].nombre+
           '<td><button class="btn btn-success" id="btncuenta_'+(i+1)+'" onclick="guardarCuentasAutomaticas('+json.result[i].id+')">Agregar</button>'
           );

        }
      }).fail(function(xhr, ajaxOptions, thrownError){
       failErrors(xhr, ajaxOptions, thrownError);  
     });
    }
    function guardarCuentasAutomaticas(idCuenta){

      $.post('../CONTROLADORES/cuentaAutomaticaController.php',{proceso:'guardar',tipo:tipo,modulo:modulo,
        idCuenta :idCuenta},function(res){
          var json = JSON.parse(res);
          if (verificarError(json.error)) {
            return;
          }
          cargarCuentasEnTablas();
          $('#myModalCuenta').modal('hide');
        }).fail(function(xhr, ajaxOptions, thrownError){
          failErrors(xhr, ajaxOptions, thrownError);  
        });
      }