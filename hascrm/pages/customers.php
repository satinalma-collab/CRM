<?php
$page_title = 'Müşteriler';
require_once __DIR__ . '/../includes/header.php';

require_login();

$pdo = get_db_connection();

// Arama terimini al
$search_term = $_GET['search'] ?? '';

// SQL sorgusunu arama terimine göre dinamik olarak oluştur
$sql = "SELECT * FROM customers WHERE organization_id = ?";
$params = [$_SESSION['organization_id']];

if (!empty($search_term)) {
    // Arama terimi varsa, WHERE koşuluna ek yap
    $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%" . $search_term . "%";
    $params[] = "%" . $search_term . "%";
    $params[] = "%" . $search_term . "%";
}
$sql .= " ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<header class="page-header">
    <h1 class="page-title">Müşteri Yönetimi</h1>
    <a href="customer_form.php" role="button">Yeni Müşteri Ekle</a>
</header>

<article class="search-bar">
    <form action="customers.php" method="GET">
        <div class="grid">
            <input type="search" id="search" name="search" placeholder="Müşteri adı, e-posta veya telefona göre ara..." value="<?php echo e($search_term); ?>">
            <button type="submit">Ara</button>
        </div>
    </form>
</article>

<figure>
    <table>
        <thead>
            <tr>
                <th scope="col">Müşteri Adı</th>
                <th scope="col">E-posta</th>
                <th scope="col">Telefon</th>
                <th scope="col">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="4" style="text-align:center;">
                        <?php echo empty($search_term) ? 'Henüz hiç müşteri eklenmemiş.' : 'Aramanızla eşleşen müşteri bulunamadı.'; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo e($customer['name']); ?></td>
                        <td><?php echo e($customer['email']); ?></td>
                        <td><?php echo e($customer['phone']); ?></td>
                        <td>
                            <div class="grid" style="--grid-spacing: 0.5rem; min-width: 160px;">
                                <a href="customer_form.php?id=<?php echo e($customer['id']); ?>" role="button" class="secondary outline">Düzenle</a>
                                <a href="../api/customer_handler.php?action=delete&id=<?php echo e($customer['id']); ?>" role="button" class="contrast" onclick="return confirm('Bu müşteriyi silmek istediğinizden emin misiniz?');">Sil</a>
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
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}
.page-title {
    margin-bottom: 0;
}
.search-bar {
    margin-bottom: 1rem;
    padding: 1rem;
}
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>