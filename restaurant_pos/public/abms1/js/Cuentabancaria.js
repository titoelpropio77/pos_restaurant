$(document).ready(function(){
listarCuentabancaria();
listarBanco();listarCuentacontable();});$("#frmGuardarCuentabancaria").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var idBanco = $('#inputIdBanco').val();var nroCuenta = $('#inputNroCuenta').val();var tipoCuenta = $('#inputTipoCuenta').val();var estado = $('#inputEstado').val();var razonSocial = $('#inputRazonSocial').val();var moneda = $('#inputMoneda').val();var idCuenta = $('#inputIdCuenta').val();var tarjetaDebito = $('#inputTarjetaDebito').val();var tarjetaCredito = $('#inputTarjetaCredito').val();var chekera = $('#inputChekera').val();if (validar("decimal", nroCuenta)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO NROCUENTA.</span><br>";
                         }if (validar("decimal", tipoCuenta)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO TIPOCUENTA.</span><br>";
                         }if (validar("decimal", estado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ESTADO.</span><br>";
                         }if (validar("texto y entero", razonSocial)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO RAZONSOCIAL.</span><br>";
            }if (validar("decimal", moneda)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO MONEDA.</span><br>";
                         }if (validar("decimal", tarjetaDebito)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO TARJETADEBITO.</span><br>";
                         }if (validar("decimal", tarjetaCredito)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO TARJETACREDITO.</span><br>";
                         }if (validar("decimal", chekera)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO CHEKERA.</span><br>";
                         }if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    } var parametros = new FormData($(this)[0]);
