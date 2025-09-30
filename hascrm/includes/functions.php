<?php

/**
 * Kullanıcının giriş yapıp yapmadığını kontrol eder.
 *
 * @return bool Kullanıcı giriş yapmışsa true, aksi takdirde false döner.
 */
function is_logged_in() {
    // Session'da user_id'nin varlığını ve boş olmadığını kontrol et
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Kullanıcı giriş yapmamışsa, onu giriş sayfasına yönlendirir.
 * Bu fonksiyon, sadece yetkili kullanıcıların erişebileceği sayfaların başına konulmalıdır.
 */
function require_login() {
    if (!is_logged_in()) {
        // Kullanıcıyı giriş sayfasına yönlendir
        // Not: Yönlendirme yolunu projenin yapısına göre ayarlamak gerekebilir.
        header('Location: /login.php');
        exit;
    }
}

/**
 * Güvenlik için HTML çıktısını temizler.
 *
 * @param string|null $string Temizlenecek metin.
 * @return string Temizlenmiş metin.
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

?>