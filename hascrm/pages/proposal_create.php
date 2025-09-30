<?php
$page_title = 'Teklif Oluştur';
require_once __DIR__ . '/../includes/header.php';
require_login();

$pdo = get_db_connection();

// Müşterileri ve ürünleri formda kullanmak için çek
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

// Düzenleme modu
if (isset($_GET['id'])) {
    // ... Düzenleme modu için veri çekme kodu buraya eklenecek ...
    // Bu kısım şimdilik basit tutulmuştur.
    $form_title = 'Teklifi Düzenle';
    $action = 'update';
}
?>

<div class="page-header">
    <h1><?php echo e($form_title); ?></h1>
    <a href="proposals.php" class="button-secondary">Geri Dön</a>
</div>

<form id="proposal-form" action="../api/proposal_handler.php?action=<?php echo $action; ?>" method="POST">
    <input type="hidden" name="proposal_id" value="<?php echo e($proposal['id']); ?>">

    <h3>Genel Bilgiler</h3>
    <div class="form-row">
        <div class="form-group">
            <label for="title">Teklif Başlığı:</label>
            <input type="text" id="title" name="title" value="<?php echo e($proposal['title']); ?>" required>
        </div>
        <div class="form-group">
            <label for="customer_id">Müşteri:</label>
            <select id="customer_id" name="customer_id" required>
                <option value="">Müşteri Seçin...</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo e($customer['id']); ?>" <?php echo ($customer['id'] == $proposal['customer_id']) ? 'selected' : ''; ?>>
                        <?php echo e($customer['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="proposal_date">Teklif Tarihi:</label>
            <input type="date" id="proposal_date" name="proposal_date" value="<?php echo e($proposal['proposal_date']); ?>" required>
        </div>
        <div class="form-group">
            <label for="valid_until_date">Geçerlilik Tarihi:</label>
            <input type="date" id="valid_until_date" name="valid_until_date" value="<?php echo e($proposal['valid_until_date']); ?>">
        </div>
    </div>

    <h3>Teklif Kalemleri</h3>
    <table id="proposal-items-table">
        <thead>
            <tr>
                <th>Ürün/Hizmet</th>
                <th width="100px">Miktar</th>
                <th width="100px">Birim</th>
                <th width="150px">Birim Fiyat</th>
                <th width="100px">İndirim (%)</th>
                <th width="180px">Satır Toplamı</th>
                <th width="50px"></th>
            </tr>
        </thead>
        <tbody id="proposal-items-body">
            <!-- Dinamik satırlar buraya eklenecek -->
        </tbody>
    </table>
    <button type="button" id="add-item-btn" class="button-add">Yeni Kalem Ekle</button>

    <div class="totals-section">
        <table>
            <tr>
                <td>Ara Toplam:</td>
                <td id="subtotal">0.00 TL</td>
            </tr>
            <tr>
                <td>Genel İndirim:</td>
                <td id="total-discount">0.00 TL</td>
            </tr>
            <tr>
                <td><strong>Genel Toplam:</strong></td>
                <td id="grand-total"><strong>0.00 TL</strong></td>
            </tr>
        </table>
    </div>

    <button type="submit" class="button-primary" style="margin-top: 20px;">Teklifi Kaydet</button>
</form>

<!-- Yeni satır için klonlanacak HTML şablonu -->
<template id="item-template">
    <tr>
        <td>
            <input type="hidden" name="items[product_id][]" class="item-product-id">
            <input type="text" name="items[name][]" class="item-name" placeholder="Ürün adı veya açıklama..." required>
        </td>
        <td><input type="number" name="items[quantity][]" class="item-quantity" value="1" step="0.01" required></td>
        <td><input type="text" name="items[unit][]" class="item-unit" placeholder="adet"></td>
        <td><input type="number" name="items[unit_price][]" class="item-price" value="0.00" step="0.01" required></td>
        <td><input type="number" name="items[discount_percentage][]" class="item-discount" value="0" step="0.01"></td>
        <td><span class="item-line-total">0.00 TL</span></td>
        <td><button type="button" class="remove-item-btn button-delete">X</button></td>
    </tr>
</template>

<style>
.form-row { display: flex; gap: 20px; }
.form-group { flex: 1; }
.totals-section { float: right; width: 300px; margin-top: 20px; }
.button-add { background-color: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; margin-top: 10px; }
</style>

<script>
// Ürünleri JavaScript'e aktar
const productCatalog = <?php echo json_encode($products); ?>;
</script>
<script src="../assets/js/proposal_form.js"></script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>