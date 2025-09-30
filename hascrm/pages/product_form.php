<?php
$page_title = 'Ürün/Hizmet Formu';
require_once __DIR__ . '/../includes/header.php';

// Bu sayfaya sadece giriş yapmış kullanıcılar erişebilir
require_login();

// Veritabanı bağlantısını al
$pdo = get_db_connection();

// Varsayılan değerler (Ekleme modu için)
$product = [
    'id' => '',
    'name' => '',
    'description' => '',
    'unit' => '',
    'price' => ''
];
$form_title = 'Yeni Ürün/Hizmet Ekle';
$action_url = '../api/product_handler.php?action=create';

// Düzenleme modunu kontrol et (URL'de id var mı?)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = $_GET['id'];

    // Ürünün mevcut organizasyona ait olduğunu doğrula
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND organization_id = ?");
    $stmt->execute([$product_id, $_SESSION['organization_id']]);
    $product = $stmt->fetch();

    // Ürün bulunamazsa veya başka organizasyona aitse, listeye yönlendir
    if (!$product) {
        $_SESSION['error_message'] = 'Geçersiz ürün IDsi.';
        header('Location: products.php');
        exit;
    }

    $form_title = 'Ürün/Hizmet Bilgilerini Düzenle';
    $action_url = '../api/product_handler.php?action=update';
}
?>

<div class="page-header">
    <h1><?php echo e($form_title); ?></h1>
    <a href="products.php" class="button-secondary">Geri Dön</a>
</div>

<form action="<?php echo e($action_url); ?>" method="POST">

    <?php // Düzenleme modunda ürün ID'sini gizli olarak gönder ?>
    <?php if (!empty($product['id'])): ?>
        <input type="hidden" name="product_id" value="<?php echo e($product['id']); ?>">
    <?php endif; ?>

    <label for="name">Ürün/Hizmet Adı:</label>
    <input type="text" id="name" name="name" value="<?php echo e($product['name']); ?>" required>

    <label for="description">Açıklama:</label>
    <textarea id="description" name="description" rows="4"><?php echo e($product['description']); ?></textarea>

    <label for="unit">Birim (örn: adet, kg, saat, m²):</label>
    <input type="text" id="unit" name="unit" value="<?php echo e($product['unit']); ?>">

    <label for="price">Birim Fiyatı (TL):</label>
    <input type="number" step="0.01" id="price" name="price" value="<?php echo e($product['price']); ?>" required>

    <button type="submit">
        <?php echo (isset($_GET['id'])) ? 'Güncelle' : 'Kaydet'; ?>
    </button>
</form>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>