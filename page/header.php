<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'BStarOne Clinic' ?> | ระบบนัดหมายคลินิก</title>
  
  <!-- Google Fonts: Lato, Prompt, JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Lato:wght@400;500;700&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- jQuery 3.7.1 -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <!-- Vue 2.7.16 -->
  <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.js"></script>
  <!-- Bootstrap 5.3.3 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <style>
    [v-cloak] {
      display: none !important;
    }

    :root {
      --corp-primary: #007BFF;
      --corp-primary-hover: #0069d9;
      --corp-secondary: #343A40;
      --corp-neutral: #F8F9FA;
      --corp-white: #FFFFFF;
      --corp-success: #28A745;
      --corp-warning: #FFC107;
      --corp-danger: #DC3545;
      --corp-info: #17A2B8;
      --corp-border: #DEE2E6;
      --corp-border-light: #E9ECEF;
      --corp-text: #343A40;
      --corp-text-muted: #6C757D;
      --radius-sm: 4px;
      --radius-md: 8px;
    }

    body {
      font-family: 'Lato', 'Prompt', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background-color: var(--corp-neutral);
      color: var(--corp-text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      line-height: 1.6;
    }

    code, kbd, samp, pre, .font-monospace {
      font-family: 'JetBrains Mono', SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
    }

    .card {
      background-color: var(--corp-white);
      border: 1px solid var(--corp-border);
      border-radius: var(--radius-sm);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    .card-header {
      background-color: var(--corp-white);
      border-bottom: 1px solid var(--corp-border-light);
      padding: 0.875rem 1.25rem;
    }
    .card-footer {
      background-color: var(--corp-white);
      border-top: 1px solid var(--corp-border-light);
      padding: 0.875rem 1.25rem;
    }

    .btn {
      border-radius: var(--radius-sm);
      font-weight: 500;
      transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .btn-primary, .btn-corp-primary {
      background-color: var(--corp-primary);
      border-color: var(--corp-primary);
      color: #ffffff !important;
      font-weight: 600;
    }
    .btn-primary:hover, .btn-corp-primary:hover {
      background-color: var(--corp-primary-hover);
      border-color: var(--corp-primary-hover);
    }
    .btn-outline-corp {
      border: 1.5px solid var(--corp-border);
      color: var(--corp-primary);
      background-color: transparent;
    }
    .btn-outline-corp:hover {
      background-color: var(--corp-neutral);
      border-color: var(--corp-border);
      color: var(--corp-primary-hover);
    }

    .form-control, .form-select {
      border-radius: var(--radius-sm);
      border: 1px solid var(--corp-border);
      font-size: 0.9375rem;
      padding: 0.5rem 0.75rem;
      color: var(--corp-text);
      background-color: #ffffff;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--corp-primary);
      box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }
    .input-group-text {
      border-radius: var(--radius-sm);
      border: 1px solid var(--corp-border);
      background-color: var(--corp-neutral);
      color: var(--corp-text-muted);
    }

    .badge {
      border-radius: var(--radius-sm);
      font-weight: 500;
      padding: 0.35em 0.65em;
    }

    .table {
      color: var(--corp-text);
      margin-bottom: 0;
    }
    .table > :not(caption) > * > * {
      padding: 0.85rem 1rem;
      border-bottom: 1px solid var(--corp-border-light);
    }
    .table thead th {
      background-color: var(--corp-neutral);
      color: var(--corp-secondary);
      font-weight: 600;
      font-size: 0.875rem;
      border-bottom: 1px solid var(--corp-border);
    }
    .table-hover > tbody > tr:hover > * {
      background-color: #F1F3F5;
    }

    .navbar-corporate {
      background-color: #ffffff;
      border-bottom: 1px solid var(--corp-border);
    }
    .navbar-corporate .navbar-brand {
      font-weight: 700;
      font-size: 1.15rem;
      color: var(--corp-secondary);
      letter-spacing: -0.2px;
    }
    .navbar-corporate .brand-badge {
      background-color: var(--corp-primary);
      color: #ffffff;
      width: 32px;
      height: 32px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: var(--radius-sm);
    }
    .navbar-corporate .nav-link {
      color: var(--corp-secondary);
      font-weight: 500;
      font-size: 0.9375rem;
      padding: 0.5rem 0.9rem !important;
      border-radius: var(--radius-sm);
      transition: background-color 0.15s, color 0.15s;
    }
    .navbar-corporate .nav-link:hover {
      background-color: var(--corp-neutral);
      color: var(--corp-primary);
    }
    .navbar-corporate .nav-link.active {
      color: var(--corp-primary) !important;
      background-color: rgba(0, 123, 255, 0.08);
      font-weight: 600;
    }
  </style>
</head>
<body>
  <!-- Corporate Flat Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-corporate sticky-top py-2">
    <div class="container-xxl">
      <!-- Brand -->
      <a class="navbar-brand d-flex align-items-center gap-2 me-4" href="/page/index.php">
        <span class="brand-badge"><i class="bi bi-heart-pulse-fill"></i></span>
        <span>BStar<span style="color: var(--corp-primary);">One</span></span>
      </a>

      <!-- Mobile Toggler -->
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#corpNavbar" aria-controls="corpNavbar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Nav Items -->
      <div class="collapse navbar-collapse" id="corpNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">
          <li class="nav-item">
            <a class="nav-link d-inline-flex align-items-center gap-1.5 <?= ($activePage ?? '') === 'today' ? 'active' : '' ?>" href="/page/index.php">
              <span>การนัดหมาย</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link d-inline-flex align-items-center gap-1.5 <?= ($activePage ?? '') === 'patients' ? 'active' : '' ?>" href="/page/patients.php">
              <span>ข้อมูลผู้ป่วย</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link d-inline-flex align-items-center gap-1.5 <?= ($activePage ?? '') === 'reports' ? 'active' : '' ?>" href="/page/reports.php">
              <span>รายงานสรุป</span>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Container -->
  <main class="container-xxl py-4 flex-grow-1">
