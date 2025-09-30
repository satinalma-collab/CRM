<?php

// Hata raporlamayı aç
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>"; // Daha okunaklı çıktı için

// Gerekli dosyaları dahil et
require_once __DIR__ . '/includes/db_connection.php';

try {
    // Veritabanı bağlantısını al
    $pdo = get_db_connection();

    // 1. Test Organizasyonu Oluştur
    $org_name = 'Test Organizasyonu';
    $stmt = $pdo->prepare("INSERT INTO organizations (name) VALUES (?)");
    $stmt->execute([$org_name]);
    $organization_id = $pdo->lastInsertId();
    echo "Organizasyon oluşturuldu: '" . htmlspecialchars($org_name) . "' (ID: $organization_id)\n";

    // 2. Test Kullanıcısı Oluştur
    $user_name = 'Test Kullanıcısı';
    $user_email = 'test@example.com';
    $user_password = 'password'; // Test için basit bir şifre
    $password_hash = password_hash($user_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO users (organization_id, name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$organization_id, $user_name, $user_email, $password_hash, 'admin']);
    $user_id = $pdo->lastInsertId();
    echo "Kullanıcı oluşturuldu: '" . htmlspecialchars($user_name) . "' (ID: $user_id)\n";

    echo "\n--------------------------------------------------\n";
    echo "VERİTABANI BAŞARIYLA DOLDURULDU!\n";
    echo "Aşağıdaki bilgilerle giriş yapabilirsiniz:\n";
    echo "E-posta: " . htmlspecialchars($user_email) . "\n";
    echo "Şifre: " . htmlspecialchars($user_password) . "\n";
    echo "--------------------------------------------------\n";

    echo "\nKurulum tamamlandı. Güvenlik için bu dosyayı silebilirsiniz.\n";

} catch (Exception $e) {
    // Hataları yakala ve göster
    die("HATA: " . $e->getMessage());
}

echo "</pre>";

?>