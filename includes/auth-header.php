<?php
$pageTitle = $pageTitle ?? 'Tài khoản';
$assetPrefix = $assetPrefix ?? '';
$homePrefix = $homePrefix ?? '';
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> | Linh Florist</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= h($assetPrefix) ?>styles.css">
    <link rel="stylesheet" href="<?= h($assetPrefix) ?>auth.css">
  </head>
  <body class="auth-page">
    <header class="auth-header">
      <div class="page-shell auth-header-inner">
        <a class="brand" href="<?= h($homePrefix) ?>index.html" aria-label="Linh Florist - Trang chủ">
          <span class="brand-mark">ℒ</span>
          <span class="brand-name">Linh Florist</span>
          <span class="brand-tagline">Flowers for every feeling</span>
        </a>
        <a class="auth-back" href="<?= h($homePrefix) ?>index.html">← Về cửa hàng</a>
      </div>
    </header>
