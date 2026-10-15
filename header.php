<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'Bestar-one Luxury Clinic' ?> | ระบบนัดหมายคลินิก</title>
  <!-- Google Fonts: Prompt -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>

  <!-- Top Navigation Bar -->
  <header class="navbar">
    <div class="brand">
      <div class="brand-icon">✦</div>
      <div>
        <a href="/index.php" style="text-decoration: none; color: inherit;">
          <div class="brand-title">BESTAR-ONE LUXURY CLINIC</div>
          <div class="brand-subtitle">Smart Medical Scheduling System</div>
        </a>
      </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="nav-tabs">
      <a href="/index.php" class="nav-tab <?= ($activePage ?? '') === 'today' ? 'active' : '' ?>">
        <span>📅</span> รายการนัดของวันนี้
      </a>
      <a href="/booking.php" class="nav-tab <?= ($activePage ?? '') === 'booking' ? 'active' : '' ?>">
        <span>➕</span> นัดหมายแพทย์
      </a>
      <a href="/patients.php" class="nav-tab <?= ($activePage ?? '') === 'patients' ? 'active' : '' ?>">
        <span>👥</span> ข้อมูลผู้ป่วย
      </a>
      <a href="/reports.php" class="nav-tab <?= ($activePage ?? '') === 'reports' ? 'active' : '' ?>">
        <span>📊</span> รายงานสรุป
      </a>
    </nav>
  </header>

  <!-- Main Container -->
  <main class="main-content">
