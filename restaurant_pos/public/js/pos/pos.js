exchangeRate = 0;
buttonProduct = "";
UrCurrent = "";
exitProduct = "";
ipCustomer = "";
counter = 1;//ESTE PARA CONTROLAR EL DATAPPICKER
dateToday = "";
function verifyError(error){
    if (error.length > 0) {
        if ("Error Session" === error) {
          $('#loading').css('display', 'none');

          alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE ENTRAR EN SESION NUEVAMENTE", function () {
            alertify.message('OK');
            window.location = "/";
        });

          return true;
      }
      alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', error, function () {
        alertify.message('OK');
        if ("DEBE ABRIR CAJA" === error) {
          $('#ModalOpenCash').modal('show');
        }
    });
      $('#loading').css('display', 'none');
      return true;
  }
}
$(document).ready(function () {
 $(this).bind("contextmenu", function(e) {

                // e.preventDefault();
            });
 var d = new Date();
 dateToday = d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate();
 listTDeliveredExpense();
 listWaiter();
 getRestaurant();
 verifyAuthorization();
 verifyBackup();
 var UrCurrent = window.location;
 toastr.options = {
    "closeButton": false,
    "debug": false,
    "newestOnTop": false,
    "progressBar": false,
    "positionClass": "toast-bottom-center",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "100",
    "hideDuration": "1000",
    "timeOut": "100",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut" 
}
getIpCustomer();
listMenuGroup();
listTables();
listBranch();
var d = new Date();
var strDate = d.getFullYear() + "/" + (d.getMonth() + 1) + "/" + d.getDate();
exchangeRate = $('#exchangeRate').val();


paginatorTbodyHistory();
tableHistory = $('#tableHistory').DataTable();
   // verifyBackup();
});
indexDetail = 0;
totalBs = 0;
numbers = "";
rowName = "";
rowNameSpan = "";
idProd = 0;
dateToday = "";
$('body').on('hidden.bs.modal', '.modal', function () {
    $(this).removeData('bs.modal');
});
function failErrors(xhr, ajaxOptions, thrownError){
    console.log(xhr.status)
    console.log(xhr.responseText)
    console.log(thrownError)
    error="";
    if (xhr.status === 0) {
        error="<span style='font-weight:bold'>* Error de conexión, verifica tu instalación de RED.</span><br>";

    } else if (xhr.status == 302) {
       error="<span style='font-weight:bold'>* La página ha sido movida. error 302.</span><br>";
   } else if (xhr.status == 404) {
       error="<span style='font-weight:bold'>* La página no ha sido encontrada. error 404.</span><br>";
   } else if (xhr.status == 500) {
       error="<span style='font-weight:bold'>* Internal Server Error error 500.</span><br>";
   } else if (thrownError === 'parsererror') {
    error="<span style='font-weight:bold'>* Error parse JSON.</span><br>";
} else if (thrownError === 'timeout') {
    error="<span style='font-weight:bold'>* Exceso tiempo.</span><br>";
} else if (thrownError === 'abort') {
    error="<span style='font-weight:bold'>* Petición post abortada.</span><br>";
} else {
    error="<span style='font-weight:bold'>* ERROR desconocido:  "+ xhr.responseText+".</span><br>";
}    
if (error!="") {
    alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', error, function () {
        alertify.message('OK');
    });
}  
$('#btnSaveDetail').removeAttr('disabled'); 
}
function listBranch(){
    $.ajax({
        url: ROUTES.branches,
        type: 'GET',
        typeData: 'json',
        data: {},
        success: function (res) {
            var json = $.parseJSON(res);
            if (verifyError(json.error)) {
                return;
            }
            for (var i = 0; i < json.result.length; i++) {
                if (multiBranchSales==1) {
                    $('#selectBranch').append('<option value="'+json.result[i].id+'">'+json.result[i].name+'</option>');
                }
            }
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

}
function changeBranch(idSuc){
    $.post(ROUTES.changeBranch, {idSuc:idSuc},function(res){
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            $('#selectBranch').val(idBranch);
            return;
        }
        location.reload();
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
function typeExit(buttonExit) {
    exitProduct = $(buttonExit).text();
    $('#ModalExit').modal('hide');
    $('#labelTakeaway').text(exitProduct);
    $('#ModalInvoice').modal('show');
    $('#ModalInvoice').css('overflow-y', 'scroll');
    total = $("#totalBs").val();
    var value = LITERAL(total);
    $('#numLiteral').val(value);
    $('#labelNumLiteral').text(value);
    if (notificationDateLimit!="") {
        alertify.success(notificationDateLimit);
    }
}
function maximizePage(UrCurrent) {
    window.open(UrCurrent, '', 'fullscreen=yes, scrollbars=no');
}

function listMenuGroup() {
    first_id = 0;

    $.get(ROUTES.categories, {idBranch:idBranch}, function (res) {
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }
        for (var i = 0; i < json.result.length; i++) {
            $('#divMenuGroup').append('<button onclick="listProduct(' + json.result[i].id + ',this)" name="btnMenu" class=" btn  btn-lg botonCategoriaMobil"  id="buttonMenu' + json.result[i].id + '">' + json.result[i].group_name + '</button>');
            $('#divButtonProduct').append('<button onclick="moveProductMenu(' + json.result[i].id + ')" name="btnMenu" class=" btn  btn-lg botonCategoriaMobil"  >' + json.result[i].group_name + '</button>');
            if (i == 0) {
                first_id = json.result[i].id;

            }
            // $('#loading').css('display','none');
        }

        $('#buttonMenu'+first_id).click();
        // listProduct(idprimero,$('#buttonMenu'+idprimero));
        afterDeLoad();

    });
}
function listTables() {//lista todas las mensas
    $('#divTable').empty();
    $('#divTableEnabled').empty();
    $.get(ROUTES.diningTables, {idBranch:idBranch}, function (res) {
        var json = JSON.parse(res);
        var total=0;
        if (verifyError(json.error)) {
            return;
        }
        for (var i = 0; i < json.result.length; i++) {
            if (json.result[i].processing != "") {
                total++;
                if (dining_room == "no_dining_room") {
                    $('#divTable').append('<button disabled="" numberTable="'+json.result[i].table_number+'" numberPerson="' + json.result[i].people_count + '" idTable="' + json.result[i].id + '" class="aumentarPM btn btn-warning btn-lg" style=" background:' + json.result[i].color + ';   margin: 2px;    width: 10%;      color: black;  height: 52px;white-space: inherit;"><i class="fa fa-cutlery" aria-hidden="true"></i>' + json.result[i].table_number + '</button>');
                    $('#divTableEnabled').append('<button onclick="transferItemTable(this)" disabled=""  idTable="' + json.result[i].id + '" class="btn btn-warning btn-lg" style=" background:' + json.result[i].color + ';   margin: 2px;white-space: inherit;">' + json.result[i].table_number + '</button>');
                } else {
                    customer_name=json.result[i].customer_name==""?json.result[i].table_number:json.result[i].customer_name;
                    $('#divTable').append('<button disabled="" numberTable="'+json.result[i].table_number+'"  numberPerson="' + json.result[i].people_count + '" idTable="' + json.result[i].id + '" class="aumentarPM btn btn-warning btn-lg" style="margin: 2px;white-space: inherit; background:' + json.result[i].color + ';   margin: 2px;     color: black;   width: 10%;   height: 46PX;"><i class="fa fa-cutlery" aria-hidden="true"></i>' + customer_name + '</button>');
                    $('#divTableEnabled').append('<button onclick="transferItemTable(this)" disabled=""  idTable="' + json.result[i].id + '" class="btn btn-warning btn-lg" style="    color: black; background:' + json.result[i].color + ';   margin: 2px;white-space: inherit;">' + customer_name + '</button>');

                }
                // $('#divTable').append('<button  idMesa="'+json.result[i].ID+'" class="aumentarPM btn btn-warning btn-lg" style=" background:'+json.result[i].COLORES+';   margin: 2px;">' + json.result[i].NRO_MESA + '</button>');
            }
            if (json.result[i].occupied != "" && json.result[i].processing == "") {
                total++;
                if (dining_room == "no_dining_room") {
                    $('#divTable').append('<button  idTable="' + json.result[i].id + '" numberTable="'+json.result[i].table_number+'" numberPerson="' + json.result[i].people_count + '" class="aumentarPM btn btn-warning btn-lg" style=" background:' + json.result[i].color + ';   margin: 2px; width: 10%;      color: black;  height: 52px;white-space: inherit;"><i class="fa fa-users" aria-hidden="true"></i>' + json.result[i].table_number + '</button>');
                    $('#divTableEnabled').append('<button onclick="transferItemTable(this)" status="ocupado"  idTable="' + json.result[i].id + '" class="btn btn-warning btn-lg" style=" background:' + json.result[i].color + ';   margin: 2px; width: 10%;    height: 25%;white-space: inherit;"><i class="fa fa-users" aria-hidden="true"></i>' + json.result[i].table_number + '</button>');
                } else {
                    customer_name=json.result[i].customer_name==""?json.result[i].table_number:json.result[i].customer_name;
                    $('#divTable').append('<button  idTable="' + json.result[i].id + '" numberTable="'+json.result[i].table_number+'" numberPerson="' + json.result[i].people_count + '" class="aumentarPM btn btn-warning btn-lg" style=" background:' + json.result[i].color + ';   margin: 2px; width: 10%;     color: black;   height: 52px;white-space: inherit;padding: 0;"><i class="fa fa-users" aria-hidden="true"></i>' + customer_name + '</button>');
                    $('#divTableEnabled').append('<button onclick="transferItemTable(this)" status="ocupado"  idTable="' + json.result[i].id + '" class="btn btn-warning btn-lg" style=" background:' + json.result[i].color + ';   margin: 2px; width: 10%;   height: 46PX;white-space: inherit;padding: 0;"><i class="fa fa-users" aria-hidden="true"></i>' + customer_name + '</button>');
                }
            } else {
                if (json.result[i].occupied == "" && json.result[i].processing == "") {
                    $('#divTable').append("<button  idTable='" + json.result[i].id + "' numberTable='"+json.result[i].table_number+"' numberPerson='" + json.result[i].people_count + "' class='agregarPM btn btn-warning  btn-lg' style='    margin: 2px; width: 10%;       height: 52px;white-space: inherit;'>" + json.result[i].table_number + "</button>");
                    $('#divTableEnabled').append("<button numberTable='"+json.result[i].table_number+"' onclick='transferItemTable(this)' status='abierta'  idTable='" + json.result[i].id + "'' class='btn btn-warning  btn-lg' style='    margin: 2px; width: 10%;       height: 25%;white-space: inherit;'>" + json.result[i].table_number + "</button>");
                }
            }

        }
        $('#spanQtyTable').text(total);
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}

idButtonMenu="";
auxButtonMenu="";
function listProduct(id,button) {
    if (button!=auxButtonMenu) {
     $(button).removeClass();
     $(button).addClass('clickMenuGroup');

     auxButtonMenu=auxButtonMenu==""?"":$(auxButtonMenu).removeClass(); $(auxButtonMenu).addClass('btn  btn-lg botonCategoria');
     auxButtonMenu=button;
 }
 idButtonMenu=id;
 json="";
 
 $('#divProduct').empty();
 $.get(ROUTES.products, {id: id,idBranch:idBranch}, function (res,status) {
    var json = JSON.parse(res);

    if (verifyError(json.error)) {
        return;
    }
    for (var i = 0; i < json.result.length; i++) {

       enabled= productEnabled(json,i);
       if (enabled!="") {
        if (json.result[i].options_mode == "") {
            if (json.result[i].image_path.indexOf("legacy/") !== 0) {
                $('#divProduct').append('<button  print_station_4="'+json.result[i].print_station_4+'" print_station_3="'+json.result[i].print_station_3+'" print_station_2="'+json.result[i].print_station_2+'" print_station_1="'+json.result[i].print_station_1+'" printKitchen="'+json.result[i].print_kitchen+'"  typeProduct="'+json.result[i].timed+'" idProduct="'+json.result[i].id+'" quantity="' + json.result[i].quantity + '" name="btnProduct" options_mode="' + json.result[i].options_mode + '" title="Precio: ' + json.result[i].sale_price + ' , ' + json.result[i].product_name + '" price="' + json.result[i].sale_price + '" name="' + json.result[i].product_name + '"  onclick="listSaleItem(this,' + json.result[i].id + ')" class="btnMenu btn btn-primary " style="border: none;    font-size: 12px;\n\
                    font-weight: bold;       width: 95px;text-shadow: 2px 2px black;    height: 52px; text-shadow: 2px 2px black; font-size: 8pt; box-shadow: 1px 1px #999;    margin-right: 4px;\n\
                    margin-bottom: 3px;    margin-left: 1px;    padding: 0;      margin-top: 1px; white-space: inherit; background:' + json.result[i].color + ' " >' + json.result[i].product_name + '</button>');
            } else {
                $('#divProduct').append('<a  print_station_4="'+json.result[i].print_station_4+'" print_station_3="'+json.result[i].print_station_3+'" print_station_2="'+json.result[i].print_station_2+'" printStation1="'+json.result[i].print_station_1+'" printKitchen="'+json.result[i].print_kitchen+'"  typeProduct="'+json.result[i].timed+'" idProduct="'+json.result[i].id+'"  quantity="' + json.result[i].quantity + '" class="btnMenu" href="#" name="btnProduct" options_mode="" title="' + json.result[i].sale_price + ' , ' + json.result[i].product_name + '" price="' + json.result[i].sale_price + '" name="' + json.result[i].product_name + '" onclick="listSaleItem(this,' + json.result[i].id + ')">' +
                    '<div class="" style="width: max-content;padding-left: 0;padding-right: 0;overflow: hidden;display: inline-table; ' +
                    'padding-right: 0;    overflow: hidden;"> ' +
                    '<div class="btn btn-primary" style="border: none;    font-size: 12px;' +
                    'font-weight: bold;       width: 95px;    height: 52px;   box-shadow: 1px 1px #999;    margin-right: 4px;' +
                    'margin-bottom: 3px;    margin-left: 1px;     padding: 0;      margin-top: 1px; white-space: inherit; background:; ">' +
                    '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 " >' +
                    '<img src="' + json.result[i].image_path + '" style="width: 36px;height: 56px;    position: absolute;    left: 1px;">' +
                    '</div>' +
                    ' <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 "  style="padding: 0;">' +
                    '<span style="position: relative;' +
                    'top: 1px;' +
                    'word-wrap: break-word;' +
                    'text-shadow: 2px 2px black;' +
                    'left: -5px;' +
                    'font-size: 8pt;">' + json.result[i].product_name + '</span>' +
                    '</div></div></div></a>');
            }
        } else {
            if (json.result[i].image_path.indexOf("legacy/") !== 0) {
                $('#divProduct').append('<button  print_station_4="'+json.result[i].print_station_4+'" print_station_3="'+json.result[i].print_station_3+'" print_station_2="'+json.result[i].print_station_2+'" print_station_1="'+json.result[i].print_station_1+'" printKitchen="'+json.result[i].print_kitchen+'"  typeProduct="'+json.result[i].timed+'"  idProduct="'+json.result[i].id+'" quantity="' + json.result[i].quantity + '"  name="btnProduct"  title="Precio: ' + json.result[i].sale_price + ' , ' + json.result[i].product_name + '" price="' + json.result[i].sale_price + '" name="' + json.result[i].product_name + '"  onclick="loadSuggestion(this,' + json.result[i].id + ')" class="btnMenu btn btn-primary " style="border: none;    font-size: 12px;\n\
                    font-weight: bold;       width: 95px; text-shadow: 2px 2px black;   height: 52px;   box-shadow: 1px 1px #999;    margin-right: 4px;\n\
                    margin-bottom: 3px;    margin-left: 1px;     padding: 0;      margin-top: 1px; white-space: inherit; background:' + json.result[i].color + ' " >' + json.result[i].product_name + '</button>');
            } else {
                $('#divProduct').append('<a  print_station_4="'+json.result[i].print_station_4+'" print_station_3="'+json.result[i].print_station_3+'" print_station_2="'+json.result[i].print_station_2+'" printStation1="'+json.result[i].print_station_1+'" printKitchen="'+json.result[i].print_kitchen+'"  typeProduct="'+json.result[i].timed+'" idProduct="'+json.result[i].id+'"  quantity="' + json.result[i].quantity + '"  class="btnMenu" href="#" name="btnProduct" options_mode="" title="Precio: ' + json.result[i].sale_price + ' , ' + json.result[i].product_name + '" price="' + json.result[i].sale_price + '"  name="' + json.result[i].product_name + '" onclick="loadSuggestion(this,' + json.result[i].id + ')">' +
                    '<div class="" style="width: max-content;display:inline-table;    padding-left: 0;' +
                    'padding-right: 0;    overflow: hidden;"> ' +
                    '<div class="btn btn-primary" style="border: none;    font-size: 12px;' +
                    'font-weight: bold;       width: 95px;    height: 52px;   box-shadow: 1px 1px #999;    margin-right: 4px;' +
                    'margin-bottom: 3px;    margin-left: 1px;     padding: 0;      margin-top: 1px; white-space: inherit; background:; ">' +
                    '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 " >' +
                    '<img src="' + json.result[i].image_path + '" style="width: 36px;height: 56px;    position: absolute;    left: 1px;">' +
                    '</div>' +
                    ' <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 "  style="padding: 0;">' +
                    '<span style="position: relative;' +
                    'top: 1px;' +
                    'word-wrap: break-word;' +
                    'text-shadow: 2px 2px black;' +
                    'left: -5px;' +
                    'font-size: 8pt;">' + json.result[i].product_name + '</span>' +
                    '</div></div></div></a>');
            }
        }
    }
    enabled="";
}
        // $('#loading').css('display','none');

    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

}
i = 0;
function listSaleItem(button, id) {

    var table_name=$('#tableDetail tbody tr');
    i++;
    count = 0;
    subTotal = 0;
    name = $(button).text();
    price = parseFloat($(button).attr('price'));
    quantity = (parseFloat($(button).attr('quantity'))).toFixed(2);
    typeProduct=$(button).attr('product_type');
    qty = $('#qty');
    id = id;
    tbody = $('#tbodyDetail');

    if (parseFloat(qty.val()) > 0) {
        quantity = parseFloat(qty.val());
        qty.val("");
    }
    var targetInput = $('#tableDetail span[nameProduct="' + name + '"]');

    if (targetInput.length > 0) {
      var  total=0;
      var inputQty = $(targetInput).closest("tr").find("input[name=quantity]");
      var inputPrice = $(targetInput).closest("tr").find("input[name=price]");
      var inputSubtotal = $(targetInput).closest("tr").find("input[name=subTotal]");
      var spanSubTotal = $(targetInput).closest("tr").find("#spanSubTotal");
      var spanQuantity = $(targetInput).closest("tr").find("#spanQuantity");
      var spanPrice = $(targetInput).closest("tr").find("#spanPrice");
        //     alert(inputCant.val());
        // cantidad=parseFloat(cantidad+inputCant.val());
        $(table_name).each(function (e) {
            total+=parseFloat($(this).closest("tr").find("input[name=subTotal]").val());
            
        });
        
        if ( typeProduct=="TIEMPO"){
         aux=parseFloat($('#buttonHidden').attr('subTotal'));
         $('#buttonHidden').attr('subTotal','');
     }else{
       if (CUSTOMER_MOBILE!="" && CUSTOMER_MOBILE!=null) {
        percentageDiscount=price * quantity*0.1;
        aux = price * quantity-percentageDiscount;

    }else{
        aux = price * quantity;

    }
}

totalBs = (total + aux).toFixed(2);

subTotal = parseFloat(inputSubtotal.val()) + aux;
        // totalBs = totalBs + parseFloat(precio);
        $('#totalBs').val(totalBs);

        $('#totalUsd').val((totalBs / exchangeRate).toFixed(2));
        inputSubtotal.val(subTotal.toFixed(2));
        count = (parseFloat(inputQty.val()) + parseFloat(quantity)).toFixed(2);
        inputQty.val(count);
        spanSubTotal.text(subTotal.toFixed(2));

        spanQuantity.text(count);

    } else {
        total=0;
        

        $(table_name).each(function (e) {
            total+=parseFloat($(this).closest("tr").find("input[name=subTotal]").val());
            
        });
        if ( typeProduct=="TIEMPO"){
         aux=parseFloat($('#buttonHidden').attr('subTotal'));
         $('#buttonHidden').attr('subTotal','');
     }else{
        if (CUSTOMER_MOBILE!="" && CUSTOMER_MOBILE!=null) {
            percentageDiscount=price * quantity*0.1;
            aux = price * quantity-percentageDiscount;

        }else{
            aux = price * quantity;

        }
        
    }
    totalBs = (total + aux).toFixed(2);
    var  qr="";
    if ($('#checkCodeQr').prop('checked')) {
        $('#checkCodeQr').prop('checked',false);
        qr="x";
    }
    $('#totalBs').val(totalBs);
    $('#totalUsd').val((totalBs / exchangeRate).toFixed(2));
    print_station_4=$(button).attr('print_station_4')==undefined?"":$(button).attr('print_station_4');
    print_station_3=$(button).attr('print_station_3')==undefined?"":$(button).attr('print_station_3');
    print_station_2=$(button).attr('print_station_2')==undefined?"":$(button).attr('print_station_2');
    print_station_1=$(button).attr('print_station_1')==undefined?"":$(button).attr('print_station_1');
    printKitchen=$(button).attr('print_kitchen')==undefined?"":$(button).attr('print_kitchen');
    
    tbody.append('<tr class="colorhover" style="     font-weight: bold;    " ><td  style="    border-top: 1px solid #333;width: 9.5%;    text-align: right;"><button style="display:none" name="managementPrint"  print_station_4="'+print_station_4+'" print_station_3="'+print_station_3+'" print_station_2="'+print_station_2+'" print_station_1="'+print_station_1+'" print_kitchen="'+printKitchen+'"></button><input readonly="" type="hidden" value="' + id + '" name="inputIdProduct" class="form-control"><input readonly="" type="hidden" value="' + quantity + '" name="quantity" class="form-control"><span id="spanQuantity">' + quantity + '</span>' +
        '<td style="     border-top: 1px solid #333;      width: 52.5%;"><input name="productQr" type="hidden" value="' + qr + '"><input id="inputProductId' + i + '" name="nameProduct" type="hidden" value="' + name + '"><span nameProduct="' + name + '" id="spanProd' + id + '" name="nameProduct" >' + name + '</span><input class="form-control" type="hidden" name="noteProduct" onchange="addNote(this,' + i + ')" onblur="addNote(this,' + i + ')"  id="noteProduct' + i + '">' +
        '<td  style="     text-align: right;  border-top: 1px solid #333;    width: 11%;  text-align: right;"><input readonly="" name="price" type="hidden" value="' + price + '" class="form-control"><span id="spanPrice">' + price.toFixed(2) + '</span>' +
        '<td style="     text-align: right;    border-top: 1px solid #333;    width: 11%;  text-align: right;"><input name="typeProduct" type="hidden" value="'+$(button).attr("product_type")+'"><input readonly="" type="hidden"  value="' + (aux).toFixed(2) + '" name="subTotal" class="form-control col-xs-3"><span id="spanSubTotal">' + (aux).toFixed(2) + '</span>' +
        '<td style="    padding: 0px 3%; border-top: 1px solid #333;"><button class="btn btn-danger btn-sm delete-btn"><i class="fa fa-trash-o" aria-hidden="true"></i></button>' +
        '<button class="menuNota btn btn-warning btn-sm" onclick="loadModalNote(this)" >Nota</button>'
        );
}
toastr["success"](name);
return i;

}

function clearDetail() {
    tbody = $('#tbodyDetail');
    tbody.empty();
    $('#totalBs').val("0.00");
    $('#totalUsd').val("0.00");
    $('#totalInvoiceBs').val("");
    $('#totalInvoiceUs').val("");
    $('#nameCustomer').val("");
    $('#customerTaxId').val("");
    $('#inputCashInvoice').click();
    $('#btnInvoice').attr('onclick', 'fillDataModalF()');
    $('#numberOrder').val('');
    if (idTable != null && idTable!="") {
        enableTableProcessing(idTable);
    }
    idTable=null;

    $('#spanProcessing').text("");
    i = null;
    totalBs = null;
    CUSTOMER_MOBILE=null;

    
    json=[];
    table_name=null;
    error=null;
    subTotal=null;
    optionItemTable=0;
    total=null;
    result=null;
    imPrint=null;
    idItem = [];
    quantityTableProd = [];
    nameProduct = [];
    idProdMonth = [];
    counter2 = null;
    idProduct=[];
    quantityA=[];
    nameProduct=[];
    arrayNoteProduct=[];
    priceA=[];
    NameProduct=[];
    subtotalA=[];
    numMobileTigo=null;
    transactionTigoMoney=null;
    $('#selectTypePaymentCredit').prop('selectedIndex',0);
}

$(document).on("click", ".delete-btn", function () {

    $(this).closest("tr").remove();
    var subtotal = 0;
    var targetInput = $('#tableDetail input[name=quantity]');
    if (targetInput.length > 0) {
        $(targetInput).each(function (e) {
            var gridNum = $(this).closest("tr").find("input[name=quantity]");
            var itemPrice = $(this).closest("tr").find("input[name=price]");
            subtotal += $(gridNum).val() * $(itemPrice).val()
        });
    }
    total_bs = document.getElementById("totalBs").value = parseFloat(subtotal).toFixed(2);
    totalBs = parseFloat(total_bs);
    $('#totalUsd').val((total_bs / exchangeRate).toFixed(2));


    // document.getElementById("cantext-total_us").value = parseFloat(subtotal).toFixed(2)/parseFloat(vcam_of).toFixed(3);             
});
rowNameInput = "";
function loadModalNote(input) {

    rowNoteProduct = $(input).closest("tr").find("input[name=noteProduct]");
    rowNoteProduct.attr('type', 'text');
    rowNoteProduct.focus();

    // $('#titleProductNote').text('"' + filaNombreinput.val() + '"');

    // $('#inputNote').val(filaNombre)
}
function addNote(input, index) {
    rowName = $(input).closest("tr").find("span[name=nameProduct]");
    rowNameInput = $(input).closest("tr").find("input[name=nameProduct]");
    note = $(input).val();
    if (note != "") {
        rowName.attr('product_name', note);
        rowName.text(rowNameInput.val() + "(" + note + ")");
    } else {
        rowName.text(rowNameInput.val());
    }
    $(input).attr('type', 'hidden');
}

CUSTOMER_MOBILE="";
$("#phoneTaxIdCustomer").autocomplete({


    serviceUrl: ROUTES.customersSuggestionsDelivery,
    params: {field: "tax_id"},
    minLength: 3,
    cache: false,
    width: 450,
    caheLength: 1,
    noCache: true,
    
    select: function (event, ui) {
        console.log(ui.item);
        
        $("#phoneCustomer").val(ui.item.customer_phone);
        $('#NameCustomerDelivery').val(ui.item.customerName);
        $('#addressCustomer').val(ui.item.address);
        $(this).data();

    }

});

$("#NameCustomerDelivery").autocomplete({
    minChars:3,
    maxHeight:200,
    lookupLimit:50,
    lookup: function (query, done) {

        $.ajax({
           url: ROUTES.customersSuggestionsDelivery,
           type: 'get',
           dataType: 'JSON',
           async: true,
           data: { term: query, field: "name" },
           success: function(data) {
             done(data);
         }
     }).done(function(data){
        done( data );
        
    });
 },
 onSelect: function (suggestion) {
        // alert('You selected: ' + suggestion.value + ', ' + suggestion.nit);
        $("#phoneCustomer").val(suggestion.customer_phone);
        $('#NameCustomerDelivery').val(suggestion.customerName);
        $('#addressCustomer').val(suggestion.address);
        $('#phoneTaxIdCustomer').val(suggestion.customerTaxId);

        $(this).autocomplete('clear');
    }

});
$("#phoneCustomer").autocomplete({
 minChars:3,
 maxHeight:200,
 lookupLimit:50,
 width:300,
 lookup: function (query, done) {
    $.ajax({
       url: ROUTES.customersSuggestionsDelivery,
       type: 'get',
       dataType: 'JSON',
       async: true,
       data: { term: query, field: "phone" },
       success: function(data) {
         done(data);
     }
 }).done(function(data){
    done( data );
});
},
onSelect: function (suggestion) {
        // alert('You selected: ' + suggestion.value + ', ' + suggestion.nit);
        $(this).val(suggestion.label);
        $('#addressCustomer').val(suggestion.address);
        $('#phoneTaxIdCustomer').val(suggestion.customerTaxId);
        $('#NameCustomerDelivery').val(suggestion.customerName);
        
        $(this).autocomplete('clear');
    }

    
});

$("#nameCustomer").autocomplete({
    minChars:3,
    maxHeight:200,
    lookupLimit:50,
    width:300,
    lookup: function (query, done) {

        $.ajax({
           url: ROUTES.customersSuggestionsName,
           type: 'get',
           dataType: 'JSON',
           async: true,
           data: { term: query },
           success: function(data) {
             done(data);
         }
     }).done(function(data){
        done( data )
        
    });
 },
 onSelect: function (suggestion) {
        // alert('You selected: ' + suggestion.value + ', ' + suggestion.nit);
        $('#nameCustomer').val(suggestion.label);
        $('#customerTaxId').val(suggestion.number);
        CUSTOMER_MOBILE=suggestion.customer_mobile;
        $(this).autocomplete('clear');
    }

});

$("#customerTaxId").autocomplete({
 minChars:3,
 maxHeight:200,
 lookupLimit:50,
 width:300,
 lookup: function (query, done) {

    $.ajax({
       url: ROUTES.customersSuggestionsTaxId,
       type: 'get',
       dataType: 'JSON',
       async: true,
       data: { term: query },
       success: function(data) {
         done(data);
     }
 }).done(function(data){
    done( data )
    
});
},
onSelect: function (suggestion) {
 $('#nameCustomer').val(suggestion.customer);
 $("#customerTaxId").val(suggestion.label);
 CUSTOMER_MOBILE=suggestion.customer_mobile;
 
        // alert('You selected: ' + suggestion.value + ', ' + suggestion.nit);
        
    }
    
});


function fillDataModalF() {
    if ($('#totalBs').val() != '0.00') {
        $('#changeBs').val("-" + $('#totalBs').val());
        $('#changeUsd').val("-" + $('#totalUsd').val());
        $('#ModalExit').modal('show');

    } else {
        alert("SELECCIONE ITEM POR FAVOR");
    }

    // $('#changeUsd')
}


var isMobile = {
    Android: function () {
        return navigator.userAgent.match(/Android/i);
    },
    BlackBerry: function () {
        return navigator.userAgent.match(/BlackBerry/i);
    },
    iOS: function () {
        return navigator.userAgent.match(/iPhone|iPad|iPod/i);
    },
    Opera: function () {
        return navigator.userAgent.match(/Opera Mini/i);
    },
    Windows: function () {
        return navigator.userAgent.match(/IEMobile/i);
    },
    any: function () {
        return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
    }
};


function afterDeLoad() {
    if (isMobile.any())
    {
        $('#labelUser').removeClass();
        $('#labelUser').addClass('labelUMobile');
        $('#labelExchangeRate').removeClass();
        $('#labelExchangeRate').addClass('labelTMobile');

        $('#labelShift').removeClass();
        $('#labelShift').addClass('labelShiftMobile');

        $('#h1_clock').removeClass();
        $('#h1_clock').addClass('clockMobile');
        $('#h3CurrentDate').removeClass();
        $('#h3CurrentDate').addClass('dateMobileH3');

    } else {

        $('button[name=btnMenu]').removeClass("buttonCategoryMobile");
        $('button[name=btnMenu]').addClass("buttonCategory");
        $('#divProduct').removeClass('divProductMobile');
        $('#divProduct').addClass('divProduct');

        $('#divMenuGroup').removeClass('divCategoryMobile');
        $('#divMenuGroup').addClass('divCategory');

    }

}


function putCurrency(currency) {
    if ($('#totalInvoiceBs').val()) {
        totalInvoiceBs = parseFloat($('#totalInvoiceBs').val());
        totalInvoiceBs += currency;
        totalInvoiceUsd = totalInvoiceBs / exchangeRate;
        $('#totalInvoiceBs').val(totalInvoiceBs);
        // $('#totalInvoiceUs').val((totalFacturaUsd).toFixed(2));
    } else {
        $('#totalInvoiceBs').val(currency);
        // $('#totalInvoiceUs').val((moneda/tipoCambio).toFixed(2));
    }
    takeChange();
}

function takeChange() {

    verify=(parseFloat($('#totalInvoiceBs').val()));
    typeCurrencyInvoice=$('input[name=typeCurrencyInvoice]:checked').val();
    if (typeCurrencyInvoice=="bs") {
        totalInvoiceBs =$('#totalInvoiceBs').val();    
    }else{
        totalInvoiceBs="0";
    }
    
    // if (validar('decimal')) {}
    // alert(verificar);
    if (totalInvoiceBs != "") {
        if (typeCurrencyInvoice=="dollar") {
         totalInvoiceBs=parseFloat($('#totalInvoiceUs').val()*exchangeRate);
         $('#totalInvoiceBs').val(totalInvoiceBs);
     }
     totalMust = parseFloat($('#totalBs').val());
     changeBs = $('#changeBs').val();
     totalChangeBs = parseFloat(totalInvoiceBs) - totalMust;
     $('#changeBs').val((totalChangeBs).toFixed(2));
     $('#changeUsd').val((totalChangeBs / exchangeRate).toFixed(2));
     $('#totalInvoiceUs').val(parseFloat((totalInvoiceBs / exchangeRate).toFixed(2)));
        // $('#totalInvoiceUs').val((totalFacturaBs/tipoCambio).toFixed(2));

    } else {

        $('#changeBs').val("-" + $('#totalBs').val());
        $('#changeUsd').val("-" + $('#totalUsd').val());
        $('#totalInvoiceBs').val("");
        $('#totalInvoiceUs').val("");
        // $('#totalInvoiceUs').val("");

    }
    statusChangeCheckout();

}
function keyCheckout(key) {
    var input = $('#radioCurrencyDollarInvoice').is(':checked') ? $('#totalInvoiceUs') : $('#totalInvoiceBs');
    var value = input.val();
    if (key == 'C') {
        value = '';
    } else if (key == 'delete') {
        value = value.slice(0, -1);
    } else if (key == '.') {
        if (value.indexOf('.') == -1) {
            value = (value == '' ? '0' : value) + '.';
        }
    } else {
        value += key;
    }
    input.val(value);
    if (value == '' || value == '0.') {
        $('#totalInvoiceBs, #totalInvoiceUs').val(value);
        $('#changeBs').val("-" + $('#totalBs').val());
        $('#changeUsd').val("-" + $('#totalUsd').val());
        statusChangeCheckout();
        return;
    }
    takeChange();
}

function billCheckout(currency) {
    $('#radioCurrencyBsInvoice').prop('checked', true);
    putCurrency(currency);
}

function paymentExactCheckout() {
    $('#radioCurrencyBsInvoice').prop('checked', true);
    $('#totalInvoiceBs').val(parseFloat($('#totalBs').val()) || 0);
    takeChange();
}

function statusChangeCheckout() {
    var change = parseFloat($('#changeBs').val());
    $('#checkoutChange').toggleClass('is-missing', isNaN(change) || change < 0);
}

function cancelCheckout() {
    $('#totalInvoiceBs').val('');
    $('#totalInvoiceUs').val('');
    $('#inputCashInvoice').click();
    $('#selectTypePaymentCredit').prop('selectedIndex', 0);
    $('#numberOrder').val('');
}

$('#ModalInvoice').on('show.bs.modal', function () {
    $('#checkoutTotalBs').text($('#totalBs').val());
    $('#checkoutTotalUsd').text($('#totalUsd').val());
    $('#radioCurrencyBsInvoice').prop('checked', true);
    statusChangeCheckout();
});

function  orderTable(buttonTable) {
    if ($('#totalBs').val() != '0.00') {
        $('#changeBs').val("-" + $('#totalBs').val());
        $('#changeUsd').val("-" + $('#totalUsd').val());

        $('#labelTakeaway').text("MESA");
    } else {
        alert("SELECCIONE ITEM POR FAVOR");
        return;
    }
    if (notificationDateLimit!="") {
        alertify.success(notificationDateLimit);
    }
    numTable = $('#numTable').val();
    // var nroOrden=numMesa==''
    $('#numberOrder').val(numTable);
    buttonMessage = $(buttonTable).attr('service_type');
    // salidaProducto = botonMensa + ":" + numMesa;
    exitProduct = buttonMessage;
    $('#ModalKeypadTable').modal('hide');

    $('#ModalExit').modal('hide');
    $('#ModalInvoice').modal('show');
    $('#ModalInvoice').css('overflow-y', 'scroll');
    total = $("#totalBs").val();
    var value = LITERAL(total);
    $('#numLiteral').val(value);
    $('#labelNumLiteral').text(value);
    $('#numTable').val("");
}

processRefresh=0;
function saveDetail() {//guarda la venta en geneneral1
    processRefresh++;
    $('#btnSaveDetail').attr('disabled','disabled');
    // $('#loading').css('display','block');
    var  table_name = $('#tableDetail input[name=quantity]');
    var  selectPrepaid ="";

    var nameCustomer = $('#nameCustomer').val();
    var customerTaxId = $('#customerTaxId').val();
    var  changeBs = $('#changeBs').val();
    var  changeUsd = $('#changeUsd').val();
    var  totalInvoiceBs = $('#totalInvoiceBs').val();
    var  totalInvoiceUs = $('#totalInvoiceUs').val();
    // formaPago = $('input[name=formaPago]').val();
    var  totalBs = $('#totalBs').val();
    var  totalUsd = $('#totalUsd').val();
    var  paymentMethod = $('input:radio[name=paymentMethod]:checked').val();
    var selectTypePaymentCredit=$('#selectTypePaymentCredit').val();

    if (paymentMethod == "CREDITO" && selectTypePaymentCredit!="tigomon") {
        if (nameCustomer == "SIN NOMBRE" || nameCustomer == "") {
            alert("EL CAMPO NOMBRE DE CLIENTE NO DEBE ESTAR VACIO");
            $('#btnSaveDetail').removeAttr('disabled');
            return;
        }
    }
    if (paymentMethod=="PREPAGO") {
     selectPrepaid=$('#selectPrepaid ').val();
 }
 var  saleDate = vf_sale;
 var  shift = $('#shift_value').text();
 var value = LITERAL(totalBs);
    // alert(valor);
    if (parseFloat(changeBs) < 0) {
     $('#btnSaveDetail').removeAttr('disabled');
     alertify.error('LA CANTIDAD TIENE QUE SER MAYOR AL TOTAL');

     return;
 }
 var  selectSeller = $('#selectSeller').val();
 var  idProduct = new Array();
 var quantityA = new Array();
 var priceA = new Array();
 var arrayNoteProduct = new Array();
 var NameProduct=new Array();
 var subtotalA=new Array();
 var gQr=new Array();
   var nameProduct=new Array();//este array se encarga d mandar a cocina el producto con la nota

   var counter=0;
   var  imPrint='{"imp":[';
   if (table_name.length > 0) {
    $(table_name).each(function (e) {
        var id = $(this).closest("tr").find("input[name=inputIdProduct]").val();
        var quantity = $(this).closest("tr").find("input[name=quantity]").val();
        var price = $(this).closest("tr").find("input[name=price]").val();
        var noteProduct = $(this).closest("tr").find("input[name=noteProduct]").val();
        var product_name = $(this).closest("tr").find("input[name=nameProduct]").val();
        var subTotal = $(this).closest("tr").find("#spanSubTotal").text();
        var qr = $(this).closest("tr").find("input[name=productQr]").val();
        var printStation4A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_4');
        var printStation3A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_3');
        var printStation2A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_2');
        var printStation1A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_1');
        var printKitchenA=$(this).closest("tr").find("button[name=managementPrint]").attr('print_kitchen');
        counter++;
        if(table_name.length!=counter){
            imPrint+='{"impCoc":"'+printKitchenA+'","imppunto4":"'+printStation4A+'","imppunto3":"'+printStation3A+'","imppunto2":"'+printStation2A+'","imppunto1":"'+printStation1A+'"},';
        }else{
            imPrint+='{"impCoc":"'+printKitchenA+'","imppunto4":"'+printStation4A+'","imppunto3":"'+printStation3A+'","imppunto2":"'+printStation2A+'","imppunto1":"'+printStation1A+'"}';

        }
        
        idProduct.push(id);
        arrayNoteProduct.push(noteProduct);
        NameProduct.push(product_name);
        subtotalA.push(subTotal);
        gQr.push(qr);
        priceA.push(price);
        quantityA.push(quantity);
        if (noteProduct == "") {
            nameProduct.push(product_name);
        } else {
            nameProduct.push(product_name + "(" + noteProduct + ")");
        }
    });
    imPrint+=']}';
    JSON.stringify(imPrint);
    var result=JSON.parse(imPrint);
    numberOrder=$('#numberOrder').val()==""?"":':'+$('#numberOrder').val();
    exitProduct+=numberOrder;
    $.post(ROUTES.invoice, {note: arrayNoteProduct, exitProduct: exitProduct, totalUsd: totalUsd, totalBs: totalBs, seller_id: selectSeller, quantity: quantityA, idProduct: idProduct, nameCustomer: nameCustomer, customerTaxId: customerTaxId,
        changeBs: changeBs,addressCustomer:addressCustomer,phoneCustomer:phoneCustomer ,changeUsd: changeUsd, totalInvoiceBs: totalInvoiceBs, totalInvoiceUs: totalInvoiceUs, paymentMethod: paymentMethod, saleDate: saleDate,
        shift: shift,selectPrepaid:selectPrepaid,price:priceA,nameProduct:NameProduct,subTotal:subtotalA,billiards:billiards,qr:gQr,transactionTigoMoney:transactionTigoMoney}, function (res) {
            var json = JSON.parse(res);
            var invoice=json.numberInvoice;
            if (verifyError(json.error)) {
                return;
            }
             $('#dateRateHis').val("");
            changeDateCustom=0;
            $('#ModalInvoice').modal('hide');
            $('#order_number').val(json.result2);
            // $('#loading').css('display','none');
            
            // Nota de venta (o factura) y luego la comanda, en la ticketera de la caja
            var saleId = json.result;
            var withOrder = optionItemTable == 0;
            TicketPrint.receipt(saleId, function () {
                if (withOrder) {
                    TicketPrint.saleOrder(saleId, function () {
                        if (processRefresh==10) {
                            location.reload();
                        }
                    });
                }
            });
            if (withOrder) {
                clearDetail();
            }
            if (optionItemTable == 1) {//solo paga los items selecionados
                enableUpdateTable(optionItemTable);
                $('#numTable').val("");
                $('#btnInvoice').attr('onclick', 'fillDataModalF()');
                $('#spanProcessing').text("");
                optionItemTable = 0;
            }
            if (optionItemTable == 2) {//paga toda la mesa y la habilita para el siguiente uso
                enableUpdateTable(optionItemTable);
                $('#numTable').val("");
                $('#btnInvoice').attr('onclick', 'fillDataModalF()');
                $('#spanProcessing').text("");
                optionItemTable = 0;
            }
            $('#btnSaveDetail').removeAttr('disabled');

            addressCustomer="";
            phoneCustomer="";
            idCard="";
            // ventana=window.open("legacy/REPORTEPHP/prueba4.php?idVenta="+json.result+"&&literal="+valor);    
            // ventana.close();
            alertify.success("GUARDADO CORRECTAMENTE");



            

        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    } else {
        alertify.error("coloque los datos ");
    }

}


function unnamed() {


    if (!$('#nameCustomer').val()) {
        $('#nameCustomer').val("SIN NOMBRE");
    }


}
enable_dtp = 1;
datePrevious = "";
function listHistory() {

//     if (habilitardtp == 1) {
//         $.fn.datepicker.defaults.format = "yyyy-mm-dd";
//         $('.datepicker').datepicker({
//             autoclose: true,
//             dateFormat: 'yy-mm-dd'
//         }).datepicker("setDate", f_Venta);
//         habilitardtp = 0;
//         $('#dateHistory').attr('onchange', 'listHistory()');
// //        $('.datepicker').datepicker({
// //         autoclose: true,
// //         dateFormat: 'yy-mm-dd'
// //     }).on("change", function (e) {
// //     //console.log("Date changed: ", e.target.value);
// //         alert("holi");

// // });
//     }


$('#loading').css('display', 'block');
var dateHistory = moment($('#dateHistory').val(), 'DD/MM/YYYY');

dateHistory = dateHistory.format('YYYY-MM-DD');
    datePrevious = dateHistory;
    tbodyHistory = $('#tbodyHistory');
    clearDetailHistory();
    $('.modal-backdrop').css("height", '100%');
    $.get(ROUTES.history, {dateHistory: dateHistory}, function (res, status) {
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }
        $('#tableHistory').DataTable().destroy();
        tbodyHistory.empty();
        for (var i = 0; i < json.result.length; i++) {
            tbodyHistory.append(rowHistory(json.result[i]));
        }

        paginatorTbodyHistory();
        summaryHistory();

        $('#loading').css('display', 'none');
        if (status != "success") {
            alert("LO SIENTO A SUCEDIDO UN ERROR")
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);
    });

}

function escHistory(value) {
    return $('<div>').text(value == null ? '' : value).html();
}

function rowHistory(sale) {
    var active = sale.status == "ACTIVE";
    var total = parseFloat(sale.invoice_total) || 0;
    var time = (sale.sale_time || '').substring(0, 5);
    return "<tr class='colorhover" + (active ? "" : " is-voided") + "' data-total='" + total + "'>" +
        "<td data-order='" + (parseInt(sale.order_number, 10) || 0) + "'><input name='idSale' type='hidden' value='" + sale.id + "'><strong>#" + escHistory(sale.order_number) + "</strong></td>" +
        "<td>" + escHistory(time) + "</td>" +
        "<td>" + escHistory(sale.login) + "</td>" +
        "<td>" + escHistory(sale.payment_method) + "</td>" +
        "<td>" + escHistory(sale.service_type) + "</td>" +
        "<td>" + escHistory(sale.shift) + "</td>" +
        "<td class='text-right' data-order='" + total + "'>" + total.toFixed(2) + "</td>" +
        "<td class='js-estado'>" + badgeStatusHistory(sale.status) + "</td>" +
        "<td class='js-eliminpor'>" + escHistory(sale.voided_by) + "</td>" +
        "<td class='text-right'><button class='pos-hist-action' title='Reimprimir nota de venta' onclick='TicketPrint.receipt(" + parseInt(sale.id, 10) + ", null, true)'><i class='fa fa-print'></i></button> " + (active
            ? "<button class='pos-hist-action is-void' idProduct='" + sale.id + "' onclick='voidSale(this,1)'>ANULAR</button>"
            : "<button class='pos-hist-action is-activate' idProduct='" + sale.id + "' onclick='voidSale(this,1)'>ACTIVAR</button>") +
        "</td></tr>";
}

function badgeStatusHistory(status) {
    var active = status == "ACTIVE";
    return "<span class='pos-hist-badge " + (active ? "is-ok" : "is-bad") + "'>" + (active ? "Activa" : "Anulada") + "</span>";
}

function summaryHistory() {
    var rows = $('#tbodyHistory tr').has('input[name=idSale]');
    var voided = rows.filter('.is-voided');
    var total = 0;
    rows.not('.is-voided').each(function () {
        total += parseFloat($(this).attr('data-total')) || 0;
    });
    $('#histResSales').text(rows.length);
    $('#histResActive').text(rows.length - voided.length);
    $('#histResVoided').text(voided.length);
    $('#histResTotal').text(total.toFixed(2));
}

function clearDetailHistory() {
    $('#tbodyHistoryDetail').html("<tr class='pos-hist-empty'><td colspan='3'><i class='fa fa-hand-pointer-o'></i> Seleccione una venta de la lista</td></tr>");
    $('#spanHistoryTime').text('--:--');
    $('#totalDetail').text('0.00');
    $('#nameCustomerHistory, #processHistory, #df, #td').val('');
}

$("#tableHistory tbody").on('click', 'tr', function () {    // function_tr
    var data = $(this).find("input[name=idSale]").val();
    if (!data) {
        return;
    }
    $('#tbodyHistory tr.is-selected').removeClass('is-selected');
    $(this).addClass('is-selected');
    totalDetail = 0;
    tbodyHistoryDetail = $('#tbodyHistoryDetail');
    $.get(ROUTES.saleItem, {id: data}, function (res) {
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }
        tbodyHistoryDetail.empty();
        for (var i = 0; i < json.result.length; i++) {
            var subTotal=parseFloat(json.result[i].subtotal) || 0;
            totalDetail +=subTotal;
            tbodyHistoryDetail.append("<tr>" +
                "<td class='text-right'>" + escHistory(json.result[i].quantity) + "</td>" +
                "<td>" + escHistory(json.result[i].product_name) + "</td>" +
                "<td class='text-right'>" + subTotal.toFixed(2) + "</td></tr>"
                );
        }
        if (json.result.length == 0) {
            tbodyHistoryDetail.append("<tr class='pos-hist-empty'><td colspan='3'>La venta no tiene productos</td></tr>");
        }
        $('#spanHistoryTime').text(json.result2[0].sale_time || '--:--');
        $('#totalDetail').text(totalDetail.toFixed(2));
        $('#nameCustomerHistory').val(json.result2[0].customer_name);
        $('#processHistory').val(json.result2[0].id);
        $('#df').val(json.result2[0].invoice_number);
        $('#td').val(json.result2[0].real_invoice_number);
    }).fail(function (res) {
        $('#loading').css('display', 'none');
        alert("ERROR INTENTE NUEVAMENTE");
    });
});
function paginatorTbodyHistory() {
    $('#tableHistory').DataTable({
        "destroy": true,
        "order": [[0, "asc"]],
        "scrollY": "calc(100vh - 330px)",
        "scrollCollapse": true,
        "paging": false,
        "sun": "t<'pos-hist-info'i>",
        "columnDefs": [{"orderable": false, "targets": [9]}],
        "language": {
            "emptyTable": "No hay ventas registradas en esta fecha",
            "zeroRecords": "Ninguna venta coincide con la búsqueda",
            "info": "Mostrando _TOTAL_ ventas",
            "infoEmpty": "Sin ventas",
            "infoFiltered": "(filtradas de _MAX_)"
        },
        retrieve: true

    });
    var search = $('#searchHistory').val();
    if (search) {
        $('#tableHistory').DataTable().search(search).draw();
    }
}
$('#searchHistory').on('input', function () {
    $('#tableHistory').DataTable().search(this.value).draw();
});
$('#ModalHistoryDay').on('shown.bs.modal', function () {
    $('#tableHistory').DataTable().columns.adjust();
});

function voidSale(button, index) {
    id = $(button).attr('idProduct');
    status = $(button).text();
    $('#loading').css('display', 'block');
    $.post(ROUTES.voidSale, {id: id, status: status}, function (res, status) {
        var json = JSON.parse(res);
        var row = $(button).parents("tr");
        if (verifyError(json.error)) {
            return;
        }
        if (status == "ANULAR") {
            $(button).removeClass('is-void').addClass('is-activate').text("ACTIVAR");
            row.addClass('is-voided');
            row.find('td.js-status').html(badgeStatusHistory("INACTIVE"));
        } else {
            $(button).removeClass('is-activate').addClass('is-void').text("ANULAR");
            row.removeClass('is-voided');
            row.find('td.js-status').html(badgeStatusHistory("ACTIVE"));
        }
        summaryHistory();

        $('#loading').css('display', 'none');

    }).fail(function () {
        alert("ERROR INTENTE NUEVAMENTE");
    });
}

function loadSuggestion(button, id) {
    buttonProduct = button;//variable global

    idProd = id;//variable global
    spanName = $('#tableDetail span[id="spanProd' + idProd + '"]');
// if (spanNombre.length!=0) {
// listSaleItem(boton,id);
// }else{
    $('#titleProductSuggestion').val($(button).attr('name'));
    // $('#loading').css('display','block');
    $('#divSuggestion').empty();
    $.get(ROUTES.optionsProduct, {idProduct: id}, function (res) {
        json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }
        if (json.result.length != 0) {
            for (var i = 0; i < json.result.length; i++) {
                $('#divSuggestion').append("<button style='    margin-top: 4px;min-width: 100px;max-width: 100px; margin-right: 3px; white-space: inherit;font-size: 14px; padding: 0;    min-height: 41px;' onclick='loadSuggestionProduct(this)' class='btn btn-lg'>" + json.result[i].option_name);
            }

            $('#ModalSuggestions').modal('show');
            $('#titleProductSuggestion').text($(button).attr('name'));

        } else {
            listSaleItem(button, id,"");
        }
        // $('#loading').css('display','none');

    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
// }
}
detail = new Array();
count = 0;
function loadSuggestionProduct(button) {

    $(button).css('background', '#96f1bf');
    $(button).attr('disabled', true);
    suggestion = $(button).text();

    detail[count] = suggestion;
    count++;
}
function addSuggestionProduct() {
    nameProduct = $(buttonProduct).text();
    suggestion = "";
    if (detail.length == 0) {//si no selecciona ninguna sugerencia entra por verdadero
        listSaleItem(buttonProduct, idProd,"");
        $('#ModalSuggestions').modal('hide');
        return;
    }
    for (var i = 0; i < detail.length; i++) {
        // if (i==detalle.length-1) {

        // }
        suggestion += detail[i] + ", ";
    }
    target = $('#tableDetail span[id="spanProd' + idProd + '"]');
    if (target.length == 0) {//cuando es por primara vez
        index = listSaleItem(buttonProduct, idProd,"");

        spanName = $('#tableDetail span[id="spanProd' + idProd + '"]');
        inputProductId = $('#tableDetail input[id="inputProductId' + index + '"]');
        note = $('#tableDetail input[id="noteProduct' + index + '"]');
//aqui
note.val(suggestion);
suggestion = inputProductId.val() + "(" + suggestion + ")";
spanName.attr('product_name', suggestion);

// alert(spanNombre.text());
        // alert(sugerencia);
        if (suggestion == spanName.text()) {
// alert("existe");
} else {
    spanName.text(spanName.text() + "(" + suggestion + ")");
}
} else {
    inputProductId = $(buttonProduct).text();
    product_name = $(buttonProduct).text();
    suggestion = product_name + "(" + suggestion + ")";
    spanName = $('#tableDetail span[id="spanProd' + idProd + '"]');
        // inputProductoId = $('#tableDetail input[id="inputProductoId' + idProd + '"]');
        name = $(buttonProduct).attr('name');
        price = $(buttonProduct).attr('price');
        product_quantity = $(buttonProduct).attr('quantity');

        if (suggestion == spanName.text()) {//si es igual a la sugerencia no agrega al detalle solo cuenta a la cantidad

            $('#buttonHidden').attr('name', name + suggestion);
            $('#buttonHidden').attr('price', price);
            $('#buttonHidden').attr('quantity', product_quantity);
            $('#buttonHidden').text(suggestion);

            index = listSaleItem($('#buttonHidden'), idProd,"");
            $('#inputProductId' + index).val($(buttonProduct).text());//renombra nombre del producto del input ocultado
            note = $('#tableDetail input[id="noteProduct' + index + '"]');
            note.val(suggestion);
        } else {


            $('#buttonHidden').attr('name', suggestion);
            $('#buttonHidden').attr('price', price);
            $('#buttonHidden').attr('quantity', product_quantity);

            $('#buttonHidden').text(suggestion);
            index = listSaleItem($('#buttonHidden'), idProd,"");
            $('#inputProductId' + index).val($(buttonProduct).text());//renombra nombre del producto del input ocultado
            note = $('#tableDetail input[id="noteProduct' + index + '"]');
            note.val(suggestion);

        }

    }
    $('#ModalSuggestions').modal('hide');
    detail = new Array();
    count = 0;

}
//Ocultamos el men� al cargar la p�gina
$("#menu").hide();

typeExpense = "";
idTable = "";
numberTable = "";
optionItemTable = 0;
nameTable = "";//esta variable es para guardar el nombre del boton de la mesa
$(function () {
    $.contextMenu({
        selector: '.btnMenu',
        callback: function (key, options) {
            switch(key){
                case 'INSUMO':
                var idProduct=$(this).attr('idProduct');
                listSupplyGivenProduct(idProduct);
                break;
                case 'DESACTIVAR':
                var idProduct=$(this).attr('idProduct');
                deactivateProduct(idProduct);
                break;
                case 'MOVER':
                var idProduct=$(this).attr('idProduct');
                $('#ModalButtonProduct').modal('show');
                $('#idProductForMenu').val(idProduct);
                // moveProductMenu(idProducto); 
                break;
            }
        },
        items: {
            "INSUMO": {name: "INSUMOS QUE LLEVA ESTE PRODUCTO", icon: "fa-edit"},
            "DESACTIVAR": {name: "DESACTIVAR EL BOTON", icon: "fa-cloud-download"},
            "MOVER": {name: "MOVER A OTRA PAGINA O BOTON", icon: "fa-certificate"}
        }
    });

    $('.context-menu-one').on('click', function (e) {
        console.log('clicked', this);
    })


    $.contextMenu({
        selector: '#btnExpensesNew',
        trigger: 'left',
        callback: function (key, options) {
            typeExpense = key;
            $('#btnExpensesNew').text(typeExpense);
            $('#inputExpensesDelivered').attr("readonly", false);
            $('#inputExpensesConcept').attr("readonly", false);
            $('#inputExpensesAmount').attr("readonly", false);
            $('#inputInvoiceExpense').attr("readonly", false);
            listTExpenses(key);
        },
        items: {
            "INGRESO": {name: "INGRESO"},
            "EGRESO": {name: "COMPRA-EGRESO"}
        }
    });
    $.contextMenu({
        selector: '.menuNota',
        trigger: 'left',
        callback: function (key, options) {
         rowName = $(this).closest("tr").find("span[name=nameProduct]");
         rowNameInput = $(this).closest("tr").find("input[name=nameProduct]");
         noteProduct= $(this).closest("tr").find("input[name=noteProduct]");
         noteProduct.val(noteProduct.val()+","+key+",");
         rowName = $(this).closest("tr").find("span[name=nameProduct]");
         rowName.attr('product_name', noteProduct.val());
         rowName.text(rowNameInput.val() + "(" + noteProduct.val() + ")");
         
     },
     items: {
        "MESA": {name: "MESA"},
        "LLEVAR": {name: "LLEVAR"},
        "ABAJO": {name: "ABAJO"}
    }
});



    $.contextMenu({
        selector: '.agregarPM',
        trigger: 'left',
        callback: function (key, options) {

            if ($('#selectWaiterTables').val()=="0") {
             alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "* DEBE DE SELECCIONAR EL MESERO PRIMERO", function () {
                alertify.message('OK');
            });
         }else{

            idTable = $(this).attr('idTable');
            numberTable = $(this).attr('numberTable');
            verifyEmployee($(this));   
        }
            // addProductTable(idMesa,nroMesa);
        },
        items: {
            "AGREGAR": {name: "ABRIR"}

        }
    });


    $.contextMenu({
        selector: '.aumentarPM',
        trigger: 'left',
        callback: function (key, options) {
            if ($('#spanProcessing').text()!="") {
                alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "EXISTEN PRODUCTOS EN PROCESO DE FACTURACION.", function () {
                    alertify.message('OK');
                });
            }else{
                if (key === "AUMENTAR") {
                    idTable = $(this).attr('idTable');
                    numberPerson = $(this).attr('numberPerson');
                    numberTable = $(this).text() == "" ? "0" :  $(this).attr('numberTable');
                    nameTable = $(this).text();
                    increaseProductTable(idTable, numberTable);
                } else {


                 idTable = $(this).attr('idTable');
                 numberPerson = dining_room=="no_dining_room"?$(this).attr('numberPerson'):$(this).text();
                 numberTable = $(this).text() == "" ? "0" : $(this).attr('numberTable');

                 nameTable = $(this).text();
                 listTableItem(idTable, numberTable, numberPerson);

             }
         }
            // addProductTable(idMesa,nroMesa);
        },
        items: {
            "AUMENTAR": {name: "AUMENTAR"},
            "DETALLE": {name: "VER DETALLE"}
        }
    });

    $.contextMenu({
        selector: '#btnActionItem',
        trigger: 'left',
        callback: function (key, options) {

            switch (key) {
                case "PAGAR":
                payItemSelectedTable();
                optionItemTable = 1;

                break;
                case "TRASPASAR":
                transferItemSelectedTable(2);
                optionItemTable = 3;
                break;
                case "ELIMINARITEM":
                deleteItemQty();
                break;
            }
            // addProductTable(idMesa,nroMesa);
        },
        items: {
            "PAGAR": {name: "PAGAR ITEMS"},
            "TRASPASAR": {name: "TRASPASAR ITEMS A OTRA MESA"},
            "ELIMINARITEM": {name: "ELIMINAR ITEM"}


        }
    });


});
// $(document).bind("contextmenu ", function(e){
//                   $("#menu").css({'display':'block', 'left':e.pageX, 'top':e.pageY});
//                   return false;
//   });

//  $(document).click(function(e){
//                   if(e.button == 0){
//                         $("#menu").css("display", "none");
//                   }
// });
//        


function getIpCustomer() {
    $.get(ROUTES.printBranch, {idBranch:idBranch}, function (res) {
        var json_ip = JSON.parse(res);
        ipCustomer = json_ip.result[0].ipPrint;
        
    });
}

function typePayment(radio) {
    payment_type = $(radio).val();
    switch (payment_type) {
        case "CREDITO":
        $('#divSelectedCredit').css('display', 'block');
        $('#divInputConsumptionInternal').css('display', 'none');
        $('#divSelectedPrePayment').css('display', 'none');

        break;
        case "CONTADO":
        $('#divSelectedCredit').css('display', 'none');
        $('#divInputConsumptionInternal').css('display', 'none');
        $('#divSelectedPrePayment').css('display', 'none');


        break;
        case "CONSUMO":
        $('#divSelectedCredit').css('display', 'none');
        $('#divInputConsumptionInternal').css('display', 'block');

        $('#divSelectedPrePayment').css('display', 'none');

        break;
        case "PREPAGO":
        $('#divSelectedCredit').css('display', 'none');
        $('#divInputConsumptionInternal').css('display', 'none');
        $('#divSelectedPrePayment').css('display', 'block');

        break;
        case "QR":
        $('#divSelectedCredit').css('display', 'none');
        $('#divInputConsumptionInternal').css('display', 'none');
        $('#divSelectedPrePayment').css('display', 'none');
        // El QR se paga por el monto exacto: no hay cambio que devolver.
        paymentExactCheckout();

        break;
    }
}
function listTDeliveredExpense() {
    $.get(ROUTES.delivered, {}, function (res) {
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }
        var availableTagsV = json.result;

        $("#inputExpensesDelivered").autocomplete({
            lookup: availableTagsV
        });
    });
}
function listTExpenses(typeTransaction) {
    $('#tbodyExpenses').empty();
    var date = vf_sale;
    $.get(ROUTES.expenses, {date: date,typeTransaction:typeTransaction}, function (res) {
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }
        for (var i = 0; i < json.result.length; i++) {
            $('#tbodyExpenses').append("<tr><td>" + json.result[i].expense_number +
                "<td>" + json.result[i].delivered_to +
                "<td>" + json.result[i].concept +
                "<td>" + json.result[i].amount +
                "<td>" + json.result[i].expense_date +
                "<td>" + json.result[i].login
                );
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}
function saveExpense() {
    if ($('#btnExpensesNew').text()=="NUEVO") {
     alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>',
      "DEBE PRESIONAR EL BOTON NUEVO  Y ELEGIR EL TIPO DE TRANSACCION", function () {
        alertify.message('OK');

    });
     return;
 }
 delivered = $('#inputExpensesDelivered').val();
 concept = $('#inputExpensesConcept').val();
 amount = $('#inputExpensesAmount').val();
 shift = $('#shift_value').text();
 invoice_ref = $('#inputInvoiceExpense').text();
 dateInvoice=$('#dateInvoice').val();
 taxIdSupplier= $('#taxIdSupplier').val();
 numberAuthorization= $('#numberAuthorization').val();
 controlCode= $('#controlCode').val();
 numberInvoice= $('#inputNumberInvoice').val();
 hasTaxCredit=$('#checkboxTaxCredit').prop('checked')?1:0;
 hasInvoice=$('#checkboxIncludesInvoice').prop('checked')?1:0;
 leftCashRegister=$('#checkboxLeftCashRegister').prop('checked')?1:0;

 var dateInvoice = moment(dateInvoice, 'DD/MM/YYYY');

 dateInvoice = dateInvoice.format('YYYY-MM-DD');
 vatTaxCredit= $('#inputDebitFiscal').val();
 $.post(ROUTES.saveExpense, {date: vf_sale, 
    delivered: delivered, concept: concept,
    amount: amount, shift: shift,invoice_ref:invoice_ref,typeExpense:typeExpense,dateInvoice:dateInvoice,
    taxIdSupplier:taxIdSupplier,numberAuthorization:numberAuthorization,controlCode:controlCode,
    numberInvoice:numberInvoice,vatTaxCredit:vatTaxCredit,hasTaxCredit:hasTaxCredit,
    hasInvoice:hasInvoice,leftCashRegister:leftCashRegister
}, function (res) {
    var json = JSON.parse(res);

    if (verifyError(json.error)) {
        return;
    }
    if (cloud_print=="cloud_print") {
     
       $.post('http://'+ ipCustomer + ':8080/reportes/ingreso_egreso.php',{restaurant:restaurant,date:vf_sale,typeExpense:typeExpense,amount:amount,concept:concept,delivered:delivered,username:$('#spanUser').text()},function(res){
          
       });
   }else{
       $.post('legacy/reports/login_expense.php',{restaurant:restaurant,date:vf_sale,typeExpense:typeExpense,amount:amount,concept:concept,delivered:delivered,username:$('#spanUser').text()},function(res){
          
       });
   }
   
   $('#btnExpensesNew').text("NUEVO");
   $('#inputExpensesDelivered').attr("readonly", true);
   $('#inputExpensesConcept').attr("readonly", true);
   $('#inputExpensesAmount').attr("readonly", true);
   $('#inputInvoiceExpense').attr("readonly", true);

   $('#inputExpensesDelivered').val("");
   $('#inputExpensesConcept').val("");
   $('#inputExpensesAmount').val("");
   $('#inputInvoiceExpense').val("");
   $('#dateInvoice').val("");
   $('#taxIdSupplier').val("");
   $('#numberAuthorization').val("");
   $('#controlCode').val("");
   $('#inputNumberInvoice').val("");
   $('#checkboxTaxCredit').prop('checked',false);
   $('#checkboxIncludesInvoice').prop('checked',false);
   $('#inputDebitFiscal').val("");
   $('#ModalPurchaseVat').modal('hide');

});
}

function addProductTable(person) {//parametro de entra cantidad de clientes en la mesa
    idTable = idTable;//la trae de una variable publica
    numberTable = numberTable;//la trae de una variable publica
    idWaiter = idWaiter;//la trae de una variable publica

    table_name = $('#tableDetail input[name=quantity]');

    selectSeller = $('#selectSeller').val();
    var idProduct = new Array();
    var quantityA = new Array();
    var nameProduct = new Array();
    var arrayNoteProduct = new Array();
    var   priceA = new Array();
    var NameProduct=new Array();
    var priceA=new Array();
    var subtotalA=new Array();
    var counter=0;
    var imPrint='{"imp":[';

    if (table_name.length > 0) {
        $(table_name).each(function (e) {
            var id = $(this).closest("tr").find("input[name=inputIdProduct]").val();
            var quantity = $(this).closest("tr").find("input[name=quantity]").val();
            var nameProduct = $(this).closest("tr").find("input[name=nameProduct]").val();
            var noteProduct = $(this).closest("tr").find("input[name=noteProduct]").val();
            var price = $(this).closest("tr").find("input[name=price]").val();
            var product_name = $(this).closest("tr").find("input[name=nameProduct]").val();
            var subTotal = $(this).closest("tr").find("#spanSubTotal").text();
            var printStation4A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_4');
            var printStation3A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_3');
            var printStation2A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_2');
            var printStation1A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_1');
            var printKitchenA=$(this).closest("tr").find("button[name=managementPrint]").attr('print_kitchen');
            quantityA.push(quantity);
            idProduct.push(id);
            arrayNoteProduct.push(noteProduct);
            NameProduct.push(product_name);
            subtotalA.push(subTotal);
            priceA.push(price);
            counter++;
            if(table_name.length!=counter){
                imPrint+='{"impCoc":"'+printKitchenA+'","imppunto4":"'+printStation4A+'","imppunto3":"'+printStation3A+'","imppunto2":"'+printStation2A+'","imppunto1":"'+printStation1A+'"},';
            }else{
                imPrint+='{"impCoc":"'+printKitchenA+'","imppunto4":"'+printStation4A+'","imppunto3":"'+printStation3A+'","imppunto2":"'+printStation2A+'","imppunto1":"'+printStation1A+'"}';

            }
            if (noteProduct == "") {
                nameProduct.push(nameProduct);
            } else {
                nameProduct.push(nameProduct + "(" + noteProduct + ")");
            }
        });
        imPrint+=']}';
        JSON.stringify(imPrint);
        result=JSON.parse(imPrint);
        if (billiards=="billiards") {
           quantityA.push("1");
           idProduct.push("99999");
           arrayNoteProduct.push("");
           NameProduct.push("Atencion");
           subtotalA.push("0");
           priceA.push("0");

       }

       

       var saleDate = vf_sale;
       var shift = $('#shift_value').text();

       $.post(ROUTES.openTable, {qtyCustomer: person, note: arrayNoteProduct
        , quantity: quantityA, idWaiter: idWaiter, shift: shift, idProduct: idProduct, idTable: idTable
        , numberTable: numberTable, saleDate: saleDate,dining_room:dining_room,waiters_no_code:waiters_no_code,NamePro:NameProduct,price:priceA,subtotal
        :subtotalA,billiards:billiards,percentRest:percentRest}, function (res) {
            var json = JSON.parse(res);
            if (verifyError(json.error)) {
                return;
            }
            var timeA=horaActualSistemaOp();
            var PRODUCT_NAME=$('#selectWaiterTables option:selected').text();
            // Comanda en la ticketera de la caja (json.result = número de comanda de la mesa)
            TicketPrint.tableOrder(idTable, json.result, saleDate);
            clearDetail();
            alertify.success('PRODUCTOS AGREGADOS A MESA');

            $('#aBilling').click();
            idTable = "";
            numberTable = "";

        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });

    } else {
        alertify.alert('<span style="font-weight: bold;    color: red;">ADVERTENCIA</span>', "DEBE SELECCIONAR AL MENOS UN ITEM", function () {

        });
    }
}
function listWaiter() {
    $.get(ROUTES.waiters, {}, function (res) {
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }
        for (var i = 0; i < json.result.length; i++) {
            if (json.result[i].status=="ACTIVE") {
               var code=waiters_no_code=="waiters_no_code"?json.result[i].id:json.result[i].id;
               $('#selectWaiterTables').append("<option value=" + code + " >" + json.result[i].staff_name);
               $('#selectSeller').append("<option value=" + json.result[i].id + " >" + json.result[i].staff_name);
           }
       }
   }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

// function verificarEmpleado(){
//  $.get(RUTAS.meseros, {},function(res){
//        var json = JSON.parse(res);
//        for (var i = 0; i < json.result.length; i++) {
//             $('#selectWaiterTables').append("<option value="+json.result[i].ID+" >"+json.result[i].NOM_EMPL);
//         }

//     })
// }
idWaiter = "";
function verifyEmployee(btnTable) {
    validated = false;
    if (no_staff_view=="no_staff_view") {
       idWaiter = $('#selectWaiterTables').val();
       addProductTable("");
   }else{
    alertify.prompt("CODIGO EMPLEADO", "CODIGO", "",
        function (evt, value) {
            idWaiter = $('#selectWaiterTables').val();

            code = value.trim();
            if (idWaiter == code || waiters_no_code=="waiters_no_code") {
                if (dining_room == "no_dining_room") {
                    person = prompt("POR FAVOR COLOQUE LA CANTIDAD DE PERSONAS", "");


                } else {
                    person = prompt("POR FAVOR COLOQUE EL NOMBRE DEL CLIENTE", "");

                }

                addProductTable(person);



            } else {
                alertify.error('CODIGO ERRONEO INTENTE NUEVAMENTE');
                validated = false;
            }
                // GuardarProductoAGrupo(value);
                //  alertify.success('GUARDADO CORRECTAMENTE');
            },
            function () {
                // alertify.error('Cancel');
            });
}


}
timeA="";
nameEmployeeServed="";
function listTableItem(idTable, numberTable, numberPerson) {
    saleDate = vf_sale;
    $('#tbodyTableItem').empty();
    
    $.get(ROUTES.tableDetail, {idTable: idTable, numberTable: numberTable,
       saleDate: saleDate}, function (res) {
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return false;
        }
        total=0;
        if (json.result=="") {
            alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "NO ES DE LA FECHA ACTUAL", function () {
                alertify.message('OK');

            });
            return false;
        }
        for (var i = 0; i < json.result.length; i++) {
            // if (json.result[i].) {}
            if (parseInt(json.result[i].quantity) != 0) {
                note = json.result[i].note == "" ? "" : "(" + json.result[i].note + ")";
                var typeProduct=json.result[i].timed=="T"?"TIEMPO":"";
                if(json.result[i].timed=="T"){
                // var fecha1 = moment('2016-07-12');
                // var fecha2 = moment('2016-08-1');
                // console.log(fecha2.diff(fecha1, 'hours'), ' horas de diferencia');

                quantity=getTimeBilliards(json.result[i].time_start,json.result[i].time_end);
                
                timeA=horaActualSistemaOp();
                subTotal=getSubtotalBilliards(quantity,json.result[i].sale_price);
                total=parseFloat(total)+parseFloat(subTotal);
                buttonTime=json.result[i].time_end==""?"<button title='PARAR TIEMPO' class='btn btn-success btn-sm '  onclick='stopTimeBilliards("+json.result[i].id+",this)'>"+ json.result[i].time_start:"<button disabled='' title='PARAR TIEMPO' class='btn btn-success btn-sm '  onclick='stopTimeBilliards("+json.result[i].id+",this)'>"+ json.result[i].time_start+" / "+json.result[i].time_end;
                $('#tbodyTableItem').append("<tr><td><input type='hidden' name='idTableItem' value='" + json.result[i].id + "' class='form-control'>" + quantity +
                    "<td><input  style='width: 90px;' onchange='validateQuantityItemTable(this)' type='number' name='inputAction' class='form-control'>" +
                    "<td><input type='hidden' name='typeProduct' value='"+typeProduct+"'><input type='hidden' name='idProductTable' value='" + json.result[i].product_code + "' >" + json.result[i].product_name + note +
                    "<td style='text-align:right'>" + json.result[i].sale_price +
                    "<td style='text-align:right'>" + subTotal +
                    "<td>"+buttonTime
                    );
            }else{
                buttonDeleteItem="";
                buttonDeleteItem=json.result[i].product_code=="99999"?"":"<button class='btn btn-danger btn-sm delete-btn' onclick='deleteItem("+json.result[i].id+",this)'><i class='fa fa-trash-o' aria-hidden='true'></i></button>";
                $('#tbodyTableItem').append("<tr><td><input type='hidden' name='idTableItem' value='" + json.result[i].id + "' class='form-control'>" + json.result[i].quantity +
                    "<td><input  style='width: 90px;' onchange='validateQuantityItemTable(this)' type='number' name='inputAction' class='form-control'>" +
                    "<td><input type='hidden' name='typeProduct' value='"+typeProduct+"'><input type='hidden' name='idProductTable' value='" + json.result[i].product_code + "' >" + json.result[i].product_name + note +
                    "<td style='text-align:right'>" + json.result[i].sale_price +
                    "<td style='text-align:right'>" + (parseFloat(json.result[i].sale_price * json.result[i].quantity)).toFixed(3) +
                    "<td>"+buttonDeleteItem
                    );

                total=parseFloat(total)+parseFloat(json.result[i].sale_price * json.result[i].quantity);
            }

        }
        nameEmployeeServed=json.result[i].staff_name;

    }
    $('#spanEmpServed').text(nameEmployeeServed);
    if (billiards=="billiards") {
       var targetInput = $('#tbodyTableItem input[value="99999"]');

       priceService=targetInput.val()!=undefined?getPriceService(total):0;
       total=priceService+total;

       $(targetInput).closest("tr").find("input[name=inputAction]");

       $(targetInput).parents("tr").find("td").eq(3).text(priceService.toFixed(2));
       $(targetInput).parents("tr").find("td").eq(4).text(priceService.toFixed(2));

   }

   $('#tdTotalTableItem').text((total).toFixed(3));
   $('#ModalTableItem').modal('show');
   $('#spanModalTableItem').text(numberTable);
   $('#spanModalDetailNumberPerson').text(numberPerson);
   return true;
}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

function validateQuantityItemTable(input) {
    quantity = parseFloat($(input).val());
    var data = $(input).parents("tr").find("td").eq(0);
    quantity2 = parseFloat(data.text());
    if (quantity > quantity2) {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "* LA CANTIDAD ES MAYOR A LA ORIGINAL", function () {
            alertify.message('OK');
        });
        $(input).val("");

    }

}
tablePay = "";
function payItemSelectedTable() {
    table_name = $('#tableTableItem input[name=idTableItem]');
    var  counter= 0;
    error = "";
    tablePay = idTable;
    idItem = new Array();
    quantityTableProd = new Array();
    nameProduct = new Array();
    idProdMonth = new Array();
    counter2 = 0;
    if (table_name.length > 0) {
        tbody = $('#tbodyDetail');
        tbody.empty();
        $('#nameCustomer').val(nameTable);
        $(table_name).each(function (e) {
            var idDetail = $(this).closest("tr").find("input[name=idTableItem]").val();
            var idProductTable = $(this).closest("tr").find("input[name=idProductTable]").val();
            var quantity = $(this).closest("tr").find("input[name=inputAction]").val();
            var idProductTable = $(this).closest("tr").find("input[name=idProductTable]").val();
            var quantity2 = $(this).closest("tr").find("input[name=inputAction]");
            var quantityDetail = $(quantity2).parents("tr").find("td").eq(0);
            var nameProduct = $(quantity2).parents("tr").find("td").eq(2).text();
            var price = $(quantity2).parents("tr").find("td").eq(3).text();
            var typeProduct=$(this).closest("tr").find("input[name=typeProduct]").val();
            var subtotal=$(quantity2).parents("tr").find("td").eq(4).text();
            if (typeProduct=="TIEMPO" && quantity!="") {
              quantity=quantityDetail.text();
          }

          if (parseFloat(quantityDetail.text()) == parseFloat(quantity)) {
            counter++;
        }
        if (quantity != "" && quantity != "0") {

            quantityTableProd.push(quantity);
            idItem.push(idDetail);
            idProdMonth.push(idProductTable);
            $('#buttonHidden').attr('subtotal', subtotal);
            $('#buttonHidden').attr('name', nameProduct);
            $('#buttonHidden').attr('price', price);
            $('#buttonHidden').attr('quantity', quantity);
            $('#buttonHidden').attr('typeProduct', typeProduct);
            $('#buttonHidden').text(nameProduct);
            listSaleItem($('#buttonHidden'), idProductTable);

        } else {
            counter2++;
        }

        if (counter == table_name.length || counter2 == table_name.length) {
            alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "<span style='font-weight: bold; '>* DEBE PAGAR MESA COMPLETA O DEBE PONER ALGUNA CANTIDAD EN LA COLUMNA ACCION</span><br>", function () {
                alertify.message('OK');
            });
            clearDetail();
            error = "error";
        }
    });
        if (error == "") {
            $('#ModalTableItem').modal('hide');
            $('#aBilling').click();
            tableProcessing();
            $('#numTable').val(numberTable);
            $('#btnInvoice').attr('onclick', 'orderTable(this)');//el boton facturar le agrego esa funcion para q no muestre el modal de opcion salida
            $('#btnInvoice').attr('service_type', 'MESA');
        }


    }
}
function tableProcessing(option_name) {

    $.post(ROUTES.tablesProcessing, {idTable: tablePay}, function (res) {
        $('#spanProcessing').css('display', 'inline-block');
        $('#spanProcessing').text($('#spanProcessing').text() + "PROCESANDO: " + numberTable);



    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}

enableTable = 0;//0 = no se da de baja, 1 se da baja
function transferItemSelectedTable(option_name) {//opcion =1 traspasa todo, opcion 2 traspasa item por item
    var table_name = $('#tableTableItem input[name=idTableItem]');
    var counterTableTransfer = 0;
    var counterVerify = 0;
    idItem = new Array();
    quantityA = new Array();
    nameProduct = new Array();
    idProdMonth = new Array();
    if (table_name.length > 0) {


        $(table_name).each(function (e) {

            var idDetail = $(this).closest("tr").find("input[name=idTableItem]").val();
            var idProductTable = $(this).closest("tr").find("input[name=idProductTable]").val();
            var quantity = $(this).closest("tr").find("input[name=inputAction]").val();
            var idProductTable = $(this).closest("tr").find("input[name=idProductTable]").val();
            var quantity2 = $(this).closest("tr").find("input[name=inputAction]");
            var quantityDetail = $(quantity2).parents("tr").find("td").eq(0);
            var nameProduct = $(quantity2).parents("tr").find("td").eq(2).text();
            var price = $(quantity2).parents("tr").find("td").eq(3).text();
            if (option_name==1) {
                quantity=quantityDetail.text();
            }
            if ((parseFloat(quantityDetail.text()) - parseFloat(quantity)) == 0) {
                counterVerify++;
            }
            if (parseFloat(quantityDetail.text()) == parseFloat(quantity)) {
                counter++;
            }
            if (quantity != "" && quantity != "0") {
                quantityA.push(quantity);
                idItem.push(idDetail);
                idProdMonth.push(idProductTable);

            } else {
                counterTableTransfer++;
            }
        });
        if (counterVerify == table_name.length) {
            enableTable = 1;
        } else {
            enableTable = 0;
        }
        if (counterTableTransfer == table_name.length) {
            alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "<span style='font-weight: bold; '>* DEBE COLOCAR AL MENOS UNA CANTIDAD A TRASPASAR</span><br>", function () {
                alertify.message('OK');
            });
        } else {
            $('#ModalTablesEnabled').modal('show');
        }


    }
}

function transferItemTable(button) {
    idTableTransfer = $(button).attr('idTable');
    statusTable = $(button).attr('status');
    numberTableTransfer = $(button).text();
    idTableDecrease = idTable;
    date = vf_sale;
    $.post(ROUTES.transferTable, {dining_room:dining_room, statusTable: statusTable, enableTable: enableTable, numberTableTransfer: numberTableTransfer, date: date, idItem: idItem, quantity: quantityA, idTableTransfer: idTableTransfer,
        idTableDecrease: idTableDecrease}, function (res) {
            var json = JSON.parse(res);
            if (verifyError(json.error)) {
                return;
            }
            $('#ModalTablesEnabled').modal('hide');
            $('#ModalTableItem').modal('hide');
            listTables();

        });
}
function increaseProductTable(idTable, numberTable) {
    table_name = $('#tableDetail input[name=quantity]');
    idProduct = new Array();
    quantityA = new Array();
    nameProduct = new Array();
    arrayNoteProduct = new Array();
    saleDate = vf_sale;
    var timeEnabled="";
    var counter=0;
    var imPrint='{"imp":[';
    var subtotalA=new Array();

    if (table_name.length > 0) {
        var  productType1=$(table_name).closest("tr").find("input[value=T]").val()!=undefined?"T":"";
        $(table_name).each(function (e) {
            var id = $(this).closest("tr").find("input[name=inputIdProduct]").val();
            var quantity = $(this).closest("tr").find("input[name=quantity]").val();
            var nameProduct = $(this).closest("tr").find("input[name=nameProduct]").val();
            var noteProduct = $(this).closest("tr").find("input[name=noteProduct]").val();
            var typeProduct = $(this).closest("tr").find("input[name=typeProduct]").val();
            var subTotal = $(this).closest("tr").find("#spanSubTotal").text();

            quantityA.push(quantity);
            idProduct.push(id);
            subtotalA.push(subTotal);
            arrayNoteProduct.push(noteProduct);
            var printStation4A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_4');
            var printStation3A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_3');
            var printStation2A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_2');
            var printStation1A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_1');
            var printKitchenA=$(this).closest("tr").find("button[name=managementPrint]").attr('print_kitchen');
            counter++;
            if(table_name.length!=counter){
                imPrint+='{"impCoc":"'+printKitchenA+'","imppunto4":"'+printStation4A+'","imppunto3":"'+printStation3A+'","imppunto2":"'+printStation2A+'","imppunto1":"'+printStation1A+'"},';
            }else{
                imPrint+='{"impCoc":"'+printKitchenA+'","imppunto4":"'+printStation4A+'","imppunto3":"'+printStation3A+'","imppunto2":"'+printStation2A+'","imppunto1":"'+printStation1A+'"}';

            }
            if (noteProduct == "") {
                nameProduct.push(nameProduct);
            } else {
                nameProduct.push(nameProduct + "(" + noteProduct + ")");
            }

        });
        imPrint+=']}';
        JSON.stringify(imPrint);
        result=JSON.parse(imPrint);
        
        $.post(ROUTES.addTable, {idTable: idTable, idProduct: idProduct,
            quantity: quantityA, noteProduct: arrayNoteProduct, saleDate: saleDate, numberTable: numberTable,typeProduct:productType1}, function (res) {
                var json = JSON.parse(res);
                if (verifyError(json.error)) {
                    return;
                }
                var timeA=horaActualSistemaOp();
                var PRODUCT_NAME=$('#selectWaiterTables option:selected').text();
                // Comanda adicional en la ticketera de la caja
                TicketPrint.tableOrder(idTable, json.result, saleDate);
                clearDetail();
                alertify.success('PRODUCTOS AGREGADOS A MESA');
                listTables();
                $('#aBilling').click();

                idTable = "";
                numberTable = "";


            }).fail(function(xhr, ajaxOptions, thrownError){
                failErrors(xhr, ajaxOptions, thrownError);  
            });
            // }
        } else {
            alertify.alert('<span style="font-weight: bold;    color: red;">ADVERTENCIA</span>', "DEBE SELECCIONAR AL MENOS UN ITEM", function () {
                alertify.message('OK');
            });
        }
    }

    function payAllTable() {
        table_name = $('#tableTableItem input[name=idTableItem]');
        counter = 0;
        error = "";
        tablePay = idTable;
        idItem = new Array();
        quantityTableProd = new Array();
        nameProduct = new Array();
        idProdMonth = new Array();
        optionItemTable = 2;

        if (table_name.length > 0) {
            $('#nameCustomer').val(nameTable);
            tbody = $('#tbodyDetail');
            tbody.empty();
            $(table_name).each(function (e) {

                var idDetail = $(this).closest("tr").find("input[name=idTableItem]").val();
                var idProductTable = $(this).closest("tr").find("input[name=idProductTable]").val();
                var quantity2 = $(this).closest("tr").find("input[name=inputAction]");
                var typeProduct=$(this).closest("tr").find("input[name=typeProduct]").val();
                var quantityDetail = $(quantity2).parents("tr").find("td").eq(0);
                var nameProduct = $(quantity2).parents("tr").find("td").eq(2).text();
                var price = $(quantity2).parents("tr").find("td").eq(3).text();
                var subtotal=$(quantity2).parents("tr").find("td").eq(4).text();


                quantityTableProd.push(quantityDetail.text());
                idItem.push(idDetail);
                idProdMonth.push(idProductTable);


                $('#buttonHidden').attr('subtotal', subtotal);


                $('#buttonHidden').attr('name', nameProduct);
                $('#buttonHidden').attr('name', nameProduct);
                $('#buttonHidden').attr('price', price);
                $('#buttonHidden').attr('quantity', quantityDetail.text());
                $('#buttonHidden').attr('typeProduct', typeProduct);
                $('#buttonHidden').text(nameProduct);
                listSaleItem($('#buttonHidden'), idProductTable,typeProduct);
            });
            if (error == "") {

                $('#ModalTableItem').modal('hide');
                $('#aBilling').click();
                tableProcessing();
                $('#numTable').val(numberTable);
            $('#btnInvoice').attr('onclick', 'orderTable(this)');//el boton facturar le agrego esa funcion para q no muestre el modal de opcion salida
            $('#btnInvoice').attr('service_type', 'MESA');
        }


    }
}
function enableTableProcessing(idTable) {

    $.post(ROUTES.tablesReleaseProcessing, {idTable: idTable}, function (res) {
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }

        idTable = null;
        listTables();
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}

function enableUpdateTable(optionItemTable) {//opcion =1 solo paga los items seleccionados, 2 paga toda la mesa


    $.post(ROUTES.tablePaid, {idTable: idTable, optionItemTable: optionItemTable
        , quantity: quantityTableProd, idProduct: idProdMonth, idDetail: idItem}, function (res) {
            var json = JSON.parse(res);
            if (verifyError(json.error)) {
                return;
            }
            $('#spanProcessing').val("");
            $('#spanProcessing').css("display", 'none');
            clearDetail();
            listTables();
            if (processRefresh==10) {
                location.reload();
            }
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }

    /** Precuenta de la mesa abierta en el detalle, en la ticketera de la caja. */
    function imprimirProductsTables(operation){
        if (!idTable) {
            alertify.error('SELECCIONE UNA MESA');
            return;
        }
        TicketPrint.preBill(idTable, vf_sale);
    }


    function voidTable(){
     $.post(ROUTES.voidTable, {idTable: idTable, saleDate: saleDate, numberTable: numberTable}, function (res) {
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }

        imprimirProductsTables("ANULAR MESA");
        alertify.success('MESA ANULADA CORRECTAMENTE');
        $('#ModalTableItem').modal('hide');
        $('#aBilling').click();
        idTable = "";
        numberTable = "";
        listTables();
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}

function deleteItem(id,button){
    table_name = $('#tableTableItem input[name=idTableItem]');
    var quantityDetail = $(button).parents("tr").find("td").eq(0).text();
    var nameProduct = $(button).parents("tr").find("td").eq(2).text();
    var price = $(button).parents("tr").find("td").eq(3).text();
    var subtotalA = $(button).parents("tr").find("td").eq(4).text();
    $('#tdTotalTableItem').text(subtotalA);

    totalDetail=table_name.length==1?"open_table":"";
    if (totalDetail=="open_table") {
       alertify.alert('<span style="font-weight: bold;    color: blue;">INFORMACION</span>', "* TOME EN CUENTA QUE LE QUEDA SOLO UN ITEM, LA MESA SE ANULARA POR DEFECTO", function () {
        alertify.message('OK');
    }); 
   }
   
   var idDetail = id;

   $.post(ROUTES.deleteItemTable, {idDetail: idDetail,totalDetail:totalDetail,idTable:idTable}, function (res) {
    var json = JSON.parse(res);
    if (verifyError(json.error)) {
        return;
    }
    $('#tbodyTableItem').empty();
    $('#tbodyTableItem').append("<tr><td><input type='hidden' name='idTableItem' value='" + idDetail + "' class='form-control'>" + quantityDetail +
        "<td><input  style='width: 90px;' onchange='validateQuantityItemTable(this)' type='number' name='inputAction' class='form-control'>" +
        "<td><input type='hidden' name='idProductTable' value='" + nameProduct + "' >" +nameProduct +
        "<td>" + price +
        "<td>" + subtotalA +
        "<td><button class='btn btn-danger btn-sm delete-btn' onclick='deleteItem("+idDetail+",this)'><i class='fa fa-trash-o' aria-hidden='true'></i></button> "
        );
    imprimirProductsTables("ANULAR ITEM");
    listTables();
    alertify.success('ITEM ANULADO CORRECTAMENTE');
    
    if (totalDetail=="open_table") {
        $('#aBilling').click();
        $('#ModalTableItem').modal('hide');

    }else{
     listTableItem(idTable,idTable,"");
 }
 
        // idMesa = "";
        // nroMesa = "";

    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });  
}

function removeDefaultFunction()
{
    window.onhelp = function () { return false; }
}
        /** use keydown event and trap only the F-key, 
        but not combinations with SHIFT/CTRL/ALT **/
        $(window).bind('keydown', function(e) {
            //This is the F1 key code, but NOT with SHIFT/CTRL/ALT
            var keyCode = e.keyCode || e.which;
            //&& 
              //  !(event.altKey ||event.ctrlKey || event.shiftKey || event.metaKey)
              if((keyCode == 123 || e.key == 'F12') )
              {
                // prevent code starts here:
                removeDefaultFunction();
                e.cancelable = true;
                e.stopPropagation();
                e.preventDefault();
                e.returnValue = false;
                // Open help window here instead of alert
                fillDataModalF();
            }else{
        //          var reportStr   =
        // "The " +
        // ( e.ctrlKey  ? "Control " : "" ) +
        // ( e.shiftKey ? "Shift "   : "" ) +
        // ( e.altKey   ? "Alt "     : "" ) +
        // ( e.metaKey  ? "Meta "    : "" ) +
        // e.code + " " +
        // "key was pressed.";
        
    // $("#statusReport").text (reportStr);

    //--- Was a Ctrl-Alt-E combo pressed?
    if (event.shiftKey  && event.keyCode == 121) {
        // this.hitCnt = ( this.hitCnt || 0 ) + 1;
        alert('adfadf');
        
        
    }
    
}

            // Add other F-keys here:
            // else if((keyCode == 113 || e.key == 'F2') && 
            //         !(event.altKey ||event.ctrlKey || event.shiftKey || event.metaKey))
            //  {
            //     // prevent code starts here:
            //     removeDefaultFunction();
            //     e.cancelable = true;
            //     e.stopPropagation();
            //     e.preventDefault();
            //     e.returnValue = false;
            //     // Do something else for F2
            //     alert('F2 key opened, ' + keyCode);
            //     }
        });

       restaurant=Array();
    function getRestaurant(){//obtiene los datos de la empresa
        $.get(ROUTES.authorization, {idBranch,idBranch },function(res){
          var json = $.parseJSON(res);
          if (verifyError(json.error)) {
            return;
        }
        restaurant=json.result;
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}



function openCashRegister(){
    var amount=$('#inputOpeningAmount').val();
    if (amount==="" || parseFloat(amount)<0) {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE INDICAR EL MONTO INICIAL", function () {
        });
        return;
    }
    $.post(ROUTES.openCashRegister, {openingAmount:amount},function(res){
        var json = $.parseJSON(res);
        if (verifyError(json.error)) {
            return;
        }
        $('#ModalOpenCash').modal('hide');
        alertify.success('CAJA ABIERTA');
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);
    });
}

closingSessionId=0;
closingDifference=0;
function closingCashRegister(){
    var counted=$('#inputCountedCash').val();
    if (counted==="" || parseFloat(counted)<0) {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE INDICAR EL EFECTIVO CONTADO", function () {
        });
        return;
    }
    $('#btnClosingCash').prop('disabled', true);
    $.post(ROUTES.closingCashRegister, {countedCash:counted},function(res){
        $('#btnClosingCash').prop('disabled', false);
        var json = $.parseJSON(res);
        if (verifyError(json.error)) {
            return;
        }
        var totals=json.result[0];
        closingSessionId=json.sessionId;
        closingDifference=parseFloat(totals.difference);

        $('#spanClosingExpected').text(totals.expected);
        $('#spanClosingCounted').text(totals.counted);
        $('#spanClosingDifference').text(totals.difference);
        $('#divClosingNote').toggle(closingDifference!==0);
        $('#ModalDailyClose').modal('hide');
        $('#ModalClosingResult').modal('show');

        printClosing(json.date,json.cashier,json.result);
    }).fail(function(xhr, ajaxOptions, thrownError){
        $('#btnClosingCash').prop('disabled', false);
        failErrors(xhr, ajaxOptions, thrownError);
    });
}

function finishClosing(){
    if (closingDifference===0) {
        location.reload();
        return;
    }
    var note=$.trim($('#inputClosingNote').val());
    if (note==="") {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE EXPLICAR LA DIFERENCIA", function () {
        });
        return;
    }
    $.post(ROUTES.closingNote, {sessionId:closingSessionId,note:note},function(res){
        var json = $.parseJSON(res);
        if (verifyError(json.error)) {
            return;
        }
        location.reload();
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);
    });
}

function printClosing(date,cashier,totals){
    var shift="MAÑANA Y NOCHE";
    $.post('http://localhost:8080/reportes/printClosing.php',{process:'listaTOTALCAJACIERRE',date:date,login:cashier,shift:shift,restaurant:restaurant,
        listClosingCashTotal:totals,balanceCashRegister:totals[0].expected,sellDay:(parseFloat(totals[0].invoiced)).toFixed(3)},function(res){
        });
    imprimirItemDeleted(date,shift,cashier);
}
function imprimirItemDeleted(date,shift,cashier){//esta funcion imprime desde el historico
    $.get(ROUTES.itemsDeleted, {date:date,employee:cashier
        ,entry:shift},function(res){
            var json = $.parseJSON(res);
            if (verifyError(json.error)) {
                return;
            }
            if (json.result.length>0) {

                $.post('http://localhost:8080/reportes/printClosing.php',{process:'listarItemElimDadaFecha',date:date,login:login,shift:shift,listItemDeleted:json.result},function(res){
                });

            }else{

            }
            listTables();
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }
    notificationDateLimit="";
    function verifyAuthorization(){
        $.get(ROUTES.verifyAuthorization, {date:vf_sale},function(res){
            var json = $.parseJSON(res);
            if (verifyError(json.error)) {
                return;
            }
            notificationDateLimit=json.result;
            if (notificationDateLimit!="") {
              alertify.success(notificationDateLimit);

          }

      });
    }
function productEnabled(json,i){//esta funcion verifica si el producto esta habilitado o no para ser mostrado
 var d = new Date();

 dayToday=d.getDay();
 dayCurrent = f_sale.getDay();


 var time = f_sale.getHours();
 var time = ("0" + time).slice(-2);
 var minute = f_sale.getMinutes();
 var minute = ("0" + minute).slice(-2);
 var second = d.getSeconds();
 var sliceegundo = ("0" + second).slice(-2);

 currentTime = time + ":" + minute;
 mon=json.result[i].mon!=""?"1":json.result[i].mon;
 tue=json.result[i].tue!=""?"2":json.result[i].tue;
 wed=json.result[i].wed!=""?"3":json.result[i].wed;
 thu=json.result[i].thu!=""?"4":json.result[i].thu;
 fri=json.result[i].fri!=""?"5":json.result[i].fri;
 sat=json.result[i].sat!=""?"6":json.result[i].sat;
 sun=json.result[i].sun!=""?"7":json.result[i].do;
            enabled="";//si la variable habilitado esta vacia no esta disponible caso contrario lo esta
            switch(dayCurrent){
                case 1:
                if (mon!="") {

                    if (json.result[i].start_time>json.result[i].end_time) {
                        if (dayToday==dayCurrent) {
                            if (currentTime>=json.result[i].start_time) {
                                enabled="x";
                            }
                        }
                        else{
                            if (currentTime<=json.result[i].end_time) {
                                enabled="x";
                            }
                        }
                    }
                    else{
                       if (currentTime>=json.result[i].start_time &&  currentTime<=json.result[i].end_time || json.result[i].start_time==json.result[i].end_time) {
                        enabled="x"
                    }
                }
            }
            break;
            case 2:
            if (tue!="") {
               if (json.result[i].start_time>json.result[i].end_time) {
                if (dayToday==dayCurrent) {
                    if (currentTime>=json.result[i].start_time) {
                        enabled="x";
                    }
                }
                else{
                    if (currentTime<=json.result[i].end_time) {
                        enabled="x";
                    }
                }
            }
            else{
               if (currentTime>=json.result[i].start_time &&  currentTime<=json.result[i].end_time || json.result[i].start_time==json.result[i].end_time) {
                enabled="x"
            }
        }
    }
    break;
    case 3:
    if (wed!="") {
       if (json.result[i].start_time>json.result[i].end_time) {
        if (dayToday==dayCurrent) {
            if (currentTime>=json.result[i].start_time) {
                enabled="x";
            }
        }
        else{
            if (currentTime<=json.result[i].end_time) {
                enabled="x";
            }
        }
    }
    else{
       if (currentTime>=json.result[i].start_time &&  currentTime<=json.result[i].end_time || json.result[i].start_time==json.result[i].end_time) {
        enabled="x"
    }
}
}
break;
case 4:
if (thu!="") {
   if (json.result[i].start_time>json.result[i].end_time) {
    if (dayToday==dayCurrent) {
        if (currentTime>=json.result[i].start_time) {
            enabled="x";
        }
    }
    else{
        if (currentTime<=json.result[i].end_time) {
            enabled="x";
        }
    }
}
else{
   if (currentTime>=json.result[i].start_time &&  currentTime<=json.result[i].end_time || json.result[i].start_time==json.result[i].end_time) {
    enabled="x"
}
}
}
break;
case 5:
if (fri!="") {
   if (json.result[i].start_time>json.result[i].end_time) {
    if (dayToday==dayCurrent) {
        if (currentTime>=json.result[i].start_time) {
            enabled="x";
        }
    }
    else{
        if (currentTime<=json.result[i].end_time) {
            enabled="x";
        }
    }
}
else{
   if (currentTime>=json.result[i].start_time &&  currentTime<=json.result[i].end_time || json.result[i].start_time==json.result[i].end_time) {
    enabled="x"
}
}
}
break;
case 6:

if (sat!="") {

   if (json.result[i].start_time>json.result[i].end_time) {
    if (dayToday==dayCurrent) {
        if (currentTime>=json.result[i].start_time) {
            enabled="x";
        }
    }
    else{
        if (currentTime<=json.result[i].end_time) {
            enabled="x";
        }
    }
}
else{
   if (currentTime>=json.result[i].start_time &&  currentTime<=json.result[i].end_time || json.result[i].start_time==json.result[i].end_time) {
    enabled="x"
}
}

}
break;
case 0:
if (sun!="") {
   if (json.result[i].start_time>json.result[i].end_time) {
    if (dayToday==dayCurrent) {
        if (currentTime>=json.result[i].start_time) {
            enabled="x";
        }
    }
    else{
        if (currentTime<=json.result[i].end_time) {
            enabled="x";
        }
    }
}
else{
   if (currentTime>=json.result[i].start_time &&  currentTime<=json.result[i].end_time || json.result[i].start_time==json.result[i].end_time) {
    enabled="x"
}
}
}
break;
}
if (json.result[i].status!="ACTIVE") {
    enabled="";
}
return enabled;
}
function searchProductKeyUp(input){
    input=$(input).val();
    if (input.length>=3) {
        $('#divProduct').empty();
        $.get(ROUTES.searchProduct, {productName:input},function(res){
            var json = $.parseJSON(res);
            if (verifyError(json.error)) {
                return;
            }

            for (var i = 0; i < json.result.length; i++) {
               if (json.result[i].status=="ACTIVE") {
                enabled="";
                enabled= productEnabled(json,i);
                if (enabled!="") {
                    if (json.result[i].options_mode == "") {
                        if (json.result[i].image_path.indexOf("legacy/") !== 0) {
                            $('#divProduct').append('<button  print_station_4="'+json.result[i].print_station_4+'" print_station_3="'+json.result[i].print_station_3+'" print_station_2="'+json.result[i].print_station_2+'" print_station_1="'+json.result[i].print_station_1+'" printKitchen="'+json.result[i].print_kitchen+'"  typeProduct="'+json.result[i].timed+'" idProduct="'+json.result[i].id+'" quantity="' + json.result[i].quantity + '" name="btnProduct" options_mode="' + json.result[i].options_mode + '" title="Precio: ' + json.result[i].sale_price + ' , ' + json.result[i].product_name + '" price="' + json.result[i].sale_price + '" name="' + json.result[i].product_name + '"  onclick="listSaleItem(this,' + json.result[i].id + ')" class="btnMenu btn btn-primary " style="border: none;    font-size: 12px;\n\
                                font-weight: bold;       width: 95px;text-shadow: 2px 2px black;    height: 52px; text-shadow: 2px 2px black; font-size: 8pt; box-shadow: 1px 1px #999;    margin-right: 4px;\n\
                                margin-bottom: 3px;    margin-left: 1px;    padding: 0;      margin-top: 1px; white-space: inherit; background:' + json.result[i].color + ' " >' + json.result[i].product_name + '</button>');
                        } else {
                            $('#divProduct').append('<a  print_station_4="'+json.result[i].print_station_4+'" print_station_3="'+json.result[i].print_station_3+'" print_station_2="'+json.result[i].print_station_2+'" printStation1="'+json.result[i].print_station_1+'" printKitchen="'+json.result[i].print_kitchen+'"  typeProduct="'+json.result[i].timed+'" idProduct="'+json.result[i].id+'"  quantity="' + json.result[i].quantity + '" class="btnMenu" href="#" name="btnProduct" options_mode="" title="' + json.result[i].sale_price + ' , ' + json.result[i].product_name + '" price="' + json.result[i].sale_price + '" name="' + json.result[i].product_name + '" onclick="listSaleItem(this,' + json.result[i].id + ')">' +
                                '<div class="" style="width: max-content;padding-left: 0;padding-right: 0;overflow: hidden;display: inline-table; ' +
                                'padding-right: 0;    overflow: hidden;"> ' +
                                '<div class="btn btn-primary" style="border: none;    font-size: 12px;' +
                                'font-weight: bold;       width: 95px;    height: 52px;   box-shadow: 1px 1px #999;    margin-right: 4px;' +
                                'margin-bottom: 3px;    margin-left: 1px;     padding: 0;      margin-top: 1px; white-space: inherit; background:; ">' +
                                '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 " >' +
                                '<img src="' + json.result[i].image_path + '" style="width: 36px;height: 56px;    position: absolute;    left: 1px;">' +
                                '</div>' +
                                ' <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 "  style="padding: 0;">' +
                                '<span style="position: relative;' +
                                'top: 1px;' +
                                'word-wrap: break-word;' +
                                'text-shadow: 2px 2px black;' +
                                'left: -5px;' +
                                'font-size: 8pt;">' + json.result[i].product_name + '</span>' +
                                '</div></div></div></a>');
                        }
                    } else {
                        if (json.result[i].image_path.indexOf("legacy/") !== 0) {
                            $('#divProduct').append('<button  print_station_4="'+json.result[i].print_station_4+'" print_station_3="'+json.result[i].print_station_3+'" print_station_2="'+json.result[i].print_station_2+'" print_station_1="'+json.result[i].print_station_1+'" printKitchen="'+json.result[i].print_kitchen+'"  typeProduct="'+json.result[i].timed+'"  idProduct="'+json.result[i].id+'" quantity="' + json.result[i].quantity + '"  name="btnProduct"  title="Precio: ' + json.result[i].sale_price + ' , ' + json.result[i].product_name + '" price="' + json.result[i].sale_price + '" name="' + json.result[i].product_name + '"  onclick="loadSuggestion(this,' + json.result[i].id + ')" class="btnMenu btn btn-primary " style="border: none;    font-size: 12px;\n\
                                font-weight: bold;       width: 95px; text-shadow: 2px 2px black;   height: 52px;   box-shadow: 1px 1px #999;    margin-right: 4px;\n\
                                margin-bottom: 3px;    margin-left: 1px;     padding: 0;      margin-top: 1px; white-space: inherit; background:' + json.result[i].color + ' " >' + json.result[i].product_name + '</button>');
                        } else {
                            $('#divProduct').append('<a  print_station_4="'+json.result[i].print_station_4+'" print_station_3="'+json.result[i].print_station_3+'" print_station_2="'+json.result[i].print_station_2+'" printStation1="'+json.result[i].print_station_1+'" printKitchen="'+json.result[i].print_kitchen+'"  typeProduct="'+json.result[i].timed+'" idProduct="'+json.result[i].id+'"  quantity="' + json.result[i].quantity + '"  class="btnMenu" href="#" name="btnProduct" options_mode="" title="Precio: ' + json.result[i].sale_price + ' , ' + json.result[i].product_name + '" price="' + json.result[i].sale_price + '"  name="' + json.result[i].product_name + '" onclick="loadSuggestion(this,' + json.result[i].id + ')">' +
                                '<div class="" style="width: max-content;display:inline-table;    padding-left: 0;' +
                                'padding-right: 0;    overflow: hidden;"> ' +
                                '<div class="btn btn-primary" style="border: none;    font-size: 12px;' +
                                'font-weight: bold;       width: 95px;    height: 52px;   box-shadow: 1px 1px #999;    margin-right: 4px;' +
                                'margin-bottom: 3px;    margin-left: 1px;     padding: 0;      margin-top: 1px; white-space: inherit; background:; ">' +
                                '<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 " >' +
                                '<img src="' + json.result[i].image_path + '" style="width: 36px;height: 56px;    position: absolute;    left: 1px;">' +
                                '</div>' +
                                ' <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 "  style="padding: 0;">' +
                                '<span style="position: relative;' +
                                'top: 1px;' +
                                'word-wrap: break-word;' +
                                'text-shadow: 2px 2px black;' +
                                'left: -5px;' +
                                'font-size: 8pt;">' + json.result[i].product_name + '</span>' +
                                '</div></div></div></a>');
                        }
                    }
                }
                enabled="";
            }
        }
    });
};

}
productNameBarcode="";
function verifyBarcodeProd(input){

    productCodeBar=$(input).val();
    $.get(ROUTES.searchBarcode, {productCodeBar:productCodeBar},function(res){
        var json = $.parseJSON(res);
        if (verifyError(json.error)) {
            return;
        }
        if(json.result==""){
            alertify.error("NO EXISTE ESE ITEM CON ESE CODIGO DE BARRA");
            return;
        }
        for (var i = 0; i < json.result.length; i++) {
           if (json.result[i].status=="ACTIVE") {
            enabled="";
            enabled= productEnabled(json,i);
            if (enabled!="") {
                $('#buttonHidden').attr('name', json.result[i].product_name);
                $('#buttonHidden').attr('price', json.result[i].sale_price);
                $('#buttonHidden').attr('quantity', 0);
                $('#buttonHidden').text(json.result[i].product_name);
                listSaleItem($('#buttonHidden'), json.result[i].id,"");
                productNameBarcode=json.result[i].product_name;
                $('#inputQtyBarcode').focus();
            }
        }
        enabled="";
    }
});
}

function addQtyProdBarcode(input){//esta funcion agrega la cantidad al producto agregado en el detalle de venta y por ende modifica el subtotal y el total
    quantity=parseFloat($(input).val());
    targetInput = $('#tableDetail span[nameProduct="' + productNameBarcode + '"]');
    var table_name=$('#tableDetail tbody tr');
    if (targetInput.length>0) {
     var inputQty = $(targetInput).closest("tr").find("input[name=quantity]");
     var inputPrice = $(targetInput).closest("tr").find("input[name=price]");
     var inputSubtotal = $(targetInput).closest("tr").find("input[name=subTotal]");
     var spanSubTotal = $(targetInput).closest("tr").find("#spanSubTotal");
     var spanQuantity = $(targetInput).closest("tr").find("#spanQuantity");
     var spanPrice = $(targetInput).closest("tr").find("#spanPrice");
        //     alert(inputCant.val());
        // cantidad=parseFloat(cantidad+inputCant.val());
        total=0;
        $(table_name).each(function (e) {
            total+=parseFloat($(this).closest("tr").find("input[name=subTotal]").val());
            
        });
        aux = price * quantity;
        subTotal = parseFloat(inputSubtotal.val()) + aux;
        
        totalBs=(parseFloat(total+aux)).toFixed(2);
        
        

        $('#totalBs').val(totalBs);

        $('#totalUsd').val((totalBs / exchangeRate).toFixed(2));
        inputSubtotal.val(subTotal.toFixed(2));
        count = (parseFloat(inputQty.val()) + parseFloat(quantity)).toFixed(2);
        inputQty.val(count);
        spanSubTotal.text(subTotal.toFixed(2));

        spanQuantity.text(count);
        $(input).val("");
        $('#inputBarcode').val("");
        $('#inputBarcode').focus();
    }
}

function listSupplyGivenProduct(idProduct){

    $('#tbodySupplyProduct').empty();
    $.get(ROUTES.suppliesProduct, {idProduct: idProduct}, function (res) {
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }

        for (var i = 0; i < json.result.length; i++) {
            $('#tbodySupplyProduct').append('<tr>'+
                '<td>'+json.result[i].supply_name+
                '<td>'+json.result[i].current_stock
                );
        }
        $('#ModalSupplyProduct').modal('show');

    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
}


function deactivateProduct(idProduct){


  $.post(ROUTES.deactivateProduct, {idProduct: idProduct}, function (res) {
    var json = JSON.parse(res);
    if (verifyError(json.error)) {
        return;
    }
    listProduct(idButtonMenu,auxButtonMenu);

    alertify.success("PRODUCTO DESACTIVADO CORRECTAMENTE");

}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

function moveProductMenu(category_group){
    var idProduct=$('#idProductForMenu').val();
    $.post(ROUTES.moveProduct, {idProduct: idProduct,category_group:category_group}, function (res) {
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }
        $('#buttonMenu'+category_group).click()
        $('#ModalButtonProduct').modal('hide');
        alertify.success("PRODUCTO MOVIDO CORRECTAMENTE");

    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });

}

addressCustomer="";
phoneCustomer="";
nameCustomerDelivery="";
function getAddressPhone(){
    nameCustomerDelivery=$('#NameCustomerDelivery').val();
    addressCustomer=$('#addressCustomer').val().trim();
    phoneCustomer=$('#phoneCustomer').val();
    tax_id=$('#phoneTaxIdCustomer').val();

    if (nameCustomerDelivery=="" || addressCustomer=="" || phoneCustomer=="") {
     alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "LOS CAMPOS TELEFONO, NOMBRE DEL CLIENTE Y DIRECCION NO DEBEN ESTAR VACIOS", function () {
        alertify.message('OK');
    });
 }else{
    $('#nameCustomer').val(nameCustomerDelivery);
    $('#customerTaxId').val(tax_id);
    $('#aBilling').click();
    $('#NameCustomerDelivery').val("");
    $('#addressCustomer').val("");
    $('#phoneCustomer').val("");
    $('#phoneTaxIdCustomer').val("");
}
}

function loadTg(){
    var table_name=$('#tableDetail tbody tr');
    if (table_name.length>0) {
        $('#ModalGiftCards').modal('show');
    }else{
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "DEBE HABER AL MENOS 1 ITEM", function () {
            alertify.message('OK');
        });
        $('#inputCard').prop('checked',false);

    }

}
idCard="";
function searchGiftCard(){
    card=$('#inputGiftCard').val();
    
    $.get(ROUTES.cards, {card: card}, function (res) {
        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }
        if (parseFloat(json.result[0].prepaid_balance)==0) {
           alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "YA NO TIENE SALDO DISPONIBLE", function () {
            alertify.message('OK');
        });
           idCard="";
           return;
       }
       if (json.result.length>0 && json.result!="") {
        idCard=card;
        totalBsCard=$('#totalBs').val();
        Balance=(parseFloat(json.result[0].prepaid_balance-totalBsCard)).toFixed(2);
        $('#divCardGif').css('display','block');
        $("#spanNameCards").text(json.result[0].customer_name);
        $("#spanTotalCards").text(json.result[0].invoice_total);
        $("#spanConsumptionCards").text(totalBsCard);
        $("#spanLastBalanceCards").text(json.result[0].prepaid_balance);
        $("#spanNewBalanceCards").text(Balance);
        $("#spanPaymentMethodCards").text(json.result[0].prepaid_ref);
    }
    $('#tableGiftCards').DataTable().destroy();
    $('#tbodyGiftCards').empty();
    if (json.result2.length>0 && json.result!="") {
        for (var i = 0; i < json.result2.length; i++) {
           $('#tbodyGiftCards').append("<tr>"+
            "<td>"+i+
            "<td>"+json.result2[i].quantity+
            "<td>"+json.result2[i].product_name+
            "<td>"+parseFloat(json.result2[i].quantity*json.result2[i].sale_price)+
            "<td>"+json.result2[i].sale_date

            );
           
       }
       paginatortableGiftCards();
   }

}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

function saveDetailGiftCard(){
    if (idCard=="") {
       alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "YA NO TIENE SALDO DISPONIBLE", function () {
        alertify.message('OK');
    });

       return;
   }
    // $('#loading').css('display','block');
    table_name = $('#tableDetail input[name=quantity]');
    // formaPago = $('input[name=formaPago]').val();
    totalBs = $('#totalBs').val();
    totalUsd = $('#totalUsd').val();
    
    customerName=$("#spanNameCards").text();
    newBalance=parseFloat($("#spanNewBalanceCards").text())<0?"0":$("#spanNewBalanceCards").text();
    saleDate = vf_sale;
    shift = $('#shift_value').text();
    var value = LITERAL(totalBs);
    // alert(valor);

    var  selectSeller = $('#selectSeller').val();
    var  idProduct = new Array();
    var quantityA = new Array();
    var priceA = new Array();
    var arrayNoteProduct = new Array();
    var NameProduct=new Array();
    var subtotalA=new Array();
    var gQr=new Array();
   var nameProduct=new Array();//este array se encarga d mandar a cocina el producto con la nota

   var counter=0;
   var  imPrint='{"imp":[';
   if (table_name.length > 0) {
    $(table_name).each(function (e) {
        var id = $(this).closest("tr").find("input[name=inputIdProduct]").val();
        var quantity = $(this).closest("tr").find("input[name=quantity]").val();
        var price = $(this).closest("tr").find("input[name=price]").val();
        var noteProduct = $(this).closest("tr").find("input[name=noteProduct]").val();
        var product_name = $(this).closest("tr").find("input[name=nameProduct]").val();
        var subTotal = $(this).closest("tr").find("#spanSubTotal").text();
        var qr = $(this).closest("tr").find("input[name=productQr]").val();
        var printStation4A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_4');
        var printStation3A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_3');
        var printStation2A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_2');
        var printStation1A=$(this).closest("tr").find("button[name=managementPrint]").attr('print_station_1');
        var printKitchenA=$(this).closest("tr").find("button[name=managementPrint]").attr('print_kitchen');
        counter++;
        if(table_name.length!=counter){
            imPrint+='{"impCoc":"'+printKitchenA+'","imppunto4":"'+printStation4A+'","imppunto3":"'+printStation3A+'","imppunto2":"'+printStation2A+'","imppunto1":"'+printStation1A+'"},';
        }else{
            imPrint+='{"impCoc":"'+printKitchenA+'","imppunto4":"'+printStation4A+'","imppunto3":"'+printStation3A+'","imppunto2":"'+printStation2A+'","imppunto1":"'+printStation1A+'"}';

        }
        
        idProduct.push(id);
        arrayNoteProduct.push(noteProduct);
        NameProduct.push(product_name);
        subtotalA.push(subTotal);
        gQr.push(qr);
        priceA.push(price);
        quantityA.push(quantity);
        if (noteProduct == "") {
            nameProduct.push(product_name);
        } else {
            nameProduct.push(product_name + "(" + noteProduct + ")");
        }
    });
    imPrint+=']}';
    JSON.stringify(imPrint);
    var result=JSON.parse(imPrint);
    var sales_tr='{"NOM_CLI":"","NIT_CLI":"","ORDEN":"0","SALIDA":"MESA","DHORAVEN":"'+horaActualSistemaOp()+'","USUARIO":"'+CURRENT_USER+'"}';
    JSON.stringify(sales_tr);
    var sell=JSON.parse(sales_tr);
    numberOrder="0";
    exitProduct+=numberOrder;
    $.post(ROUTES.consumptionCard, {card: idCard,idProduct:idProduct,noteProduct:arrayNoteProduct,
        quantity:quantityA,saleDate:saleDate,shift:shift,totalBs:totalBs,newBalance:newBalance,customerName:customerName}, function (res) {
            var json = JSON.parse(res);
            if (verifyError(json.error)) {
                return;
            }
            $('#tbodyDetail').empty();
            if (parseFloat($("#spanNewBalanceCards").text())<0) {
                newBalance=parseFloat(newBalance)*-1;
                $('#buttonHidden').attr('name', "Consumo Adicional de Tarjeta");
                $('#buttonHidden').attr('price', parseFloat($("#spanNewBalanceCards").text())*-1);
                $('#buttonHidden').attr('quantity', '1');
                $('#buttonHidden').text("Consumo Adicional de Tarjeta");
                listSaleItem($('#buttonHidden'), 0,"");
                $('#btnInvoice').click();
            }
            $('#tbodyGiftCards').empty();
            $('#divCardGif').css('display','none');
            $('#inputGiftCard').val("");
            $("#spanNameCards").text("");
            $("#spanTotalCards").text("");
            $("#spanConsumptionCards").text("");
            $("#spanLastBalanceCards").text("");
            $("#spanNewBalanceCards").text("");
            $("#spanPaymentMethodCards").text("");
            $('#ModalGiftCards').modal('hide');
            $('#inputCard').prop('checked',false);
            alertify.success('GUARDADO CORRECTAMENTE');

            if (cloud_print=="cloud_print") {
                $.ajax({
                    url:"http://" + ipCustomer + ":8080/reportes/printCocina.php",
                    datatype:"jsonp",
                    data:{numberOrder:numberOrder,quantity:quantityA,subTotal:subtotalA,imPrint:result.print,
                     nameProduct: nameProduct, listSale: sell},
                     type:"post",
                     success:function(res){
                        if (processRefresh==10) {
                            location.reload();
                        }
                    }
                        });//Imprimir cocina
            }else{
                $.ajax({
                    url:"legacy/reportes/printCocina.php",
                    datatype:"jsonp",
                    data:{numberOrder:numberOrder,quantity:quantityA,subTotal:subtotalA,imPrint:result.print, 
                        nameProduct: nameProduct, listSale: sell},
                        type:"post",
                        success:function(res){
                            if (processRefresh==10) {
                                location.reload();
                            }
                        }
                        });//Imprimir cocina
            }
            clearDetail();
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }

}

function getTimeBilliards(TimeStart,endTime){
 var currentTime = movesClock();

 hour=endTime==""?currentTime:endTime+":00";
// hora_inicio=endTime!=""?startTime:
var time1 = (hour).split(":"),//tiempo mayor
    time2 = (TimeStart+":00").split(":"),//tiempo menor
   // var hora1 = ("04:29:01").split(":"),
   //  hora2 = ("03:28:56").split(":"), 

   t1 = new Date(),
   t2 = new Date();
   var t3 = new Date();
 // t2.setDate(fechaE);

 t1.setHours(time1[0], time1[1], time1[2]);
 t2.setHours(time2[0], time2[1], time2[2]);
//Aquí hago la resta
// alert(t2.getMinutes());
// alert(t1.getMinutes() - t2.getMinutes());
t3.setHours(t1.getHours() - t2.getHours(), t1.getMinutes() - t2.getMinutes(), t1.getSeconds() - t2.getSeconds());
// t3.setHours(t1.getHours() - hora2[0], t1.getMinutes() - hora2[1], t1.getSeconds() - t2.getSeconds());
time=("0" + t3.getHours()).slice(-2);

minutos=("0" + t3.getMinutes()).slice(-2);

//Imprimo el resultado

return (time+"." + minutos );

// alert ("La diferencia es de: " + (t1.getHours() ? t1.getHours() + (t1.getHours() > 1 ? " horas" : " hora") : "") + (t1.getMinutes() ? ", " + t1.getMinutes() + (t1.getMinutes() > 1 ? " minutos" : " minuto") : "") + (t1.getSeconds() ? (t1.getHours() || t1.getMinutes() ? " y " : "") + t1.getSeconds() + (t1.getSeconds() > 1 ? " segundos" : " segundo") : ""));
}
function getSubtotalBilliards(quantity,price){
var preh=parseFloat(quantity.substring(0, 2)*price);//precio hora
var prem=quantity.substring(3, 5);
// alert(cantidad.substring(0, 2));
// alert(prem);
vvalm=0;
switch(true){
    case (prem>=1 && prem<=5) :                                        
    vvalm = 2;
    break;
    case (prem>=6 && prem<=10):                
    vvalm = 3;
    break;
    case (prem>=11 && prem<=15):               
    vvalm = 5;
    break;
    case (prem>=16 && prem<=20):   
    vvalm = 6;
    break;
    case (prem>=21 && prem<=25):      
    vvalm = 7;
    break;
    case (prem>=26 && prem<=30):                
    vvalm = 8;
    break;
    case (prem>=31 && prem<=35):                
    vvalm = 10;
    break;
    case (prem>=36 && prem<=40):                
    vvalm = 11;
    break;
    case (prem>=41 && prem<=45):                
    vvalm = 13;
    break;
    case (prem>=46 && prem<=50):                
    vvalm = 14;
    break;
    case (prem>=51 && prem<=55):                
    vvalm = 15;
    break;
    case (prem>=56 && prem<=59):                 
    vvalm = 16;
    break;
    default:
    break;
}

return (preh+vvalm).toFixed(3);
}

function getPriceService(total){
    vtotalsercub=0;
    switch(true){
       case  (total>=1 && total<=50):
       vtotalsercub = 0
       break;
       case  (total>=51 && total<=100) :
       vtotalsercub = 5
       break;
       case  (total>=101 && total<=500) :
       vtotalsercub = 10
       break;
       case (total>=501 && total<=1000):
       vtotalsercub = 12
       break;
       case (total>=1001 && total<=2000):
       vtotalsercub = 15
       break;
       case (total>=2001 && total<=3000):
       vtotalsercub = 20           
       break;
   }
   return vtotalsercub;
}

function stopTimeBilliards(idDetail,button){

   $.post(ROUTES.stopTime, {idDetail: idDetail,timeA:timeA,idTable:idTable}, function (res) {
    var json = JSON.parse(res);
    if (verifyError(json.error)) {
        return;
    }
    $(button).attr('disabled','disabled');
    alertify.success('GUARDADO CORRECTAMENTE');
    timeA="";
}).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}
numMobileTigo=0;
transactionTigoMoney=null;
function saveNumberTigoMon(select){
    if ($(select).val()=="tigomon") {
        alertify.prompt("NUMERO DE CELULAR", "NUMERO", "",
            function (evt, value) {
                numMobileTigo=value;
            // if (numCelularTigo!="" && numCelularTigo!=null && selectTipoPagoCredito=="tigomon" && formaPago == "CREDITO") {
                $('#loading').css('display','block');
                var  totalBs=$('#totalBs').val();
                $.ajax({
                    url:"legacy/CONTROLADORES/tigoMoneyController.php",
                    datatype:"jsonp",
                    cache:false,
                    data:{process:"tigomon",phone:numMobileTigo,amount: totalBs},
                    type:"post",
                    success:function(res){
                        var json = JSON.parse(res);
                        if (verifyError(json.error)) {
                            $('#loading').css('display','none');
                            return;
                        }else{
                            $('#totalInvoiceBs').dblclick();
                            alertify.alert('<span style="font-weight: bold;    color: blue;">TRANSACCION</span>', "TRANSACCION EJECUTADA SATIFACTORIAMENTE", function () {
                                alertify.message('OK');
                            });
                            $('#loading').css('display','none');
                            transactionTigoMoney=json.result;
                        }
                    }   
                }).fail(function(xhr, ajaxOptions, thrownError){
                    failErrors(xhr, ajaxOptions, thrownError);  
                });
  // }
},
function () {
                // alertify.error('Cancel');
            });
    }
}


var customerList = {};
var lastCustomerSearch = null;

$(document).on('shown.bs.modal', '#ModalListCustomer', function () {
    searchPorName();
});

function searchPorName(){
    listCustomers(ROUTES.searchCustomersByName, {name: $('#searchNameCustomerTaxId').val()});
}
function searchPorTaxId(){
    listCustomers(ROUTES.searchCustomersByTaxId, {tax_id: $('#searchNameCustomerTaxId').val()});
}
function listCustomers(url, data){
    lastCustomerSearch = {url: url, data: data};
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'JSON',
        async: true,
        data: data,
        success: function(res) {
            if (verifyError(res.error)) {
                return;
            }
            $('#tableCustomer').DataTable().destroy();
            $('#tbodyListCustomer').empty();
            customerList = {};
            for (var i = 0; i < res.result.length; i++) {
                var customer = res.result[i];
                var row = $('<tr>');
                customerList[customer.id] = customer;
                ['customer_name', 'customer_tax_id', 'customer_id_number', 'customer_phone', 'customer_mobile', 'customer_email', 'customer_address'].forEach(function (field) {
                    $('<td>').text(customer[field]).appendTo(row);
                });
                row.append("<td style='white-space: nowrap;'><button class='btn btn-info' title='EDITAR CLIENTE' onclick='loadCustomer("+customer.id+")'><i class='fa fa-pencil-square-o' aria-hidden='true'></i></button> <button class='btn btn-danger' title='ELIMINAR CLIENTE' onclick='deleteCustomer("+customer.id+")'><i class='fa fa-trash-o' aria-hidden='true'></i></button></td>");
                $('#tbodyListCustomer').append(row);
            }
            paginatorTbodyCustomer();
        }
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);
    });
}
function refreshCustomers(){
    listCustomers(lastCustomerSearch.url, lastCustomerSearch.data);
}
function paginatorTbodyCustomer() {
    $('#tableCustomer').DataTable({
        "pagingType": "full_numbers",
        "destroy": true,
        "order": [[0, "asc"]],
        "scrollY": "418px",
        "scrollCollapse": true,
        "paging": false,
        "search": false,
        retrieve: true,
        "language": {
            "emptyTable": "No hay clientes registrados",
            "zeroRecords": "No se encontraron clientes",
            "info": "Clientes: _TOTAL_",
            "infoEmpty": "Clientes: 0",
            "infoFiltered": "(de _MAX_ en total)"
        }

    });
}

function paginatortableGiftCards() {
    $('#tableGiftCards').DataTable({
        "pagingType": "full_numbers",
        "destroy": true,
        "order": [[0, "asc"]],
        "scrollY": "418px",
        "scrollCollapse": true,
        "paging": false,
        retrieve: true

    });
}
idCustomer=null;
function fillCustomerForm(title, customer){
    $('#titleCustomerForm').text(title);
    $('#nameCustomerA').val(customer.customer_name);
    $('#customerTaxIdA').val(customer.customer_tax_id);
    $('#customerIdNumberA').val(customer.customer_id_number);
    $('#customerAddressA').val(customer.customer_address);
    $('#customerPhoneA').val(customer.customer_phone);
    $('#customerMobileA').val(customer.customer_mobile);
    $('#customerEmailA').val(customer.customer_email);
    $('#birthDateA').val(customer.birth_date);
    $('#ModalModifyCustomer').modal('show');
}
function newCustomer(){
    idCustomer=null;
    fillCustomerForm('REGISTRAR CLIENTE', {});
}
function loadCustomer(id){
    idCustomer=id;
    fillCustomerForm('MODIFICAR CLIENTE', customerList[id]);
}
function saveCustomer(){
    $.ajax({
        url: idCustomer ? ROUTES.customersUpdate : ROUTES.customersStore,
        type: 'POST',
        dataType: 'JSON',
        cache:false,
        async: true,
        data: {
            idCustomer: idCustomer,
            customerName: $('#nameCustomerA').val(),
            customerTaxId: $('#customerTaxIdA').val(),
            customerIdNumber: $('#customerIdNumberA').val(),
            customerAddressA: $('#customerAddressA').val(),
            customerPhone: $('#customerPhoneA').val(),
            customerMobile: $('#customerMobileA').val(),
            customerEmail: $('#customerEmailA').val(),
            birthDate: $('#birthDateA').val()
        },
        success: function(res) {
            if (verifyError(res.error)) {
               
             return;
         }
         refreshCustomers();
         $('#ModalListCustomer').css('overflow-y','auto');
         $('#ModalModifyCustomer').modal('hide');
     }

 }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

function deleteCustomer(id){
    $.ajax({
        url: ROUTES.customersDelete,
        type: 'POST',
        dataType: 'JSON',
        cache:false,
        async: true,
        data: {idCustomer:id},
        success: function(res) {
            if (verifyError(res.error)) {
               
             return;
         }
         refreshCustomers();
         
     }

 }).fail(function(xhr, ajaxOptions, thrownError){
    failErrors(xhr, ajaxOptions, thrownError);  
});
}

function deleteItemQty(){
   table_name = $('#tableTableItem input[name=idTableItem]');
   var  counter= 0;
   error = "";
   tablePay = idTable;
   idItem = new Array();
   quantityTableProd = new Array();
   nameProduct = new Array();
   idProdMonth = new Array();
   counter2 = 0;
   if (table_name.length > 0) {
    tbody = $('#tbodyDetail');
    tbody.empty();
    $('#nameCustomer').val(nameTable);
    $(table_name).each(function (e) {
        var idDetail = $(this).closest("tr").find("input[name=idTableItem]").val();
        var idProductTable = $(this).closest("tr").find("input[name=idProductTable]").val();
        var quantity = $(this).closest("tr").find("input[name=inputAction]").val();
        var idProductTable = $(this).closest("tr").find("input[name=idProductTable]").val();
        var quantity2 = $(this).closest("tr").find("input[name=inputAction]");
        var quantityDetail = $(quantity2).parents("tr").find("td").eq(0);
        var nameProduct = $(quantity2).parents("tr").find("td").eq(2).text();
        var price = $(quantity2).parents("tr").find("td").eq(3).text();
        var typeProduct=$(this).closest("tr").find("input[name=typeProduct]").val();
        var subtotal=$(quantity2).parents("tr").find("td").eq(4).text();
        if (typeProduct=="TIEMPO" && quantity!="") {
         alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "<span style='font-weight: bold; '>* NO PUEDE ELIMINAR UN PRODUCTO DE TIEMPO</span><br>", function () {
            alertify.message('OK');
        });
         return;
     }

     if (parseFloat(quantityDetail.text()) == parseFloat(quantity)) {
        counter++;
    }
    if (quantity != "" && quantity != "0") {
        quantityTableProd.push(quantity);
        idItem.push(idDetail);
        idProdMonth.push(idProductTable);
    } else {
        counter2++;
    }
    if (counter == table_name.length || counter2 == table_name.length) {
        alertify.alert('<span style="font-weight: bold;    color: red;">ERROR</span>', "<span style='font-weight: bold; '>* DEBE ANULAR MESA</span><br>", function () {
            alertify.message('OK');
        });
        clearDetail();
        error = "error";
        return;
    }
});
    if (error == "") {
        saleDate = vf_sale;
        $.post(ROUTES.deleteQuantityTable, {idDetail: idItem,idTable:idTable,quantity:quantityTableProd,saleDate:saleDate}, function (res) {
            var json = JSON.parse(res);
            if (verifyError(json.error)) {
                return;
            }
            alertify.success('GUARDADO CORRECTAMENTE');
            idTable=null;
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }
}
}
function verifyBackup(){
    if (backup!="no_backup") {
     JSON.stringify(backup);
     var obj=JSON.parse(backup);
     var date = new Date();

     if(obj.day_code==f_sale.getDate() && obj.status=="0" || obj.pending=="1"){
 // if(navigator.onLine){//verifica si hay internet, caso contrario queda pendiente el backup


    $('#loading').css('display','block');
    $.post('legacy/Google-Drive/index.php', {process:"BACKUP",day_code:obj.day_code,pending:obj.pending}, function (res) {

        var json = JSON.parse(res);
        if (verifyError(json.error)) {
            return;
        }

        alert("BACKUP ENVIADO CORRECTAMENTE");
        $('#loading').css('display','none');
    }).fail(function(xhr, ajaxOptions, thrownError){
        failErrors(xhr, ajaxOptions, thrownError);  
    });
// }else{
//     $.post('legacy/Google-Drive/index.php', {proceso:"backupPendiente",dia:obj.DIA,pendiente:obj.PENDIENTE}, function (res) {
   
// }).fail(function(xhr, ajaxOptions, thrownError){
//     failErrors(xhr, ajaxOptions, thrownError);  
// });
// }
}else{

    if (obj.day_code!=f_sale.getDate()  && obj.status=="1") {
        $.post('legacy/Google-Drive/index.php', {process:"changeStatus",day_code:obj.day_code,pending:obj.pending}, function (res) {
           
        }).fail(function(xhr, ajaxOptions, thrownError){
            failErrors(xhr, ajaxOptions, thrownError);  
        });
    }
}
}
}
function activateValue(input){
    if ($(input).prop('checked')) {
        $('#ModalPurchaseVat').modal('show');
    }
}
function convertQr(value){
    if (value!="") {
     var str = value;
     var res = str.split("|");
     taxIdSupplier= res[0];
     numberInvoice= res[1];
     authorization= res[2];
     date= res[3];
     total= res[4];
     controlCode= res[6];
     vatTaxCredit= res[5];
     $('#dateInvoice').val(date);
     $('#taxIdSupplier').val(taxIdSupplier);
     $('#numberAuthorization').val(authorization);
     $('#controlCode').val(controlCode);
     $('#inputNumberInvoice').val(numberInvoice);
     $('#inputDebitFiscal').val(vatTaxCredit);
     $('#inputExpensesAmount').val(total);
 }
}
// var targArea = document.getElementById ("keyPrssInp");
// targArea.addEventListener ('keydown',  reportKeyEvent);

// function reportKeyEvent (zEvent) {
//     var reportStr   =
//         "The " +
//         ( zEvent.ctrlKey  ? "Control " : "" ) +
//         ( zEvent.shiftKey ? "Shift "   : "" ) +
//         ( zEvent.altKey   ? "Alt "     : "" ) +
//         ( zEvent.metaKey  ? "Meta "    : "" ) +
//         zEvent.code + " " +
//         "key was pressed.";

//     // $("#statusReport").text (reportStr);

//     //--- Was a Ctrl-Alt-E combo pressed?
//     if (zEvent.ctrlKey  &&  zEvent.altKey  &&  zEvent.code === "KeyE") {
//         this.hitCnt = ( this.hitCnt || 0 ) + 1;
//     alert(reportStr);

//         // $("#statusReport").after (
//         //     '<p>Bingo! cnt: ' + this.hitCnt + '</p>'
//         // );
//     }
//     zEvent.stopPropagation ();
//     zEvent.preventDefault ()
// }

changeDateCustom=0;
function changeDate(date){

if (date!="" && change_date) {
vf_sale=date;
changeDateCustom=1;
}
}