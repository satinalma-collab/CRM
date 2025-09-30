<?php
$page_title = 'Ürün/Hizmet Formu';
require_once __DIR__ . '/../includes/header.php';

require_login();

$pdo = get_db_connection();

// Varsayılan değerler
$product = ['id' => '', 'name' => '', 'description' => '', 'unit' => '', 'price' => ''];
$form_title = 'Yeni Ürün/Hizmet Ekle';
$action_url = '../api/product_handler.php?action=create';

// Düzenleme modunu kontrol et
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND organization_id = ?");
    $stmt->execute([$product_id, $_SESSION['organization_id']]);
    $product_data = $stmt->fetch();

    if (!$product_data) {
        $_SESSION['error_message'] = 'Geçersiz ürün IDsi.';
        header('Location: products.php');
        exit;
    }
    $product = $product_data;
    $form_title = 'Ürün/Hizmet Bilgilerini Düzenle';
    $action_url = '../api/product_handler.php?action=update';
}
?>

<header class="page-header">
    <h1 class="page-title"><?php echo e($form_title); ?></h1>
    <a href="products.php" role="button" class="secondary">Geri Dön</a>
</header>

<article>
    <form action="<?php echo e($action_url); ?>" method="POST">

        <?php if (!empty($product['id'])): ?>
            <input type="hidden" name="product_id" value="<?php echo e($product['id']); ?>">
        <?php endif; ?>

        <label for="name">Ürün/Hizmet Adı</label>
        <input type="text" id="name" name="name" value="<?php echo e($product['name']); ?>" placeholder="Ürün veya hizmetin tam adı" required>

        <label for="description">Açıklama</label>
        <textarea id="description" name="description" rows="4" placeholder="Ürünle ilgili detaylar"><?php echo e($product['description']); ?></textarea>

        <div class="grid">
            <label for="unit">
                Birim
                <input type="text" id="unit" name="unit" value="<?php echo e($product['unit']); ?>" placeholder="adet, kg, saat, m²">
            </label>
            <label for="price">
                Birim Fiyatı (TL)
                <input type="number" step="0.01" id="price" name="price" value="<?php echo e($product['price']); ?>" placeholder="0.00" required>
            </label>
        </div>

        <button type="submit">
            <?php echo (isset($_GET['id'])) ? 'Güncelle' : 'Kaydet'; ?>
        </button>
    </form>
</article>

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
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>