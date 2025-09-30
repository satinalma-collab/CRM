<?php

// Hata raporlamayı aç
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>"; // Daha okunaklı çıktı için

// Gerekli dosyaları dahil et
require_once __DIR__ . '/includes/db_connection.php';

try {
    $pdo = get_db_connection();

    // --- KONTROL ---
    // Betiğin daha önce çalışıp çalışmadığını kontrol et
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $check_stmt->execute(['test@example.com']);
    if ($check_stmt->fetchColumn() > 0) {
        echo "BİLGİ: Test kullanıcısı (test@example.com) zaten mevcut.\n";
        echo "Veritabanı doldurma işlemi atlandı.\n";
        echo "Veritabanını sıfırlamak isterseniz, önce 'database/hascrm.sqlite' dosyasını silip ardından 'setup_database.php' betiğini çalıştırabilirsiniz.\n";
        exit;
    }

    // --- VERİ EKLEME ---

    // 1. Test Organizasyonu Oluştur
    $org_name = 'Monett Home';
    $org_logo = '/assets/images/logo_placeholder.png'; // Örnek logo yolu
    $stmt = $pdo->prepare("INSERT INTO organizations (name, logo_url) VALUES (?, ?)");
    $stmt->execute([$org_name, $org_logo]);
    $organization_id = $pdo->lastInsertId();
    echo "Organizasyon oluşturuldu: '" . htmlspecialchars($org_name) . "' (ID: $organization_id)\n";

    // 2. Test Kullanıcısı Oluştur
    $user_name = 'Mümin Vatansever';
    $user_email = 'test@example.com';
    $user_password = 'password';
    $password_hash = password_hash($user_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO users (organization_id, name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$organization_id, $user_name, $user_email, $password_hash, 'admin']);
    $user_id = $pdo->lastInsertId();
    echo "Kullanıcı oluşturuldu: '" . htmlspecialchars($user_name) . "' (ID: $user_id)\n";

    // 3. Örnek Ürün Kategorileri Oluştur
    $categories = ['Koltuk Takımları', 'Yemek Odası', 'Yatak Odası', 'Aksesuarlar'];
    $cat_stmt = $pdo->prepare("INSERT INTO product_categories (organization_id, name) VALUES (?, ?)");
    foreach ($categories as $category) {
        $cat_stmt->execute([$organization_id, $category]);
        echo "Kategori oluşturuldu: '" . htmlspecialchars($category) . "'\n";
    }

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