parametros.append("proceso","guardar"); $.ajax({
        type: 'POST',
        url: '../../../CONTROLADORES/cuentabancariaController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('GUARDADO CORRECTAMENTE');
                $('#ModalCuentabancariaGuardar').modal('hide');
                $('#frmGuardarCuentabancaria')[0].reset();


            }

            listarCuentabancaria();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});$("#frmEditarCuentabancaria").submit(function (e) {
    errores = ""
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();var idBanco = $('#inputIdBancoA').val();var nroCuenta = $('#inputNroCuentaA').val();var tipoCuenta = $('#inputTipoCuentaA').val();var estado = $('#inputEstadoA').val();var razonSocial = $('#inputRazonSocialA').val();var moneda = $('#inputMonedaA').val();var idCuenta = $('#inputIdCuentaA').val();var tarjetaDebito = $('#inputTarjetaDebitoA').val();var tarjetaCredito = $('#inputTarjetaCreditoA').val();var chekera = $('#inputChekeraA').val();if (validar("decimal", nroCuenta)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO NROCUENTA.</span><br>";
                         }if (validar("decimal", tipoCuenta)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO TIPOCUENTA.</span><br>";
                         }if (validar("decimal", estado)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO ESTADO.</span><br>";
                         }if (validar("texto y entero", razonSocial)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO RAZONSOCIAL.</span><br>";
            }if (validar("decimal", moneda)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO MONEDA.</span><br>";
                         }if (validar("decimal", tarjetaDebito)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO TARJETADEBITO.</span><br>";
                         }if (validar("decimal", tarjetaCredito)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO TARJETACREDITO.</span><br>";
                         }if (validar("decimal", chekera)) {
        errores += "<span style= '.font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO CHEKERA.</span><br>";
                         }if (errores != "") {
        alertify.alert("<span style='font-weight: bold;    color: red;'>ERROR</span>", errores, function () {
            alertify.message("OK");
        });
        return;
    } var parametros = new FormData($(this)[0]);
parametros.append("proceso","editar"); $.ajax({
        type: 'POST',
        url: '../../../CONTROLADORES/cuentabancariaController.php',
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          } else {
                alertify.success('EDITADO CORRECTAMENTE');
                $('#ModalCuentabancariaEdit').modal('hide');
                $('#frmEditarCuentabancaria')[0].reset();


            }

            listarCuentabancaria();
        },
        error: function (r) {

            alert('Error del servidor');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});idCuentabancaria='';
function cargarDatosCuentabancaria(idCuentabancaria1) {
    idCuentabancaria=idCuentabancaria1;
    var url = '../../../CONTROLADORES/cuentabancariaController.php';var idBanco = $('#inputIdBancoA');var nroCuenta = $('#inputNroCuentaA');var tipoCuenta = $('#inputTipoCuentaA');var estado = $('#inputEstadoA');var razonSocial = $('#inputRazonSocialA');var moneda = $('#inputMonedaA');var idCuenta = $('#inputIdCuentaA');var tarjetaDebito = $('#inputTarjetaDebitoA');var tarjetaCredito = $('#inputTarjetaCreditoA');var chekera = $('#inputChekeraA'); $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idCuentabancaria},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
            return;
          }
           if (json.result != '') {idBanco.val(json.result[0].idBanco);nroCuenta.val(json.result[0].nroCuenta);tipoCuenta.val(json.result[0].tipoCuenta);estado.val(json.result[0].estado);razonSocial.val(json.result[0].razonSocial);moneda.val(json.result[0].moneda);idCuenta.val(json.result[0].idCuenta);tarjetaDebito.val(json.result[0].tarjetaDebito);tarjetaCredito.val(json.result[0].tarjetaCredito);chekera.val(json.result[0].chekera);
                $("#ModalCuentabancariaEdit").modal("show");
            }
                 else {
                alertify.error("ERROR INTENTE NUEVAMENTE");   }
                 $("#loading").css("display", "none");
                  }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function paginadorCuentabancaria() {
    $('#tableCuentabancaria').DataTable({
        'pagingType': 'full_numbers',
        'scrollY': '330px',
        'scrollCollapse': true,
        'paging': false,
        retrieve: true

    });
}function listarCuentabancaria(){

   $('#tableCuentabancaria').DataTable().destroy();
$('#tbodyCuentabancaria').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/cuentabancariaController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarCuentabancaria'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#tbodyCuentabancaria').append('<tr>'+
                     '<td>'+json.result[i].idBanco+
                     '<td>'+json.result[i].nroCuenta+
                     '<td>'+json.result[i].tipoCuenta+
                     '<td>'+json.result[i].estado+
                     '<td>'+json.result[i].razonSocial+
                     '<td>'+json.result[i].moneda+
                     '<td>'+json.result[i].idCuenta+
                     '<td>'+json.result[i].tarjetaDebito+
                     '<td>'+json.result[i].tarjetaCredito+
                     '<td>'+json.result[i].chekera+'<td><button onclick="cargarDatosCuentabancaria('+json.result[i].id+')" class="btn btn-info"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button><button onclick="eliminarCuentabancaria('+json.result[i].id+')" class="btn btn-danger"><i class="fa fa-trash-o" aria-hidden="true"></i></button></tr>');
}
paginadorCuentabancaria();
            $('#loading').css('display', 'none');

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function listarBanco(){

$('#selectIdBanco').empty();
$('#selectIdBancoA').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/bancoController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarBanco'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#selectIdBanco').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
            $('#selectIdBancoA').append('<option value='+json.result[i].id+'>'+json.result[i].nombre); 

        }
        $('#loading').css('display', 'none');
    }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}function listarCuentacontable(){

$('#selectIdCuentacontable').empty();
$('#selectIdCuentacontableA').empty();
  
    $.ajax({
        url: '../../../CONTROLADORES/cuentacontableController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarCuentacontable'},
        success: function (res) {
            var json = $.parseJSON(res);
          if (verificarError(json.error)) {
            return;
          }
            for (var i = 0; i < json.result.length; i++) {
            $('#selectIdCuentacontable').append('<option value='+json.result[i].id+'>'+json.result[i].nombre);
            $('#selectIdCuentacontableA').append('<option value='+json.result[i].id+'>'+json.result[i].nombre); 

        }
        $('#loading').css('display', 'none');
    }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}