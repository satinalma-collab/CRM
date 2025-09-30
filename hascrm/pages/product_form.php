<?php
$page_title = 'Ürün/Hizmet Formu';
require_once __DIR__ . '/../includes/header.php';

require_login();

$pdo = get_db_connection();

// Formda kullanmak için kategorileri çek
$categories_stmt = $pdo->prepare("SELECT id, name FROM product_categories WHERE organization_id = ? ORDER BY name");
$categories_stmt->execute([$_SESSION['organization_id']]);
$categories = $categories_stmt->fetchAll();

// Varsayılan değerler
$product = ['id' => '', 'name' => '', 'description' => '', 'unit' => '', 'price' => '', 'category_id' => '', 'image_url' => ''];
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
    <form action="<?php echo e($action_url); ?>" method="POST" enctype="multipart/form-data">

        <?php if (!empty($product['id'])): ?>
            <input type="hidden" name="product_id" value="<?php echo e($product['id']); ?>">
        <?php endif; ?>

        <label for="name">Ürün/Hizmet Adı</label>
        <input type="text" id="name" name="name" value="<?php echo e($product['name']); ?>" required>

        <div class="grid">
             <label for="category_id">
                Kategori
                <select id="category_id" name="category_id">
                    <option value="">Kategori Seçin...</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo e($category['id']); ?>" <?php echo ($category['id'] == $product['category_id']) ? 'selected' : ''; ?>>
                            <?php echo e($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label for="image">
                Ürün Fotoğrafı
                <input type="file" id="image" name="image">
            </label>
        </div>

        <?php if (!empty($product['image_url'])): ?>
            <div class="current-image">
                <p>Mevcut Fotoğraf:</p>
                <img src="/<?php echo e($product['image_url']); ?>" alt="<?php echo e($product['name']); ?>" style="max-width: 150px; height: auto;">
            </div>
        <?php endif; ?>

        <label for="description">Açıklama</label>
        <textarea id="description" name="description" rows="4"><?php echo e($product['description']); ?></textarea>

        <div class="grid">
            <label for="unit">Birim</label>
            <input type="text" id="unit" name="unit" value="<?php echo e($product['unit']); ?>" placeholder="adet, kg, saat...">

            <label for="price">Birim Fiyatı</label>
            <input type="number" step="0.01" id="price" name="price" value="<?php echo e($product['price']); ?>" required>

            <label for="currency">
                Para Birimi
                <select id="currency" name="currency">
                    <option value="TRY" <?php echo ($product['currency'] ?? 'TRY') === 'TRY' ? 'selected' : ''; ?>>TRY</option>
                    <option value="USD" <?php echo ($product['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD</option>
                    <option value="EUR" <?php echo ($product['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                </select>
            </label>
        </div>

        <button type="submit">
            <?php echo (isset($_GET['id'])) ? 'Güncelle' : 'Kaydet'; ?>
        </button>
    </form>
</article>

<style>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.page-title { margin-bottom: 0; }
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>