<?php
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
    "SELECT p.*, c.name as customer_name, c.address as customer_address, c.email as customer_email, o.name as org_name
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

    // Durumu 'viewed' olarak güncelle
    if (in_array($proposal['status'], ['draft', 'sent'])) {
        $update_stmt = $pdo->prepare("UPDATE proposals SET status = 'viewed' WHERE id = ?");
        $update_stmt->execute([$proposal['id']]);
        $proposal['status'] = 'viewed';
    }
} catch (PDOException $e) {
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        body { background-color: var(--pico-secondary-background); }
        .container { max-width: 800px; }
        .text-right { text-align: right; }
        .actions-bar { text-align: center; margin-top: 2rem; }
    </style>
</head>
<body>
    <main class="container">
        <article>
            <header>
                <div class="grid">
                    <div>
                        <h1 style="margin-bottom: 0;"><?php echo e($proposal['org_name']); ?></h1>
                    </div>
                    <div class="text-right">
                        <strong>Teklif No:</strong> #<?php echo e($proposal['id']); ?><br>
                        <strong>Tarih:</strong> <?php echo e(date('d.m.Y', strtotime($proposal['proposal_date']))); ?><br>
                        <strong>Geçerlilik:</strong> <?php echo e(date('d.m.Y', strtotime($proposal['valid_until_date']))); ?>
                    </div>
                </div>
                <a href="/api/generate_proposal_pdf.php?token=<?php echo e($token); ?>" role="button" class="outline" style="margin-top: 1rem;">PDF Olarak İndir</a>
            </header>

            <p>
                <strong>Müşteri:</strong><br>
                <?php echo e($proposal['customer_name']); ?><br>
                <?php echo e($proposal['customer_address']); ?><br>
                <?php echo e($proposal['customer_email']); ?>
            </p>

            <figure>
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Ürün/Hizmet</th>
                            <th scope="col" class="text-right">Miktar</th>
                            <th scope="col" class="text-right">Birim Fiyat</th>
                            <th scope="col" class="text-right">Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($item['name']); ?></strong>
                                <?php if($item['description']): ?><br><small><?php echo e($item['description']); ?></small><?php endif; ?>
                            </td>
                            <td class="text-right"><?php echo e(rtrim(rtrim(number_format($item['quantity'], 2, ',', '.'), '0'), ',') . ' ' . $item['unit']); ?></td>
                            <td class="text-right"><?php echo e(number_format($item['unit_price'], 2, ',', '.')); ?> TL</td>
                            <td class="text-right"><?php echo e(number_format($item['line_total'], 2, ',', '.')); ?> TL</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th scope="row" colspan="3" class="text-right">Genel Toplam:</th>
                            <td class="text-right"><strong><?php echo e(number_format($proposal['total_amount'], 2, ',', '.')); ?> TL</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </figure>
        </article>

        <div class="actions-bar">
            <?php if (in_array($proposal['status'], ['viewed', 'sent'])): ?>
                <p>Lütfen teklifi inceledikten sonra aşağıdaki seçeneklerden birini belirleyin.</p>
                <div class="grid">
                    <a href="api/proposal_status_handler.php?token=<?php echo e($token); ?>&status=accepted" role="button" class="success">Teklifi Kabul Et</a>
                    <a href="api/proposal_status_handler.php?token=<?php echo e($token); ?>&status=rejected" role="button" class="danger">Teklifi Reddet</a>
                </div>
            <?php else: ?>
                <p>Bu teklifin durumu: <strong><?php echo e(ucfirst($proposal['status'])); ?></strong></p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>