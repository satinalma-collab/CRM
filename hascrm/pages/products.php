<?php
$page_title = 'Ürünler ve Hizmetler';
require_once __DIR__ . '/../includes/header.php';

require_login();

$pdo = get_db_connection();

// Arama terimini al
$search_term = $_GET['search'] ?? '';

// SQL sorgusunu kategori adını da içerecek şekilde JOIN ile güncelle
$sql = "SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN product_categories c ON p.category_id = c.id
        WHERE p.organization_id = ?";
$params = [$_SESSION['organization_id']];

if (!empty($search_term)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%" . $search_term . "%";
    $params[] = "%" . $search_term . "%";
}
$sql .= " ORDER BY p.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<header class="page-header">
    <h1 class="page-title">Ürün ve Hizmet Yönetimi</h1>
    <a href="product_form.php" role="button">Yeni Ürün/Hizmet Ekle</a>
</header>

<article class="search-bar">
    <form action="products.php" method="GET">
        <div class="grid">
            <input type="search" id="search" name="search" placeholder="Ürün adı veya açıklamasına göre ara..." value="<?php echo e($search_term); ?>">
            <button type="submit">Ara</button>
        </div>
    </form>
</article>

<figure>
    <table>
        <thead>
            <tr>
                <th scope="col" style="width: 5%;"></th>
                <th scope="col">Ürün/Hizmet Adı</th>
                <th scope="col">Kategori</th>
                <th scope="col">Fiyat</th>
                <th scope="col">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;">
                        <?php echo empty($search_term) ? 'Henüz hiç ürün veya hizmet eklenmemiş.' : 'Aramanızla eşleşen ürün/hizmet bulunamadı.'; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <?php if(!empty($product['image_url'])): ?>
                                <img src="/<?php echo e($product['image_url']); ?>" alt="<?php echo e($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo e($product['name']); ?></strong><br>
                            <small><?php echo e(substr($product['description'] ?? '', 0, 70) . '...'); ?></small>
                        </td>
                        <td><?php echo e($product['category_name'] ?? '---'); ?></td>
                        <td><?php echo e(number_format($product['price'], 2, ',', '.')); ?> <?php echo e($product['currency']); ?></td>
                        <td>
                            <div class="grid" style="--grid-spacing: 0.5rem; min-width: 160px;">
                                <a href="product_form.php?id=<?php echo e($product['id']); ?>" role="button" class="secondary outline">Düzenle</a>
                                <a href="../api/product_handler.php?action=delete&id=<?php echo e($product['id']); ?>" role="button" class="contrast" onclick="return confirm('Bu ürünü silmek istediğinizden emin misiniz?');">Sil</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</figure>

<style>
/* Sayfa başlığı ve butonunu yan yana getirmek için */
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.page-title { margin-bottom: 0; }
.search-bar { margin-bottom: 1rem; padding: 1rem; }
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>