<?php
session_start();

// Tüm session değişkenlerini temizle
$_SESSION = [];

// Session cookie'sini silmek için, cookie'nin süresini geçmiş bir zamana ayarla
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Son olarak, session'ı yok et
session_destroy();

// Kullanıcıyı giriş sayfasına yönlendir
header("Location: login.php");
exit;
?>