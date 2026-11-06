$(document).ready(function (){
	cargarCuentasUtilizables();
  cargarCentroCosto();
  gestionActual();
});
var Tamaño = 1;
var idcuenta = ["0"];
var onclick = [false];
var indice = 0;

var debebs = ["0.00"];
var haberbs = ["0.00"];
var debesus = ["0.00"];
var habersus = ["0.00"];
idGestion="";
function gestionActual(){
  $.post('../CONTROLADORES/gestionController.php',{proceso:'gestionActual'},function(res){
    var json = JSON.parse(res);
          if (verificarError(json.error)) {
              return;
          }
          $('#spanGestionActual').text(json.result[0].nombre_gestion);
          idGestion=json.result[0].id;
  }).fail(function(xhr, ajaxOptions, thrownError){
          failErrors(xhr, ajaxOptions, thrownError);  
      });
}
function tamañoInput(id) {
  
  var valueLength = $(id).val().length;
  
    // Para que no arroje error si el input se vacía
    if (valueLength > 0) {
      
      $(id).attr('size', valueLength*2);
    }
}
function obtenerTipoCambio(){
	$.post('../CONTROLADORES/Controller.php',{proceso:'obtenerTipoCambio'},function(res){
		var json = JSON.parse(res);
	        if (verificarError(json.error)) {
	            return;
	        }
	}).fail(function(xhr, ajaxOptions, thrownError){
	        failErrors(xhr, ajaxOptions, thrownError);  
	    });
}

