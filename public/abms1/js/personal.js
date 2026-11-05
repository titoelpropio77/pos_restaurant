$(document).ready(function(){
    listarTodoPersonal();
    listarSucursal();
    cerrarCargando();
});
function listarSucursal(){

selectSucursal= $('select[name=sucursal]');
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
            for (var i = 0; i < json.result.length; i++) {
               selectSucursal.append("<option value='"+json.result[i].id+"'>"+json.result[i].nombre);
            }

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
$("#frmPersonalGuardar").submit(function (e) {
    errores = ""
    nombrePers = $('#nom_personal').val();
    ci = $('#ci').val();
    cargo = $('#cargo').val();
    salario = $('#salario').val()==""?"0":$('#salario').val();
    porcentajeV = $('#porcentajeV').val()==""?"0":$('#porcentajeV').val();
    clave = $('#clave').val();

    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();

    //ruta la cual recibira nuestro archivo
    // url="@Url.Content("~/Archivo/Recibe")"

    //FormData es necesario para el envio de archivo, 
    //y de la siguiente manera capturamos todos los elementos del formulario
    if (nombrePers == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("texto y entero", nombrePers)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    }
    if (ci == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO CI NO DEBE ESTAR VACIO.</span><br>";

    }
    if (validar("entero", ci)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO CI.</span><br>";
    }
    
    if (validar("decimal", salario)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO SALARIO.</span><br>";
    }
   
    if (validar("decimal", porcentajeV)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO % de venta.</span><br>";
    }
  
    if (clave == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO CLAVE NO DEBE ESTAR VACIO.</span><br>";

    }
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
        url: "CONTROLADORES/meseroController.php",
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
            listarTodoPersonal();
            $('#modalPersonal').modal("hide");

            $('#frmPersonalGuardar')[0].reset();
            
            
        },
        error: function (r) {

            alert("Error del servidor");
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});


function listarTodoPersonal(){
    // tbodyHistorico.empty();
    $('#tablaPersonal').DataTable().destroy();
    $('#tbodyPersonal').empty();

    $.post("CONTROLADORES/meseroController.php", {proceso: "listarTodo"}, function (res) {
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
        for (var i = 0; i < json.result.length; i++) {
            if (json.result[i].ESTADO=="ACTIVO") {
            $('#tbodyPersonal').append("<tr>"+
                "<td>"+json.result[i].ID+
                "<td>"+json.result[i].NOM_EMPL+
                "<td>"+json.result[i].DIR_EMPL+
                "<td>"+json.result[i].nombre+
                "<td>"+json.result[i].CI_EMPL+
                "<td>"+json.result[i].CARGO+
                "<td>"+json.result[i].SUELD_EMPL+
                "<td><button style='     width: 45px;    height: 24px;background: "+json.result[i].COLOREMP+"'>"+
                "<td><button onclick='cargarPersonal("+json.result[i].ID+")' class='btn btn-info'><i class='fa fa-pencil-square-o' aria-hidden='true'></i></button>"+
                "<button onclick='eliminarPersonal("+json.result[i].ID+")' class='btn btn-danger'><i class='fa fa-trash-o' aria-hidden='true'></i></button>"
                );   
            }
        }
        paginatorPersonal();
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}
function paginatorPersonal(){
 $('#tablaPersonal').DataTable({
        "pagingType": "full_numbers",
        "destroy": true,
        "order": [[0, "asc"]],
        "scrollY": "418px",
        "scrollCollapse": true,
        "paging": false,
        retrieve: true

    });
}
idPersonal="";
function cargarPersonal(id){
idPersonal=id;
nombrePers = $('#nom_personalA');
direccion = $('#direccionA');


    ci = $('#ciA');
    cargo = $('#cargoA');
    salario = $('#salarioA');
    porcentajeV = $('#porcentajeVA');
    clave = $('#claveA');
    colores = $('#coloresA');
    selectSucursalPer = $('#selectSucursalPerA');
$.post('CONTROLADORES/meseroController.php',{proceso:'buscarXID',id:idPersonal},function(res){
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
            nombrePers.val(json.result[0].NOM_EMPL);
    ci.val(json.result[0].CI_EMPL);
    cargo.val(json.result[0].CARGO);
    salario.val(json.result[0].SUELD_EMPL);
    porcentajeV.val(json.result[0].PORCENTAJE);
    clave.val(json.result[0].CODIGO);
    colores.val(json.result[0].COLOREMP);
    direccion.val(json.result[0].DIR_EMPL);
    selectSucursalPer.val(json.result[0].IDSUCURSAL);
            $('#modalPersonalModificar').modal('show');

}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}


$("#frmPersonalModificar").submit(function (e) {
    errores = ""
    nombrePers = $('#nom_personalA');
    direccion = $('#direccionA');
    ci = $('#ciA');
    cargo = $('#cargoA');
    salario = $('#salarioA');
    porcentajeV = $('#porcentajeVA');
    clave = $('#claveA');
    colores = $('#coloresA');

    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();

    //ruta la cual recibira nuestro archivo
    // url="@Url.Content("~/Archivo/Recibe")"

    //FormData es necesario para el envio de archivo, 
    //y de la siguiente manera capturamos todos los elementos del formulario
    if (nombrePers.val() == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("texto y entero", nombrePers.val())) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    }
    if (ci == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO CI NO DEBE ESTAR VACIO.</span><br>";

    }
    if (validar("entero", ci.val())) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO CI.</span><br>";
    }
    
    if (validar("decimal", salario.val())) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO SALARIO.</span><br>";
    }
   
    if (validar("decimal", porcentajeV.val())) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO % de venta.</span><br>";
    }
  
    if (clave.val() == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO CLAVE NO DEBE ESTAR VACIO.</span><br>";

    }
    if (errores != "") {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', errores, function () {
            alertify.message('OK');
        });
        return;
    }

    var parametros1 = new FormData($(this)[0]);
    parametros1.append("id",idPersonal);
    //realizamos la petición ajax con la función de jquery
    $.ajax({
        type: "POST",
        url: "CONTROLADORES/meseroController.php",
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
            alertify.success("MODIFICAR CORRECTAMENTE");
            listarTodoPersonal();
            $('#modalPersonalModificar').modal("hide");

            $('#frmPersonalModificar')[0].reset();
            
            
        },
        error: function (r) {

            alert("Error del servidor");
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

});



function eliminarPersonal(id){
$.post('CONTROLADORES/meseroController.php',{proceso:'eliminarPersonal',id:id},function(res){
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
            alertify.success("ELIMINADO CORRECTAMENTE");
            listarTodoPersonal();
}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

}