idProductoGrupo = new Array();
factorProducto = new Array();
botonProducto = "";
cont = 0;
botonEditarGrupo="";
botonEliminarRelProGrupo="";
// 
function listarProductoGrupo(boton) {
	$('#loading').css('display','block');
	 // $('#ghatableProductoGrupo tbody  tr').remove();
    botonProducto = boton;
    id = $(boton).attr('id');
    nombre = $(boton).attr('nombre');
    $('#tituloProductoGrupo').text('"' + nombre + '"');
   

// $('#ghatableProductoGrupo').append("<tbody id='tbodyProductoGrupo'></tbody>");
//   $('#ghatableProductoGrupo').DataTable().clear();
$('#ghatableProductoGrupo').DataTable().destroy();
 $('#tbodyProductoGrupo').empty();
    $.post('CONTROLADORES/productoGrupoController.php', {proceso: 'listarProductoGrupo', id: id}, function (res) {
        json = $.parseJSON(res);
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
            $('#tbodyProductoGrupo').append('<tr style="text-align:center"><td>' + json.result[i].ID +
                    '<td><input class="form-control" type="hidden" name="nombreGrupoProducto[]" nombreGrupoProducto=true id="nombreGrupoProducto' + i + '" value="' + json.result[i].NOM_GRUPO + '" disabled>' + json.result[i].NOM_GRUPO +
                    '<td>' + json.result[i].ORDEN +
                    '<td><button type="button" class="btn btn-info btn-sm" onclick="verProductosGrupo('+json.result[i].ID+')"><i class="fa fa-eye" aria-hidden="true"></i></button><button data-toggle="modal" data-target="#ModalProductoGrupoModificar" class="btn btn-primary btn-sm"  type="button" onclick="cargarDatosModalGrupoPro(this,'+json.result[i].ID+')"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button>' +
                    '<td><input type="checkbox" value="' + json.result[i].COD_GRUPO + '" name="grupoProducto" onclick="habilitarInputG(' + i + ')">' +
                    '<td><input type="text" habilitado="true" id="factor' + i + '" name="factorProducto" disabled="disabled" class="form-control">'
                    );
        }
        paginadorProductoGrupo();
        $('#loading').css('display','none');
        
    }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}
function limpiarTabla(){
	 var oSettings = $('#ghatableProductoGrupo').dataTable().fnSettings(); 
	 var iTotalRecords = oSettings.fnRecordsTotal(); 
	 for (i=0;i<=iTotalRecords;i++) {
	  $('#ghatableProductoGrupo').dataTable().fnDeleteRow(0,null,true); }
}


function habilitarInputG(index) {
    cantidad = $('#factor' + index).attr('habilitado');
    if (cantidad === 'true') {
        $('#factor' + index).attr('disabled', false);
        $('#factor' + index).attr('habilitado', false);
        $('#nombreGrupoProducto' + index).attr('nombreGrupoProducto', false);
        $('#nombreGrupoProducto' + index).attr('disabled', false);



    } else {
        $('#factor' + index).attr('disabled', true);
        $('#factor' + index).val("");
        $('#factor' + index).attr('habilitado', true);
        $('#nombreGrupoProducto' + index).attr('nombreGrupoProducto', true);
        $('#nombreGrupoProducto' + index).attr('disabled', true);



    }
}

function paginadorProductoGrupo() {
    $('#ghatableProductoGrupo').DataTable({
        "pagingType": "full_numbers",
        "destroy": true,
        "order": [[0, "asc"]],
        "scrollY": "450px",
        "scrollCollapse": true,
        "paging": false,
        retrieve: true

    });
}

function guardarProductoGrupo() {


    for (var i = 0; i < idProductoGrupo.length; i++) {
        if (idProductoGrupo[i] != null) {
            
            alert($('input[name= factorProducto' + idProductoGrupo[i] + ' ]').val());
        }
    }
}


