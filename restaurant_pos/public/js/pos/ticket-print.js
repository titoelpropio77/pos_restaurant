/*
 * Impresión de tickets (comandas, precuentas y notas de venta) en la ticketera USB de la caja, desde el navegador.
 * El servidor devuelve el ticket en HTML; aquí se carga en un iframe oculto y se imprime.
 * Con Chrome abierto con --kiosk-printing sale directo en la impresora predeterminada, sin diálogo.
 */
(function (window, $) {
    'use strict';

    /** done: se llama al terminar (impreso, sin nada que imprimir o con error). */
    function print(url, params, done) {
        var query = $.param(params || {});
        var frame = document.createElement('iframe');
        var finished = false;

        frame.setAttribute('aria-hidden', 'true');
        frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
        frame.onload = function () {
            var doc = frame.contentDocument;
            var win = frame.contentWindow;
            var body = doc && doc.body;

            // Sesión vencida u otro error: el servidor responde JSON en lugar del ticket
            if (!body || !body.hasAttribute('data-empty')) {
                cleanup();
                alertify.error('NO SE PUDO IMPRIMIR EL TICKET');
                return;
            }
            // Ningún producto va a una estación de impresión: no hay nada que imprimir
            if (body.getAttribute('data-empty') === '1') {
                cleanup();
                return;
            }

            win.onafterprint = cleanup;
            win.focus();
            win.print();
            // Respaldo por si el navegador no dispara afterprint
            setTimeout(cleanup, 60000);
        };
        frame.onerror = function () {
            cleanup();
            alertify.error('NO SE PUDO IMPRIMIR EL TICKET');
        };

        function cleanup() {
            if (finished) {
                return;
            }
            finished = true;
            if (frame.parentNode) {
                frame.parentNode.removeChild(frame);
            }
            if (typeof done === 'function') {
                done();
            }
        }

        frame.src = url + (query ? (url.indexOf('?') === -1 ? '?' : '&') + query : '');
        document.body.appendChild(frame);
    }

    window.TicketPrint = {
        /** Comanda de los productos recién enviados a una mesa. */
        tableOrder: function (table, ticket, date, done) {
            print(ROUTES.printTableOrder, {table: table, ticket: ticket, date: date}, done);
        },
        /** Comanda de una venta directa. */
        saleOrder: function (sale, done) {
            print(ROUTES.printSaleOrder, {sale: sale}, done);
        },
        /** Nota de venta o factura; copy = true al reimprimir. */
        receipt: function (sale, done, copy) {
            print(ROUTES.printReceipt, copy ? {sale: sale, copy: 1} : {sale: sale}, done);
        },
        /** Precuenta de una mesa abierta. */
        preBill: function (table, date, done) {
            print(ROUTES.printPreBill, {table: table, date: date}, done);
        }
    };
})(window, jQuery);
