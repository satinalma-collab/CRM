<?php

// Bu dosya doğrudan çağrılmamalıdır, bu yüzden config.php'nin dahil edilip edilmediğini kontrol edelim.
if (!defined('DB_PATH')) {
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Veritabanına bir PDO bağlantısı oluşturur ve döndürür.
 *
 * @return PDO|null Başarılı olursa PDO nesnesini, başarısız olursa null döner.
 */
function get_db_connection() {
    static $pdo = null; // Bağlantıyı tekrar tekrar oluşturmamak için static değişken kullan

    if ($pdo === null) {
        try {
            // config.php'de tanımlanan yolla yeni bir PDO nesnesi oluştur
            $pdo = new PDO('sqlite:' . DB_PATH);

            // Hata raporlama modunu istisnaları fırlatacak şekilde ayarla
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Fetch modunu varsayılan olarak associatives (ilişkisel dizi) yap
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Geliştirme ortamında hatayı göster, production'da logla
            // Basitlik adına şimdilik doğrudan hatayı basıyoruz.
            error_log('Veritabanı Bağlantı Hatası: ' . $e->getMessage());
            die('Veritabanına bağlanılamıyor. Lütfen daha sonra tekrar deneyin.');
        }
    }

    return $pdo;
}

// Betik ilk dahil edildiğinde test amaçlı bir bağlantı denemesi yapılabilir
// $test_conn = get_db_connection();
// if ($test_conn) {
//     echo "Veritabanı bağlantısı başarılı.";
// }
?>