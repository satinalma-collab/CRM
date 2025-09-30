<?php
$page_title = 'Müşteriler';
require_once __DIR__ . '/../includes/header.php';

// Bu sayfaya sadece giriş yapmış kullanıcılar erişebilir
require_login();

// Veritabanı bağlantısını al
$pdo = get_db_connection();

// Mevcut organizasyonun müşterilerini getir
$stmt = $pdo->prepare("SELECT * FROM customers WHERE organization_id = ? ORDER BY name ASC");
$stmt->execute([$_SESSION['organization_id']]);
$customers = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Müşteri Yönetimi</h1>
    <a href="customer_form.php" class="button-primary">Yeni Müşteri Ekle</a>
</div>

<table>
    <thead>
        <tr>
            <th>Müşteri Adı</th>
            <th>E-posta</th>
            <th>Telefon</th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($customers)): ?>
            <tr>
                <td colspan="4" style="text-align:center;">Henüz hiç müşteri eklenmemiş.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?php echo e($customer['name']); ?></td>
                    <td><?php echo e($customer['email']); ?></td>
                    <td><?php echo e($customer['phone']); ?></td>
                    <td class="actions">
                        <a href="customer_form.php?id=<?php echo e($customer['id']); ?>" class="button-edit">Düzenle</a>
                        <a href="../api/customer_handler.php?action=delete&id=<?php echo e($customer['id']); ?>" class="button-delete" onclick="return confirm('Bu müşteriyi silmek istediğinizden emin misiniz?');">Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.button-primary {
    background-color: #007bff;
    color: white;
    padding: 10px 15px;
    text-decoration: none;
    border-radius: 5px;
}
.actions a {
    margin-right: 10px;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    color: white;
}
.button-edit {
    background-color: #ffc107;
}
.button-delete {
    background-color: #dc3545;
}
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>