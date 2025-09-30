<?php
$page_title = 'Müşteri Formu';
require_once __DIR__ . '/../includes/header.php';

// Bu sayfaya sadece giriş yapmış kullanıcılar erişebilir
require_login();

// Veritabanı bağlantısını al
$pdo = get_db_connection();

// Varsayılan değerler (Ekleme modu için)
$customer = [
    'id' => '',
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => ''
];
$form_title = 'Yeni Müşteri Ekle';
$action_url = '../api/customer_handler.php?action=create';

// Düzenleme modunu kontrol et (URL'de id var mı?)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $customer_id = $_GET['id'];

    // Müşterinin mevcut organizasyona ait olduğunu doğrula
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND organization_id = ?");
    $stmt->execute([$customer_id, $_SESSION['organization_id']]);
    $customer = $stmt->fetch();

    // Müşteri bulunamazsa veya başka organizasyona aitse, listeye yönlendir
    if (!$customer) {
        $_SESSION['error_message'] = 'Geçersiz müşteri IDsi.';
        header('Location: customers.php');
        exit;
    }

    $form_title = 'Müşteri Bilgilerini Düzenle';
    $action_url = '../api/customer_handler.php?action=update';
}
?>

<div class="page-header">
    <h1><?php echo e($form_title); ?></h1>
    <a href="customers.php" class="button-secondary">Geri Dön</a>
</div>

<form action="<?php echo e($action_url); ?>" method="POST">

    <?php // Düzenleme modunda müşteri ID'sini gizli olarak gönder ?>
    <?php if (!empty($customer['id'])): ?>
        <input type="hidden" name="customer_id" value="<?php echo e($customer['id']); ?>">
    <?php endif; ?>

    <label for="name">Müşteri Adı:</label>
    <input type="text" id="name" name="name" value="<?php echo e($customer['name']); ?>" required>

    <label for="email">E-posta Adresi:</label>
    <input type="email" id="email" name="email" value="<?php echo e($customer['email']); ?>">

    <label for="phone">Telefon Numarası:</label>
    <input type="text" id="phone" name="phone" value="<?php echo e($customer['phone']); ?>">

    <label for="address">Adres:</label>
    <textarea id="address" name="address" rows="4"><?php echo e($customer['address']); ?></textarea>

    <button type="submit">
        <?php echo (isset($_GET['id'])) ? 'Güncelle' : 'Kaydet'; ?>
    </button>
</form>

<style>
.button-secondary {
    background-color: #6c757d;
    color: white;
    padding: 10px 15px;
    text-decoration: none;
    border-radius: 5px;
}
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>