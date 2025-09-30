<?php
session_start();

// Gerekli çekirdek dosyaları dahil et
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

// Sadece giriş yapmış kullanıcılar işlem yapabilir
require_login();

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
            $_SESSION['error_message'] = 'Geçersiz işlem.';
            header('Location: ../pages/products.php');
            exit;
    }
} catch (PDOException $e) {
    error_log("Ürün İşlemi Hatası: " . $e->getMessage());
    $_SESSION['error_message'] = 'Bir veritabanı hatası oluştu. İşlem gerçekleştirilemedi.';
    header('Location: ../pages/products.php');
    exit;
}

/**
 * Yeni ürün oluşturma işlemini yönetir.
 */
function handle_create($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../pages/products.php');
        exit;
    }

    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? null;
    $unit = $_POST['unit'] ?? null;
    $price = $_POST['price'] ?? 0;

    if (empty($name) || !is_numeric($price)) {
        $_SESSION['error_message'] = 'Ürün adı ve geçerli bir fiyat zorunludur.';
        header('Location: ../pages/product_form.php');
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO products (organization_id, name, description, unit, price) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$_SESSION['organization_id'], $name, $description, $unit, $price]);

    $_SESSION['success_message'] = 'Ürün/Hizmet başarıyla oluşturuldu.';
    header('Location: ../pages/products.php');
    exit;
}

/**
 * Ürün güncelleme işlemini yönetir.
 */
function handle_update($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../pages/products.php');
        exit;
    }

    $id = $_POST['product_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? null;
    $unit = $_POST['unit'] ?? null;
    $price = $_POST['price'] ?? 0;

    if (empty($name) || empty($id) || !is_numeric($price)) {
        $_SESSION['error_message'] = 'Ürün adı, ID ve geçerli bir fiyat zorunludur.';
        header('Location: ../pages/product_form.php?id=' . $id);
        exit;
    }

    // Güvenlik: Kullanıcının bu ürünü güncelleme yetkisi var mı?
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND organization_id = ?");
    $stmt->execute([$id, $_SESSION['organization_id']]);
    if ($stmt->fetchColumn() === false) {
        $_SESSION['error_message'] = 'Bu işlem için yetkiniz yok.';
        header('Location: ../pages/products.php');
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE products SET name = ?, description = ?, unit = ?, price = ? WHERE id = ?"
    );
    $stmt->execute([$name, $description, $unit, $price, $id]);

    $_SESSION['success_message'] = 'Ürün/Hizmet başarıyla güncellendi.';
    header('Location: ../pages/products.php');
    exit;
}

/**
 * Ürün silme işlemini yönetir.
 */
function handle_delete($pdo) {
    $id = $_GET['id'] ?? 0;

    if (empty($id)) {
        $_SESSION['error_message'] = 'Geçersiz ürün IDsi.';
        header('Location: ../pages/products.php');
        exit;
    }

    // Güvenlik: Kullanıcının bu ürünü silme yetkisi var mı?
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND organization_id = ?");
    $stmt->execute([$id, $_SESSION['organization_id']]);
    if ($stmt->fetchColumn() === false) {
        $_SESSION['error_message'] = 'Bu işlem için yetkiniz yok.';
        header('Location: ../pages/products.php');
        exit;
    }

    // Kontrol: Bu ürün herhangi bir teklifte kullanılmış mı?
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM proposal_items WHERE product_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error_message'] = 'Bu ürün/hizmet tekliflerde kullanıldığı için silinemez.';
        header('Location: ../pages/products.php');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['success_message'] = 'Ürün/Hizmet başarıyla silindi.';
    header('Location: ../pages/products.php');
    exit;
}

?>