$(document).ready(function () {

    $('#example-select-all').on('click', function () {
        // Check/uncheck all checkboxes in the table
        var rows =  $('#ghatableProductoGrupo').DataTable().rows({'search': 'applied'}).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });

    // Handle click on checkbox to set state of "Select all" control
    $('#ghatableProductoGrupo tbody').on('change', 'input[type="checkbox"]',
            function () {
                // If checkbox is not checked
                if (!this.checked) {
                    var el = $('#example-select-all').get(0);
                    // If "Select all" control is checked and has 'indeterminate' property
                    if (el && el.checked && ('indeterminate' in el)) {
                        // Set visual state of "Select all" control 
                        // as 'indeterminate'
                        el.indeterminate = true;
                    }
                }
            });


    $('#frm-example').on('submit', function (e) {
        var form = this;
        dato=new Array();
        dato2=new Array();
        id= $(botonProducto).attr("id");
        nombre= $(botonProducto).attr("nombre");
        cantidad= $(botonProducto).attr("cantidad");
        unidad= $(botonProducto).attr("unidad");
 
        // Iterate over all checkboxes in the table
        $('#ghatableProductoGrupo').DataTable().$('input[type="checkbox"]').each(function () {
            // If checkbox doesn't exist in DOM
                 
                // If checkbox is checked
                if (this.checked) {

                     var gridNum = $(this).closest("tr").find("input[name=factorProducto]");
//                     Create a hidden element 
					var gridNum2 = $(this).closest("tr").find("td:eq(0)").text();
                  dato.push(gridNum.val());
                 dato2.push(gridNum2);
                }
        });
        $.post('CONTROLADORES/relProGrupoController.php', {proceso: 'guardar',id:id, dato:dato,nombre:nombre,cantidad:cantidad,unidad:unidad,dato2:dato2}, function (res) {
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
             $('#ModalaAgregarGrupoProducto').modal('hide') ;
              location.reload();
              listarProducto();   
            }
        	 
        }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});

        // FOR TESTING ONLY

        // Output form data to a console
        $('#example-console').text($(form).serialize());
        console.log("Form submission", $(form).serialize());

        // Prevent actual form submission
        e.preventDefault();
    });
});


function cargarModalFactor(boton){
botonEditarGrupo=boton;
$('#spanGrupo').text('"'+$(boton).attr('nombregrupo')+'"');
id=$(boton).attr('idrelprogru');
factor=$(boton).attr('factor');
$('#factorM').val(factor);
}


function modificarFactor(){
	id=$(botonEditarGrupo).attr("idrelprogru");
	factor=$('#factorM').val();
	$.post('CONTROLADORES/relProGrupoController.php',{proceso:"modificarFactor",factor:factor,id:id},function(res){
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
        listarProducto();
        alertify.success("MODIFICARDO CORRECTAMENTE");   
            }
		$('#ModalModificarFactor').modal('hide');
		$('#factorM').val("");
	}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}


function cargarModalEliminarRelProGrupo(boton){
botonEliminarRelProGrupo=boton;


}
function eliminarRelProGru(){
id=$(botonEliminarRelProGrupo).attr("id");
$.post("CONTROLADORES/relProGrupoController.php",{proceso:"eliminarRelProGru",id:id},function(res){
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

listarProducto();
        botonEliminarRelProGrupo="";
        alertify.success("ELIMINAR CORRECTAMENTE");   
            }
        $('#ModalEliminarGrupo').modal('hide');
            
}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}
function errorServidor(json){
if (json.error.length > 0) {
                if ("Error Session" === json.error) {
                    $('#loading').css('display', 'none');

                    alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
                        alertify.message('OK');
                        window.location = "../desconectar.php";
                    });


                    return false;
                }
                alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', json.error, function () {
                    alertify.message('OK');
                });
                $('#loading').css('display', 'none');
                return false;
            }
            return true;
}
function verProductosGrupo(id){
    $('#tbodyProductoDeGrupo').empty();
$.post('CONTROLADORES/relProGrupoController.php',{proceso:'verProductosGrupo',id:id},function(res){
        var json = $.parseJSON(res);
        if (!errorServidor(json)) {
            return;
        }
            if(json.result.length>0){
$('#tituloProductoDeGrupo').text('"'+json.result[0].NOM_GRUPO+'"');
            for (var i = 0; i < json.result.length; i++) {
    $('#tbodyProductoDeGrupo').append('<tr>'+
        '<td>'+json.result[i].COD_PROD+
        '<td>'+json.result[i].NOM_PROD+
        '<td>'+json.result[i].FACTOR
        );    
           }
           }
           $('#ModalProductoGrupo').modal('show');
});
}
idProductoGrupo="";
function modificarGrupoPro(){
 var nombre=$('#inputNombreGrupo').val();
   var orden= $('#ordenGrupo').val();
$.post('CONTROLADORES/relProGrupoController.php',{proceso:'modificarGrupoPro',id:idProductoGrupo,nombre:nombre,orden:orden},function(res){
        var json = $.parseJSON(res);
        if (!errorServidor(json)) {
            return;
        }
            alertify.success("MODIFICADO CORRECTAMENTE");
          listarProductoGrupo(botonProducto);
          $('#ModalProductoGrupoModificar').modal('hide');
});

}
function cargarDatosModalGrupoPro(boton,id){
    idProductoGrupo=id;
    $('#inputNombreGrupo').val($(boton).parents("tr").find("td").eq(1).text());
    $('#ordenGrupo').val($(boton).parents("tr").find("td").eq(2).text());
}