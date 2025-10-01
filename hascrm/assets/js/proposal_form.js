// Global state for modal
const openModal = (modal) => modal && modal.setAttribute('open', 'true');
const closeModal = (modal) => modal && modal.removeAttribute('open');
const toggleModal = (event) => {
    event.preventDefault();
    const modal = document.getElementById(event.currentTarget.dataset.target);
    if (modal) {
        modal.getAttribute('open') ? closeModal(modal) : openModal(modal);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const itemsTableBody = document.getElementById('proposal-items-body');
    const itemTemplate = document.getElementById('item-template');
    const currencySelect = document.getElementById('currency');
    const productModal = document.getElementById('product-modal');
    const addItemBtn = document.querySelector('[data-target="product-modal"]');

    // Bir ürünü teklife ekler
    function addItemFromProduct(product) {
        const clone = itemTemplate.content.cloneNode(true);
        const row = clone.querySelector('tr');

        row.querySelector('.item-product-id').value = product.id;
        row.querySelector('.item-name').value = product.name;
        row.querySelector('.item-unit').value = product.unit;
        row.querySelector('.item-price').value = parseFloat(product.price).toFixed(2);

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
    }

    // Tüm toplamları günceller
    function updateTotals() {
        let subtotal = 0;
        let totalDiscount = 0;
        const currency = currencySelect.value;

        itemsTableBody.querySelectorAll('tr').forEach(row => {
            const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const discountPercent = parseFloat(row.querySelector('.item-discount').value) || 0;

            const lineTotalBeforeDiscount = quantity * price;
            const lineDiscountAmount = lineTotalBeforeDiscount * (discountPercent / 100);
            const lineTotal = lineTotalBeforeDiscount - lineDiscountAmount;

            subtotal += lineTotalBeforeDiscount;
            totalDiscount += lineDiscountAmount;

            row.querySelector('.item-line-total').textContent = `${lineTotal.toFixed(2)} ${currency}`;
        });

        const grandTotal = subtotal - totalDiscount;

        document.getElementById('subtotal').textContent = `${subtotal.toFixed(2)} ${currency}`;
        document.getElementById('total-discount').textContent = `${totalDiscount.toFixed(2)} ${currency}`;
        document.getElementById('grand-total').innerHTML = `<strong>${grandTotal.toFixed(2)} ${currency}</strong>`;
    }

    // --- Olay Dinleyicileri ve Başlangıç Ayarları ---

    // Modal'daki ürün kartlarına tıklama olayı
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', () => {
            const productId = card.dataset.productId;
            const product = productCatalog.find(p => p.id == productId);
            if (product) {
                addItemFromProduct(product);
                closeModal(productModal);
            }
        });
    });

    // Para birimi değiştiğinde toplamları güncelle
    currencySelect.addEventListener('change', updateTotals);

    // Modal kapatma düğmesi
    const closeButton = productModal.querySelector('.close');
    if(closeButton) {
        closeButton.addEventListener('click', (event) => {
            event.preventDefault();
            closeModal(productModal);
        });
    }

    // "Ürün Ekle" butonuna tıklama olayı
    if(addItemBtn) {
        addItemBtn.addEventListener('click', toggleModal);
    }

    // --- DÜZELTME: Sayfa yüklendiğinde mevcut satırlara olay dinleyicileri ekle ve toplamları hesapla ---
    const existingRows = itemsTableBody.querySelectorAll('tr');
    if (existingRows.length > 0) {
        existingRows.forEach(row => {
            attachRowEventListeners(row);
        });
        updateTotals(); // Mevcut verilerle toplamları hesapla
    } else {
        // Eğer hiç satır yoksa (yeni teklif modu), boş bir satır ekle
        addItemFromProduct({ id: '', name: '', unit: 'adet', price: '0.00' });
    }
});