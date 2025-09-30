<?php
session_start();

// Gerekli dosyaları dahil et
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db_connection.php';
require_once __DIR__ . '/includes/functions.php';

// Sadece POST metodu ile gelen istekleri kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // POST değilse, ana sayfaya yönlendir
    header('Location: index.php');
    exit;
}

// Formdan gelen verileri al
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// E-posta veya şifre boşsa, hata mesajıyla geri yönlendir
if (empty($email) || empty($password)) {
    $_SESSION['error_message'] = 'Lütfen e-posta ve şifrenizi girin.';
    header('Location: login.php');
    exit;
}

try {
    // Veritabanı bağlantısını al
    $pdo = get_db_connection();

    // Kullanıcıyı e-posta adresine göre bul
    $stmt = $pdo->prepare("SELECT id, organization_id, name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Kullanıcı bulunduysa ve şifre doğruysa
    if ($user && password_verify($password, $user['password_hash'])) {
        // Güvenlik için session ID'sini yenile
        session_regenerate_id(true);

        // Kullanıcı bilgilerini session'a kaydet
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['organization_id'] = $user['organization_id'];

        // Başarılı giriş sonrası ana sayfaya veya dashboard'a yönlendir
        // Şimdilik projenin ana dizininde bir index.php oluşturalım.
        header('Location: index.php');
        exit;
    } else {
        // Kullanıcı bulunamazsa veya şifre yanlışsa
        $_SESSION['error_message'] = 'Geçersiz e-posta veya şifre.';
        header('Location: login.php');
        exit;
    }

} catch (PDOException $e) {
    // Veritabanı hatası olursa
    error_log('Login Hatası: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Bir sistem hatası oluştu. Lütfen daha sonra tekrar deneyin.';
    header('Location: login.php');
    exit;
}
?>