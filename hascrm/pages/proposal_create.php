<?php
$page_title = 'Teklif Oluştur';
require_once __DIR__ . '/../includes/header.php';
require_login();

$pdo = get_db_connection();

// Formda kullanmak için müşterileri ve ürünleri çek
$customers_stmt = $pdo->prepare("SELECT id, name FROM customers WHERE organization_id = ? ORDER BY name");
$customers_stmt->execute([$_SESSION['organization_id']]);
$customers = $customers_stmt->fetchAll();

$products_stmt = $pdo->prepare("SELECT id, name, description, unit, price FROM products WHERE organization_id = ? ORDER BY name");
$products_stmt->execute([$_SESSION['organization_id']]);
$products = $products_stmt->fetchAll();

// Varsayılan değerler (Ekleme modu)
$proposal = ['id' => '', 'title' => '', 'customer_id' => '', 'proposal_date' => date('Y-m-d'), 'valid_until_date' => date('Y-m-d', strtotime('+30 days'))];
$proposal_items = [];
$form_title = 'Yeni Teklif Oluştur';
$action = 'create';

// Düzenleme modu (Bu kısım daha sonra detaylandırılabilir)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    // ... Düzenleme modu için veri çekme kodu ...
    $form_title = 'Teklifi Düzenle';
    $action = 'update';
}
?>

<header class="page-header">
    <h1 class="page-title"><?php echo e($form_title); ?></h1>
    <a href="proposals.php" role="button" class="secondary">Geri Dön</a>
</header>

<article>
    <form id="proposal-form" action="../api/proposal_handler.php?action=<?php echo $action; ?>" method="POST">
        <input type="hidden" name="proposal_id" value="<?php echo e($proposal['id']); ?>">

        <div class="grid">
            <label for="title">
                Teklif Başlığı
                <input type="text" id="title" name="title" value="<?php echo e($proposal['title']); ?>" required>
            </label>
            <label for="customer_id">
                Müşteri
                <select id="customer_id" name="customer_id" required>
                    <option value="">Seçin...</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo e($customer['id']); ?>" <?php echo ($customer['id'] == $proposal['customer_id']) ? 'selected' : ''; ?>>
                            <?php echo e($customer['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="grid">
            <label for="proposal_date">
                Teklif Tarihi
                <input type="date" id="proposal_date" name="proposal_date" value="<?php echo e($proposal['proposal_date']); ?>" required>
            </label>
            <label for="valid_until_date">
                Geçerlilik Tarihi
                <input type="date" id="valid_until_date" name="valid_until_date" value="<?php echo e($proposal['valid_until_date']); ?>">
            </label>
        </div>

        <h3 style="margin-top: 2rem;">Teklif Kalemleri</h3>
        <figure>
            <table id="proposal-items-table" role="grid">
                <thead>
                    <tr>
                        <th scope="col">Ürün/Hizmet</th>
                        <th scope="col" style="width: 10%;">Miktar</th>
                        <th scope="col" style="width: 10%;">Birim</th>
                        <th scope="col" style="width: 15%;">Birim Fiyat</th>
                        <th scope="col" style="width: 10%;">İndirim (%)</th>
                        <th scope="col" style="width: 15%;">Satır Toplamı</th>
                        <th scope="col" style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="proposal-items-body">
                    <!-- Dinamik satırlar buraya eklenecek -->
                </tbody>
            </table>
        </figure>
        <button type="button" id="add-item-btn" class="secondary outline">Yeni Kalem Ekle</button>

        <div class="grid" style="margin-top: 2rem;">
            <div style="flex-grow: 2;"></div>
            <div style="min-width: 300px;">
                <table class="totals-table">
                    <tbody>
                        <tr>
                            <td>Ara Toplam:</td>
                            <td id="subtotal" style="text-align:right;">0.00 TL</td>
                        </tr>
                        <tr>
                            <td>Genel İndirim:</td>
                            <td id="total-discount" style="text-align:right;">0.00 TL</td>
                        </tr>
                        <tr>
                            <td><strong>Genel Toplam:</strong></td>
                            <td id="grand-total" style="text-align:right;"><strong>0.00 TL</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <button type="submit" style="margin-top: 1rem;">Teklifi Kaydet</button>
    </form>
</article>

<!-- Yeni satır için klonlanacak HTML şablonu -->
<template id="item-template">
    <tr>
        <td>
            <input type="hidden" name="items[product_id][]" class="item-product-id">
            <input type="text" name="items[name][]" class="item-name" placeholder="Ürün adı..." required>
        </td>
        <td><input type="number" name="items[quantity][]" class="item-quantity" value="1" step="any" required></td>
        <td><input type="text" name="items[unit][]" class="item-unit" placeholder="adet"></td>
        <td><input type="number" name="items[unit_price][]" class="item-price" value="0.00" step="any" required></td>
        <td><input type="number" name="items[discount_percentage][]" class="item-discount" value="0" step="any"></td>
        <td class="item-line-total" style="text-align:right;">0.00 TL</td>
        <td><button type="button" class="remove-item-btn secondary outline" style="padding: 0.25rem 0.5rem;">X</button></td>
    </tr>
</template>

<style>
/* Sayfa başlığı ve butonunu yan yana getirmek için */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}
.page-title {
    margin-bottom: 0;
}
.totals-table td {
    padding: 0.5rem;
}
</style>

<script>
// Ürünleri JavaScript'e aktar
const productCatalog = <?php echo json_encode($products); ?>;
</script>
<script src="../assets/js/proposal_form.js"></script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>