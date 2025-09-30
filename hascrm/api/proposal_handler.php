<?php
session_start();

// Gerekli çekirdek dosyaları dahil et
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pdo = get_db_connection();
$action = $_GET['action'] ?? '';

// Sadece POST isteklerini ve geçerli eylemleri kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($action, ['create', 'update', 'delete'])) {
    $_SESSION['error_message'] = 'Geçersiz istek.';
    header('Location: ../pages/proposals.php');
    exit;
}

// Silme işlemi diğerlerinden farklıdır, onu ayrı ele alalım
if ($action === 'delete') {
    handle_delete($pdo);
    exit;
}


// --- CREATE ve UPDATE İŞLEMLERİ ---

// Formdan gelen verileri al
$title = $_POST['title'] ?? 'İsimsiz Teklif';
$customer_id = $_POST['customer_id'] ?? 0;
$proposal_date = $_POST['proposal_date'] ?? date('Y-m-d');
$valid_until_date = $_POST['valid_until_date'] ?? null;
$items = $_POST['items'] ?? [];

// Temel doğrulama
if (empty($title) || empty($customer_id) || empty($items['name'])) {
    $_SESSION['error_message'] = 'Teklif başlığı, müşteri ve en az bir kalem zorunludur.';
    header('Location: ../pages/proposal_create.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Sunucu tarafında toplamları hesapla
    $total_amount = 0;
    foreach ($items['name'] as $key => $name) {
        $quantity = (float)($items['quantity'][$key] ?? 0);
        $unit_price = (float)($items['unit_price'][$key] ?? 0);
        $discount = (float)($items['discount_percentage'][$key] ?? 0);
        $line_total = ($quantity * $unit_price) * (1 - $discount / 100);
        $total_amount += $line_total;
    }

    // Benzersiz paylaşım token'ı oluştur
    $share_token = bin2hex(random_bytes(32));

    // Ana teklifi veritabanına ekle
    $stmt = $pdo->prepare(
        "INSERT INTO proposals (organization_id, customer_id, user_id, title, proposal_date, valid_until_date, total_amount, status, share_token)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', ?)"
    );
    $stmt->execute([
        $_SESSION['organization_id'],
        $customer_id,
        $_SESSION['user_id'],
        $title,
        $proposal_date,
        $valid_until_date,
        $total_amount,
        $share_token
    ]);
    $proposal_id = $pdo->lastInsertId();

    // Teklif kalemlerini ekle
    $item_stmt = $pdo->prepare(
        "INSERT INTO proposal_items (proposal_id, product_id, name, description, quantity, unit, unit_price, discount_percentage, line_total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($items['name'] as $key => $name) {
        $product_id = !empty($items['product_id'][$key]) ? (int)$items['product_id'][$key] : null;
        $quantity = (float)($items['quantity'][$key] ?? 0);
        $unit = $items['unit'][$key] ?? '';
        $unit_price = (float)($items['unit_price'][$key] ?? 0);
        $discount = (float)($items['discount_percentage'][$key] ?? 0);
        $line_total = ($quantity * $unit_price) * (1 - $discount / 100);

        $item_stmt->execute([
            $proposal_id, $product_id, $name, null, $quantity, $unit, $unit_price, $discount, $line_total
        ]);
    }

    // Her şey yolundaysa, işlemi onayla
    $pdo->commit();

    $_SESSION['success_message'] = 'Teklif başarıyla oluşturuldu.';
    header('Location: ../pages/proposals.php');
    exit;

} catch (Exception $e) {
    // Bir hata olursa, tüm işlemleri geri al
    $pdo->rollBack();
    error_log("Teklif Oluşturma Hatası: " . $e->getMessage());
    $_SESSION['error_message'] = 'Teklif oluşturulurken bir hata oluştu: ' . $e->getMessage();
    header('Location: ../pages/proposal_create.php');
    exit;
}


/**
 * Teklif silme işlemini yönetir.
 */
function handle_delete($pdo) {
    $id = $_GET['id'] ?? 0;
    if (empty($id)) {
        $_SESSION['error_message'] = 'Geçersiz teklif IDsi.';
        header('Location: ../pages/proposals.php');
        exit;
    }

    // Güvenlik: Kullanıcının bu teklifi silme yetkisi var mı?
    $stmt = $pdo->prepare("SELECT id FROM proposals WHERE id = ? AND organization_id = ?");
    $stmt->execute([$id, $_SESSION['organization_id']]);
    if ($stmt->fetchColumn() === false) {
        $_SESSION['error_message'] = 'Bu işlem için yetkiniz yok.';
        header('Location: ../pages/proposals.php');
        exit;
    }

    // CASCADE DELETE sayesinde, bu teklife ait kalemler ve izlenme kayıtları da silinecektir.
    $stmt = $pdo->prepare("DELETE FROM proposals WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['success_message'] = 'Teklif başarıyla silindi.';
    header('Location: ../pages/proposals.php');
    exit;
}
?>