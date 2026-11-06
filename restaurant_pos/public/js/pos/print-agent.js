/*
 * El agente de impresión local (http://<ip>:8080/reportes/*.php) es un programa externo que sigue
 * leyendo los nombres de campo del sistema original. Este archivo traduce en un solo punto todo lo
 * que sale hacia él: el resto del POS trabaja con los nombres actuales.
 * LEGACY_FIELDS: nombre actual => nombres que espera el agente (generado desde su código).
 */
(function ($) {
    const LEGACY_FIELDS = {
        "BRANCH": [
                "SUCURSAL"
        ],
        "SalesGivenDate": [
                "VentasDadaFecha"
        ],
        "addressCustomer": [
                "direccionCli"
        ],
        "amount": [
                "MONTO",
                "monto"
        ],
        "auth_key": [
                "LLAVE"
        ],
        "authorization_number": [
                "NROAUTORI"
        ],
        "balanceCashRegister": [
                "saldoCaja"
        ],
        "business_address": [
                "DIREMP"
        ],
        "business_name": [
                "NOMEMP"
        ],
        "business_phone": [
                "TELEMP"
        ],
        "changeBs": [
                "cambioBs"
        ],
        "checkExpenses": [
                "checkGastos"
        ],
        "checkInvoiceAnu": [
                "checkFacturaAnu"
        ],
        "checkInvoiceCre": [
                "checkFacturaCre"
        ],
        "checkProductsSold": [
                "checkProductosVendidos"
        ],
        "checkSalesDeli": [
                "checkVentasDeli"
        ],
        "checkTotalCashRegister": [
                "checkTotalCaja"
        ],
        "checkTotaleGroups": [
                "checkTotaleGrupos"
        ],
        "city_country": [
                "CIUPAI"
        ],
        "concept": [
                "CONCEPTO",
                "concepto"
        ],
        "consumption": [
                "consumo"
        ],
        "credit": [
                "credito"
        ],
        "current_invoice": [
                "FACACT"
        ],
        "customerTaxId": [
                "nitCli"
        ],
        "customer_name": [
                "NOM_CLI"
        ],
        "customer_tax_id": [
                "NIT_CLI"
        ],
        "date": [
                "fecha"
        ],
        "dateFinal": [
                "fechaFinal"
        ],
        "dateInitial": [
                "fechaInicial"
        ],
        "dateToday": [
                "fechaHoy"
        ],
        "delivered": [
                "entregado"
        ],
        "delivered_to": [
                "ENTREGADOA"
        ],
        "expenses": [
                "gastos"
        ],
        "family": [
                "FAMILIA"
        ],
        "group_name": [
                "NOM_GRUPO"
        ],
        "idSale": [
                "idVenta"
        ],
        "imPrint": [
                "imPresion"
        ],
        "invoice": [
                "fac"
        ],
        "invoice_total": [
                "TOTAL_FACT"
        ],
        "invoiced": [
                "facturado"
        ],
        "legend_1": [
                "LEYENDA1"
        ],
        "legend_2": [
                "LEYENDA2"
        ],
        "limit_date": [
                "FECHA_LIM"
        ],
        "listClosingCashTotal": [
                "listaTOTALCAJACIERRE"
        ],
        "listDSale": [
                "listaDVenta"
        ],
        "listExpenses": [
                "listarGastos"
        ],
        "listGroup": [
                "listaGrupo"
        ],
        "listInvoicesCredit": [
                "listaFacturasCredito"
        ],
        "listInvoicesVoided": [
                "listaFacturasAnuladas"
        ],
        "listItemDeleted": [
                "listaItemEli"
        ],
        "listRestaurant": [
                "listaEmpresa"
        ],
        "listSale": [
                "listaVenta"
        ],
        "login": [
                "ingreso"
        ],
        "nameCustomer": [
                "nombreCli"
        ],
        "nameEmployeeServed": [
                "nombreEmpleadoAtendio"
        ],
        "nameProduct": [
                "nombrePdto",
                "nombreProducto"
        ],
        "note": [
                "NOTA"
        ],
        "numberOrder": [
                "nroOrden"
        ],
        "numberTable": [
                "nroMesa"
        ],
        "operation": [
                "operacion"
        ],
        "order_number": [
                "ORDEN",
                "orden"
        ],
        "phoneCustomer": [
                "telefonoCli"
        ],
        "priceA": [
                "precioA"
        ],
        "printKitchen": [
                "impCoc"
        ],
        "print_station_1": [
                "imppunto1"
        ],
        "print_station_2": [
                "imppunto2"
        ],
        "print_station_3": [
                "imppunto3"
        ],
        "print_station_4": [
                "imppunto4"
        ],
        "process": [
                "proceso"
        ],
        "product_name": [
                "NOM_PROD"
        ],
        "qr_code": [
                "AGRUPA3",
                "codigoqu"
        ],
        "quantity": [
                "CANTIDAD",
                "cantidad"
        ],
        "reportGroup": [
                "reporteGrupo"
        ],
        "restaurant": [
                "empresa"
        ],
        "saleDate": [
                "fechaVenta"
        ],
        "sale_date": [
                "F_VENTA"
        ],
        "sale_price": [
                "PRE_VENTA"
        ],
        "sale_time": [
                "DHORAVEN"
        ],
        "salesDaily": [
                "ventasDiarias"
        ],
        "sellDay": [
                "vendaDia"
        ],
        "service_type": [
                "SALIDA"
        ],
        "shift": [
                "turno"
        ],
        "staff_name": [
                "NOM_EMPL"
        ],
        "subtotal": [
                "AGRUPA1"
        ],
        "tax_id": [
                "NITRUC"
        ],
        "time": [
                "hora"
        ],
        "typeExpense": [
                "tipoGasto"
        ],
        "username": [
                "usuario"
        ],
        "voided": [
                "anulado"
        ]
    };

    function toLegacy(value) {
        if (Array.isArray(value)) {
            return value.map(toLegacy);
        }

        if (value === null || typeof value !== 'object') {
            return value;
        }

        const result = {};

        Object.keys(value).forEach(function (key) {
            result[key] = toLegacy(value[key]);
            (LEGACY_FIELDS[key] || []).forEach(function (legacyKey) {
                result[legacyKey] = result[key];
            });
        });

        return result;
    }

    const ajax = $.ajax;

    $.ajax = function (url, options) {
        const settings = typeof url === 'object' ? url : $.extend({}, options, { url: url });

        if (/:8080\/reportes\//.test(settings.url || '') && settings.data && typeof settings.data === 'object') {
            settings.data = toLegacy(settings.data);
        }

        return ajax.call($, settings);
    };

    window.toPrintAgentPayload = toLegacy;
})(jQuery);
