// Cashier screen: choose the patient, total of the ticked lines, change to give, receipt print.
// Same format as formatMoney() in PHP (Settings > General): decimals, separators, symbol side.
function cashierAmount (value) {
    let el = $('#cashierTotal');
    let decimals = parseInt(el.data('decimals')) || 0;
    let parts = value.toFixed(decimals).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, String(el.attr('data-thousands') ?? ''));
    let number = parts.join(String(el.attr('data-dec') ?? '.'));
    let symbol = el.data('symbol') || '';
    return el.data('position') === 'after' ? number + ' ' + symbol : symbol + ' ' + number;
}

function refreshCashierTotal () {
    let total = 0;
    $('.cashier-line:checked').each(function () {
        total += parseFloat($(this).data('amount')) || 0;
    });
    let received = parseFloat($('#cashierReceived').val());
    $('#cashierTotal').text(cashierAmount(total));
    $('#cashierChange').text(cashierAmount(received > total ? received - total : 0));
}

listenChange('#cashierPatient', function () {
    window.location.href = $('#cashierPage').data('index-url') + ($(this).val() ? '?patient=' + $(this).val() : '');
});

listenChange('.cashier-line', refreshCashierTotal);
listenKeyup('#cashierReceived', refreshCashierTotal);

document.addEventListener('DOMContentLoaded', function () {
    let printUrl = $('#cashierPage').data('print-url');
    if (printUrl) {
        window.open(printUrl + '?autoprint=1', '_blank');
    }
});
