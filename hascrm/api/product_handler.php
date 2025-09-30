<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$pdo = get_db_connection();
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
} catch (Exception $e) {
    error_log("Ürün İşlemi Hatası: " . $e->getMessage());
    $_SESSION['error_message'] = 'Bir hata oluştu: ' . $e->getMessage();
    header('Location: ../pages/products.php');
    exit;
}

/**
 * Dosya yükleme işlemini yönetir.
 * @return string|null Yüklenen dosyanın yolu veya bir hata durumunda null.
 */
function handle_image_upload() {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../assets/uploads/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = uniqid() . '-' . basename($_FILES['image']['name']);
        $target_path = $upload_dir . $file_name;
        $db_path = 'assets/uploads/products/' . $file_name;

        // Dosyayı taşı
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            return $db_path;
        } else {
            throw new Exception('Dosya yüklenirken bir hata oluştu.');
        }
    }
    return null; // Yeni dosya yüklenmedi
}

function handle_create($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

    $image_url = handle_image_upload();

    $stmt = $pdo->prepare(
        "INSERT INTO products (organization_id, category_id, name, description, unit, price, currency, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $_SESSION['organization_id'],
        $_POST['category_id'] ?: null,
        $_POST['name'],
        $_POST['description'],
        $_POST['unit'],
        $_POST['price'],
        $_POST['currency'],
        $image_url
    ]);

    $_SESSION['success_message'] = 'Ürün başarıyla oluşturuldu.';
    header('Location: ../pages/products.php');
    exit;
}

function handle_update($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

    $product_id = $_POST['product_id'] ?? 0;
    if (empty($product_id)) {
        throw new Exception('Geçersiz Ürün IDsi.');
    }

    // Mevcut ürünü getir (eski resmi silmek için gerekebilir)
    $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ? AND organization_id = ?");
    $stmt->execute([$product_id, $_SESSION['organization_id']]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception('Bu işlem için yetkiniz yok.');
    }

    $image_url = handle_image_upload();

    if ($image_url && $product['image_url'] && file_exists(__DIR__ . '/../' . $product['image_url'])) {
        unlink(__DIR__ . '/../' . $product['image_url']); // Eski resmi sil
    }

    $sql = "UPDATE products SET category_id=?, name=?, description=?, unit=?, price=?, currency=? ";
    $params = [
        $_POST['category_id'] ?: null,
        $_POST['name'],
        $_POST['description'],
        $_POST['unit'],
        $_POST['price'],
        $_POST['currency']
    ];

    if ($image_url) {
        $sql .= ", image_url=? ";
        $params[] = $image_url;
    }

    $sql .= "WHERE id = ?";
    $params[] = $product_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $_SESSION['success_message'] = 'Ürün başarıyla güncellendi.';
    header('Location: ../pages/products.php');
    exit;
}

function handle_delete($pdo) {
    // ... (silme fonksiyonu aynı kalabilir) ...
    $id = $_GET['id'] ?? 0;

    if (empty($id)) {
        $_SESSION['error_message'] = 'Geçersiz ürün IDsi.';
        header('Location: ../pages/products.php');
        exit;
    }

    // Güvenlik: Kullanıcının bu ürünü silme yetkisi var mı?
    $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ? AND organization_id = ?");
    $stmt->execute([$id, $_SESSION['organization_id']]);
    $product = $stmt->fetch();
    if ($product === false) {
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

    // Ürünü ve ilişkili resmi sil
    if ($product['image_url'] && file_exists(__DIR__ . '/../' . $product['image_url'])) {
        unlink(__DIR__ . '/../' . $product['image_url']);
    }

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['success_message'] = 'Ürün/Hizmet başarıyla silindi.';
    header('Location: ../pages/products.php');
    exit;
}
?>