function agregar_datos(index,boton)
{
   
   var id = $("#idcuenta_"+index).html();
   var codigo = $("#codigocuenta_"+index).html();
   var nombre = $("#nombrecuenta_"+index).html();
   if (!verificar_repetido(id))
   {
       var Fila = Tamaño - 1;
       onclick[Fila] = true;
       idcuenta[Fila] = id;
       $("#id_"+Fila).val(id);
       $("#codigo_"+Fila).val(codigo);
       $("#cuenta_"+Fila).val(nombre);
       tamañoInput("#cuenta_"+Fila);

       $("#codigo_"+Fila).attr('type','text');
       $("#cuenta_"+Fila).attr('type','text');
       $("#centro_costo_"+Fila).attr('type','text');
       $("#agregar_"+Fila).css('display','none');
       $("#quitar_"+Fila).css('display','inline');
       $('#myModal').modal('hide');

       var fila = '\n\
       <tr id="fila_'+Tamaño+'">\n\
            <td style="vertical-align: middle;">\n\
                <CENTER>\n\
                    <a href="#" name="agregar_'+Tamaño+'" id="agregar_'+Tamaño+'" data-status=0 class="btn btn-success" onclick="lista_cuentas()" title="Agregar cuenta">\n\
                        <i class="fa fa-plus" aria-hidden="true"></i>\n\
                    </a>\n\
                    <a href="#" name="quitar_'+Tamaño+'" id="quitar_'+Tamaño+'" data-status=0 class="btn btn-danger" onclick="quitar_fila('+Tamaño+')" style="display: none" title="Eliminar">\n\
                        <i class="fa fa-trash-o" aria-hidden="true"></i>\n\
                    </a>\n\
                </CENTER>\n\
            </td>\n\
            <td style="display: none">\n\
                <input type="hidden" name="id[]" id="id_'+Tamaño+'" class="form-control" style="border:none;" readonly>\n\
            </td>\n\
            <td>\n\
                <input type="hidden" name="codigo[]" id="codigo_'+Tamaño+'" class="form-control" style="border:none;" readonly>\n\
            </td>\n\
            <td>\n\
                <input type="hidden" name="cuenta[]" id="cuenta_'+Tamaño+'" class="form-control" style="border:none;" readonly>\n\
            </td>\n\
            <td style="display: none">\n\
                <input type="hidden" name="tipo[]" id="tipo_'+Tamaño+'" class="form-control" style="border:none;" readonly>\n\
            </td>\n\
            <td onclick="bloquear_desbloquear('+Tamaño+','+"'"+'debe_bs'+"'"+')">\n\
                <input type="hidden" name="debe_bs[]" id="debe_bs_'+Tamaño+'" class="form-control" style="border:none; text-align: right;" onchange="calcular('+Tamaño+','+"'"+'debe_bs'+"'"+')" value="0.00" readonly>\n\
            </td>\n\
            <td onclick="bloquear_desbloquear('+Tamaño+','+"'"+'haber_bs'+"'"+')">\n\
                <input type="hidden" name="haber_bs[]" id="haber_bs_'+Tamaño+'" class="form-control" style="border:none; text-align: right;" onchange="calcular('+Tamaño+','+"'"+'haber_bs'+"'"+')" value="0.00" readonly>\n\
            </td>\n\
            <td onclick="bloquear_desbloquear('+Tamaño+','+"'"+'debe_sus'+"'"+')">\n\
                <input type="hidden" name="debe_sus[]" id="debe_sus_'+Tamaño+'" class="form-control" style="border:none; text-align: right;" onchange="calcular('+Tamaño+','+"'"+'debe_sus'+"'"+')" value="0.00" readonly>\n\
            </td>\n\
            <td onclick="bloquear_desbloquear('+Tamaño+','+"'"+'haber_sus'+"'"+')">\n\
                <input type="hidden" name="haber_sus[]" id="haber_sus_'+Tamaño+'" class="form-control" style="border:none; text-align: right;" onchange="calcular('+Tamaño+','+"'"+'haber_sus'+"'"+')" value="0.00" readonly>\n\
            </td>\n\
            <td style="display: none">\n\
                <input type="hidden" name="id_centro_costo[]" id="id_centro_costo_'+Tamaño+'" class="form-control" style="border:none;" readonly>\n\
            </td>\n\
            <td>\n\
                <input type="hidden" name="centro_costo[]" id="centro_costo_'+Tamaño+'" class="form-control" onclick="lista_centro_costo('+Tamaño+')" style="border:none;" readonly>\n\
            </td>\n\
        </tr>';
        $('#tabla').append(fila);
        idcuenta[Tamaño] = "0";
        onclick[Tamaño] = false;
        debebs[Tamaño] = "0.00";
        haberbs[Tamaño] = "0.00";
        debesus[Tamaño] = "0.00";
        habersus[Tamaño] = "0.00";
        Tamaño++;

   }
   else
   {
    alertify.error("LA CUENTA YA ESTA EN USO");
   }
   $('#myModalCuenta').modal('hide');
}

function verificar_repetido(id_cuenta){//este se encarga de ferificar que no pueda agregar 2 cuentas iguales
    var sw = false;
    if (Tamaño > 0) {
        for (var i = 0; i < Tamaño; i++)
        {
            if (idcuenta[i] == id_cuenta)
            {
               sw = true;
            }
        }
    }
    return sw;
}
function agregar_centro_costo(index)
{
    $("#id_centro_costo_"+indice).val($("#idcentrocosto_"+index).html());
    $("#centro_costo_"+indice).val($("#nombrecentrocosto_"+index).html());
    $('#myModal_CC').modal('hide');
}

function quitar_fila(index)
{
    idcuenta[index] = "0";

    debebs[index] = "0.00";
    haberbs[index] = "0.00";
    debesus[index] = "0.00";
    habersus[index] = "0.00";

    $("#total_debe_bs").val(parseFloat(suma_debebs()).toFixed(2));
    $("#total_haber_bs").val(parseFloat(suma_haberbs()).toFixed(2));
    $("#total_debe_sus").val(parseFloat(suma_debesus()).toFixed(2));
    $("#total_haber_sus").val(parseFloat(suma_habersus()).toFixed(2));
    $('#fila_'+index).remove();
}

