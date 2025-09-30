<?php
$page_title = 'Müşteri Formu';
require_once __DIR__ . '/../includes/header.php';

require_login();

$pdo = get_db_connection();

// Varsayılan değerler
$customer = ['id' => '', 'name' => '', 'email' => '', 'phone' => '', 'address' => ''];
$form_title = 'Yeni Müşteri Ekle';
$action_url = '../api/customer_handler.php?action=create';

// Düzenleme modunu kontrol et
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $customer_id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND organization_id = ?");
    $stmt->execute([$customer_id, $_SESSION['organization_id']]);
    $customer_data = $stmt->fetch();

    if (!$customer_data) {
        $_SESSION['error_message'] = 'Geçersiz müşteri IDsi.';
        header('Location: customers.php');
        exit;
    }
    $customer = $customer_data;
    $form_title = 'Müşteri Bilgilerini Düzenle';
    $action_url = '../api/customer_handler.php?action=update';
}
?>

<header class="page-header">
    <h1 class="page-title"><?php echo e($form_title); ?></h1>
    <a href="customers.php" role="button" class="secondary">Geri Dön</a>
</header>

<article>
    <form action="<?php echo e($action_url); ?>" method="POST">

        <?php if (!empty($customer['id'])): ?>
            <input type="hidden" name="customer_id" value="<?php echo e($customer['id']); ?>">
        <?php endif; ?>

        <label for="name">Müşteri Adı</label>
        <input type="text" id="name" name="name" value="<?php echo e($customer['name']); ?>" placeholder="Müşteri tam adı" required>

        <div class="grid">
            <label for="email">
                E-posta Adresi
                <input type="email" id="email" name="email" value="<?php echo e($customer['email']); ?>" placeholder="musteri@ornek.com">
            </label>
            <label for="phone">
                Telefon Numarası
                <input type="tel" id="phone" name="phone" value="<?php echo e($customer['phone']); ?>" placeholder="+90 555 123 4567">
            </label>
        </div>

        <label for="address">Adres</label>
        <textarea id="address" name="address" rows="4" placeholder="Müşteri adresi"><?php echo e($customer['address']); ?></textarea>

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