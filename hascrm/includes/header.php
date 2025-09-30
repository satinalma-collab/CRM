<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

// Pico.css için tema ayarını cookie üzerinden yapabiliriz.
$theme = $_COOKIE['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?php echo e($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HasCRM - <?php echo htmlspecialchars($page_title ?? 'Hoş Geldiniz'); ?></title>

    <!-- Pico.css CDN bağlantısı -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">

    <!-- Kendi özel stillerimiz için (Pico'yu ezmek veya eklemeler yapmak için) -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<nav class="container-fluid">
  <ul>
    <li><a href="/index.php"><strong>HasCRM</strong></a></li>
  </ul>
  <?php if (is_logged_in()): ?>
  <ul>
    <li><a href="/pages/customers.php">Müşteriler</a></li>
    <li><a href="/pages/products.php">Ürünler</a></li>
    <li><a href="/pages/proposals.php">Teklifler</a></li>
  </ul>
  <ul>
    <li>
        <details role="list" dir="rtl">
          <summary aria-haspopup="listbox" role="link"><?php echo e($_SESSION['user_name']); ?></summary>
          <ul role="listbox">
            <li><a href="/logout.php">Çıkış Yap</a></li>
          </ul>
        </details>
    </li>
  </ul>
  <?php endif; ?>
</nav>

<main class="container">
    <!-- Sayfa içeriği bu araya gelecek -->