function bloquear_desbloquear(index,valor)
{
    if (onclick[index])
    {
        if (valor == "debe_bs" || valor == "debe_sus")
        {
           $("#debe_bs_"+index).prop('readonly', false);
           $("#debe_sus_"+index).prop('readonly', false);
           $("#haber_bs_"+index).prop('readonly', true);
           $("#haber_sus_"+index).prop('readonly', true);
           $("#debe_bs_"+index).attr('type','text');
           $("#debe_sus_"+index).attr('type','text');
           $("#haber_bs_"+index).attr('type','hidden');
           $("#haber_sus_"+index).attr('type','hidden');
           $("#tipo_"+index).val("1");
        }
        else if (valor == "haber_bs" || valor == "haber_sus")
        {
           $("#haber_bs_"+index).prop('readonly', false);
           $("#haber_sus_"+index).prop('readonly', false);
           $("#debe_bs_"+index).prop('readonly', true);
           $("#debe_sus_"+index).prop('readonly', true);
           $("#haber_bs_"+index).attr('type','text');
           $("#haber_sus_"+index).attr('type','text');
           $("#debe_bs_"+index).attr('type','hidden');
           $("#debe_sus_"+index).attr('type','hidden');
           $("#tipo_"+index).val("2");
        }
        calcular(index,valor);
    }
}

function calcular(index,valor)
{
    var cambio = $("#tipo_cambio").val();
    if (valor == "debe_bs")
    {
        debebs[index] = $("#debe_bs_"+index).val();
        debesus[index] = (parseFloat(debebs[index]) / parseFloat(cambio)).toFixed(2);
        $("#debe_sus_"+index).val(debesus[index]);

        haberbs[index] = "0.00";
        habersus[index] = "0.00";

        $("#total_debe_bs").val(suma_debebs().toFixed(2));
        $("#total_debe_sus").val(suma_debesus().toFixed(2));

        
        $("#total_haber_bs").val(suma_haberbs().toFixed(2));
        $("#total_haber_sus").val(suma_habersus().toFixed(2));
        
    }
    else if (valor == "debe_sus")
    {
        debesus[index] = $("#debe_sus_"+index).val();
        debebs[index] = (parseFloat(debesus[index]) * parseFloat(cambio)).toFixed(2);
        $("#debe_bs_"+index).val(debebs[index]);

        habersus[index] = "0.00";
        haberbs[index] = "0.00";

        $("#total_debe_bs").val(suma_debebs().toFixed(2));
        $("#total_debe_sus").val(suma_debesus().toFixed(2));
        
        $("#total_haber_bs").val(suma_haberbs().toFixed(2));
        $("#total_haber_sus").val(suma_habersus().toFixed(2));
    }
    else if (valor == "haber_bs")
    {
        haberbs[index] = $("#haber_bs_"+index).val();
        habersus[index] = (parseFloat(haberbs[index]) / parseFloat(cambio)).toFixed(2);
        $("#haber_sus_"+index).val(habersus[index]);

        debebs[index] = "0.00";
        debesus[index] = "0.00";

        $("#total_haber_bs").val(suma_haberbs().toFixed(2));
        $("#total_haber_sus").val(suma_habersus().toFixed(2));
        
        $("#total_debe_bs").val(suma_debebs().toFixed(2));
        $("#total_debe_sus").val(suma_debesus().toFixed(2));
    }
    else if (valor == "haber_sus")
    {
        habersus[index] = $("#haber_sus_"+index).val();
        haberbs[index] = (parseFloat(habersus[index]) * parseFloat(cambio)).toFixed(2);
        $("#haber_bs_"+index).val(haberbs[index]);

        debesus[index] = "0.00";
        debebs[index] = "0.00";

        $("#total_haber_bs").val(suma_haberbs().toFixed(2));
        $("#total_haber_sus").val(suma_habersus().toFixed(2));
        
        $("#total_debe_bs").val(suma_debebs().toFixed(2));
        $("#total_debe_sus").val(suma_debesus().toFixed(2));
    }
}

