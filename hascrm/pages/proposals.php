<?php
$page_title = 'Teklifler';
require_once __DIR__ . '/../includes/header.php';

// Bu sayfaya sadece giriş yapmış kullanıcılar erişebilir
require_login();

// Veritabanı bağlantısını al
$pdo = get_db_connection();

// Mevcut organizasyonun tekliflerini, müşteri adıyla birlikte getir
// SQL JOIN kullanarak proposals ve customers tablolarını birleştiriyoruz.
$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS customer_name
     FROM proposals p
     JOIN customers c ON p.customer_id = c.id
     WHERE p.organization_id = ?
     ORDER BY p.proposal_date DESC"
);
$stmt->execute([$_SESSION['organization_id']]);
$proposals = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Teklif Yönetimi</h1>
    <a href="proposal_create.php" class="button-primary">Yeni Teklif Oluştur</a>
</div>

<table>
    <thead>
        <tr>
            <th>Başlık</th>
            <th>Müşteri</th>
            <th>Teklif Tarihi</th>
            <th>Tutar</th>
            <th>Durum</th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($proposals)): ?>
            <tr>
                <td colspan="6" style="text-align:center;">Henüz hiç teklif oluşturulmamış.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($proposals as $proposal): ?>
                <tr>
                    <td><?php echo e($proposal['title']); ?></td>
                    <td><?php echo e($proposal['customer_name']); ?></td>
                    <td><?php echo e(date('d.m.Y', strtotime($proposal['proposal_date']))); ?></td>
                    <td><?php echo e(number_format($proposal['total_amount'], 2, ',', '.')); ?> TL</td>
                    <td><span class="status-<?php echo e($proposal['status']); ?>"><?php echo e(ucfirst($proposal['status'])); ?></span></td>
                    <td class="actions">
                        <a href="../view_proposal.php?token=<?php echo e($proposal['share_token']); ?>" target="_blank" class="button-view">Görüntüle</a>
                        <a href="proposal_create.php?id=<?php echo e($proposal['id']); ?>" class="button-edit">Düzenle</a>
                        <a href="../api/proposal_handler.php?action=delete&id=<?php echo e($proposal['id']); ?>" class="button-delete" onclick="return confirm('Bu teklifi silmek istediğinizden emin misiniz?');">Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<style>
/* Durum etiketleri için stiller */
.status-draft { background-color: #6c757d; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8em; }
.status-sent { background-color: #007bff; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8em; }
.status-viewed { background-color: #ffc107; color: black; padding: 3px 8px; border-radius: 12px; font-size: 0.8em; }
.status-accepted { background-color: #28a745; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8em; }
.status-rejected { background-color: #dc3545; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.8em; }
.button-view { background-color: #17a2b8; }
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>