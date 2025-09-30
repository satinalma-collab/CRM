<?php
$page_title = 'Teklifler';
require_once __DIR__ . '/../includes/header.php';

require_login();

$pdo = get_db_connection();

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS customer_name
     FROM proposals p
     JOIN customers c ON p.customer_id = c.id
     WHERE p.organization_id = ?
     ORDER BY p.proposal_date DESC"
);
$stmt->execute([$_SESSION['organization_id']]);
$proposals = $stmt->fetchAll();

// Durumlar için renk ve metin eşleştirmesi
function get_status_badge($status) {
    $map = [
        'draft' => ['class' => 'secondary', 'text' => 'Taslak'],
        'sent' => ['class' => '', 'text' => 'Gönderildi'],
        'viewed' => ['class' => 'contrast', 'text' => 'Görüntülendi'],
        'accepted' => ['class' => 'success', 'text' => 'Kabul Edildi'],
        'rejected' => ['class' => 'danger', 'text' => 'Reddedildi'],
    ];
    $style = $map[$status] ?? ['class' => 'secondary', 'text' => ucfirst($status)];
    return "<mark class=\"{$style['class']}\">{$style['text']}</mark>";
}
?>

<header class="page-header">
    <h1 class="page-title">Teklif Yönetimi</h1>
    <a href="proposal_create.php" role="button">Yeni Teklif Oluştur</a>
</header>

<figure>
    <table>
        <thead>
            <tr>
                <th scope="col">Başlık / Müşteri</th>
                <th scope="col">Tarih</th>
                <th scope="col">Tutar</th>
                <th scope="col">Durum</th>
                <th scope="col">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($proposals)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;">Henüz hiç teklif oluşturulmamış.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($proposals as $proposal): ?>
                    <tr>
                        <td>
                            <strong><?php echo e($proposal['title']); ?></strong><br>
                            <small><?php echo e($proposal['customer_name']); ?></small>
                        </td>
                        <td><?php echo e(date('d.m.Y', strtotime($proposal['proposal_date']))); ?></td>
                        <td><?php echo e(number_format($proposal['total_amount'], 2, ',', '.')); ?> TL</td>
                        <td><?php echo get_status_badge($proposal['status']); ?></td>
                        <td>
                             <div class="grid" style="--grid-spacing: 0.5rem; min-width: 250px;">
                                <a href="../view_proposal.php?token=<?php echo e($proposal['share_token']); ?>" target="_blank" role="button" class="secondary outline">Görüntüle</a>
                                <a href="proposal_create.php?id=<?php echo e($proposal['id']); ?>" role="button" class="secondary outline">Düzenle</a>
                                <a href="../api/proposal_handler.php?action=delete&id=<?php echo e($proposal['id']); ?>" role="button" class="contrast" onclick="return confirm('Bu teklifi silmek istediğinizden emin misiniz?');">Sil</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</figure>

<style>
/* Sayfa başlığı ve butonunu yan yana getirmek için */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}
.page-title {
    margin-bottom: 0;
}
/* Durum etiketleri için özel renkler */
mark.success { background-color: var(--pico-color-green-200); border-color: var(--pico-color-green-400); }
mark.danger { background-color: var(--pico-color-red-200); border-color: var(--pico-color-red-400); }
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>