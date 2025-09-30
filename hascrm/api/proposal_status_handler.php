<?php
// Gerekli çekirdek dosyaları dahil et
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

// Parametreleri al ve doğrula
$token = $_GET['token'] ?? '';
$new_status = $_GET['status'] ?? '';
$allowed_statuses = ['accepted', 'rejected'];

if (empty($token) || !in_array($new_status, $allowed_statuses)) {
    die('Geçersiz istek veya parametreler.');
}

$pdo = get_db_connection();

try {
    // Teklifin varlığını ve mevcut durumunu kontrol et
    $stmt = $pdo->prepare("SELECT id, status FROM proposals WHERE share_token = ?");
    $stmt->execute([$token]);
    $proposal = $stmt->fetch();

    if (!$proposal) {
        die('Geçersiz teklif bağlantısı.');
    }

    // Teklifin durumu zaten kabul edilmiş veya reddedilmişse, tekrar işlem yapma
    if (in_array($proposal['status'], ['accepted', 'rejected'])) {
        $message_title = 'Bilgi';
        $message_body = 'Bu teklif daha önce ' . ($proposal['status'] === 'accepted' ? 'kabul edilmiş' : 'reddedilmiş') . '. Durum değiştirilemez.';
    } else {
        // Durumu güncelle
        $update_stmt = $pdo->prepare("UPDATE proposals SET status = ? WHERE id = ?");
        $update_stmt->execute([$new_status, $proposal['id']]);

        $message_title = 'Teşekkür Ederiz!';
        $message_body = 'Teklifle ilgili kararınız başarıyla sistemimize kaydedilmiştir.';
    }

} catch (PDOException $e) {
    error_log('Teklif Durum Güncelleme Hatası: ' . $e->getMessage());
    die('Sistemsel bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
}

// Kullanıcıya basit bir onay sayfası göster
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Teklif Durumu</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f7f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .message-box { text-align: center; padding: 40px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        p { color: #666; font-size: 1.1em; }
    </style>
</head>
<body>
    <div class="message-box">
        <h1><?php echo e($message_title); ?></h1>
        <p><?php echo e($message_body); ?></p>
    </div>
</body>
</html>