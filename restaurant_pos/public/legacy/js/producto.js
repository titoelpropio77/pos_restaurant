$(document).ready(function () {
    cerrarCargando();
    listarSucursal();
    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": "toast-bottom-center",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    }
    $('#loading').css("display",'none');
});
modifTodasScu=0;
function ponerPrecioSucursal(input){
   var tabla=$('#tableSucursal tbody tr');
   if (tabla.length > 0) {
       $(tabla).each(function (e) {
        var inputCant = $(this).closest("tr").find("input[tipo=precioNormal]");
        $(inputCant).val($(input).val());
    });
   }
}

idProducto = 0;
nombreProd = "";
function verificarError(error){
    if (error.length > 0) {
        if (verificarError(json.error)) {
            return;
        }
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', error, function () {
            alertify.message('OK');
        });
        $('#loading').css('display', 'none');
        return true;
    }
}

totalSucursal=0;
function listarSucursal(){

    $.ajax({
        url: 'CONTROLADORES/sucursalController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listarSucursal'},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            totalSucursal= json.result.length;
            for (var i = 0; i < json.result.length; i++) {
                $('#tbodySucursal').append('<tr>'+
                    '<td><label>'+json.result[i].nombre+'<input checked value="'+i+'" type="checkbox" name="checkboxSucProd[]"></label><input name="idSucursal[]" type="hidden" value="'+json.result[i].id+'">'+
                    '<td ><select tipo="CategoriaProd" id="idCategoriaProd'+json.result[i].id+'" name="idCategoriaProd[]" class="form-control"></select>'+
                    '<td ><input name="precioNormal[]" tipo="precioNormal" type="number" class="form-control">'+
                    '<td><input name="precioMayorista[]" type="number" class="form-control">'+
                    '<td> <input name="precio3[]" type="number" class="form-control">'+
                    '<td ><input name="precio4[]" type="number" class="form-control">'+
                    '<td ><input name="cocina[]" type="checkbox" >'+
                    '<td ><input name="punto1[]" type="checkbox" >'+
                    '<td ><input name="punto2[]" type="checkbox" >'+
                    '<td ><input name="punto3[]" type="checkbox" >'+
                    '<td ><input name="punto4[]" type="checkbox" >'
                    );
                $('#selectSucursal1').append('<option value="'+json.result[i].id+'">'+json.result[i].nombre);
                $('#idSucusalCategoria').append('<tr>'+
                    '<td><input name="idSucursal[]" type="hidden" value="'+json.result[i].id+'">'+json.result[i].nombre+
                    '<td ><input name="checkboxSucCat[]" value="'+json.result[i].id+'"  type="checkbox" >');
            }
            $('#loading').css('display', 'none');
            cargarSelectSuc();
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
idSuc=0;
var listarProducto = function (idSucursal) {
    cargando();
    idSuc=idSucursal;
    datos = $('#datos');
    datos.empty();
    fila = "";
    $.ajax({
        url: 'CONTROLADORES/productoController.php',
        type: 'POST',
        typeData: 'json',
        data: {proceso: 'listar',idSucursal:idSucursal},
        success: function (res) {

            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            fila = json.result;
            datos.append(fila);
            paginadorProducto();
            $('#loading').css('display', 'none');

        }
        
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

}

$("#frmFormulario").submit(function (e) {
    errores = ""
    nombreProd = $('#nom_prod').val();
    cantidad = $('#cantidad').val();
    unidad = $('#unid').val();
    pre_venta = $('#pre_venta').val();
    selectGrupo = $('#selectGrupo').val();

    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();

    //ruta la cual recibira nuestro archivo
    // url="@Url.Content("~/Archivo/Recibe")"

    //FormData es necesario para el envio de archivo, 
    //y de la siguiente manera capturamos todos los elementos del formulario
    if (nombreProd == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("texto y entero", nombreProd)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    }
    if (cantidad == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO CANTIDAD NO DEBE ESTAR VACIO.</span><br>";

    }
    if (validar("decimal", cantidad)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO CANTIDAD.</span><br>";
    }
    if (unidad == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO UNIDAD NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("decimal", unidad)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO UNIDAD.</span><br>";
    }
    if (pre_venta == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO PRECIO NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("decimal", pre_venta)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO PRECIO.</span><br>";
    }
    if (selectGrupo == "0") {
        errores += "<span style='font-weight: bold;'>* DEBE SELECCIONAR UN GRUPO.</span><br>";
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
        url: "CONTROLADORES/productoController.php",
        data: parametros1,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {

            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            alertify.success("GUARDADO CORRECTAMENTE");
            $('#myModal').modal("hide");

            $('#frmFormulario')[0].reset();
            $('#foto').attr('src', 'imagenes/imagenboton/food.svg');
            listarProducto($('#selectSucursal1').val());
            $('#loading').css('display', 'none');
        },
        error: function (r) {

            alert("Error del servidor");
            location.reload();
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

});



$("#frmFormularioEditProdu").submit(function (e) {
    //esto evita que se haga la petición común, es decir evita que se refresque la pagina
    e.preventDefault();
    nombreProd = $('#NombreProcA').val();
    cantidad = $('#cantidadA').val();
    unidad = $('#unidA').val();
    pre_venta = $('#pre_ventaA').val();
    selectGrupo = $('#grupoA').val();

    errores = "";
    //ruta la cual recibira nuestro archivo
    // url="@Url.Content("~/Archivo/Recibe")"

    //FormData es necesario para el envio de archivo, 
    //y de la siguiente manera capturamos todos los elementos del formulario
    if (nombreProd == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO NOMBRE NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("texto y entero", nombreProd)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES ESPECIALES EN EL CAMPO NOMBRE.</span><br>";
    }
    if (cantidad == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO CANTIDAD NO DEBE ESTAR VACIO.</span><br>";

    }
    if (validar("decimal", cantidad)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO CANTIDAD.</span><br>";
    }
    if (unidad == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO UNIDAD NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("decimal", unidad)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO UNIDAD.</span><br>";
    }
    if (pre_venta == "") {
        errores += "<span style='font-weight: bold;'>* EL CAMPO PRECIO NO DEBE ESTAR VACIO.</span><br>";
    }
    if (validar("decimal", pre_venta)) {
        errores += "<span style='font-weight: bold;'>* POR FAVOR NO AGREGAR CARACTERES EN EL CAMPO PRECIO.</span><br>";
    }
    if (selectGrupo == "0") {
        errores += "<span style='font-weight: bold;'>* DEBE SELECCIONAR UN GRUPO.</span><br>";
    }

    if (errores != "") {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', errores, function () {
            alertify.message('OK');
        });
        return;
    }
    var parametros = new FormData($(this)[0]);
    if (modifTodasScu==1) {
        alertify.confirm("MODIFICAR","¿DESEA APLICAR LOS PRECIOS PARA TODAS LAS SUCURSALES?",
           function(){

              modificarProd(parametros,1);
          },
          function(){
             modificarProd(parametros,0);
         })  ;
    }else{
        modificarProd(parametros,0);
    }
    //realizamos la petición ajax con la función de jquery
    modifTodasScu=0;
});
function modificarProd(parametros,todasSuc){//si todasSuc es igual a 1 entonces se modificara los precios a en todas las sucursales
    parametros.append('todasSuc',todasSuc);
    parametros.append('idSucPrdt',idSucPrdt);
    $.ajax({
        type: "POST",
        url: "CONTROLADORES/productoController.php",
        data: parametros,
        contentType: false, //importante enviar este parametro en false
        processData: false, //importante enviar este parametro en false
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            } else {
                alertify.success('MODIFICADO CORRECTAMENTE');
                $('#myModaledit').modal("hide");
                $('#frmFormularioEditProdu')[0].reset();
                $('#imgfoto').attr('src', 'imagenes/imagenboton/food.svg');
            }
            listarProducto(idSuc);
            $('#loading').css('display', 'none');
        },
        error: function (r) {

            alert("Error del servidor");
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
idSucPrdt=0;
function cargarDatosProducto(idSucPrd,idProduc) {
    idSucPrdt=idSucPrd;
    var url = "CONTROLADORES/productoController.php";
    idProducto = $('#idProductoA');
    nombreProducto = $('#NombreProcA');
    estado = $('#estadoA');
    cantidad = $('#cantidadA');
    unidad = $('#unidA');
    precioVenta = $('#pre_ventaA');
    precioMayorista = $('#precioMayoristaA');
    precio3 = $('#precio3A');
    precio4 = $('#precio4A');
    unid = $('#unidA');
    grupo = $('#grupoA');
    disponible = $('#disponibleA');
    sugiere = $('#sugiereA');
    colorProducto = $('#colorProductoA');
    barcode = $('#barcode');
    familia = $('#familiaA');
    imgfoto = $('#imgfoto');
    cocina = $('#impCocina');
    punto1 = $('#impPunto1');
    punto2 = $('#impPunto2');
    punto3 = $('#impPunto3');
    punto4 = $('#impPunto4');
    lun = $('#lun');
    mar = $('#mar');
    mier = $('#mier');
    jue = $('#jue');
    vie = $('#vie');
    sab = $('#sab');
    dom = $('#dom');
    horaIni = $('#horaIni');
    horaFin = $('#horaFin');
    $('#loading').css('display', 'block');
    $.ajax({
        url: url,
        type: 'POST',
        typedata: 'json',
        data: {proceso: 'cargarDatos', id: idSucPrd},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verificarError(json.error)) {
                return;
            }
            if (json.result != "") {
                idProducto.val(json.result['id']);
                nombreProd = json.result['nom_prod'];
                nombreProducto.val(json.result['nom_prod']);
                estado.val(json.result['estado']);
                precioVenta.val(json.result['pre_venta']);
                unid.val(json.result['unid']);
                grupo.val(json.result['grupo']);
                disponible.val(json.result['disponible']);
                sugiere.val(json.result['presa']);
                cantidad.val(json.result['cantidad']);
                colorProducto.val(json.result['colores']);
                barcode.val(json.result['barcode']);
                tiempo=json.result['tiempo']==""?"N":json.result['tiempo'];
                familia.val(tiempo);
                horaIni.val(json.result['hora_inicio']);
                horaFin.val(json.result['hora_fin']);
                precioMayorista.val(json.result['precioVentaMayorista']);
                precio3.val(json.result['precioVenta3']);
                precio4.val(json.result['precioVenta4']);
                if (json.result['impcoc'] != "") {
                    cocina.prop('checked', true);

                }
                if (json.result['imppunt1'] != "") {
                    punto1.prop('checked', true);

                }
                if (json.result['imppunt2'] != "") {
                    punto2.prop('checked', true);

                }
                if (json.result['imppunt3'] != "") {
                    punto3.prop('checked', true);

                }
                if (json.result['imppunt4'] != "") {
                    punto4.prop('checked', true);

                }
                if (json.result['lun'] != "") {
                    lun.prop('checked', true);

                }
                if (json.result['mar'] != "") {
                    mar.prop('checked', true);

                }
                if (json.result['mier'] != "") {
                    mier.prop('checked', true);

                }
                if (json.result['jue'] != "") {
                    jue.prop('checked', true);

                }
                if (json.result['vie'] != "") {
                    vie.prop('checked', true);

                }
                if (json.result['sab'] != "") {
                    sab.prop('checked', true);

                }
                if (json.result['dom'] != "") {
                    dom.prop('checked', true);

                }
                if (json.result['']) {
                }
                var letra = json.result['imgnormal'].charAt(0);
                if (letra == 'a') {
                    if (json.result['imgnormal'] != "") {
                        imgfoto.attr('src', "../" + json.result['imgnormal']);
                    } else {
                        imgfoto.attr('src', 'imagenes/imagenboton/food.svg');

                    }
                } else {
                    imgfoto.attr('src', 'imagenes/imagenboton/food.svg');
                }

                if (json.result['presa'] != "") {
                    $('#divSujiere').css('display', 'block');
                } else {
                    $('#divSujiere').css('display', 'none');
                }

                $('#myModaledit').modal('show');
            } else {
                alertify.error("ERROR INTENTE NUEVAMENTE");

            }
            $('#loading').css('display', 'none');

        }

    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

}




function cargarIdEliminar(boton) {
    id = $(boton).attr('id');
    nombre = $(boton).attr('nombre');
    $('#nombreProductoModal').text('"' + nombre + '"');
    $('#idProductoEliminar').val(id);


}


function paginadorProducto() {
    $('#ghatable').DataTable({
        "pagingType": "full_numbers",
        "scrollY": "330px",
        "scrollCollapse": true,
        "paging": false,
        retrieve: true

    });
}

function sugerirPresa(select) {
    opcion = $(select).val();
    if (opcion == "X") {
        $('#divSujiere').css('display', 'block');
    } else {
        $('#divSujiere').css('display', 'none');

    }
    // ModificarProducto();
}

function edicionDirectaProducto(){
    window.open("../edicionDirecta/edicionDirectaProducto.php");
    
}


function desactivarProducto(){

 idProducto=$('#idProductoEliminar').val();
 $.post('CONTROLADORES/productoController.php', {proceso: 'desactivarProducto', idProducto: idProducto}, function (res) {
    var json = JSON.parse(res);
    if (verificarError(json.error)) {
        return;
    }


    alertify.success("PRODUCTO ELIMINADO CORRECTAMENTE");
    location.reload();
}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}