$(document).ready(function(){
    listarTodoUsuario();
    listarSucursal();
    listarCaja();
    listarCuentabancaria();
    cerrarCargando();
});
function listarSucursal(){
    cargando();
    $('select[name=sucursal]').empty();
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
            $('select[name=sucursal]').append(' <option>SELECCIONE UN ALMACEN</option>');

            for (var i = 0; i < json.result.length; i++) {
                $('select[name=sucursal]').append('<option value="'+json.result[i].id+'">'+json.result[i].nombre);

            }
            $('#loading').css('display', 'none');
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
$("#frmUsuarioGuardar").submit(function (e) {
    cargando();
    errores = ""
    nombrePers = $('#nom_usuario').val();
    login = $('#login').val();
    cargo = $('#selectNivel option:selected').html();
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

    if (login == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO LOGIN NO DEBE ESTAR VACIO.</span><br>";
    }

    if (clave == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO CLAVE NO DEBE ESTAR VACIO.</span><br>";

    }
    if (errores != "") {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', errores, function () {
            alertify.message('OK');
        });
        cerrarCargando();
        return;
    }

    var parametros1 = new FormData($(this)[0]);
    parametros1.append('cargo',cargo);

    //realizamos la petición ajax con la función de jquery
    $.ajax({
        type: "POST",
        url: "CONTROLADORES/usuarioController.php",
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
            listarTodoUsuario();
            $('#modalUsuario').modal("hide");

            $('#frmUsuarioGuardar')[0].reset();
            cerrarCargando();
            
        },
        error: function (r) {

            alert("Error del servidor");
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

});


function listarTodoUsuario(){
    cargando()
    // tbodyHistorico.empty();
    $('#tablaUsuario').DataTable().destroy();
    $('#tbodyUsuario').empty();

    $.post("CONTROLADORES/usuarioController.php", {proceso: "listarTodo"}, function (res) {
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

            $('#tbodyUsuario').append("<tr>"+
                "<td>"+json.result[i].ID+
                "<td>"+json.result[i].NOMBRES+
                "<td>"+json.result[i].nombre+
                "<td>"+json.result[i].CARGO+
                "<td>"+json.result[i].USUARIO+
                "<td>"+json.result[i].ESTADO+
                "<td><button onclick='cargarUsuario("+json.result[i].ID+")' class='btn btn-info'><i class='fa fa-pencil-square-o' aria-hidden='true'></i></button>"+
                "<button onclick='eliminarUsuario("+json.result[i].ID+")' class='btn btn-danger'><i class='fa fa-trash-o' aria-hidden='true'></i></button>"
                );   
        }
        cerrarCargando();
        paginatorUsuario();
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
function paginatorUsuario(){
   $('#tablaUsuario').DataTable({
    "pagingType": "full_numbers",
    "destroy": true,
    "order": [[0, "asc"]],
    "scrollY": "418px",
    "scrollCollapse": true,
    "paging": false,
    retrieve: true

});
}
idUsuario="";
function cargarUsuario(id){
    cargando()
    idUsuario=id;
    nombrePers = $('#nom_usuarioA');
    login = $('#loginA');
    cargo = $('#selectCargoA');
    clave = $('#claveA');
  var  selectSucursal = $('#selectSucursalUA');
  var  selectAlmacen = $('#selectAlmacenA');

    $.post('CONTROLADORES/usuarioController.php',{proceso:'buscarXID',id:idUsuario},function(res){
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
    nombrePers.val(json.result[0].NOMBRES);
    login.val(json.result[0].USUARIO);
    clave.val(json.result[0].CLAVES);
    cargo.val(json.result[0].NIVEL);
    selectSucursal.val(json.result2[0].idSucursal);
    selectAlmacen.val(json.result2[0].alamcenPorDefecto);
    if (json.result2[0].alamcenPorDefecto==1) {
        $('#ventamultialmancenA').prop('checked',true);
    }
    $('#modalUsuarioModificar').modal('show');
cerrarCargando();
}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}


$("#frmUsuarioModificar").submit(function (e) {

cargando();
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    errores = ""
    
    nombrePers = $('#nom_usuarioA');
    login = $('#loginA');
    cargo = $('#selectCargoA option:selected').html();
    
    clave = $('#claveA');
    //ruta la cual recibira nuestro archivo
    // url="@Url.Content("~/Archivo/Recibe")"

    //FormData es necesario para el envio de archivo, 
    //y de la siguiente manera capturamos todos los elementos del formulario
    if (nombrePers == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    }

    if (login == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO LOGIN NO DEBE ESTAR VACIO.</span><br>";
    }

    if (clave == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO CLAVE NO DEBE ESTAR VACIO.</span><br>";

    }
    if (errores != "") {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', errores, function () {
            alertify.message('OK');
        });
        return;
        cerrarCargando();
    }

    var parametros1 = new FormData($(this)[0]);
    parametros1.append('cargo',cargo);
    parametros1.append("id",idUsuario);
    //realizamos la petición ajax con la función de jquery
    $.ajax({
        type: "POST",
        url: "CONTROLADORES/usuarioController.php",
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
            listarTodoUsuario();
            $('#modalUsuarioModificar').modal("hide");

            $('#frmUsuarioModificar')[0].reset();
              cerrarCargando();
            
        },
        error: function (r) {

            alert("Error del servidor");
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

});



function eliminarUsuario(id){
    cargando();
    $.post('CONTROLADORES/usuarioController.php',{proceso:'eliminarUsuario',id:id},function(res){
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
    listarTodoUsuario();
      cerrarCargando();
}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

}

function cargarDatosAlmacen(idAlmacen1) {
    cargando();
    var url = 'CONTROLADORES/almacenController.php';
    $('#selectAlmacen').empty();
    $('#loading').css('display', 'block'); $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idAlmacen1},
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
            }
            $("#loading").css("display", "none");
            for (var i = json.result.length - 1; i >= 0; i--) {
               $('#selectAlmacen').append('<option value="'+json.result[i].id+'">'+json.result[i].nombre);
           }

       }
   }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}


function listarCaja(){
cargando();
    $.ajax({
        url: 'CONTROLADORES/cajacuentaController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarCajacuenta'},
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
            }
            $('#cajaMN').append('<option value="0">SELECCIONE UNA CAJA');
            $('#cajaME').append('<option value="0">SELECCIONE UNA CAJA');
            for (var i = 0; i < json.result.length; i++) {
                if (json.result[i].moneda=='BOLIVIANO') {
                  $('#cajaMN').append('<option value="'+json.result[i].id+'">'+json.result[i].nombre);
              }else{
                $('#cajaME').append('<option value="'+json.result[i].id+'">'+json.result[i].nombre);

            }
        }
        $('#loading').css('display', 'none');

    }
}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}


function listarCuentabancaria(){

cargando();
    $.ajax({
        url: 'CONTROLADORES/cuentabancariaController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarCuentabancariaActivos'},
        success: function (res) {
            var json = $.parseJSON(res);
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
            }
            $('#bancoMN').append('<option value="0">SELECCIONE UNA CUENTA');
            $('#bancoME').append('<option value="0">SELECCIONE UNA CUENTA');
            for (var i = 0; i < json.result.length; i++) {

                if (json.result[i].moneda=="BOLIVIANO") {
                $('#bancoMN').append('<option value="'+json.result[i].id+'">'+json.result[i].banco+"|"+json.result[i].nroCuenta);
                }else{
                $('#bancoME').append('<option value="'+json.result[i].id+'">'+json.result[i].banco+"|"+json.result[i].nroCuenta);

                }
            }
            $('#loading').css('display', 'none');

        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}