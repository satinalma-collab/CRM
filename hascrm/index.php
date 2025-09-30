<?php
$page_title = 'Ana Sayfa';
require_once __DIR__ . '/includes/header.php';

// Kullanıcı giriş yapmamışsa, login sayfasına yönlendir
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
?>

<div class="dashboard-welcome">
    <h1>Hoş Geldiniz, <?php echo e($_SESSION['user_name']); ?>!</h1>
    <p>HasCRM sistemine başarıyla giriş yaptınız.</p>
    <p>Burada, son teklifleriniz, müşteri aktiviteleriniz ve diğer önemli bilgilere genel bir bakış bulacaksınız.</p>

    <div class="quick-actions">
        <a href="pages/proposal_create.php" class="button">Yeni Teklif Oluştur</a>
        <a href="pages/customers.php" class="button">Müşterileri Görüntüle</a>
    </div>
</div>

<style>
.dashboard-welcome {
    padding: 40px;
    text-align: center;
    background-color: #e9f7ef;
    border: 1px solid #a8dfc1;
    border-radius: 8px;
}
.quick-actions {
    margin-top: 20px;
}
.quick-actions .button {
    display: inline-block;
    background-color: #28a745;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    margin: 0 10px;
    font-weight: bold;
}
.quick-actions .button:hover {
    background-color: #218838;
}
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
?>