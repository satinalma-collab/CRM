<?php
// Bu sayfa halka açıktır, session başlatmaya gerek yok (şimdilik).
// Gerekli çekirdek dosyaları dahil et
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db_connection.php';
require_once __DIR__ . '/includes/functions.php';

// Token'ı URL'den al
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('Geçersiz teklif bağlantısı.');
}

$pdo = get_db_connection();

// Teklifi, müşteri ve organizasyon bilgileriyle birlikte getir
$stmt = $pdo->prepare(
    "SELECT p.*, c.name as customer_name, c.email as customer_email, o.name as org_name
     FROM proposals p
     JOIN customers c ON p.customer_id = c.id
     JOIN organizations o ON p.organization_id = o.id
     WHERE p.share_token = ?"
);
$stmt->execute([$token]);
$proposal = $stmt->fetch();

if (!$proposal) {
    die('Bu teklif bulunamadı veya artık geçerli değil.');
}

// Teklif kalemlerini getir
$items_stmt = $pdo->prepare("SELECT * FROM proposal_items WHERE proposal_id = ? ORDER BY id");
$items_stmt->execute([$proposal['id']]);
$items = $items_stmt->fetchAll();

// --- İstatistik ve Durum Güncelleme ---
try {
    // Görüntülemeyi kaydet
    $view_stmt = $pdo->prepare("INSERT INTO proposal_views (proposal_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $view_stmt->execute([$proposal['id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

    // Durumu 'viewed' olarak güncelle (eğer 'sent' veya 'draft' ise)
    if (in_array($proposal['status'], ['draft', 'sent'])) {
        $update_stmt = $pdo->prepare("UPDATE proposals SET status = 'viewed' WHERE id = ?");
        $update_stmt->execute([$proposal['id']]);
        $proposal['status'] = 'viewed'; // Sayfada güncel durumu göstermek için
    }
} catch (PDOException $e) {
    // Hata olursa işlemi durdurma, sadece logla
    error_log('Teklif görüntüleme hatası: ' . $e->getMessage());
}

$page_title = 'Teklif: ' . e($proposal['title']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        /* PDF benzeri bir görünüm için temel stiller */
        body { font-family: sans-serif; background-color: #eee; margin: 0; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); font-size: 16px; line-height: 24px; background-color: #fff; }
        .invoice-box table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        .invoice-box table td { padding: 5px; vertical-align: top; }
        .invoice-box table tr.top table td { padding-bottom: 20px; }
        .invoice-box table tr.top table td.title { font-size: 45px; line-height: 45px; color: #333; }
        .invoice-box table tr.information table td { padding-bottom: 40px; }
        .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        .invoice-box table tr.item td{ border-bottom: 1px solid #eee; }
        .invoice-box table tr.item.last td { border-bottom: none; }
        .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
        .text-right { text-align: right; }
        .actions-bar { text-align: center; margin-top: 20px; padding: 20px; background-color: #f7f7f7; border-top: 1px solid #ddd;}
        .button { padding: 10px 20px; text-decoration: none; color: white; border-radius: 5px; }
        .button-accept { background-color: #28a745; }
        .button-reject { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table>
            <tr class="top">
                <td colspan="4">
                    <table>
                        <tr>
                            <td class="title"><?php echo e($proposal['org_name']); ?></td>
                            <td class="text-right">
                                <strong>Teklif No:</strong> #<?php echo e($proposal['id']); ?><br>
                                <strong>Tarih:</strong> <?php echo e(date('d.m.Y', strtotime($proposal['proposal_date']))); ?><br>
                                <strong>Geçerlilik:</strong> <?php echo e(date('d.m.Y', strtotime($proposal['valid_until_date']))); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="information">
                <td colspan="4">
                    <table>
                        <tr>
                            <td>
                                <strong>Müşteri:</strong><br>
                                <?php echo e($proposal['customer_name']); ?><br>
                                <?php echo e($proposal['customer_email']); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="heading">
                <td>Ürün/Hizmet</td>
                <td class="text-right">Miktar</td>
                <td class="text-right">Birim Fiyat</td>
                <td class="text-right">Toplam</td>
            </tr>
            <?php foreach ($items as $item): ?>
            <tr class="item">
                <td>
                    <strong><?php echo e($item['name']); ?></strong><br>
                    <small><?php echo e($item['description']); ?></small>
                </td>
                <td class="text-right"><?php echo e($item['quantity'] . ' ' . $item['unit']); ?></td>
                <td class="text-right"><?php echo e(number_format($item['unit_price'], 2, ',', '.')); ?> TL</td>
                <td class="text-right"><?php echo e(number_format($item['line_total'], 2, ',', '.')); ?> TL</td>
            </tr>
            <?php endforeach; ?>
            <tr class="total">
                <td colspan="3" class="text-right"><strong>Genel Toplam:</strong></td>
                <td class="text-right"><strong><?php echo e(number_format($proposal['total_amount'], 2, ',', '.')); ?> TL</strong></td>
            </tr>
        </table>
    </div>

    <div class="actions-bar">
        <?php if ($proposal['status'] === 'viewed' || $proposal['status'] === 'sent'): ?>
            <p>Lütfen teklifi inceledikten sonra aşağıdaki seçeneklerden birini belirleyin.</p>
            <a href="api/proposal_status_handler.php?token=<?php echo e($token); ?>&status=accepted" class="button button-accept">Teklifi Kabul Et</a>
            <a href="api/proposal_status_handler.php?token=<?php echo e($token); ?>&status=rejected" class="button button-reject">Teklifi Reddet</a>
        <?php else: ?>
            <p>Bu teklifin durumu: <strong><?php echo e(ucfirst($proposal['status'])); ?></strong></p>
        <?php endif; ?>
    </div>
</body>
</html>