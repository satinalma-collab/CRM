<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HasCRM - <?php echo htmlspecialchars($page_title ?? 'Hoş Geldiniz'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- İleride buraya başka kütüphaneler (örn. FontAwesome) eklenebilir -->
</head>
<body>

<header class="main-header">
    <div class="logo">
        <a href="index.php">HasCRM</a>
    </div>
    <nav class="main-nav">
        <ul>
            <?php if (is_logged_in()): ?>
                <li><a href="pages/dashboard.php">Gösterge Paneli</a></li>
                <li><a href="pages/customers.php">Müşteriler</a></li>
                <li><a href="pages/products.php">Ürünler</a></li>
                <li><a href="pages/proposals.php">Teklifler</a></li>
                <li><a href="logout.php">Çıkış Yap (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)</a></li>
            <?php else: ?>
                <li><a href="login.php">Giriş Yap</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main class="container">
    <!-- Sayfa içeriği bu araya gelecek -->