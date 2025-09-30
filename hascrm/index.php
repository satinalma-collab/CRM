<?php
$page_title = 'Gösterge Paneli';
require_once __DIR__ . '/includes/header.php';

require_login();

$pdo = get_db_connection();
$org_id = $_SESSION['organization_id'];

// --- Metrikleri Hesapla ---

// Toplam Müşteri Sayısı
$total_customers_stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE organization_id = ?");
$total_customers_stmt->execute([$org_id]);
$total_customers = $total_customers_stmt->fetchColumn();

// Toplam Ürün Sayısı
$total_products_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE organization_id = ?");
$total_products_stmt->execute([$org_id]);
$total_products = $total_products_stmt->fetchColumn();

// Teklif İstatistikleri
$proposal_stats_stmt = $pdo->prepare(
    "SELECT
        COUNT(*) as total_proposals,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_proposals,
        SUM(total_amount) as total_amount_all,
        SUM(CASE WHEN status = 'accepted' THEN total_amount ELSE 0 END) as total_amount_accepted
     FROM proposals
     WHERE organization_id = ?"
);
$proposal_stats_stmt->execute([$org_id]);
$stats = $proposal_stats_stmt->fetch();

$success_rate = ($stats['total_proposals'] > 0) ? ($stats['accepted_proposals'] / $stats['total_proposals']) * 100 : 0;

// Son 5 Teklif
$recent_proposals_stmt = $pdo->prepare(
    "SELECT p.title, p.status, p.total_amount, c.name as customer_name
     FROM proposals p
     JOIN customers c ON p.customer_id = c.id
     WHERE p.organization_id = ?
     ORDER BY p.proposal_date DESC
     LIMIT 5"
);
$recent_proposals_stmt->execute([$org_id]);
$recent_proposals = $recent_proposals_stmt->fetchAll();
?>

<header class="page-header">
    <h1 class="page-title">Hoş Geldiniz, <?php echo e($_SESSION['user_name']); ?>!</h1>
</header>

<div class="grid">
    <!-- Stat Kartı 1: Müşteriler -->
    <article>
        <h3 class="card-title">Toplam Müşteri</h3>
        <p class="card-metric"><?php echo e($total_customers); ?></p>
    </article>

    <!-- Stat Kartı 2: Ürünler -->
    <article>
        <h3 class="card-title">Toplam Ürün/Hizmet</h3>
        <p class="card-metric"><?php echo e($total_products); ?></p>
    </article>

    <!-- Stat Kartı 3: Teklifler -->
    <article>
        <h3 class="card-title">Toplam Teklif</h3>
        <p class="card-metric"><?php echo e($stats['total_proposals']); ?></p>
    </article>

    <!-- Stat Kartı 4: Başarı Oranı -->
    <article>
        <h3 class="card-title">Teklif Başarı Oranı</h3>
        <p class="card-metric"><?php echo e(number_format($success_rate, 1)); ?>%</p>
    </article>
</div>

<article style="margin-top: 2rem;">
    <h3 style="margin-bottom: 1rem;">Son Teklifler</h3>
    <?php if(empty($recent_proposals)): ?>
        <p>Henüz hiç teklif oluşturulmamış.</p>
    <?php else: ?>
    <table>
        <tbody>
        <?php foreach($recent_proposals as $proposal): ?>
            <tr>
                <td><strong><?php echo e($proposal['title']); ?></strong><br><small><?php echo e($proposal['customer_name']); ?></small></td>
                <td class="text-right"><?php echo e(number_format($proposal['total_amount'], 2, ',', '.')); ?> TL</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <footer style="text-align: center; margin-top: 1rem;">
        <a href="/pages/proposals.php">Tüm Teklifleri Gör...</a>
    </footer>
</article>

<style>
.page-header { margin-bottom: 1rem; }
.page-title { margin-bottom: 0; }
.card-title { margin-bottom: 0.5rem; font-size: 1rem; color: var(--pico-muted-color); }
.card-metric { margin-bottom: 0; font-size: 2.5rem; font-weight: bold; }
.text-right { text-align: right; }
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
?>