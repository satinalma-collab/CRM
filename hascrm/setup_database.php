<?php

// Hata raporlamayı aç
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>"; // Daha okunaklı çıktı için

// 1. Gerekli dosyaları dahil et
require_once __DIR__ . '/config/config.php';

try {
    // 2. Veritabanı dosyasının bulunacağı dizini kontrol et, yoksa oluştur
    $db_dir = dirname(DB_PATH);
    if (!is_dir($db_dir)) {
        if (mkdir($db_dir, 0755, true)) {
            echo "Veritabanı dizini oluşturuldu: " . htmlspecialchars($db_dir) . "\n";
        } else {
            throw new Exception("Veritabanı dizini oluşturulamadı. Lütfen izinleri kontrol edin.");
        }
    } else {
        echo "Veritabanı dizini zaten mevcut: " . htmlspecialchars($db_dir) . "\n";
    }

    // 3. Veritabanına PDO ile bağlan (dosya yoksa oluşturulur)
    $pdo = new PDO('sqlite:' . DB_PATH);
    // Hata modunu ayarla
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Veritabanı bağlantısı başarılı. Dosya yolu: " . htmlspecialchars(DB_PATH) . "\n";

    // 4. schema.sql dosyasını oku
    $sql_schema = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql_schema === false) {
        throw new Exception("schema.sql dosyası okunamadı.");
    }
    echo "schema.sql dosyası başarıyla okundu.\n";

    // 5. SQL şemasını çalıştırarak tabloları oluştur
    $pdo->exec($sql_schema);
    echo "Veritabanı tabloları başarıyla oluşturuldu!\n";

    echo "\nKurulum tamamlandı. Artık bu dosyayı silebilirsiniz.\n";

} catch (Exception $e) {
    // Hataları yakala ve göster
    die("HATA: " . $e->getMessage());
}

echo "</pre>";

?>