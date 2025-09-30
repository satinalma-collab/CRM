<?php
session_start();

// Gerekli dosyaları dahil et
// API betikleri HTML çıktısı üretmemeli, bu yüzden header.php yerine
// sadece gerekli olan çekirdek dosyaları dahil ediyoruz.
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';


require_login(); // Sadece giriş yapmış kullanıcılar işlem yapabilir

// Veritabanı bağlantısını al
$pdo = get_db_connection();

// Hangi işlemin yapılacağını belirle
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            handle_create($pdo);
            break;
        case 'update':
            handle_update($pdo);
            break;
        case 'delete':
            handle_delete($pdo);
            break;
        default:
            // Geçersiz eylem, listeye yönlendir
            $_SESSION['error_message'] = 'Geçersiz işlem.';
            header('Location: ../pages/customers.php');
            exit;
    }
} catch (PDOException $e) {
    error_log("Müşteri İşlemi Hatası: " . $e->getMessage());
    $_SESSION['error_message'] = 'Bir veritabanı hatası oluştu. İşlem gerçekleştirilemedi.';
    header('Location: ../pages/customers.php');
    exit;
}

/**
 * Yeni müşteri oluşturma işlemini yönetir.
 */
function handle_create($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../pages/customers.php');
        exit;
    }

    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;

    if (empty($name)) {
        $_SESSION['error_message'] = 'Müşteri adı zorunludur.';
        header('Location: ../pages/customer_form.php');
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO customers (organization_id, name, email, phone, address) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$_SESSION['organization_id'], $name, $email, $phone, $address]);

    $_SESSION['success_message'] = 'Müşteri başarıyla oluşturuldu.';
    header('Location: ../pages/customers.php');
    exit;
}

/**
 * Müşteri güncelleme işlemini yönetir.
 */
function handle_update($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../pages/customers.php');
        exit;
    }

    $id = $_POST['customer_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;

    if (empty($name) || empty($id)) {
        $_SESSION['error_message'] = 'Müşteri adı ve IDsi zorunludur.';
        header('Location: ../pages/customer_form.php?id=' . $id);
        exit;
    }

    // Güvenlik: Kullanıcının bu müşteriyi güncelleme yetkisi var mı? (Aynı organizasyonda mı?)
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ? AND organization_id = ?");
    $stmt->execute([$id, $_SESSION['organization_id']]);
    if ($stmt->fetchColumn() === false) {
        $_SESSION['error_message'] = 'Bu işlem için yetkiniz yok.';
        header('Location: ../pages/customers.php');
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE customers SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?"
    );
    $stmt->execute([$name, $email, $phone, $address, $id]);

    $_SESSION['success_message'] = 'Müşteri bilgileri başarıyla güncellendi.';
    header('Location: ../pages/customers.php');
    exit;
}

/**
 * Müşteri silme işlemini yönetir.
 */
function handle_delete($pdo) {
    $id = $_GET['id'] ?? 0;

    if (empty($id)) {
        $_SESSION['error_message'] = 'Geçersiz müşteri IDsi.';
        header('Location: ../pages/customers.php');
        exit;
    }

    // Güvenlik: Kullanıcının bu müşteriyi silme yetkisi var mı? (Aynı organizasyonda mı?)
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ? AND organization_id = ?");
    $stmt->execute([$id, $_SESSION['organization_id']]);
    if ($stmt->fetchColumn() === false) {
        $_SESSION['error_message'] = 'Bu işlem için yetkiniz yok.';
        header('Location: ../pages/customers.php');
        exit;
    }

    // Önce bu müşteriye ait teklifleri kontrol et
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM proposals WHERE customer_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error_message'] = 'Bu müşteriye ait teklifler bulunduğu için silinemez. Önce teklifleri silmelisiniz.';
        header('Location: ../pages/customers.php');
        exit;
    }


    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['success_message'] = 'Müşteri başarıyla silindi.';
    header('Location: ../pages/customers.php');
    exit;
}

?>