<?php
$page_title = 'Teklif Formu';
require_once __DIR__ . '/../includes/header.php';
require_login();

$pdo = get_db_connection();

// Formda kullanmak için müşterileri ve ürünleri çek
$customers_stmt = $pdo->prepare("SELECT id, name FROM customers WHERE organization_id = ? ORDER BY name");
$customers_stmt->execute([$_SESSION['organization_id']]);
$customers = $customers_stmt->fetchAll();

$products_stmt = $pdo->prepare("SELECT * FROM products WHERE organization_id = ? ORDER BY name");
$products_stmt->execute([$_SESSION['organization_id']]);
$products = $products_stmt->fetchAll();

// Varsayılan değerler (Ekleme modu)
$proposal = ['id' => '', 'title' => '', 'customer_id' => '', 'proposal_date' => date('Y-m-d'), 'valid_until_date' => date('Y-m-d', strtotime('+30 days')), 'currency' => 'TRY', 'delivery_terms' => '', 'payment_terms' => ''];
$proposal_items = [];
$form_title = 'Yeni Teklif Oluştur';
$action = 'create';

// Düzenleme modu
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $proposal_id = $_GET['id'];
    $form_title = 'Teklifi Düzenle';
    $action = 'update';

    // Teklifi ve kalemlerini veritabanından çek
    $stmt = $pdo->prepare("SELECT * FROM proposals WHERE id = ? AND organization_id = ?");
    $stmt->execute([$proposal_id, $_SESSION['organization_id']]);
    $proposal_data = $stmt->fetch();

    if (!$proposal_data) {
        $_SESSION['error_message'] = 'Geçersiz teklif veya bu işlem için yetkiniz yok.';
        header('Location: proposals.php');
        exit;
    }
    $proposal = $proposal_data;

    $items_stmt = $pdo->prepare("SELECT * FROM proposal_items WHERE proposal_id = ? ORDER BY id");
    $items_stmt->execute([$proposal_id]);
    $proposal_items = $items_stmt->fetchAll();
}
?>

<!-- Ürün Seçim Modalı -->
<dialog id="product-modal">
  <article>
    <header>
      <a href="#close" aria-label="Close" class="close"></a>
      Ürün Seçin
    </header>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card" data-product-id="<?php echo e($product['id']); ?>">
                <img src="/<?php echo e($product['image_url'] ?? 'assets/images/placeholder.png'); ?>" alt="<?php echo e($product['name']); ?>">
                <footer><?php echo e($product['name']); ?></footer>
            </div>
        <?php endforeach; ?>
    </div>
  </article>
</dialog>

<header class="page-header">
    <h1 class="page-title"><?php echo e($form_title); ?></h1>
    <a href="proposals.php" role="button" class="secondary">Geri Dön</a>
</header>

