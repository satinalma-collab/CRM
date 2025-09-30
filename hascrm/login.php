<?php
$page_title = 'Giriş Yap';
require_once __DIR__ . '/includes/header.php';

// Eğer kullanıcı zaten giriş yapmışsa, onu ana sayfaya veya dashboard'a yönlendir
if (is_logged_in()) {
    // Şimdilik ana dizine yönlendirelim, ileride dashboard.php olabilir
    header('Location: index.php');
    exit;
}
?>

<div class="login-form-container">
    <h2>Sisteme Giriş Yap</h2>

    <?php
    // Olası hata mesajlarını göstermek için
    if (isset($_SESSION['error_message'])) {
        echo '<p class="error-message">' . e($_SESSION['error_message']) . '</p>';
        // Mesajı gösterdikten sonra session'dan sil
        unset($_SESSION['error_message']);
    }
    ?>

    <form action="handle_login.php" method="POST">
        <label for="email">E-posta Adresi:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Şifre:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Giriş Yap</button>
    </form>
</div>

<?php
// Stil için CSS'e küçük bir ekleme yapalım
// Genellikle bu tür stiller ana CSS dosyasında olur, ama burada basitlik için ekliyorum.
?>
<style>
.login-form-container {
    max-width: 400px;
    margin: 40px auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
}
.error-message {
    color: #D8000C;
    background-color: #FFD2D2;
    border: 1px solid #D8000C;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
}
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
?>