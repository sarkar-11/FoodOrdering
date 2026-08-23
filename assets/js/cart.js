document.addEventListener('DOMContentLoaded', function () {
    const qtyInputs = document.querySelectorAll('.cart-qty-input');
    const grandTotalEl = document.getElementById('grand-total');

    if (!qtyInputs.length || !grandTotalEl) {
        return;
    }

    function updateTotals() {
        let grandTotal = 0;

        qtyInputs.forEach(function (input) {
            const row = input.closest('tr');
            const price = parseFloat(input.dataset.price || '0');
            const qty = parseInt(input.value, 10) || 0;
            const subtotal = price * qty;

            const subtotalCell = row.querySelector('.row-subtotal');
            if (subtotalCell) {
                subtotalCell.textContent = 'Rs. ' + subtotal.toFixed(2);
            }

            grandTotal += subtotal;
        });

        grandTotalEl.textContent = 'Rs. ' + grandTotal.toFixed(2);
    }

    qtyInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            if (input.value < 1) {
                input.value = 1;
            }
            updateTotals();
        });
    });

    updateTotals();
});