<article>
    <form id="proposal-form" action="../api/proposal_handler.php?action=<?php echo $action; ?>" method="POST">
        <input type="hidden" name="proposal_id" value="<?php echo e($proposal['id']); ?>">

        <div class="grid">
            <label for="title">Teklif Başlığı
                <input type="text" id="title" name="title" value="<?php echo e($proposal['title']); ?>" required>
            </label>
        </div>
        <div class="grid">
            <label for="customer_id">Müşteri
                <select id="customer_id" name="customer_id" required>
                    <option value="">Seçin...</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo e($customer['id']); ?>" <?php echo ($customer['id'] == $proposal['customer_id']) ? 'selected' : ''; ?>>
                            <?php echo e($customer['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label for="currency">Para Birimi
                <select id="currency" name="currency">
                    <option value="TRY" <?php echo ($proposal['currency'] == 'TRY') ? 'selected' : ''; ?>>TRY</option>
                    <option value="USD" <?php echo ($proposal['currency'] == 'USD') ? 'selected' : ''; ?>>USD</option>
                    <option value="EUR" <?php echo ($proposal['currency'] == 'EUR') ? 'selected' : ''; ?>>EUR</option>
                </select>
            </label>
        </div>
        <div class="grid">
            <label for="proposal_date">Teklif Tarihi
                <input type="date" id="proposal_date" name="proposal_date" value="<?php echo e($proposal['proposal_date']); ?>" required>
            </label>
            <label for="valid_until_date">Geçerlilik Tarihi
                <input type="date" id="valid_until_date" name="valid_until_date" value="<?php echo e($proposal['valid_until_date']); ?>">
            </label>
        </div>

        <h3 style="margin-top: 2rem;">Teklif Kalemleri</h3>
        <figure>
            <table id="proposal-items-table" role="grid">
                <thead><tr><th>Ürün/Hizmet</th><th style="width: 10%;">Miktar</th><th style="width: 10%;">Birim</th><th style="width: 15%;">Birim Fiyat</th><th style="width: 10%;">İndirim (%)</th><th style="width: 15%;">Satır Toplamı</th><th style="width: 5%;"></th></tr></thead>
                <tbody id="proposal-items-body">
                    <?php foreach ($proposal_items as $item): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="items[product_id][]" class="item-product-id" value="<?php echo e($item['product_id']); ?>">
                                <input type="text" name="items[name][]" class="item-name" value="<?php echo e($item['name']); ?>" required>
                            </td>
                            <td><input type="number" name="items[quantity][]" class="item-quantity" value="<?php echo e($item['quantity']); ?>" step="any" required></td>
                            <td><input type="text" name="items[unit][]" class="item-unit" value="<?php echo e($item['unit']); ?>"></td>
                            <td><input type="number" name="items[unit_price][]" class="item-price" value="<?php echo e($item['unit_price']); ?>" step="any" required></td>
                            <td><input type="number" name="items[discount_percentage][]" class="item-discount" value="<?php echo e($item['discount_percentage']); ?>" step="any"></td>
                            <td class="item-line-total" style="text-align:right;">0.00 TL</td>
                            <td><button type="button" class="remove-item-btn secondary outline" style="padding: 0.25rem 0.5rem;">X</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </figure>
        <button type="button" data-target="product-modal" onClick="toggleModal(event)">Görselden Ürün Ekle</button>

        <div class="grid" style="margin-top: 2rem;">
            <label for="delivery_terms">Teslimat Şartları
                <textarea id="delivery_terms" name="delivery_terms" rows="3"><?php echo e($proposal['delivery_terms']); ?></textarea>
            </label>
            <label for="payment_terms">Ödeme Şartları
                <textarea id="payment_terms" name="payment_terms" rows="3"><?php echo e($proposal['payment_terms']); ?></textarea>
            </label>
        </div>

        <div class="grid" style="margin-top: 2rem;">
            <div style="flex-grow: 2;"></div>
            <div style="min-width: 300px;"><table class="totals-table"><tbody>
                <tr><td>Ara Toplam:</td><td id="subtotal" style="text-align:right;">0.00 TL</td></tr>
                <tr><td>Genel İndirim:</td><td id="total-discount" style="text-align:right;">0.00 TL</td></tr>
                <tr><td><strong>Genel Toplam:</strong></td><td id="grand-total" style="text-align:right;"><strong>0.00 TL</strong></td></tr>
            </tbody></table></div>
        </div>

        <button type="submit" style="margin-top: 1rem;"><?php echo $is_update ? 'Güncelle' : 'Kaydet'; ?></button>
    </form>
</article>

<template id="item-template">
    <tr>
        <td>
            <input type="hidden" name="items[product_id][]" class="item-product-id">
            <input type="text" name="items[name][]" class="item-name" required>
        </td>
        <td><input type="number" name="items[quantity][]" class="item-quantity" value="1" step="any" required></td>
        <td><input type="text" name="items[unit][]" class="item-unit"></td>
        <td><input type="number" name="items[unit_price][]" class="item-price" value="0.00" step="any" required></td>
        <td><input type="number" name="items[discount_percentage][]" class="item-discount" value="0" step="any"></td>
        <td class="item-line-total" style="text-align:right;">0.00 TL</td>
        <td><button type="button" class="remove-item-btn secondary outline" style="padding: 0.25rem 0.5rem;">X</button></td>
    </tr>
</template>

<style>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.page-title { margin-bottom: 0; }
.totals-table td { padding: 0.5rem; }
/* Modal stilleri */
.product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; }
.product-card { border: 1px solid var(--pico-muted-border-color); border-radius: var(--pico-border-radius); text-align: center; cursor: pointer; transition: transform 0.2s; }
.product-card:hover { transform: scale(1.05); border-color: var(--pico-primary); }
.product-card img { width: 100%; height: 100px; object-fit: cover; border-top-left-radius: var(--pico-border-radius); border-top-right-radius: var(--pico-border-radius); }
.product-card footer { padding: 0.5rem; font-size: 0.9em; }
</style>

<script>
const productCatalog = <?php echo json_encode($products); ?>;
</script>
<script src="../assets/js/proposal_form.js"></script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>