function suma_debebs()
{
   var suma = 0.00;
   for (var i = 0; i < Tamaño; i++) {
       suma = suma + parseFloat(debebs[i]);
   }
   return suma;
}

function suma_debesus()
{
    var suma = 0.00;
   for (var i = 0; i < Tamaño; i++) {
       suma = suma + parseFloat(debesus[i]);
   }
   return suma;
}

function suma_haberbs()
{
    var suma = 0.00;
   for (var i = 0; i < Tamaño; i++) {
       suma = suma + parseFloat(haberbs[i]);
   }
   return suma;
}

function suma_habersus()
{
    var suma = 0.00;
   for (var i = 0; i < Tamaño; i++) {
       suma = suma + parseFloat(habersus[i]);
   }
   return suma;
}

function validar()
{
    var sw = true;
    if ($("#codigo_0").val() == "") 
    {
        toastr.error("El comprobante esta vacío");
        sw = false; 
    }
    if ($("#total_debe_bs").val() == "0.00" && $("#total_haber_bs").val() == "0.00") 
    {
        toastr.error("No se ha agregado ningún monto en los campos Debe y Haber");
        sw = false;
    }
    if ($("#glosa").val() == "") 
    {
        toastr.error("El campo Detalle de Transacción no debe estar vacio");
        sw = false; 
    }
    if ($("#total_debe_bs").val() != $("#total_haber_bs").val()) 
    {
        toastr.error("El Total tiene que cuadrar");
        sw = false; 
    }
    
    return sw;
}

function agregar() {

   var tito=$('#idcuenta').val();
    if(verificarrepetido(tito)){
         idcuenta[cont] = $('#idcuenta').val();
    cuenta = $('#idcuenta option:selected').text();
    
    debe = parseInt($('#iddebe').val());

    haber = parseInt($('#idhaber').val());
    if (isNaN(debe)) {
        debe = 0;
    }
    if (isNaN(haber)) {
        haber = 0;
    }
    tipocambio = $('#tipocambio').val();
    if (debe != "" || haber != "")
    {
        subtotaldebe[cont] = debe;
        subtotalhaber[cont] = haber;
        subtotaldebed[cont] = debe / tipocambio;
        subtotalhaberd[cont] = haber / tipocambio;
        totaldebe = totaldebe + subtotaldebe[cont];
        totalhaber = totalhaber + subtotalhaber[cont];
        totaldebed = totaldebed + subtotaldebed[cont];
        totalhaberd = totalhaberd + subtotalhaberd[cont];

        var fila = '<tr class="selected" id="fila' + cont + '"><td><button type="button" class="btn btn-danger" onclick="eliminar(' + cont + ')">X</button>\n\
<button type="button" class="btn btn-primary" onclick="modificar(' + cont + ')">MODIFICAR</button></td>\n\
<td><input type="text" name="id_cuenta[]" value="'  +idcuenta[cont] + '"/></td>\n\
<td><label>' + cuenta + '</label></td>\n\
<td><input name="debe[]" type="number"   value="' + subtotaldebe[cont] + '" id="debe' + cont + '" onclick="bloquear(this)"></input></td>\n\
<td><input name="haber[]" type="number"  value="' + subtotalhaber[cont] + '" id="haber' + cont + '" onclick="bloquear(this)"></input></td>\n\
<td><input type="number" value="' + subtotaldebed[cont].toFixed(2) + '" id="debed' + cont + '" onclick="bloquear(this)"></input></td>\n\
<td><input type="number" value="' + subtotalhaberd[cont].toFixed(2) + '" id="haberd' + cont + '" onclick="bloquear(this)"></input></td></tr>';

        $("#totaldebe").text("B/. " + totaldebe);
        $("#totalhaber").text("B/. " + totalhaber);
        $("#totaldebed").text("$/. " + totaldebed.toFixed(2));
        $("#totalhaberd").text("$/. " + totalhaberd.toFixed(2));

        $('#detalles').append(fila);
    } else {
        alert("error al ingresar el detalle");

    }
    $('#iddebe').val("");
    $('#idhaber').val("");
    cont++;
    evaluar();}

}
function eliminar(index) {
    idcuenta[index]=0;
    totaldebe = totaldebe - subtotaldebe[index];
    totalhaber = totalhaber - subtotalhaber[index];
    totaldebed = totaldebed - subtotaldebed[index];
    totalhaberd = totalhaberd - subtotalhaberd[index];

    subtotaldebe[index] = 0;
    subtotalhaber[index] = 0;

    $('#totaldebe').html("" + totaldebe);
    $('#totalhaber').html("" + totalhaber);
    $('#totaldebed').html("" + totaldebed.toFixed(2));
    $('#totalhaberd').html("" + totalhaberd.toFixed(2));
    $('#fila' + index).remove();
    evaluar();

}
function evaluar() {//sirve para activar o desactivar el guardar si en caso los totales del debe o haber estan en "0"
    if (totaldebe > 0 || totalhaber > 0) {
        $('#guardar').show();
    } else {
        $('#guardar').hide();
    }
}
function desabilitar(id, input) {
    $(input).val("");
    $('#' + id).val("0");

}
function lista_cuentas() {
   $("#myModalCuenta").modal('show');
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
              '<td><button class="btn btn-success" id="btncuenta_'+(i+1)+'" onclick="agregar_datos('+(i+1)+')">Agregar</button>'
              );

          }
	}).fail(function(xhr, ajaxOptions, thrownError){
	        failErrors(xhr, ajaxOptions, thrownError);  
	    });
}

