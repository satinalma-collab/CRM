document.addEventListener('DOMContentLoaded', function() {
    const addItemBtn = document.getElementById('add-item-btn');
    const itemsTableBody = document.getElementById('proposal-items-body');
    const itemTemplate = document.getElementById('item-template');

    // Yeni bir satır ekler
    function addNewItem() {
        const clone = itemTemplate.content.cloneNode(true);
        itemsTableBody.appendChild(clone);
        attachRowEventListeners(itemsTableBody.lastElementChild);
        updateTotals();
    }

    // Bir satırı siler
    function removeItem(row) {
        row.remove();
        updateTotals();
    }

    // Bir satırdaki tüm inputlara olay dinleyicileri ekler
    function attachRowEventListeners(row) {
        row.querySelector('.remove-item-btn').addEventListener('click', () => removeItem(row));

        const inputs = row.querySelectorAll('.item-quantity, .item-price, .item-discount');
        inputs.forEach(input => {
            input.addEventListener('input', updateTotals);
        });

        // Basit bir otomatik tamamlama
        const nameInput = row.querySelector('.item-name');
        nameInput.addEventListener('input', () => {
            const value = nameInput.value.toLowerCase();
            if (value.length < 2) return;

            const match = productCatalog.find(p => p.name.toLowerCase().startsWith(value));
            if (match) {
                // Öneri göstermek yerine direkt dolduruyoruz (basit implementasyon)
                // Daha gelişmiş bir yapı için bir dropdown listesi oluşturulabilir.
                if (nameInput.value !== match.name) {
                    nameInput.value = match.name;
                    row.querySelector('.item-product-id').value = match.id;
                    row.querySelector('.item-unit').value = match.unit;
                    row.querySelector('.item-price').value = match.price;
                    updateTotals();
                }
            }
        });
    }

    // Tüm toplamları günceller
    function updateTotals() {
        let subtotal = 0;
        let totalDiscount = 0;

        itemsTableBody.querySelectorAll('tr').forEach(row => {
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const discountPercent = parseFloat(row.querySelector('.item-discount').value) || 0;

            const lineTotalBeforeDiscount = quantity * price;
            const lineDiscountAmount = lineTotalBeforeDiscount * (discountPercent / 100);
            const lineTotal = lineTotalBeforeDiscount - lineDiscountAmount;

            subtotal += lineTotalBeforeDiscount;
            totalDiscount += lineDiscountAmount;

            row.querySelector('.item-line-total').textContent = lineTotal.toFixed(2) + ' TL';
        });

        const grandTotal = subtotal - totalDiscount;

        document.getElementById('subtotal').textContent = subtotal.toFixed(2) + ' TL';
        document.getElementById('total-discount').textContent = totalDiscount.toFixed(2) + ' TL';
        document.getElementById('grand-total').innerHTML = `<strong>${grandTotal.toFixed(2)} TL</strong>`;
    }

    // Başlangıç için bir satır ekle
    addNewItem();

    // Buton olay dinleyicisi
    addItemBtn.addEventListener('click', addNewItem);
});