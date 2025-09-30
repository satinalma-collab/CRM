<?php
$page_title = 'Giriş Yap';
// login.php ana dizinde olduğu için, header'ı doğrudan çağırabiliriz.
require_once __DIR__ . '/includes/header.php';

// Eğer kullanıcı zaten giriş yapmışsa, onu ana sayfaya veya dashboard'a yönlendir
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}
?>

<div class="grid">
    <div></div>
    <article>
        <h2 class="text-center">Sisteme Giriş Yap</h2>

        <?php
        // Olası hata mesajlarını göstermek için
        if (isset($_SESSION['error_message'])) {
            echo '<p><mark>' . e($_SESSION['error_message']) . '</mark></p>';
            // Mesajı gösterdikten sonra session'dan sil
            unset($_SESSION['error_message']);
        }
        ?>

        <form action="handle_login.php" method="POST">
            <label for="email">E-posta Adresi</label>
            <input type="email" id="email" name="email" placeholder="E-posta adresiniz" required>

            <label for="password">Şifre</label>
            <input type="password" id="password" name="password" placeholder="Şifreniz" required>

            <button type="submit">Giriş Yap</button>
        </form>
    </article>
    <div></div>
</div>

<?php
// footer.php ana dizinde olduğu için, doğrudan çağırabiliriz.
require_once __DIR__ . '/includes/footer.php';
?>