function lista_centro_costo(index) {
   $("#myModal_CC").modal('show');
   indice = index;
}

function cargarCentroCosto(){
  $.post('../CONTROLADORES/centroCostoController.php',{proceso:'listarCentroCosto'},function(res){
    var json = JSON.parse(res);
          if (verificarError(json.error)) {
              return;
          }
          $('#tbodyCentroCosto').empty();
          for (var i = 0; i < json.result.length; i++) {
          $('#tbodyCentroCosto').append('<tr>'+
            '<td id="idcentrocosto_'+(i+1)+'" style="display: none">'+json.result[i].id+
            '<td id="codigocentrocosto_'+(i+1)+'" ">'+json.result[i].abreviatura+
            '<td id="nombrecentrocosto_'+(i+1)+'"">'+json.result[i].nombre+
            '<td><button class="btn btn-success" id="btncentrocosto_'+(i+1)+'" onclick="agregar_centro_costo('+(i+1)+')">Agregar</button>'

            );
            
          }
  }).fail(function(xhr, ajaxOptions, thrownError){
          failErrors(xhr, ajaxOptions, thrownError);  
      });
}
function agregar_centro_costo(index)
{
    $("#id_centro_costo_"+indice).val($("#idcentrocosto_"+index).html());
    $("#centro_costo_"+indice).val($("#nombrecentrocosto_"+index).html());
    $('#myModal_CC').modal('hide');
}
$("#frmGuardarAsiento").submit(function (e) {
    errores = ""
  
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();


    if (errores != "") {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', errores, function () {
            alertify.message('OK');
        });
        return;
    }

    var parametros1 = new FormData($(this)[0]);
    parametros1.append('proceso','guardar');
    parametros1.append('idGestion',idGestion);
 
    //realizamos la petición ajax con la función de jquery
    $.ajax({
        type: "POST",
        url: "../CONTROLADORES/asientoController.php",
        data: parametros1,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
          //   var json = $.parseJSON(res);
          //  if (verificarError(json.error)) {
          //   return;
          // }
            alertify.success("GUARDADO CORRECTAMENTE");

            $('#frmGuardarAsiento')[0].reset();
            location.reload();
        },
        error: function (r) {

            alert("Error del servidor");
            location.reload();
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});