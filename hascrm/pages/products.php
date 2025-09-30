<?php
$page_title = 'Ürünler ve Hizmetler';
require_once __DIR__ . '/../includes/header.php';

// Bu sayfaya sadece giriş yapmış kullanıcılar erişebilir
require_login();

// Veritabanı bağlantısını al
$pdo = get_db_connection();

// Mevcut organizasyonun ürünlerini getir
$stmt = $pdo->prepare("SELECT * FROM products WHERE organization_id = ? ORDER BY name ASC");
$stmt->execute([$_SESSION['organization_id']]);
$products = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Ürün ve Hizmet Yönetimi</h1>
    <a href="product_form.php" class="button-primary">Yeni Ürün/Hizmet Ekle</a>
</div>

<table>
    <thead>
        <tr>
            <th>Ürün/Hizmet Adı</th>
            <th>Açıklama</th>
            <th>Birim</th>
            <th>Fiyat</th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($products)): ?>
            <tr>
                <td colspan="5" style="text-align:center;">Henüz hiç ürün veya hizmet eklenmemiş.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo e($product['name']); ?></td>
                    <td><?php echo e(substr($product['description'] ?? '', 0, 50) . '...'); ?></td>
                    <td><?php echo e($product['unit']); ?></td>
                    <td><?php echo e(number_format($product['price'], 2, ',', '.')); ?> TL</td>
                    <td class="actions">
                        <a href="product_form.php?id=<?php echo e($product['id']); ?>" class="button-edit">Düzenle</a>
                        <a href="../api/product_handler.php?action=delete&id=<?php echo e($product['id']); ?>" class="button-delete" onclick="return confirm('Bu ürünü silmek istediğinizden emin misiniz?');">Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>