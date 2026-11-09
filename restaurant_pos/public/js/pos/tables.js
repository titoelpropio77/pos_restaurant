/*
 * Mesas: apertura con formulario (mesero, código, personas o cliente) y cuenta compartida
 * (la mesa se cobra en partes, cada una con su nombre, NIT y forma de pago).
 *
 * Usa las variables globales del POS heredado (pos.js): idTable, numberTable, numberPerson,
 * idWaiter, waiterCode, dining_room, waiters_no_code, no_staff_view, vf_sale.
 */
(function (window, $) {
    'use strict';

    function money(value) {
        return (parseFloat(value) || 0).toFixed(2);
    }

    function qty(value) {
        return String(parseFloat((parseFloat(value) || 0).toFixed(2)));
    }

    function esc(text) {
        return $('<div>').text(text == null ? '' : text).html();
    }

    function codeRequired() {
        return window.waiters_no_code != 'waiters_no_code' && window.no_staff_view != 'no_staff_view';
    }

    function isDiningRoom() {
        return window.dining_room != 'no_dining_room';
    }

    /** Productos que hay en el ticket (pestaña Menú): se enviarán como primera comanda. */
    function ticketSummary() {
        var lines = [];
        var total = 0;
        $('#tableDetail input[name=quantity]').each(function () {
            var row = $(this).closest('tr');
            lines.push({quantity: $(this).val(), name: row.find('input[name=nameProduct]').val()});
            total += parseFloat(row.find('#spanSubTotal').text()) || 0;
        });
        return {lines: lines, total: total};
    }

    function renderOpenSummary() {
        var ticket = ticketSummary();
        var box = $('#openTableSummary');

        if (ticket.lines.length === 0) {
            box.removeClass('has-items').html(
                '<i class="fa fa-info-circle"></i> <div><strong>La mesa se abrirá sin productos.</strong>' +
                '<span>Agregue los pedidos después con <b>AUMENTAR</b>.</span></div>'
            );
            return;
        }

        var items = ticket.lines.slice(0, 6).map(function (line) {
            return '<li><b>' + esc(qty(line.quantity)) + '</b> ' + esc(line.name) + '</li>';
        }).join('');
        if (ticket.lines.length > 6) {
            items += '<li class="is-more">y ' + (ticket.lines.length - 6) + ' más…</li>';
        }

        box.addClass('has-items').html(
            '<i class="fa fa-cutlery"></i> <div><strong>Se enviará a cocina la comanda · Bs ' + money(ticket.total) + '</strong>' +
            '<ul>' + items + '</ul></div>'
        );
    }

    // ---------- Cuenta compartida ----------
    var split = {
        items: [],      // ítems pendientes de la mesa (detalle)
        lines: {},      // id de ítem => línea de la parte (vista previa del servidor)
        mode: 'equal',
        parts: 2,
        paid: [],       // partes cobradas en esta sesión del modal
        total: 0,
        timer: null,
        request: null
    };

    function remainingParts() {
        return Math.max(1, split.parts - split.paid.length);
    }

    function chosenItems() {
        var items = [];
        $('#splitItems input[data-item]').each(function () {
            var quantity = parseFloat($(this).val()) || 0;
            if (quantity > 0) {
                items.push({id: $(this).data('item'), quantity: quantity});
            }
        });
        return items;
    }

    function loadSplitItems(done) {
        $.get(ROUTES.tableDetail, {idTable: window.idTable, numberTable: window.numberTable, saleDate: window.vf_sale}, function (res) {
            var json = JSON.parse(res);
            if (verifyError(json.error)) {
                return;
            }
            split.items = json.result === '' ? [] : json.result.filter(function (item) {
                return parseFloat(item.quantity) > 0;
            });
            done();
        }).fail(failErrors);
    }

    function renderSplitItems() {
        var equal = split.mode === 'equal';
        $('#splitQtyHead').text(equal ? 'Le toca' : 'Cantidad');

        $('#splitItems').html(split.items.map(function (item) {
            var name = esc(item.product_name) + (item.note ? ' <small>(' + esc(item.note) + ')</small>' : '');
            var available = qty(item.quantity);
            var cell = equal
                ? '<span class="pos-split-share" data-share="' + item.id + '">–</span> <small>de ' + available + '</small>'
                : '<div class="pos-stepper is-small">' +
                    '<button type="button" onclick="TableService.stepItem(' + item.id + ', -1)" aria-label="Menos"><i class="fa fa-minus"></i></button>' +
                    '<input type="number" min="0" step="any" max="' + available + '" value="" placeholder="0" data-item="' + item.id + '" oninput="TableService.itemChanged(this)">' +
                    '<button type="button" onclick="TableService.stepItem(' + item.id + ', 1)" aria-label="Más"><i class="fa fa-plus"></i></button>' +
                  '</div>' +
                  '<button type="button" class="pos-split-all" onclick="TableService.allItem(' + item.id + ')">Todo (' + available + ')</button>';

            return '<tr><td>' + name + '</td><td class="text-right">' + money(item.sale_price) + '</td>' +
                '<td class="text-center">' + cell + '</td><td class="text-right" data-subtotal="' + item.id + '">–</td></tr>';
        }).join(''));
    }

    function renderSplitHead() {
        var number = split.paid.length + 1;
        $('#splitPartLabel').text(split.mode === 'equal' ? 'Parte ' + number + ' de ' + split.parts : 'Parte ' + number);
        $('#splitParts').val(split.parts);
        $('#splitPaidSummary').text(split.paid.length ? split.paid.length + (split.paid.length === 1 ? ' parte cobrada' : ' partes cobradas') : '');
        $('#splitPaidList').html(split.paid.length ? '<span class="pos-label">Partes cobradas</span>' + split.paid.map(function (part, i) {
            return '<div class="pos-split-paid-row"><span class="pos-split-paid-n">' + (i + 1) + '</span>' +
                '<span class="pos-split-paid-name">' + esc(part.name) + (part.taxId ? ' <small>NIT ' + esc(part.taxId) + '</small>' : '') + '</span>' +
                '<span class="pos-split-paid-method">' + esc(part.method) + '</span><b>Bs ' + money(part.total) + '</b></div>';
        }).join('') : '');
    }

    function applyPreview(result) {
        split.total = parseFloat(result.total) || 0;
        split.lines = {};
        (result.lines || []).forEach(function (line) {
            split.lines[line.id] = line;
        });

        if (result.pending !== undefined) {
            $('#splitPending').text(money(result.pending));
        }
        $('#splitItems [data-subtotal]').each(function () {
            var line = split.lines[$(this).data('subtotal')];
            $(this).text(line ? money(line.subtotal) : '–');
        });
        $('#splitItems [data-share]').each(function () {
            var line = split.lines[$(this).data('share')];
            $(this).text(line ? qty(line.quantity) : '0');
        });
        $('#splitPartTotal, #splitChargeTotal').text(money(split.total));
        $('#btnSplitCharge').prop('disabled', split.total <= 0);
        TableService.updateChange();
    }

    function refreshPreview() {
        clearTimeout(split.timer);
        split.timer = setTimeout(function () {
            var data = {idTable: window.idTable, saleDate: window.vf_sale, mode: split.mode};
            if (split.mode === 'equal') {
                data.parts = remainingParts();
            } else {
                data.items = chosenItems();
                if (data.items.length === 0) {
                    applyPreview({total: 0, lines: []});
                    return;
                }
            }

            if (split.request) {
                split.request.abort();
            }
            split.request = $.get(ROUTES.tableSplitPreview, data, function (res) {
                var json = typeof res === 'string' ? JSON.parse(res) : res;
                if (verifyError(json.error)) {
                    return;
                }
                applyPreview(json.result);
            }).fail(function (xhr, status, error) {
                if (status !== 'abort') {
                    failErrors(xhr, status, error);
                }
            });
        }, 150);
    }

    function resetCustomer() {
        $('#splitName, #splitTaxId, #splitReceived').val('');
        $('input[name=splitPayment][value=CONTADO]').prop('checked', true);
        TableService.setPayment('CONTADO');
    }

    var TableService = {
        // ---------- Apertura ----------

        /** Toque en una mesa libre: muestra el formulario de apertura. */
        open: function (button) {
            window.idTable = $(button).attr('idTable');
            window.numberTable = $(button).attr('numberTable');

            var waiter = $('#openTableWaiter').empty();
            $('#selectWaiterTables option').each(function () {
                waiter.append($(this).clone());
            });
            waiter.val($('#selectWaiterTables').val() || '0');

            $('#openTableNumber').text(window.numberTable);
            $('#openTableCode').val('');
            $('#openTableCodeField').toggle(codeRequired());
            $('#openTablePeopleField').toggle(!isDiningRoom());
            $('#openTableCustomerField').toggle(isDiningRoom());
            $('#openTablePeople').val(2);
            $('#openTableCustomer').val('');
            $('#btnOpenTable').prop('disabled', false);
            renderOpenSummary();

            $('#ModalOpenTable').modal('show');
        },

        stepPeople: function (delta) {
            var input = $('#openTablePeople');
            input.val(Math.min(99, Math.max(1, (parseInt(input.val(), 10) || 0) + delta)));
        },

        submitOpen: function () {
            var waiter = $('#openTableWaiter').val();
            var code = $.trim($('#openTableCode').val());

            if (!waiter || waiter === '0') {
                alertify.error('SELECCIONE EL MESERO');
                $('#openTableWaiter').focus();
                return;
            }
            if (codeRequired() && code === '') {
                alertify.error('INGRESE EL CÓDIGO DEL MESERO');
                $('#openTableCode').focus();
                return;
            }

            $('#selectWaiterTables').val(waiter);
            window.idWaiter = waiter;
            window.waiterCode = code;
            var person = isDiningRoom() ? $.trim($('#openTableCustomer').val()) : String(parseInt($('#openTablePeople').val(), 10) || 1);

            $('#btnOpenTable').prop('disabled', true);
            addProductTable(person, function (opened) {
                $('#btnOpenTable').prop('disabled', false);
                if (opened) {
                    $('#ModalOpenTable').modal('hide');
                } else if (codeRequired()) {
                    $('#openTableCode').val('').focus();
                }
            });
        },

        // ---------- Cuenta compartida ----------

        openSplit: function () {
            if (!window.idTable) {
                alertify.error('SELECCIONE UNA MESA');
                return;
            }
            if ($('#spanProcessing').text() != '') {
                alertify.alert('<span style="font-weight: bold; color: red;">ERROR</span>', 'EXISTEN PRODUCTOS EN PROCESO DE FACTURACION.');
                return;
            }

            loadSplitItems(function () {
                if (split.items.length === 0) {
                    alertify.error('LA MESA NO TIENE PRODUCTOS PENDIENTES DE COBRO');
                    return;
                }
                if (split.items.some(function (item) { return item.timed === 'T'; })) {
                    alertify.alert('<span style="font-weight: bold; color: red;">ERROR</span>', 'LA CUENTA COMPARTIDA NO ADMITE PRODUCTOS POR TIEMPO');
                    return;
                }

                split.paid = [];
                split.mode = 'equal';
                split.parts = isDiningRoom() ? 2 : Math.min(50, Math.max(2, parseInt(window.numberPerson, 10) || 2));
                $('input[name=splitMode][value=equal]').prop('checked', true);
                $('#splitEqual').show();
                $('#splitTableNumber').text(window.numberTable);
                resetCustomer();
                renderSplitItems();
                renderSplitHead();
                applyPreview({total: 0, lines: []});
                refreshPreview();

                $('#ModalTableItem').modal('hide');
                $('#ModalSplitBill').modal('show');
            });
        },

        closeSplit: function () {
            $('#ModalSplitBill').modal('hide');
            if (split.paid.length) {
                alertify.message('LA MESA QUEDA CON SALDO PENDIENTE');
            }
            listTables();
        },

        setMode: function (mode) {
            split.mode = mode;
            $('#splitEqual').toggle(mode === 'equal');
            renderSplitItems();
            renderSplitHead();
            applyPreview({total: 0, lines: []});
            refreshPreview();
        },

        setParts: function (value) {
            split.parts = Math.min(50, Math.max(split.paid.length + 1, parseInt(value, 10) || 1));
            renderSplitHead();
            refreshPreview();
        },

        stepParts: function (delta) {
            TableService.setParts(split.parts + delta);
        },

        itemChanged: function (input) {
            var max = parseFloat($(input).attr('max')) || 0;
            var value = parseFloat($(input).val());
            if (value > max) {
                $(input).val(qty(max));
            } else if (value < 0) {
                $(input).val('');
            }
            refreshPreview();
        },

        stepItem: function (id, delta) {
            var input = $('#splitItems input[data-item=' + id + ']');
            var max = parseFloat(input.attr('max')) || 0;
            var value = Math.min(max, Math.max(0, (parseFloat(input.val()) || 0) + delta));
            input.val(value > 0 ? qty(value) : '');
            refreshPreview();
        },

        allItem: function (id) {
            var input = $('#splitItems input[data-item=' + id + ']');
            input.val(input.attr('max'));
            refreshPreview();
        },

        setPayment: function (method) {
            $('#splitCash').toggle(method === 'CONTADO');
            TableService.updateChange();
        },

        exact: function () {
            $('#splitReceived').val(money(split.total));
            TableService.updateChange();
        },

        updateChange: function () {
            var received = $('#splitReceived').val();
            var change = received === '' ? 0 : (parseFloat(received) || 0) - split.total;
            $('#splitChange').text(money(change));
            $('#splitChangeBox').toggleClass('is-missing', change < 0);
        },

        chargePart: function () {
            var method = $('input[name=splitPayment]:checked').val();
            var name = $.trim($('#splitName').val());
            var received = $.trim($('#splitReceived').val());

            if (split.total <= 0) {
                alertify.error(split.mode === 'items' ? 'ELIJA LOS PRODUCTOS QUE PAGA ESTE CLIENTE' : 'NO HAY SALDO PARA COBRAR');
                return;
            }
            if (method === 'CREDITO' && name === '') {
                alertify.error('EL CAMPO NOMBRE DE CLIENTE NO DEBE ESTAR VACIO');
                $('#splitName').focus();
                return;
            }
            if (method === 'CONTADO' && received !== '' && (parseFloat(received) || 0) < split.total) {
                alertify.error('EL MONTO RECIBIDO ES MENOR AL TOTAL DE LA PARTE');
                return;
            }

            var data = {
                idTable: window.idTable,
                saleDate: window.vf_sale,
                mode: split.mode,
                nameCustomer: name,
                customerTaxId: $.trim($('#splitTaxId').val()),
                paymentMethod: method,
                received: method === 'CONTADO' ? received : ''
            };
            if (split.mode === 'equal') {
                data.parts = remainingParts();
            } else {
                data.items = chosenItems();
            }

            var button = $('#btnSplitCharge').prop('disabled', true);
            $.post(ROUTES.tableSplitCharge, data, function (res) {
                var json = typeof res === 'string' ? JSON.parse(res) : res;
                if (verifyError(json.error)) {
                    button.prop('disabled', false);
                    return;
                }

                var result = json.result;
                TicketPrint.receipt(result.sale_id);
                split.paid.push({name: name || 'SIN NOMBRE', taxId: data.customerTaxId, method: $('input[name=splitPayment]:checked').parent().text().trim(), total: result.total});

                var change = parseFloat(result.change) || 0;
                alertify.success('PARTE ' + split.paid.length + ' COBRADA: Bs ' + money(result.total) + (change > 0 ? ' · CAMBIO Bs ' + money(change) : ''));

                if (result.closed) {
                    $('#ModalSplitBill').modal('hide');
                    alertify.success('CUENTA COMPLETA: MESA ' + window.numberTable + ' LIBRE');
                    window.idTable = '';
                    window.numberTable = '';
                    listTables();
                    return;
                }

                resetCustomer();
                loadSplitItems(function () {
                    renderSplitItems();
                    renderSplitHead();
                    applyPreview({total: 0, lines: [], pending: result.pending});
                    refreshPreview();
                });
            }).fail(function (xhr, status, error) {
                button.prop('disabled', false);
                failErrors(xhr, status, error);
            });
        }
    };

    $('#ModalOpenTable').on('shown.bs.modal', function () {
        if ($('#openTableWaiter').val() === '0') {
            $('#openTableWaiter').focus();
        } else if (codeRequired()) {
            $('#openTableCode').focus();
        } else {
            $('#btnOpenTable').focus();
        }
    });

    // Mesa libre: un toque abre el formulario (antes era un menú con una sola opción "ABRIR")
    $(document).on('click', '#divTable .agregarPM', function () {
        TableService.open(this);
    });

    window.TableService = TableService;
})(window, jQuery);
