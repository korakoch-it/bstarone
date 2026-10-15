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

  <!-- Tailwind CSS v3 Play CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Prompt', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
          },
          colors: {
            clinic: {
              900: '#091e17',
              800: '#064e3b',
              700: '#043d2e',
              50: '#ecfdf5',
              gold: '#d97706',
              amber: '#f59e0b',
            }
          }
        }
      }
    }
  </script>
</head>
<body class="bg-slate-50 text-slate-900 font-sans min-h-screen flex flex-col">

  <!-- Top Navigation Bar -->
  <header class="sticky top-0 z-50 bg-gradient-to-r from-[#091e17] to-[#064e3b] text-white shadow-lg px-4 sm:px-8 py-3.5 flex flex-col sm:flex-row justify-between items-center gap-3">
    <!-- Brand -->
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 text-white flex items-center justify-center font-bold text-lg shadow-md shrink-0">
        ✦
      </div>
      <div>
        <a href="/page/index.php" class="no-underline text-white block">
          <div class="text-base sm:text-lg font-bold tracking-tight">BESTAR-ONE LUXURY CLINIC</div>
          <div class="text-xs text-emerald-300 font-medium tracking-wide uppercase">Smart Medical Scheduling System</div>
        </a>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto max-w-full pb-1 sm:pb-0">
      <a href="/page/index.php" class="px-3.5 py-1.5 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 <?= ($activePage ?? '') === 'today' ? 'bg-white text-emerald-900 font-semibold shadow-sm' : 'text-slate-200 border border-white/10 hover:bg-white/10 hover:text-white' ?>">
        <span>📅</span> <span>รายการนัดวันนี้</span>
      </a>
      <a href="/page/booking.php" class="px-3.5 py-1.5 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 <?= ($activePage ?? '') === 'booking' ? 'bg-white text-emerald-900 font-semibold shadow-sm' : 'text-slate-200 border border-white/10 hover:bg-white/10 hover:text-white' ?>">
        <span>➕</span> <span>นัดหมายแพทย์</span>
      </a>
      <a href="/page/patients.php" class="px-3.5 py-1.5 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 <?= ($activePage ?? '') === 'patients' ? 'bg-white text-emerald-900 font-semibold shadow-sm' : 'text-slate-200 border border-white/10 hover:bg-white/10 hover:text-white' ?>">
        <span>👥</span> <span>ข้อมูลผู้ป่วย</span>
      </a>
      <a href="/page/reports.php" class="px-3.5 py-1.5 rounded-lg text-sm font-medium transition-all flex items-center gap-1.5 <?= ($activePage ?? '') === 'reports' ? 'bg-white text-emerald-900 font-semibold shadow-sm' : 'text-slate-200 border border-white/10 hover:bg-white/10 hover:text-white' ?>">
        <span>📊</span> <span>รายงานสรุป</span>
      </a>
    </nav>
  </header>

  <!-- Main Container -->
  <main class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 flex-1">
