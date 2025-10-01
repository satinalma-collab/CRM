<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pdo = get_db_connection();
$action = $_GET['action'] ?? '';

// Yönlendirme ve hata yönetimi için yardımcı fonksiyon
function redirect_with_message($type, $message, $location) {
    $_SESSION[$type] = $message;
    header("Location: $location");
    exit;
}

// İşlem yönlendirme
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
        handle_proposal($pdo);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
        handle_proposal($pdo, true);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
        handle_delete($pdo);
    } else {
        redirect_with_message('error_message', 'Geçersiz istek.', '../pages/proposals.php');
    }
} catch (Exception $e) {
    error_log("Teklif İşlemi Hatası: " . $e->getMessage());
    redirect_with_message('error_message', 'Bir hata oluştu: ' . $e->getMessage(), '../pages/proposals.php');
}


/**
 * Teklif oluşturma ve güncelleme işlemlerini yönetir.
 */
function handle_proposal($pdo, $is_update = false) {
    // Formdan gelen verileri al
    $proposal_id = $_POST['proposal_id'] ?? 0;
    $title = $_POST['title'] ?? 'İsimsiz Teklif';
    $customer_id = $_POST['customer_id'] ?? 0;
    $proposal_date = $_POST['proposal_date'] ?? date('Y-m-d');
    $valid_until_date = $_POST['valid_until_date'] ?? null;
    $currency = $_POST['currency'] ?? 'TRY';
    $delivery_terms = $_POST['delivery_terms'] ?? '';
    $payment_terms = $_POST['payment_terms'] ?? '';
    $items = $_POST['items'] ?? [];

    if ($is_update && empty($proposal_id)) {
        redirect_with_message('error_message', 'Güncellenecek teklif IDsi bulunamadı.', '../pages/proposals.php');
    }
    if (empty($title) || empty($customer_id) || empty($items['name'])) {
        redirect_with_message('error_message', 'Teklif başlığı, müşteri ve en az bir kalem zorunludur.', '../pages/proposal_create.php' . ($is_update ? "?id=$proposal_id" : ''));
    }

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

    if ($is_update) {
        // --- GÜNCELLEME İŞLEMİ ---
        $delete_stmt = $pdo->prepare("DELETE FROM proposal_items WHERE proposal_id = ?");
        $delete_stmt->execute([$proposal_id]);

        $update_stmt = $pdo->prepare(
            "UPDATE proposals SET customer_id=?, title=?, proposal_date=?, valid_until_date=?, total_amount=?, currency=?, delivery_terms=?, payment_terms=? WHERE id = ? AND organization_id = ?"
        );
        $update_stmt->execute([
            $customer_id, $title, $proposal_date, $valid_until_date, $total_amount, $currency, $delivery_terms, $payment_terms, $proposal_id, $_SESSION['organization_id']
        ]);
        $message = 'Teklif başarıyla güncellendi.';
    } else {
        // --- OLUŞTURMA İŞLEMİ ---
        $share_token = bin2hex(random_bytes(32));
        $insert_stmt = $pdo->prepare(
            "INSERT INTO proposals (organization_id, customer_id, user_id, title, proposal_date, valid_until_date, total_amount, currency, delivery_terms, payment_terms, status, share_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)"
        );
        $insert_stmt->execute([
            $_SESSION['organization_id'], $customer_id, $_SESSION['user_id'], $title, $proposal_date, $valid_until_date, $total_amount, $currency, $delivery_terms, $payment_terms, $share_token
        ]);
        $proposal_id = $pdo->lastInsertId();
        $message = 'Teklif başarıyla oluşturuldu.';
    }

    // Yeni teklif kalemlerini ekle
    $item_stmt = $pdo->prepare(
        "INSERT INTO proposal_items (proposal_id, product_id, name, description, quantity, unit, unit_price, discount_percentage, line_total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($items['name'] as $key => $name) {
        $line_total = ((float)$items['quantity'][$key] * (float)$items['unit_price'][$key]) * (1 - (float)$items['discount_percentage'][$key] / 100);
        $item_stmt->execute([
            $proposal_id,
            !empty($items['product_id'][$key]) ? (int)$items['product_id'][$key] : null,
            $name,
            null, // Açıklama alanı formda yok, null olarak ekliyoruz
            (float)$items['quantity'][$key],
            $items['unit'][$key],
            (float)$items['unit_price'][$key],
            (float)$items['discount_percentage'][$key],
            $line_total
        ]);
    }

    $pdo->commit();
    redirect_with_message('success_message', $message, '../pages/proposals.php');
}

/**
 * Teklif silme işlemini yönetir.
 */
function handle_delete($pdo) {
    $id = $_GET['id'] ?? 0;
    if (empty($id)) {
        redirect_with_message('error_message', 'Geçersiz teklif IDsi.', '../pages/proposals.php');
    }

    // Güvenlik: Kullanıcının bu teklifi silme yetkisi var mı?
    $stmt = $pdo->prepare("SELECT id FROM proposals WHERE id = ? AND organization_id = ?");
    $stmt->execute([$id, $_SESSION['organization_id']]);
    if ($stmt->fetchColumn() === false) {
        redirect_with_message('error_message', 'Bu işlem için yetkiniz yok.', '../pages/proposals.php');
    }

    // CASCADE DELETE sayesinde, bu teklife ait kalemler ve izlenme kayıtları da silinecektir.
    $stmt = $pdo->prepare("DELETE FROM proposals WHERE id = ?");
    $stmt->execute([$id]);

    redirect_with_message('success_message', 'Teklif başarıyla silindi.', '../pages/proposals.php');
}
?>