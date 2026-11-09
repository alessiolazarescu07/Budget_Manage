<?php
// Avvia o prepara eventuali costanti PHP se necessario
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alessio budget - Controllo Finanze Personali</title>
  <link rel="icon" type="image/png" href="soldi.png">

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <!-- html2pdf.js for dedicated PDF report creation -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <!-- Google Fonts Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
          },
          colors: {
            brand: {
              50: '#eef2ff',
              100: '#e0e7ff',
              500: '#6366f1',
              600: '#4f46e5',
              700: '#4338ca',
              800: '#3730a3',
              900: '#312e81',
            }
          }
        }
      }
    }
  </script>
  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    /* Custom Scrollbar */
    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.05);
    }
    ::-webkit-scrollbar-thumb {
      background: rgba(99, 102, 241, 0.4);
      border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: rgba(99, 102, 241, 0.7);
    }
    .glass-card {
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
    }
    .dark .glass-card {
      background: rgba(30, 41, 59, 0.88);
    }
    /* Print specific optimization */
    @media print {
      aside, header, #toastContainer, #customAlertModal, .no-print, button, nav {
        display: none !important;
      }
      body {
        background: #ffffff !important;
        color: #000000 !important;
      }
      .flex-1 {
        margin: 0 !important;
        padding: 0 !important;
      }
      #tab-reports {
        display: block !important;
      }
      #pdfPrintableArea {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
      }
      .page-break {
        page-break-before: always;
      }
    }
  </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 min-h-screen transition-colors duration-200">

  <!-- Main Container -->
  <div class="flex h-screen overflow-hidden">
    
    <!-- Sidebar Navigation -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white/95 dark:bg-slate-800/95 border-r border-slate-200 dark:border-slate-700/60 flex flex-col justify-between transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out backdrop-blur-md">
      <div>
        <!-- App Logo & Branding -->
        <div class="p-6 border-b border-slate-100 dark:border-slate-700/40 flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-400 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25">
              <i class="fa-solid fa-wallet text-lg"></i>
            </div>
            <div>
              <h1 class="text-lg font-extrabold tracking-tight bg-gradient-to-r from-brand-600 to-indigo-400 bg-clip-text text-transparent">Alessio budget</h1>
              <div class="flex items-center gap-1.5">
                <span class="text-[11px] text-slate-400 font-medium">Wealth & Life Hub</span>
                <span id="backendBadge" class="text-[10px] px-1.5 py-0.2 rounded font-semibold bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400" title="Archiviazione attiva">SQLite</span>
              </div>
            </div>
          </div>
          <button id="closeSidebarBtn" class="md:hidden text-slate-400 hover:text-slate-600 dark:hover:text-white p-1">
            <i class="fa-solid fa-times text-xl"></i>
          </button>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4 space-y-1.5">
          <button onclick="switchTab('dashboard')" id="nav-dashboard" class="nav-item w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200 bg-brand-500/10 text-brand-600 dark:text-indigo-400">
            <i class="fa-solid fa-chart-pie w-5"></i>
            <span>Panoramica</span>
          </button>
          <button onclick="switchTab('transactions')" id="nav-transactions" class="nav-item w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50">
            <i class="fa-solid fa-arrow-right-arrow-left w-5"></i>
            <span>Movimenti</span>
            <span id="badge-tx-count" class="ml-auto text-xs py-0.5 px-2 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-semibold">0</span>
          </button>
          <button onclick="switchTab('savings')" id="nav-savings" class="nav-item w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50">
            <i class="fa-solid fa-piggy-bank w-5"></i>
            <span>Salvadanaio & Risparmi</span>
          </button>
          <button onclick="switchTab('goals')" id="nav-goals" class="nav-item w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50">
            <i class="fa-solid fa-bullseye w-5"></i>
            <span>Traguardi & Obiettivi</span>
          </button>
          <button onclick="switchTab('reports')" id="nav-reports" class="nav-item w-full flex items-center space-x-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50">
            <i class="fa-solid fa-file-pdf w-5"></i>
            <span>Report ed Export PDF</span>
          </button>
        </nav>
      </div>

      <!-- Quick Action / Backup JSON / Mode Switcher -->
      <div class="p-4 border-t border-slate-200 dark:border-slate-700/50 space-y-2">
        <!-- JSON Backup & Restore buttons -->
        <div class="grid grid-cols-2 gap-2 pb-1">
          <button onclick="exportJSON()" title="Salva tutti i tuoi dati in un file .json" class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-slate-700/70 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
            <i class="fa-solid fa-file-export text-indigo-500"></i>
            <span>Esporta JSON</span>
          </button>
          <label title="Carica dati da un file .json" class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-slate-700/70 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition cursor-pointer">
            <i class="fa-solid fa-file-import text-emerald-500"></i>
            <span>Importa JSON</span>
            <input type="file" id="jsonFileInput" accept=".json" class="hidden" onchange="importJSON(event)">
          </label>
        </div>

        <!-- Dark mode switch -->
        <button id="themeToggle" class="w-full flex items-center justify-between px-4 py-2 rounded-xl text-sm font-medium bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
          <span class="flex items-center gap-2">
            <i id="themeIcon" class="fa-solid fa-moon"></i>
            <span id="themeText">Tema Scuro</span>
          </span>
          <span class="text-xs text-slate-400">Ctrl+D</span>
        </button>

        <!-- Svuota tutto -->
        <button onclick="confirmClearAllData()" class="w-full flex items-center justify-center gap-2 px-3 py-1.5 text-xs text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-lg transition font-medium">
          <i class="fa-solid fa-trash-can"></i>
          <span>Azzera Tutti i Dati</span>
        </button>
      </div>
    </aside>

    <!-- Overlay for mobile sidebar -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/50 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col md:ml-64 overflow-y-auto">

      <!-- Top Bar Header -->
      <header class="sticky top-0 z-30 bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-700/60 px-4 sm:px-8 py-3.5 flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <button id="openSidebarBtn" class="md:hidden text-slate-600 dark:text-slate-200 p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">
            <i class="fa-solid fa-bars text-lg"></i>
          </button>
          <div>
            <h2 id="sectionTitle" class="text-xl font-bold tracking-tight">Panoramica Finanziaria</h2>
            <p id="currentDateDisplay" class="text-xs text-slate-400 font-medium"></p>
          </div>
        </div>

        <div class="flex items-center space-x-2 sm:space-x-3">
          <!-- Backup JSON Quick Button -->
          <button onclick="exportJSON()" title="Salva backup JSON" class="p-2 sm:px-3 sm:py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-200 text-sm font-medium flex items-center gap-1.5 transition">
            <i class="fa-solid fa-download text-indigo-500 text-xs"></i>
            <span class="hidden sm:inline">Backup JSON</span>
          </button>

          <!-- Nuova Transazione Button -->
          <button onclick="openModal('transactionModal')" class="flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-brand-600 to-indigo-500 hover:from-brand-700 hover:to-indigo-600 shadow-md shadow-brand-500/25 active:scale-95 transition">
            <i class="fa-solid fa-plus text-xs"></i>
            <span class="hidden sm:inline">Nuovo Movimento</span>
            <span class="sm:hidden">Movimento</span>
          </button>

          <!-- Quick PDF Export Button -->
          <button onclick="quickPrintPDF()" title="Stampa o Salva PDF Istantaneo" class="p-2 sm:px-3 sm:py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-200 text-sm font-medium flex items-center gap-2 transition">
            <i class="fa-solid fa-print text-indigo-500"></i>
            <span class="hidden sm:inline">Stampa / PDF</span>
          </button>
        </div>
      </header>

      <!-- Main Body Container -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full space-y-6">

        <!-- ============================================== -->
        <!-- TAB 1: DASHBOARD / PANORAMICA                  -->
        <!-- ============================================== -->
        <section id="tab-dashboard" class="space-y-6 block">
          
          <!-- Stat Summary Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <!-- Saldo Totale -->
            <div class="glass-card p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm relative overflow-hidden group">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Patrimonio Netto</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 flex items-center justify-center">
                  <i class="fa-solid fa-scale-balanced text-sm"></i>
                </div>
              </div>
              <h3 id="statTotalBalance" class="text-2xl sm:text-3xl font-extrabold mt-2 tracking-tight text-slate-900 dark:text-white">€ 0,00</h3>
              <div class="mt-2 flex items-center text-xs text-slate-400">
                <span id="statSavingsRate" class="text-emerald-500 font-semibold mr-1.5 flex items-center"><i class="fa-solid fa-shield-halved mr-1"></i>0%</span>
                tasso di risparmio
              </div>
            </div>

            <!-- Entrate Mese -->
            <div class="glass-card p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm relative overflow-hidden group">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Entrate Mensili</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-500 flex items-center justify-center">
                  <i class="fa-solid fa-arrow-trend-up text-sm"></i>
                </div>
              </div>
              <h3 id="statMonthlyIncome" class="text-2xl sm:text-3xl font-extrabold mt-2 tracking-tight text-emerald-600 dark:text-emerald-400">€ 0,00</h3>
              <p class="mt-2 text-xs text-slate-400 flex items-center">
                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block mr-1.5"></span>
                Accrediti del mese in corso
              </p>
            </div>

            <!-- Uscite Mese -->
            <div class="glass-card p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm relative overflow-hidden group">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Uscite Mensili</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-500 flex items-center justify-center">
                  <i class="fa-solid fa-arrow-trend-down text-sm"></i>
                </div>
              </div>
              <h3 id="statMonthlyExpense" class="text-2xl sm:text-3xl font-extrabold mt-2 tracking-tight text-rose-600 dark:text-rose-400">€ 0,00</h3>
              <p class="mt-2 text-xs text-slate-400 flex items-center">
                <span class="w-2 h-2 rounded-full bg-rose-500 inline-block mr-1.5"></span>
                Spese complessive correnti
              </p>
            </div>

            <!-- Risparmi Totali Accantonati -->
            <div class="glass-card p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm relative overflow-hidden group">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Salvadanaio Totale</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-500 flex items-center justify-center">
                  <i class="fa-solid fa-vault text-sm"></i>
                </div>
              </div>
              <h3 id="statTotalSavings" class="text-2xl sm:text-3xl font-extrabold mt-2 tracking-tight text-amber-600 dark:text-amber-400">€ 0,00</h3>
              <div class="mt-2 flex items-center justify-between text-xs text-slate-400">
                <span id="statGoalsCount">0 traguardi attivi</span>
                <a href="javascript:switchTab('savings')" class="text-indigo-500 hover:underline">Dettagli</a>
              </div>
            </div>
          </div>

          <!-- Charts Row -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Spese per Categoria (Doughnut) -->
            <div class="glass-card p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm flex flex-col justify-between">
              <div class="flex items-center justify-between mb-4">
                <h4 class="font-bold text-base flex items-center gap-2">
                  <i class="fa-solid fa-chart-pie text-brand-500"></i>
                  Ripartizione Spese
                </h4>
                <span class="text-xs text-slate-400">Totale categorie</span>
              </div>
              <div class="relative w-full h-64 flex items-center justify-center">
                <canvas id="categoryChart"></canvas>
              </div>
              <div id="topExpenseCategory" class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700/50 text-xs text-slate-500 text-center">
                Nessun dato registrato.
              </div>
            </div>

            <!-- Flusso di Cassa: Entrate vs Spese (Bar) -->
            <div class="glass-card p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm lg:col-span-2 flex flex-col justify-between">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <div>
                  <h4 class="font-bold text-base flex items-center gap-2">
                    <i class="fa-solid fa-chart-column text-indigo-500"></i>
                    Andamento Finanziario Mensile
                  </h4>
                  <p class="text-xs text-slate-400">Confronto tra Entrate e Uscite negli ultimi 6 mesi</p>
                </div>
                <div class="flex items-center space-x-2 text-xs">
                  <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Entrate</span>
                  <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-rose-500"></span> Uscite</span>
                </div>
              </div>
              <div class="relative w-full h-64">
                <canvas id="trendChart"></canvas>
              </div>
              <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700/50 flex justify-between items-center text-xs text-slate-400">
                <span>Risparmio netto stimato periodo:</span>
                <span id="periodNetSavings" class="font-bold text-indigo-600 dark:text-indigo-400">€ 0,00</span>
              </div>
            </div>
          </div>

          <!-- Quick Preview: Traguardi e Ultime Transazioni -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Traguardi in evidenza -->
            <div class="glass-card p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm">
              <div class="flex items-center justify-between mb-4">
                <h4 class="font-bold text-base flex items-center gap-2">
                  <i class="fa-solid fa-flag-checkered text-amber-500"></i>
                  Avanzamento Obiettivi
                </h4>
                <button onclick="switchTab('goals')" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-indigo-400">Tutti i Traguardi &rarr;</button>
              </div>
              <div id="dashboardGoalsPreview" class="space-y-4">
                <!-- Generated dynamically -->
              </div>
            </div>

            <!-- Ultime Transazioni Recenti -->
            <div class="glass-card p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm">
              <div class="flex items-center justify-between mb-4">
                <h4 class="font-bold text-base flex items-center gap-2">
                  <i class="fa-solid fa-clock-rotate-left text-brand-500"></i>
                  Ultimi Movimenti
                </h4>
                <button onclick="switchTab('transactions')" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-indigo-400">Vedi Registro &rarr;</button>
              </div>
              <div id="dashboardRecentTx" class="space-y-3">
                <!-- Generated dynamically -->
              </div>
            </div>

          </div>
        </section>

        <!-- ============================================== -->
        <!-- TAB 2: TRANSAZIONI & MOVIMENTI                 -->
        <!-- ============================================== -->
        <section id="tab-transactions" class="space-y-6 hidden">
          <!-- Control Bar -->
          <div class="glass-card p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-stretch md:items-center">
            <!-- Search & Filters -->
            <div class="flex flex-wrap gap-2.5 flex-1 items-center">
              <div class="relative flex-1 min-w-[200px]">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" id="txSearchInput" oninput="renderTransactionsTable()" placeholder="Cerca descrizione, note..." class="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500">
              </div>

              <!-- Tipo Filter -->
              <select id="txFilterType" onchange="renderTransactionsTable()" class="px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="all">Tutti i tipi</option>
                <option value="income">Entrate</option>
                <option value="expense">Uscite</option>
              </select>

              <!-- Categoria Filter -->
              <select id="txFilterCategory" onchange="renderTransactionsTable()" class="px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="all">Tutte le categorie</option>
                <!-- Dynamically filled -->
              </select>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2">
              <button onclick="exportCSV()" class="px-3.5 py-2 text-sm font-medium border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition flex items-center gap-2 text-slate-700 dark:text-slate-300">
                <i class="fa-solid fa-file-csv text-emerald-500"></i>
                <span>CSV</span>
              </button>
              <button onclick="openModal('transactionModal')" class="px-4 py-2 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus"></i>
                <span>Aggiungi</span>
              </button>
            </div>
          </div>

          <!-- Table Container -->
          <div class="glass-card rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-sm">
                <thead class="bg-slate-100/60 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 font-semibold uppercase text-xs tracking-wider border-b border-slate-200 dark:border-slate-700">
                  <tr>
                    <th class="py-3.5 px-4">Data</th>
                    <th class="py-3.5 px-4">Descrizione</th>
                    <th class="py-3.5 px-4">Categoria</th>
                    <th class="py-3.5 px-4">Conto / Metodo</th>
                    <th class="py-3.5 px-4 text-right">Importo</th>
                    <th class="py-3.5 px-4 text-center">Azioni</th>
                  </tr>
                </thead>
                <tbody id="transactionsTableBody" class="divide-y divide-slate-100 dark:divide-slate-800">
                  <!-- Generated via JS -->
                </tbody>
              </table>
            </div>

            <!-- Empty State -->
            <div id="txEmptyState" class="hidden py-12 text-center text-slate-400">
              <i class="fa-solid fa-folder-open text-4xl mb-3 opacity-40"></i>
              <p class="font-medium">Nessuna transazione registrata.</p>
            </div>
          </div>
        </section>

        <!-- ============================================== -->
        <!-- TAB 3: SALVADANAIO & RISPARMI (VAULTS)         -->
        <!-- ============================================== -->
        <section id="tab-savings" class="space-y-6 hidden">
          <!-- Savings Header Overview -->
          <div class="glass-card p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6 bg-gradient-to-br from-indigo-50/50 via-white to-purple-50/50 dark:from-slate-800 dark:via-slate-800 dark:to-indigo-950/30">
            <div class="space-y-1">
              <h3 class="text-xl font-extrabold">Salvadanaio Digitale & Fondi Dedicati</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400 max-w-xl">
                Crea fondi separati dal conto principale per le tue riserve di liquidità, fondi di emergenza o progetti futuri. Trasferisci denaro con un click!
              </p>
            </div>
            <div class="flex gap-3">
              <button onclick="openModal('savingsVaultModal')" class="px-4 py-2.5 rounded-xl font-semibold text-sm bg-brand-600 hover:bg-brand-700 text-white shadow-md shadow-brand-500/20 transition flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Crea Salvadanaio</span>
              </button>
            </div>
          </div>

          <!-- Total Locked in Savings -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="glass-card p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
              <p class="text-xs uppercase tracking-wider font-semibold text-slate-400">Totale nei Salvadanai</p>
              <h2 id="vaultsTotalAmount" class="text-3xl font-extrabold text-brand-600 dark:text-brand-400 mt-2">€ 0,00</h2>
              <p class="text-xs text-slate-500 mt-1">Capitale al sicuro e segregato</p>
            </div>
            <div class="glass-card p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
              <p class="text-xs uppercase tracking-wider font-semibold text-slate-400">Fondo Emergenza</p>
              <h2 id="emergencyFundAmount" class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-2">€ 0,00</h2>
              <p class="text-xs text-slate-500 mt-1">Riserva di sicurezza consigliata</p>
            </div>
            <div class="glass-card p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/60">
              <p class="text-xs uppercase tracking-wider font-semibold text-slate-400">Regola 50/30/20 Raccomandata</p>
              <div class="flex items-center gap-2 mt-2">
                <span class="text-xs px-2 py-1 rounded bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-bold">20% Risparmio</span>
                <span class="text-xs px-2 py-1 rounded bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 font-bold">50% Bisogni</span>
              </div>
              <p class="text-xs text-slate-500 mt-2">Allocazione ideale per la stabilità</p>
            </div>
          </div>

          <!-- Vaults Grid -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="font-bold text-lg">I Tuoi Forzieri & Riserve</h4>
              <span id="vaultsCountDisplay" class="text-xs text-slate-400">0 forzieri attivi</span>
            </div>
            <div id="vaultsGridContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- Dynamically populated vaults -->
            </div>
          </div>
        </section>

        <!-- ============================================== -->
        <!-- TAB 4: TRAGUARDI & OBIETTIVI (GOALS)           -->
        <!-- ============================================== -->
        <section id="tab-goals" class="space-y-6 hidden">
          <!-- Goals Banner -->
          <div class="glass-card p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="space-y-1">
              <h3 class="text-xl font-extrabold">Traguardi di Vita e Finanziari</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400 max-w-xl">
                Definisci le tue tappe fondamentali: acquisto casa, vacanza dei sogni, automobile nuova o pensione anticipata. Monitora l'avanzamento in tempo reale!
              </p>
            </div>
            <button onclick="openModal('goalModal')" class="px-4 py-2.5 rounded-xl font-semibold text-sm bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white shadow-md shadow-amber-500/20 transition flex items-center gap-2">
              <i class="fa-solid fa-trophy"></i>
              <span>Nuovo Traguardo</span>
            </button>
          </div>

          <!-- Active Goals Grid -->
          <div id="goalsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Dynamically populated goals -->
          </div>
        </section>

        <!-- ============================================== -->
        <!-- TAB 5: REPORT ED ESPORTAZIONE PDF              -->
        <!-- ============================================== -->
        <section id="tab-reports" class="space-y-6 hidden">
          <!-- Report Generators Card -->
          <div class="glass-card p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm">
            <h3 class="text-xl font-extrabold mb-2">Centro Stampa & Creazione Report Ufficiali</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
              Scegli il modello di report che desideri esportare in formato PDF ad alta definizione pronto per la stampa, la dichiarazione o l'archiviazione contabile.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Report Completo -->
              <div class="p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-brand-500 transition cursor-pointer flex flex-col justify-between" onclick="prepareReport('complete')">
                <div class="space-y-2">
                  <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-500 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-file-invoice"></i>
                  </div>
                  <h4 class="font-bold text-base">Rendiconto Finanziario Completo</h4>
                  <p class="text-xs text-slate-500">Patrimonio, entrate, spese, forzieri e traguardi con dettaglio transazioni.</p>
                </div>
                <div class="mt-4 flex items-center text-xs font-semibold text-brand-600 gap-1">
                  <span>Visualizza & Esporta</span>
                  <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
              </div>

              <!-- Report Solo Movimenti -->
              <div class="p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-brand-500 transition cursor-pointer flex flex-col justify-between" onclick="prepareReport('transactions')">
                <div class="space-y-2">
                  <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-950 text-emerald-500 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-receipt"></i>
                  </div>
                  <h4 class="font-bold text-base">Estratto Conto Transazioni</h4>
                  <p class="text-xs text-slate-500">Tabella analitica con tutte le voci registrate, date, categorie e saldi parziali.</p>
                </div>
                <div class="mt-4 flex items-center text-xs font-semibold text-brand-600 gap-1">
                  <span>Visualizza & Esporta</span>
                  <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
              </div>

              <!-- Report Traguardi e Risparmio -->
              <div class="p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-brand-500 transition cursor-pointer flex flex-col justify-between" onclick="prepareReport('goals')">
                <div class="space-y-2">
                  <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-950 text-amber-500 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-bullseye"></i>
                  </div>
                  <h4 class="font-bold text-base">Piano Salvadanaio & Traguardi</h4>
                  <p class="text-xs text-slate-500">Stato avanzamento obiettivi futuri, proiezioni e somme accantonate.</p>
                </div>
                <div class="mt-4 flex items-center text-xs font-semibold text-brand-600 gap-1">
                  <span>Visualizza & Esporta</span>
                  <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Report Preview & Action Box -->
          <div class="glass-card p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700/60 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-700">
              <div>
                <h4 id="previewReportTitle" class="text-base font-bold text-slate-900 dark:text-white">Rendiconto Finanziario</h4>
                <p class="text-xs text-slate-400">Anteprima in tempo reale del documento pronto per la stampa o PDF</p>
              </div>
              <div class="flex items-center gap-2">
                <button onclick="downloadActivePDF()" class="px-4 py-2 rounded-xl text-xs font-semibold bg-brand-600 hover:bg-brand-700 text-white flex items-center gap-2 shadow-sm transition">
                  <i class="fa-solid fa-file-pdf"></i>
                  <span>Scarica PDF</span>
                </button>
                <button onclick="window.print()" class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center gap-2 transition">
                  <i class="fa-solid fa-print"></i>
                  <span>Stampa</span>
                </button>
              </div>
            </div>

            <!-- Printable Area Container -->
            <div class="bg-white text-slate-900 p-6 sm:p-10 rounded-xl shadow-inner border border-slate-200 max-w-4xl mx-auto overflow-x-auto" id="pdfPrintableArea">
              <!-- populated by prepareReport() -->
            </div>
          </div>

        </section>

      </main>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- MODALS                                         -->
  <!-- ============================================== -->

  <!-- Modal 1: Nuova Transazione -->
  <div id="transactionModal" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl relative">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-lg font-bold flex items-center gap-2">
          <i class="fa-solid fa-receipt text-brand-500"></i>
          <span>Nuovo Movimento</span>
        </h3>
        <button onclick="closeModal('transactionModal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>

      <form id="transactionForm" onsubmit="handleTransactionSubmit(event)" class="mt-4 space-y-4">
        <!-- Tipo Movimento: Entrata / Spesa -->
        <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 dark:bg-slate-900 rounded-xl">
          <label class="cursor-pointer">
            <input type="radio" name="txType" value="expense" checked class="peer hidden" onchange="updateCategoryOptions()">
            <div class="py-2 text-center text-xs font-bold rounded-lg peer-checked:bg-white dark:peer-checked:bg-slate-800 peer-checked:text-rose-600 peer-checked:shadow text-slate-500 transition">
              <i class="fa-solid fa-arrow-trend-down mr-1"></i> Uscita
            </div>
          </label>
          <label class="cursor-pointer">
            <input type="radio" name="txType" value="income" class="peer hidden" onchange="updateCategoryOptions()">
            <div class="py-2 text-center text-xs font-bold rounded-lg peer-checked:bg-white dark:peer-checked:bg-slate-800 peer-checked:text-emerald-600 peer-checked:shadow text-slate-500 transition">
              <i class="fa-solid fa-arrow-trend-up mr-1"></i> Entrata
            </div>
          </label>
        </div>

        <!-- Importo -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Importo (€)</label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold">€</span>
            <input type="number" step="0.01" min="0.01" required id="txAmount" placeholder="0.00" class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-brand-500">
          </div>
        </div>

        <!-- Descrizione -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Descrizione</label>
          <input type="text" required id="txTitle" placeholder="es. Stipendio, Spesa supermercato, Bolletta..." class="w-full px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
        </div>

        <!-- Categoria & Data -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Categoria</label>
            <select id="txCategory" required class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
              <!-- Dynamically populated -->
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Data</label>
            <input type="date" required id="txDate" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
          </div>
        </div>

        <!-- Metodo di pagamento -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Conto / Metodo</label>
          <select id="txAccount" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="Conto Principale">Conto Corrente Principale</option>
            <option value="Carta di Credito">Carta di Credito</option>
            <option value="Contanti">Contanti</option>
            <option value="Revolut/PayPal">Revolut / PayPal</option>
          </select>
        </div>

        <div class="pt-3 flex gap-3">
          <button type="button" onclick="closeModal('transactionModal')" class="w-1/2 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-700 transition">Annulla</button>
          <button type="submit" class="w-1/2 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm shadow-md transition">Salva</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal 2: Nuovo Traguardo -->
  <div id="goalModal" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl relative">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-lg font-bold flex items-center gap-2">
          <i class="fa-solid fa-trophy text-amber-500"></i>
          <span>Nuovo Traguardo</span>
        </h3>
        <button onclick="closeModal('goalModal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>

      <form id="goalForm" onsubmit="handleGoalSubmit(event)" class="mt-4 space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Nome Obiettivo</label>
          <input type="text" required id="goalName" placeholder="es. Vacanza Estiva, Auto Nuova, Acquisto Casa..." class="w-full px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Target (€)</label>
            <input type="number" step="1" min="1" required id="goalTarget" placeholder="1000" class="w-full px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Già Risparmiati (€)</label>
            <input type="number" step="1" min="0" id="goalCurrent" placeholder="0" class="w-full px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Data Target</label>
            <input type="date" required id="goalDeadline" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Icona</label>
            <select id="goalIcon" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
              <option value="fa-plane">✈️ Viaggi</option>
              <option value="fa-car">🚗 Auto</option>
              <option value="fa-house">🏠 Casa</option>
              <option value="fa-laptop">💻 Tecnologia</option>
              <option value="fa-graduation-cap">🎓 Studio / Formazione</option>
              <option value="fa-ring">💍 Matrimonio</option>
              <option value="fa-piggy-bank">🪙 Risparmio Libero</option>
            </select>
          </div>
        </div>

        <div class="pt-3 flex gap-3">
          <button type="button" onclick="closeModal('goalModal')" class="w-1/2 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-700 transition">Annulla</button>
          <button type="submit" class="w-1/2 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm shadow-md transition">Aggiungi</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal 3: Nuovo Salvadanaio/Vault -->
  <div id="savingsVaultModal" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl relative">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-lg font-bold flex items-center gap-2">
          <i class="fa-solid fa-piggy-bank text-indigo-500"></i>
          <span>Crea Salvadanaio</span>
        </h3>
        <button onclick="closeModal('savingsVaultModal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>

      <form id="vaultForm" onsubmit="handleVaultSubmit(event)" class="mt-4 space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Nome Salvadanaio</label>
          <input type="text" required id="vaultName" placeholder="es. Fondo Emergenza, Riserva Tasse..." class="w-full px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Importo Iniziale (€)</label>
          <input type="number" step="0.01" min="0" id="vaultAmount" placeholder="0.00" class="w-full px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Colore Identificativo</label>
          <select id="vaultColor" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="indigo">Indaco / Blu</option>
            <option value="emerald">Smeraldo / Verde</option>
            <option value="amber">Ambra / Oro</option>
            <option value="rose">Rosa / Rosso</option>
            <option value="purple">Viola / Magenta</option>
          </select>
        </div>

        <div class="pt-3 flex gap-3">
          <button type="button" onclick="closeModal('savingsVaultModal')" class="w-1/2 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-700 transition">Annulla</button>
          <button type="submit" class="w-1/2 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm shadow-md transition">Crea</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal 4: Versa/Preleva su Salvadanaio/Traguardo -->
  <div id="transferModal" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl w-full max-w-sm p-6 shadow-2xl relative">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
        <h3 id="transferModalTitle" class="text-base font-bold text-slate-900 dark:text-white">Modifica Saldo</h3>
        <button onclick="closeModal('transferModal')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="mt-4 space-y-4">
        <p id="transferItemName" class="text-sm text-slate-500 font-medium"></p>
        
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Importo (€)</label>
          <input type="number" step="0.01" min="0.01" id="transferAmount" placeholder="100.00" class="w-full px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>

        <div class="grid grid-cols-2 gap-3 pt-2">
          <button onclick="executeTransfer('deposit')" class="py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow">
            <i class="fa-solid fa-circle-plus"></i>
            <span>Deposita</span>
          </button>
          <button onclick="executeTransfer('withdraw')" class="py-2.5 px-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs transition flex items-center justify-center gap-1.5 shadow">
            <i class="fa-solid fa-circle-minus"></i>
            <span>Preleva</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Notification Toast Box -->
  <div id="toastContainer" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

  <!-- Custom Alert / Confirm Box Modal -->
  <div id="customAlertModal" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl w-full max-w-sm p-6 shadow-2xl text-center space-y-4">
      <div id="customAlertIcon" class="w-12 h-12 mx-auto rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-500 flex items-center justify-center text-xl">
        <i class="fa-solid fa-info"></i>
      </div>
      <h3 id="customAlertTitle" class="text-base font-bold text-slate-900 dark:text-white">Attenzione</h3>
      <p id="customAlertMessage" class="text-xs text-slate-500 dark:text-slate-400"></p>
      <div id="customAlertButtons" class="flex justify-center gap-2 pt-2">
        <!-- populated dynamically -->
      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- APPLICATION JAVASCRIPT LOGIC                   -->
  <!-- ============================================== -->
  <script>
    // ----------------------------------------------
    // Data Storage & Backend Sync
    // ----------------------------------------------
    const API_URL = 'api.php';
    const LOCAL_STORAGE_KEY = 'ALESSIO_BUDGET_CACHE_V2';

    const CATEGORIES = {
      income: ['Stipendio', 'Freelance / Extra', 'Investimenti / Dividendi', 'Rimborsi', 'Regali', 'Altro'],
      expense: ['Casa & Utenze', 'Alimentari & Spesa', 'Ristoranti & Svago', 'Trasporti & Auto', 'Salute & Benessere', 'Shopping', 'Abbonamenti', 'Viaggi', 'Istruzione', 'Altro']
    };

    const EMPTY_DATA = {
      transactions: [],
      vaults: [],
      goals: []
    };

    let appData = { ...EMPTY_DATA };
    let categoryChartInstance = null;
    let trendChartInstance = null;
    let currentTransferTarget = { type: null, id: null, name: '' };
    let activeReportType = 'complete';
    let isServerConnected = false;

    // ----------------------------------------------
    // Initialization & Storage
    // ----------------------------------------------
    async function initApp() {
      // 1. Carica prima la cache locale se esiste per visualizzazione istantanea
      const cached = localStorage.getItem(LOCAL_STORAGE_KEY);
      if (cached) {
        try {
          const parsed = JSON.parse(cached);
          if (parsed && typeof parsed === 'object') {
            appData = {
              transactions: Array.isArray(parsed.transactions) ? parsed.transactions : [],
              vaults: Array.isArray(parsed.vaults) ? parsed.vaults : [],
              goals: Array.isArray(parsed.goals) ? parsed.goals : []
            };
          }
        } catch (e) {
          console.warn('Cache locale non valida');
        }
      }

      // Imposta data odierna negli input
      const today = new Date().toISOString().split('T')[0];
      const txDateInput = document.getElementById('txDate');
      if (txDateInput) txDateInput.value = today;

      const dateOpts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      document.getElementById('currentDateDisplay').textContent = new Date().toLocaleDateString('it-IT', dateOpts);

      populateFilterCategories();
      updateCategoryOptions();

      // Render iniziale immediato
      renderDashboard();
      renderTransactionsTable();
      renderVaults();
      renderGoals();
      prepareReport('complete');

      setupEventListeners();

      // 2. Sincronizza con Backend PHP (SQLite)
      await syncFromBackend();
    }

    async function syncFromBackend() {
      try {
        const response = await fetch(`${API_URL}?action=get_all`);
        if (!response.ok) throw new Error(`HTTP error ${response.status}`);
        const result = await response.json();
        
        if (result.success && result.data) {
          isServerConnected = true;
          appData = {
            transactions: result.data.transactions || [],
            vaults: result.data.vaults || [],
            goals: result.data.goals || []
          };
          saveLocalCache();

          // Aggiorna badge motore database (SQLite / JSON)
          const badge = document.getElementById('backendBadge');
          if (badge) {
            badge.textContent = (result.data.engine === 'json') ? 'JSON Sync' : 'SQLite DB';
            badge.className = 'text-[10px] px-1.5 py-0.2 rounded font-semibold bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400';
          }

          renderDashboard();
          renderTransactionsTable();
          renderVaults();
          renderGoals();
          prepareReport(activeReportType);
        }
      } catch (err) {
        console.warn('Backend PHP non raggiungibile direttamente o ambiente statico. Utilizzo persistenza locale.', err);
        isServerConnected = false;
        const badge = document.getElementById('backendBadge');
        if (badge) {
          badge.textContent = 'Offline Cache';
          badge.className = 'text-[10px] px-1.5 py-0.2 rounded font-semibold bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400';
        }
      }
    }

    function saveLocalCache() {
      localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(appData));
    }

    // ----------------------------------------------
    // JSON Export & Import Logic
    // ----------------------------------------------
    async function exportJSON() {
      if (isServerConnected) {
        try {
          const res = await fetch(`${API_URL}?action=export_data`);
          const json = await res.json();
          if (json.success) {
            downloadJsonFile(json.data);
            showToast('Backup JSON esportato dal database!', 'success');
            return;
          }
        } catch (e) {
          console.warn('Errore export da server, fallback locale', e);
        }
      }

      // Fallback export locale
      const exportObject = {
        app: "Alessio budget",
        version: "2.0",
        exportDate: new Date().toISOString(),
        data: appData
      };
      downloadJsonFile(exportObject);
      showToast('Backup JSON esportato con successo!', 'success');
    }

    function downloadJsonFile(obj) {
      const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(obj, null, 2));
      const downloadAnchor = document.createElement('a');
      const filename = `Alessio_Budget_Backup_${new Date().toISOString().split('T')[0]}.json`;
      downloadAnchor.setAttribute("href", dataStr);
      downloadAnchor.setAttribute("download", filename);
      document.body.appendChild(downloadAnchor);
      downloadAnchor.click();
      downloadAnchor.remove();
    }

    function importJSON(event) {
      const file = event.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = async function(e) {
        try {
          const parsed = JSON.parse(e.target.result);
          const importedData = parsed.data || parsed;

          if (
            importedData &&
            Array.isArray(importedData.transactions) &&
            Array.isArray(importedData.vaults) &&
            Array.isArray(importedData.goals)
          ) {
            if (isServerConnected) {
              const res = await fetch(`${API_URL}?action=import_data`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ data: importedData })
              });
              const resJson = await res.json();
              if (!resJson.success) throw new Error(resJson.error || 'Errore import server');
            }

            appData = {
              transactions: importedData.transactions,
              vaults: importedData.vaults,
              goals: importedData.goals
            };
            saveLocalCache();

            renderDashboard();
            renderTransactionsTable();
            renderVaults();
            renderGoals();
            prepareReport(activeReportType);
            showToast('Dati importati e sincronizzati con successo!', 'success');
          } else {
            showToast('Formato JSON non riconosciuto o non valido.', 'error');
          }
        } catch (err) {
          console.error(err);
          showToast('Errore durante l\'importazione del file JSON: ' + err.message, 'error');
        }
        event.target.value = '';
      };
      reader.readAsText(file);
    }

    // ----------------------------------------------
    // Theme Management
    // ----------------------------------------------
    function initTheme() {
      const isDark = localStorage.getItem('THEME_MODE') === 'dark' || 
        (!('THEME_MODE' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
      
      applyTheme(isDark);

      document.getElementById('themeToggle').addEventListener('click', () => {
        const currentlyDark = document.documentElement.classList.contains('dark');
        applyTheme(!currentlyDark);
      });

      // Shortcut Ctrl+D o Cmd+D per toggle tema scuro
      window.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'd') {
          e.preventDefault();
          const currentlyDark = document.documentElement.classList.contains('dark');
          applyTheme(!currentlyDark);
        }
      });
    }

    function applyTheme(isDark) {
      const icon = document.getElementById('themeIcon');
      const text = document.getElementById('themeText');
      if (isDark) {
        document.documentElement.classList.add('dark');
        icon.className = 'fa-solid fa-sun';
        text.textContent = 'Tema Chiaro';
        localStorage.setItem('THEME_MODE', 'dark');
      } else {
        document.documentElement.classList.remove('dark');
        icon.className = 'fa-solid fa-moon';
        text.textContent = 'Tema Scuro';
        localStorage.setItem('THEME_MODE', 'light');
      }

      if (categoryChartInstance && trendChartInstance) {
        updateCharts();
      }
    }

    // ----------------------------------------------
    // Navigation / Tabs
    // ----------------------------------------------
    function switchTab(tabId) {
      const tabs = ['dashboard', 'transactions', 'savings', 'goals', 'reports'];
      tabs.forEach(t => {
        const sec = document.getElementById(`tab-${t}`);
        const nav = document.getElementById(`nav-${t}`);
        if (!sec || !nav) return;
        if (t === tabId) {
          sec.classList.remove('hidden');
          sec.classList.add('block');
          nav.classList.add('bg-brand-500/10', 'text-brand-600', 'dark:text-indigo-400');
          nav.classList.remove('text-slate-600', 'dark:text-slate-300');
        } else {
          sec.classList.remove('block');
          sec.classList.add('hidden');
          nav.classList.remove('bg-brand-500/10', 'text-brand-600', 'dark:text-indigo-400');
          nav.classList.add('text-slate-600', 'dark:text-slate-300');
        }
      });

      const titles = {
        dashboard: 'Panoramica Finanziaria',
        transactions: 'Registro Entrate e Spese',
        savings: 'Salvadanaio & Risparmi Dedicati',
        goals: 'Traguardi & Obiettivi di Risparmio',
        reports: 'Report Contabili ed Esportazione PDF'
      };
      document.getElementById('sectionTitle').textContent = titles[tabId] || 'Alessio budget';

      // Chiudi sidebar su mobile
      document.getElementById('sidebar').classList.add('-translate-x-full');
      document.getElementById('sidebarOverlay').classList.add('hidden');

      if (tabId === 'dashboard') {
        setTimeout(() => updateCharts(), 50);
      } else if (tabId === 'reports') {
        prepareReport(activeReportType);
      }
    }

    function setupEventListeners() {
      const openBtn = document.getElementById('openSidebarBtn');
      const closeBtn = document.getElementById('closeSidebarBtn');
      const overlay = document.getElementById('sidebarOverlay');
      const sidebar = document.getElementById('sidebar');

      openBtn.addEventListener('click', () => {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
      });

      const closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
      };

      closeBtn.addEventListener('click', closeSidebar);
      overlay.addEventListener('click', closeSidebar);
    }

    // ----------------------------------------------
    // Calculations & Statistics
    // ----------------------------------------------
    function calculateStats() {
      const now = new Date();
      const currentMonth = now.getMonth();
      const currentYear = now.getFullYear();

      let totalIncomeAll = 0;
      let totalExpenseAll = 0;
      let currentMonthIncome = 0;
      let currentMonthExpense = 0;

      appData.transactions.forEach(t => {
        const d = new Date(t.date);
        const amt = parseFloat(t.amount) || 0;
        if (t.type === 'income') {
          totalIncomeAll += amt;
          if (d.getMonth() === currentMonth && d.getFullYear() === currentYear) {
            currentMonthIncome += amt;
          }
        } else {
          totalExpenseAll += amt;
          if (d.getMonth() === currentMonth && d.getFullYear() === currentYear) {
            currentMonthExpense += amt;
          }
        }
      });

      const totalVaults = appData.vaults.reduce((acc, v) => acc + (parseFloat(v.amount) || 0), 0);
      const emergencyVault = appData.vaults.find(v => v.isEmergency || v.is_emergency);
      const emergencyTotal = emergencyVault ? parseFloat(emergencyVault.amount) : 0;

      const netLiquid = totalIncomeAll - totalExpenseAll;
      const netTotal = netLiquid + totalVaults;

      let savingsRate = 0;
      if (currentMonthIncome > 0) {
        const netMonth = currentMonthIncome - currentMonthExpense;
        savingsRate = Math.max(0, Math.round((netMonth / currentMonthIncome) * 100));
      }

      return {
        netLiquid,
        netTotal,
        currentMonthIncome,
        currentMonthExpense,
        totalVaults,
        emergencyTotal,
        savingsRate
      };
    }

    // ----------------------------------------------
    // Render Functions: Dashboard
    // ----------------------------------------------
    function renderDashboard() {
      const stats = calculateStats();

      document.getElementById('statTotalBalance').textContent = formatCurrency(stats.netTotal);
      document.getElementById('statMonthlyIncome').textContent = formatCurrency(stats.currentMonthIncome);
      document.getElementById('statMonthlyExpense').textContent = formatCurrency(stats.currentMonthExpense);
      document.getElementById('statTotalSavings').textContent = formatCurrency(stats.totalVaults);
      document.getElementById('statSavingsRate').innerHTML = `<i class="fa-solid fa-arrow-up-right-dots mr-1"></i>${stats.savingsRate}%`;
      document.getElementById('statGoalsCount').textContent = `${appData.goals.length} traguardi`;
      document.getElementById('badge-tx-count').textContent = appData.transactions.length;

      // Movimenti recenti
      const recentContainer = document.getElementById('dashboardRecentTx');
      const sortedTx = [...appData.transactions].sort((a, b) => new Date(b.date) - new Date(a.date)).slice(0, 5);

      if (sortedTx.length === 0) {
        recentContainer.innerHTML = `<p class="text-xs text-slate-400 py-6 text-center">Nessun movimento recente registrato.</p>`;
      } else {
        recentContainer.innerHTML = sortedTx.map(t => {
          const isInc = t.type === 'income';
          return `
            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100/70 dark:hover:bg-slate-700/40 transition">
              <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center ${isInc ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-600' : 'bg-rose-100 dark:bg-rose-950 text-rose-600'}">
                  <i class="fa-solid ${isInc ? 'fa-arrow-down-left' : 'fa-arrow-up-right'} text-sm"></i>
                </div>
                <div>
                  <p class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-100 line-clamp-1">${escapeHtml(t.title)}</p>
                  <p class="text-[11px] text-slate-400">${escapeHtml(t.category)} &bull; ${formatDate(t.date)}</p>
                </div>
              </div>
              <div class="text-right font-bold text-xs sm:text-sm ${isInc ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-200'}">
                ${isInc ? '+' : '-'}${formatCurrency(t.amount)}
              </div>
            </div>
          `;
        }).join('');
      }

      // Anteprima traguardi
      const goalsPreview = document.getElementById('dashboardGoalsPreview');
      if (appData.goals.length === 0) {
        goalsPreview.innerHTML = `
          <div class="text-center py-6">
            <p class="text-xs text-slate-400 mb-2">Non hai ancora impostato alcun traguardo di vita.</p>
            <button onclick="openModal('goalModal')" class="text-xs font-semibold text-brand-600 dark:text-indigo-400 hover:underline">+ Aggiungi il tuo primo obiettivo</button>
          </div>
        `;
      } else {
        goalsPreview.innerHTML = appData.goals.slice(0, 3).map(g => {
          const pct = Math.min(100, Math.round((g.current / g.target) * 100));
          return `
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 space-y-2">
              <div class="flex items-center justify-between text-xs font-semibold">
                <span class="flex items-center gap-2">
                  <i class="fa-solid ${g.icon || 'fa-piggy-bank'} text-amber-500"></i>
                  ${escapeHtml(g.name)}
                </span>
                <span class="text-brand-600 dark:text-indigo-400 font-bold">${pct}%</span>
              </div>
              <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                <div class="bg-gradient-to-r from-amber-400 to-brand-600 h-full rounded-full transition-all duration-500" style="width: ${pct}%"></div>
              </div>
              <div class="flex justify-between items-center text-[11px] text-slate-400">
                <span>${formatCurrency(g.current)} di ${formatCurrency(g.target)}</span>
                <span>Scadenza: ${formatDate(g.deadline)}</span>
              </div>
            </div>
          `;
        }).join('');
      }

      updateCharts();
    }

    // ----------------------------------------------
    // Chart.js Visualization
    // ----------------------------------------------
    function updateCharts() {
      const isDark = document.documentElement.classList.contains('dark');
      const textColor = isDark ? '#94a3b8' : '#64748b';
      const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';

      // 1. Doughnut: Spese per Categoria
      const expenseCatMap = {};
      appData.transactions.filter(t => t.type === 'expense').forEach(t => {
        expenseCatMap[t.category] = (expenseCatMap[t.category] || 0) + parseFloat(t.amount);
      });

      const catLabels = Object.keys(expenseCatMap);
      const catData = Object.values(expenseCatMap);

      const ctxCategory = document.getElementById('categoryChart').getContext('2d');
      if (categoryChartInstance) categoryChartInstance.destroy();

      if (catData.length === 0) {
        document.getElementById('topExpenseCategory').textContent = 'Nessuna spesa registrata.';
      } else {
        const topCat = Object.entries(expenseCatMap).sort((a, b) => b[1] - a[1])[0];
        document.getElementById('topExpenseCategory').innerHTML = `
          Prima voce di spesa: <strong class="text-slate-800 dark:text-slate-200">${escapeHtml(topCat[0])}</strong> con ${formatCurrency(topCat[1])}
        `;
      }

      categoryChartInstance = new Chart(ctxCategory, {
        type: 'doughnut',
        data: {
          labels: catLabels.length ? catLabels : ['Nessuna spesa'],
          datasets: [{
            data: catData.length ? catData : [1],
            backgroundColor: [
              '#6366f1', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', 
              '#8b5cf6', '#14b8a6', '#f97316', '#64748b', '#ef4444'
            ],
            borderWidth: 2,
            borderColor: isDark ? '#1e293b' : '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'right',
              labels: {
                color: textColor,
                boxWidth: 10,
                font: { size: 11, family: "'Plus Jakarta Sans', sans-serif" }
              }
            }
          },
          cutout: '68%'
        }
      });

      // 2. Bar Chart: Entrate vs Spese ultimi 6 Mesi
      const months = [];
      const now = new Date();
      for (let i = 5; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        months.push({
          label: d.toLocaleDateString('it-IT', { month: 'short' }),
          year: d.getFullYear(),
          month: d.getMonth()
        });
      }

      const incPerMonth = [0, 0, 0, 0, 0, 0];
      const expPerMonth = [0, 0, 0, 0, 0, 0];

      appData.transactions.forEach(t => {
        const txDate = new Date(t.date);
        months.forEach((m, idx) => {
          if (txDate.getMonth() === m.month && txDate.getFullYear() === m.year) {
            if (t.type === 'income') incPerMonth[idx] += parseFloat(t.amount);
            else expPerMonth[idx] += parseFloat(t.amount);
          }
        });
      });

      const totalPeriodSaved = incPerMonth.reduce((a, b) => a + b, 0) - expPerMonth.reduce((a, b) => a + b, 0);
      document.getElementById('periodNetSavings').textContent = formatCurrency(totalPeriodSaved);

      const ctxTrend = document.getElementById('trendChart').getContext('2d');
      if (trendChartInstance) trendChartInstance.destroy();

      trendChartInstance = new Chart(ctxTrend, {
        type: 'bar',
        data: {
          labels: months.map(m => m.label.toUpperCase()),
          datasets: [
            {
              label: 'Entrate',
              data: incPerMonth,
              backgroundColor: '#10b981',
              borderRadius: 6,
              barPercentage: 0.6
            },
            {
              label: 'Uscite',
              data: expPerMonth,
              backgroundColor: '#f43f5e',
              borderRadius: 6,
              barPercentage: 0.6
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: textColor, font: { family: "'Plus Jakarta Sans', sans-serif" } }
            },
            y: {
              grid: { color: gridColor },
              ticks: {
                color: textColor,
                font: { family: "'Plus Jakarta Sans', sans-serif" },
                callback: (val) => '€ ' + val
              }
            }
          }
        }
      });
    }

    // ----------------------------------------------
    // Render Functions: Transactions Table
    // ----------------------------------------------
    function populateFilterCategories() {
      const select = document.getElementById('txFilterCategory');
      const allCats = [...new Set([...CATEGORIES.income, ...CATEGORIES.expense])];
      select.innerHTML = '<option value="all">Tutte le categorie</option>' + 
        allCats.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
    }

    function renderTransactionsTable() {
      const searchTerm = (document.getElementById('txSearchInput').value || '').toLowerCase();
      const filterType = document.getElementById('txFilterType').value;
      const filterCategory = document.getElementById('txFilterCategory').value;

      const filtered = appData.transactions.filter(t => {
        const matchesSearch = t.title.toLowerCase().includes(searchTerm) || 
                              t.category.toLowerCase().includes(searchTerm) || 
                              (t.account && t.account.toLowerCase().includes(searchTerm));
        const matchesType = filterType === 'all' || t.type === filterType;
        const matchesCat = filterCategory === 'all' || t.category === filterCategory;
        return matchesSearch && matchesType && matchesCat;
      });

      filtered.sort((a, b) => new Date(b.date) - new Date(a.date));

      const tbody = document.getElementById('transactionsTableBody');
      const emptyState = document.getElementById('txEmptyState');

      if (filtered.length === 0) {
        tbody.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
      }

      emptyState.classList.add('hidden');
      tbody.innerHTML = filtered.map(t => {
        const isInc = t.type === 'income';
        return `
          <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
            <td class="py-3 px-4 text-xs text-slate-500 whitespace-nowrap">${formatDate(t.date)}</td>
            <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-100">
              ${escapeHtml(t.title)}
            </td>
            <td class="py-3 px-4">
              <span class="inline-flex items-center text-xs px-2.5 py-1 rounded-full ${isInc ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400' : 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400'}">
                ${escapeHtml(t.category)}
              </span>
            </td>
            <td class="py-3 px-4 text-xs text-slate-500">${escapeHtml(t.account || 'Conto Principale')}</td>
            <td class="py-3 px-4 text-right font-extrabold text-sm whitespace-nowrap ${isInc ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-800 dark:text-slate-100'}">
              ${isInc ? '+' : '-'}${formatCurrency(t.amount)}
            </td>
            <td class="py-3 px-4 text-center whitespace-nowrap">
              <button onclick="deleteTransaction('${t.id}')" class="text-slate-400 hover:text-rose-500 transition p-1" title="Elimina Movimento">
                <i class="fa-regular fa-trash-can"></i>
              </button>
            </td>
          </tr>
        `;
      }).join('');
    }

    // ----------------------------------------------
    // Render Functions: Savings Vaults
    // ----------------------------------------------
    function renderVaults() {
      const container = document.getElementById('vaultsGridContainer');
      const totalAmount = appData.vaults.reduce((acc, v) => acc + parseFloat(v.amount || 0), 0);
      const emergency = appData.vaults.find(v => v.isEmergency || v.is_emergency);

      document.getElementById('vaultsTotalAmount').textContent = formatCurrency(totalAmount);
      document.getElementById('emergencyFundAmount').textContent = emergency ? formatCurrency(emergency.amount) : '€ 0,00';
      document.getElementById('vaultsCountDisplay').textContent = `${appData.vaults.length} forzieri attivi`;

      if (appData.vaults.length === 0) {
        container.innerHTML = `
          <div class="col-span-full py-12 text-center text-slate-400">
            <i class="fa-solid fa-piggy-bank text-4xl mb-3 opacity-40"></i>
            <p class="font-medium">Nessun salvadanaio creato.</p>
            <button onclick="openModal('savingsVaultModal')" class="mt-2 text-xs font-semibold text-brand-600 underline">Crea il tuo primo forziere</button>
          </div>
        `;
        return;
      }

      const colorMap = {
        emerald: { bg: 'bg-emerald-50 dark:bg-emerald-950/40', text: 'text-emerald-600 dark:text-emerald-400', border: 'border-emerald-200 dark:border-emerald-800/50' },
        amber: { bg: 'bg-amber-50 dark:bg-amber-950/40', text: 'text-amber-600 dark:text-amber-400', border: 'border-amber-200 dark:border-amber-800/50' },
        indigo: { bg: 'bg-indigo-50 dark:bg-indigo-950/40', text: 'text-indigo-600 dark:text-indigo-400', border: 'border-indigo-200 dark:border-indigo-800/50' },
        rose: { bg: 'bg-rose-50 dark:bg-rose-950/40', text: 'text-rose-600 dark:text-rose-400', border: 'border-rose-200 dark:border-rose-800/50' },
        purple: { bg: 'bg-purple-50 dark:bg-purple-950/40', text: 'text-purple-600 dark:text-purple-400', border: 'border-purple-200 dark:border-purple-800/50' }
      };

      container.innerHTML = appData.vaults.map(v => {
        const c = colorMap[v.color] || colorMap.indigo;
        const isEm = v.isEmergency || v.is_emergency;
        return `
          <div class="glass-card p-6 rounded-2xl border ${c.border} shadow-sm flex flex-col justify-between relative overflow-hidden group">
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="w-10 h-10 rounded-xl ${c.bg} ${c.text} flex items-center justify-center text-lg">
                  <i class="fa-solid ${isEm ? 'fa-shield-heart' : 'fa-vault'}"></i>
                </span>
                <div class="flex items-center gap-1">
                  <button onclick="openTransferModal('vault', '${v.id}', '${escapeHtml(v.name)}')" class="p-2 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-slate-700 dark:text-slate-200 transition" title="Deposita o Preleva">
                    <i class="fa-solid fa-arrows-up-down mr-1"></i>Gestisci
                  </button>
                  ${!isEm ? `
                    <button onclick="deleteVault('${v.id}')" class="p-2 text-slate-400 hover:text-rose-500 transition" title="Elimina">
                      <i class="fa-regular fa-trash-can"></i>
                    </button>
                  ` : ''}
                </div>
              </div>
              <div>
                <h4 class="font-extrabold text-base text-slate-800 dark:text-slate-100">${escapeHtml(v.name)}</h4>
                <p class="text-xs text-slate-400">${isEm ? 'Protezione Inconvenienti & Serenità' : 'Fondo Risparmio Dedicato'}</p>
              </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/50 flex items-baseline justify-between">
              <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Saldo Accantonato</span>
              <span class="text-2xl font-black ${c.text}">${formatCurrency(v.amount)}</span>
            </div>
          </div>
        `;
      }).join('');
    }

    // ----------------------------------------------
    // Render Functions: Goals
    // ----------------------------------------------
    function renderGoals() {
      const container = document.getElementById('goalsContainer');

      if (appData.goals.length === 0) {
        container.innerHTML = `
          <div class="col-span-full py-12 text-center text-slate-400">
            <i class="fa-solid fa-bullseye text-4xl mb-3 opacity-40"></i>
            <p class="font-medium">Nessun traguardo attivo al momento.</p>
            <button onclick="openModal('goalModal')" class="mt-2 text-xs font-semibold text-amber-500 underline">Fissa un nuovo traguardo</button>
          </div>
        `;
        return;
      }

      container.innerHTML = appData.goals.map(g => {
        const pct = Math.min(100, Math.round((g.current / g.target) * 100));
        const remaining = Math.max(0, g.target - g.current);
        const isCompleted = g.current >= g.target;

        return `
          <div class="glass-card p-6 rounded-2xl border ${isCompleted ? 'border-emerald-300 dark:border-emerald-700/80 bg-emerald-50/20' : 'border-slate-200/80 dark:border-slate-700/60'} shadow-sm flex flex-col justify-between space-y-4">
            <div class="flex items-start justify-between">
              <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-2xl ${isCompleted ? 'bg-emerald-500 text-white' : 'bg-amber-100 dark:bg-amber-950/60 text-amber-600'} flex items-center justify-center text-xl shadow-sm">
                  <i class="fa-solid ${isCompleted ? 'fa-check' : (g.icon || 'fa-trophy')}"></i>
                </div>
                <div>
                  <h4 class="font-bold text-base text-slate-800 dark:text-slate-100 line-clamp-1">${escapeHtml(g.name)}</h4>
                  <p class="text-xs text-slate-400">Scadenza: ${formatDate(g.deadline)}</p>
                </div>
              </div>
              <button onclick="deleteGoal('${g.id}')" class="text-slate-400 hover:text-rose-500 transition p-1">
                <i class="fa-regular fa-trash-can text-sm"></i>
              </button>
            </div>

            <!-- Progress section -->
            <div class="space-y-2">
              <div class="flex justify-between items-center text-xs font-semibold">
                <span class="${isCompleted ? 'text-emerald-600 font-bold' : 'text-slate-500'}">
                  ${isCompleted ? 'Completato con successo! 🎉' : `Mancano ${formatCurrency(remaining)}`}
                </span>
                <span class="font-bold text-sm ${isCompleted ? 'text-emerald-600' : 'text-amber-600'}">${pct}%</span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-700 h-3 rounded-full overflow-hidden p-0.5">
                <div class="h-full rounded-full transition-all duration-700 ${isCompleted ? 'bg-emerald-500' : 'bg-gradient-to-r from-amber-400 to-amber-600'}" style="width: ${pct}%"></div>
              </div>
              <div class="flex justify-between items-center text-xs text-slate-400">
                <span>Risparmiati: <strong class="text-slate-800 dark:text-slate-200">${formatCurrency(g.current)}</strong></span>
                <span>Obiettivo: <strong>${formatCurrency(g.target)}</strong></span>
              </div>
            </div>

            <!-- Action buttons -->
            <div class="pt-2 flex gap-2">
              <button onclick="openTransferModal('goal', '${g.id}', '${escapeHtml(g.name)}')" class="w-full py-2 px-3 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 text-xs font-semibold transition flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-coins text-amber-500"></i>
                <span>Aggiungi Fondi al Traguardo</span>
              </button>
            </div>
          </div>
        `;
      }).join('');
    }

    // ----------------------------------------------
    // Form Handlers & CRUD Actions (with SQLite API)
    // ----------------------------------------------
    function updateCategoryOptions() {
      const checkedRadio = document.querySelector('input[name="txType"]:checked');
      const type = checkedRadio ? checkedRadio.value : 'expense';
      const select = document.getElementById('txCategory');
      const list = CATEGORIES[type] || [];
      select.innerHTML = list.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
    }

    async function handleTransactionSubmit(e) {
      e.preventDefault();
      const type = document.querySelector('input[name="txType"]:checked').value;
      const title = document.getElementById('txTitle').value.trim();
      const amount = parseFloat(document.getElementById('txAmount').value);
      const category = document.getElementById('txCategory').value;
      const date = document.getElementById('txDate').value;
      const account = document.getElementById('txAccount').value;

      if (!title || isNaN(amount) || amount <= 0 || !date) {
        showToast('Compila tutti i campi con valori validi.', 'error');
        return;
      }

      const newTx = {
        id: 'tx-' + Date.now(),
        type,
        title,
        amount,
        category,
        date,
        account
      };

      if (isServerConnected) {
        try {
          const res = await fetch(`${API_URL}?action=save_transaction`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newTx)
          });
          const resJson = await res.json();
          if (!resJson.success) throw new Error(resJson.error);
        } catch (err) {
          console.warn('Errore salvataggio server, salvo in locale', err);
        }
      }

      appData.transactions.unshift(newTx);
      saveLocalCache();
      closeModal('transactionModal');
      document.getElementById('transactionForm').reset();
      updateCategoryOptions();

      renderDashboard();
      renderTransactionsTable();
      prepareReport(activeReportType);
      showToast('Movimento registrato con successo!', 'success');
    }

    function deleteTransaction(id) {
      customConfirm('Sei sicuro di voler eliminare questo movimento?', async () => {
        if (isServerConnected) {
          try {
            await fetch(`${API_URL}?action=delete_transaction`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id })
            });
          } catch (e) {
            console.warn(e);
          }
        }
        appData.transactions = appData.transactions.filter(t => t.id !== id);
        saveLocalCache();
        renderDashboard();
        renderTransactionsTable();
        prepareReport(activeReportType);
        showToast('Movimento eliminato.', 'info');
      });
    }

    async function handleGoalSubmit(e) {
      e.preventDefault();
      const name = document.getElementById('goalName').value.trim();
      const target = parseFloat(document.getElementById('goalTarget').value);
      const current = parseFloat(document.getElementById('goalCurrent').value) || 0;
      const deadline = document.getElementById('goalDeadline').value;
      const icon = document.getElementById('goalIcon').value;

      if (!name || isNaN(target) || target <= 0 || !deadline) {
        showToast('Compila correttamente i campi dell\'obiettivo.', 'error');
        return;
      }

      const newGoal = {
        id: 'g-' + Date.now(),
        name,
        target,
        current,
        deadline,
        icon
      };

      if (isServerConnected) {
        try {
          await fetch(`${API_URL}?action=save_goal`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newGoal)
          });
        } catch (e) {
          console.warn(e);
        }
      }

      appData.goals.push(newGoal);
      saveLocalCache();
      closeModal('goalModal');
      document.getElementById('goalForm').reset();

      renderGoals();
      renderDashboard();
      prepareReport(activeReportType);
      showToast('Nuovo traguardo aggiunto!', 'success');
    }

    function deleteGoal(id) {
      customConfirm('Eliminare definitivamente questo traguardo?', async () => {
        if (isServerConnected) {
          try {
            await fetch(`${API_URL}?action=delete_goal`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id })
            });
          } catch (e) {
            console.warn(e);
          }
        }
        appData.goals = appData.goals.filter(g => g.id !== id);
        saveLocalCache();
        renderGoals();
        renderDashboard();
        prepareReport(activeReportType);
        showToast('Traguardo rimosso.', 'info');
      });
    }

    async function handleVaultSubmit(e) {
      e.preventDefault();
      const name = document.getElementById('vaultName').value.trim();
      const amount = parseFloat(document.getElementById('vaultAmount').value) || 0;
      const color = document.getElementById('vaultColor').value;

      if (!name) {
        showToast('Inserisci un nome per il salvadanaio.', 'error');
        return;
      }

      const newVault = {
        id: 'v-' + Date.now(),
        name,
        amount,
        color,
        isEmergency: false
      };

      if (isServerConnected) {
        try {
          await fetch(`${API_URL}?action=save_vault`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newVault)
          });
        } catch (e) {
          console.warn(e);
        }
      }

      appData.vaults.push(newVault);
      saveLocalCache();
      closeModal('savingsVaultModal');
      document.getElementById('vaultForm').reset();

      renderVaults();
      renderDashboard();
      prepareReport(activeReportType);
      showToast('Salvadanaio creato!', 'success');
    }

    function deleteVault(id) {
      customConfirm('Eliminare questo forziere e sbloccare i relativi fondi?', async () => {
        if (isServerConnected) {
          try {
            await fetch(`${API_URL}?action=delete_vault`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id })
            });
          } catch (e) {
            console.warn(e);
          }
        }
        appData.vaults = appData.vaults.filter(v => v.id !== id);
        saveLocalCache();
        renderVaults();
        renderDashboard();
        prepareReport(activeReportType);
        showToast('Forziere eliminato.', 'info');
      });
    }

    // Transfer Modal (Deposit / Withdraw)
    function openTransferModal(type, id, name) {
      currentTransferTarget = { type, id, name };
      document.getElementById('transferModalTitle').textContent = `Fondi per "${name}"`;
      document.getElementById('transferItemName').textContent = type === 'vault' ? 'Salvadanaio Digitale' : 'Traguardo Personale';
      document.getElementById('transferAmount').value = '';
      openModal('transferModal');
    }

    async function executeTransfer(action) {
      const amount = parseFloat(document.getElementById('transferAmount').value);
      if (isNaN(amount) || amount <= 0) {
        showToast('Inserisci un importo valido positivo.', 'error');
        return;
      }

      const { type, id, name } = currentTransferTarget;

      if (isServerConnected) {
        try {
          const res = await fetch(`${API_URL}?action=transfer`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              type,
              id,
              transferAction: action,
              amount
            })
          });
          const resJson = await res.json();
          if (!resJson.success) throw new Error(resJson.error);
        } catch (e) {
          showToast(e.message, 'error');
          return;
        }
      }

      if (type === 'vault') {
        const vault = appData.vaults.find(v => v.id === id);
        if (!vault) return;
        if (action === 'deposit') {
          vault.amount += amount;
          showToast(`Depositati ${formatCurrency(amount)} in ${name}`, 'success');
        } else {
          if (vault.amount < amount) {
            showToast('Fondi insufficienti nel forziere.', 'error');
            return;
          }
          vault.amount -= amount;
          showToast(`Prelevati ${formatCurrency(amount)} da ${name}`, 'info');
        }
        renderVaults();
      } else if (type === 'goal') {
        const goal = appData.goals.find(g => g.id === id);
        if (!goal) return;
        if (action === 'deposit') {
          goal.current += amount;
          showToast(`Aggiunti ${formatCurrency(amount)} al traguardo!`, 'success');
        } else {
          if (goal.current < amount) {
            showToast('L\'importo eccede il saldo accumulato.', 'error');
            return;
          }
          goal.current -= amount;
          showToast(`Stornati ${formatCurrency(amount)}`, 'info');
        }
        renderGoals();
      }

      saveLocalCache();
      renderDashboard();
      prepareReport(activeReportType);
      closeModal('transferModal');
    }

    // ----------------------------------------------
    // PDF Generation & Reporting
    // ----------------------------------------------
    function prepareReport(type) {
      activeReportType = type;
      const container = document.getElementById('pdfPrintableArea');
      const titleElem = document.getElementById('previewReportTitle');
      const stats = calculateStats();
      const todayStr = new Date().toLocaleDateString('it-IT', { year: 'numeric', month: 'long', day: 'numeric' });

      let contentHtml = '';

      if (type === 'complete') {
        titleElem.textContent = 'Rendiconto Finanziario Completo (Consolidato)';
        contentHtml = `
          <div class="border-b-2 border-indigo-600 pb-6 mb-6">
            <div class="flex justify-between items-start">
              <div>
                <h1 class="text-2xl font-black text-indigo-700 tracking-tight">Alessio budget &bull; Rendiconto Patrimoniale</h1>
                <p class="text-xs text-slate-500 font-medium">Documento Ufficiale di Sintesi Finanziaria Personale</p>
              </div>
              <div class="text-right text-xs text-slate-500">
                <p>Data Emissione: <strong>${todayStr}</strong></p>
                <p>Periodo: <strong>Esercizio Corrente</strong></p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
              <span class="text-[11px] font-bold text-slate-400 uppercase">Patrimonio Netto</span>
              <p class="text-xl font-extrabold text-slate-900 mt-1">${formatCurrency(stats.netTotal)}</p>
            </div>
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
              <span class="text-[11px] font-bold text-emerald-600 uppercase">Entrate del Mese</span>
              <p class="text-xl font-extrabold text-emerald-700 mt-1">${formatCurrency(stats.currentMonthIncome)}</p>
            </div>
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-lg">
              <span class="text-[11px] font-bold text-rose-600 uppercase">Uscite del Mese</span>
              <p class="text-xl font-extrabold text-rose-700 mt-1">${formatCurrency(stats.currentMonthExpense)}</p>
            </div>
          </div>

          <div class="mb-8">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-3 border-b pb-1">1. Ripartizione Fondi & Salvadanai Riservati</h3>
            <table class="w-full text-xs text-left">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400">
                  <th class="py-2">Forziere / Destinazione</th>
                  <th class="py-2">Tipologia</th>
                  <th class="py-2 text-right">Saldo Custodito</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                ${appData.vaults.length === 0 ? '<tr><td colspan="3" class="py-2 text-slate-400">Nessun forziere attivo</td></tr>' : appData.vaults.map(v => `
                  <tr>
                    <td class="py-2 font-semibold text-slate-800">${escapeHtml(v.name)}</td>
                    <td class="py-2 text-slate-500">${(v.isEmergency || v.is_emergency) ? 'Fondo Emergenza Primario' : 'Riserva Specifica'}</td>
                    <td class="py-2 text-right font-bold text-indigo-700">${formatCurrency(v.amount)}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>

          <div class="mb-8">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-3 border-b pb-1">2. Avanzamento Traguardi ed Obiettivi di Vita</h3>
            <table class="w-full text-xs text-left">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400">
                  <th class="py-2">Traguardo</th>
                  <th class="py-2">Scadenza</th>
                  <th class="py-2">Target</th>
                  <th class="py-2">Raccolto</th>
                  <th class="py-2 text-right">Completamento</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                ${appData.goals.length === 0 ? '<tr><td colspan="5" class="py-2 text-slate-400">Nessun traguardo attivo</td></tr>' : appData.goals.map(g => {
                  const pct = Math.min(100, Math.round((g.current / g.target) * 100));
                  return `
                    <tr>
                      <td class="py-2 font-semibold text-slate-800">${escapeHtml(g.name)}</td>
                      <td class="py-2 text-slate-500">${formatDate(g.deadline)}</td>
                      <td class="py-2 font-medium">${formatCurrency(g.target)}</td>
                      <td class="py-2 font-bold text-emerald-600">${formatCurrency(g.current)}</td>
                      <td class="py-2 text-right font-bold">${pct}%</td>
                    </tr>
                  `;
                }).join('')}
              </tbody>
            </table>
          </div>

          <div class="mb-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-3 border-b pb-1">3. Registro Sintetico Ultimi Movimenti</h3>
            <table class="w-full text-xs text-left">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400">
                  <th class="py-2">Data</th>
                  <th class="py-2">Descrizione</th>
                  <th class="py-2">Categoria</th>
                  <th class="py-2 text-right">Importo</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                ${appData.transactions.length === 0 ? '<tr><td colspan="4" class="py-2 text-slate-400">Nessun movimento</td></tr>' : appData.transactions.slice(0, 10).map(t => `
                  <tr>
                    <td class="py-2 text-slate-500">${formatDate(t.date)}</td>
                    <td class="py-2 font-semibold text-slate-800">${escapeHtml(t.title)}</td>
                    <td class="py-2 text-slate-500">${escapeHtml(t.category)}</td>
                    <td class="py-2 text-right font-bold ${t.type === 'income' ? 'text-emerald-600' : 'text-slate-800'}">
                      ${t.type === 'income' ? '+' : '-'}${formatCurrency(t.amount)}
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>

          <div class="mt-8 pt-4 border-t border-slate-200 flex justify-between items-center text-[11px] text-slate-400">
            <span>Certificato da Alessio budget &bull; Gestione Finanziaria Personale</span>
            <span>Pagina 1 di 1</span>
          </div>
        `;
      } else if (type === 'transactions') {
        titleElem.textContent = 'Estratto Conto Analitico Transazioni';
        contentHtml = `
          <div class="border-b-2 border-emerald-600 pb-6 mb-6">
            <div class="flex justify-between items-start">
              <div>
                <h1 class="text-2xl font-black text-emerald-800 tracking-tight">Estratto Conto Movimenti</h1>
                <p class="text-xs text-slate-500 font-medium">Dettaglio cronologico contabile</p>
              </div>
              <div class="text-right text-xs text-slate-500">
                <p>Data Estratto: <strong>${todayStr}</strong></p>
                <p>Movimenti Totali: <strong>${appData.transactions.length}</strong></p>
              </div>
            </div>
          </div>

          <table class="w-full text-xs text-left mb-6">
            <thead>
              <tr class="border-b-2 border-slate-300 text-slate-600 uppercase">
                <th class="py-2.5">Data</th>
                <th class="py-2.5">Descrizione</th>
                <th class="py-2.5">Conto</th>
                <th class="py-2.5">Categoria</th>
                <th class="py-2.5 text-right">Importo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              ${appData.transactions.length === 0 ? `
                <tr><td colspan="5" class="py-4 text-center text-slate-400">Nessun movimento presente</td></tr>
              ` : appData.transactions.map(t => `
                <tr>
                  <td class="py-2 font-mono text-slate-500">${formatDate(t.date)}</td>
                  <td class="py-2 font-semibold text-slate-900">${escapeHtml(t.title)}</td>
                  <td class="py-2 text-slate-500">${escapeHtml(t.account || 'Conto')}</td>
                  <td class="py-2 text-slate-600">${escapeHtml(t.category)}</td>
                  <td class="py-2 text-right font-bold ${t.type === 'income' ? 'text-emerald-600' : 'text-slate-900'}">
                    ${t.type === 'income' ? '+' : '-'}${formatCurrency(t.amount)}
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>

          <div class="mt-8 pt-4 border-t border-slate-200 flex justify-between items-center text-[11px] text-slate-400">
            <span>Rapporto contabile generato tramite Alessio budget</span>
            <span>Documento valido per audit personale</span>
          </div>
        `;
      } else if (type === 'goals') {
        titleElem.textContent = 'Dossier Traguardi di Vita e Piani di Accumulo';
        contentHtml = `
          <div class="border-b-2 border-amber-500 pb-6 mb-6">
            <div class="flex justify-between items-start">
              <div>
                <h1 class="text-2xl font-black text-amber-700 tracking-tight">Dossier Traguardi e Risparmio</h1>
                <p class="text-xs text-slate-500 font-medium">Piano strategico di accumulo capitale a medio/lungo termine</p>
              </div>
              <div class="text-right text-xs text-slate-500">
                <p>Data Stampa: <strong>${todayStr}</strong></p>
              </div>
            </div>
          </div>

          <div class="space-y-6 mb-8">
            ${appData.goals.length === 0 ? `
              <p class="text-center text-slate-400 text-xs py-4">Nessun traguardo registrato.</p>
            ` : appData.goals.map(g => {
              const pct = Math.min(100, Math.round((g.current / g.target) * 100));
              const remaining = Math.max(0, g.target - g.current);
              return `
                <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
                  <div class="flex justify-between items-center mb-2">
                    <h4 class="font-bold text-base text-slate-800">${escapeHtml(g.name)}</h4>
                    <span class="text-xs font-bold text-amber-600">${pct}% RAGGIUNTO</span>
                  </div>
                  <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden mb-3">
                    <div class="bg-amber-500 h-full rounded-full" style="width: ${pct}%"></div>
                  </div>
                  <div class="grid grid-cols-3 text-xs text-slate-600">
                    <div>Capitale Attuale: <strong>${formatCurrency(g.current)}</strong></div>
                    <div>Target Finale: <strong>${formatCurrency(g.target)}</strong></div>
                    <div>Differenza Residua: <strong class="text-rose-600">${formatCurrency(remaining)}</strong></div>
                  </div>
                  <p class="text-[11px] text-slate-400 mt-2">Data limite prefissata: ${formatDate(g.deadline)}</p>
                </div>
              `;
            }).join('')}
          </div>

          <div class="mt-8 pt-4 border-t border-slate-200 flex justify-between items-center text-[11px] text-slate-400">
            <span>Alessio budget &bull; Pianificazione Finanziaria Personale</span>
          </div>
        `;
      }

      container.innerHTML = contentHtml;
    }

    function downloadActivePDF() {
      const element = document.getElementById('pdfPrintableArea');
      const opt = {
        margin: [10, 10, 10, 10],
        filename: `Alessio_Budget_${activeReportType}_${new Date().toISOString().split('T')[0]}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };

      showToast('Generazione PDF in corso...', 'info');
      html2pdf().set(opt).from(element).save().then(() => {
        showToast('PDF scaricato con successo!', 'success');
      }).catch(err => {
        console.error('PDF error', err);
        showToast('Errore durante la creazione del PDF.', 'error');
      });
    }

    function quickPrintPDF() {
      switchTab('reports');
      prepareReport('complete');
      setTimeout(() => {
        downloadActivePDF();
      }, 400);
    }

    // CSV Export
    function exportCSV() {
      if (appData.transactions.length === 0) {
        showToast('Nessun movimento presente da esportare.', 'error');
        return;
      }

      let csv = 'Data;Tipo;Titolo;Categoria;Conto;Importo\n';
      appData.transactions.forEach(t => {
        csv += `"${t.date}";"${t.type === 'income' ? 'Entrata' : 'Uscita'}";"${t.title.replace(/"/g, '""')}";"${t.category}";"${t.account || ''}";"${t.amount}"\n`;
      });

      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', `Alessio_Budget_Movimenti_${new Date().toISOString().split('T')[0]}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      showToast('File CSV esportato!', 'success');
    }

    // ----------------------------------------------
    // Modal & Toast Helpers
    // ----------------------------------------------
    function openModal(id) {
      const modal = document.getElementById(id);
      if (modal) modal.classList.remove('hidden');
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      if (modal) modal.classList.add('hidden');
    }

    function showToast(message, type = 'info') {
      const container = document.getElementById('toastContainer');
      const toast = document.createElement('div');
      
      const colors = {
        success: 'bg-emerald-600 text-white',
        error: 'bg-rose-600 text-white',
        info: 'bg-slate-800 dark:bg-slate-700 text-white'
      };

      const icons = {
        success: 'fa-circle-check',
        error: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
      };

      toast.className = `flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-xl text-xs font-semibold ${colors[type] || colors.info} transform transition-all duration-300 pointer-events-auto`;
      toast.innerHTML = `<i class="fa-solid ${icons[type]} text-sm"></i><span>${escapeHtml(message)}</span>`;

      container.appendChild(toast);
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
      }, 3200);
    }

    function customConfirm(message, onConfirm) {
      const modal = document.getElementById('customAlertModal');
      document.getElementById('customAlertTitle').textContent = 'Conferma Operazione';
      document.getElementById('customAlertMessage').textContent = message;
      document.getElementById('customAlertIcon').innerHTML = '<i class="fa-solid fa-question text-indigo-500"></i>';

      const buttons = document.getElementById('customAlertButtons');
      buttons.innerHTML = `
        <button id="alertBtnCancel" class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition">Annulla</button>
        <button id="alertBtnConfirm" class="px-4 py-2 text-xs font-semibold rounded-xl bg-rose-600 text-white hover:bg-rose-700 transition">Conferma</button>
      `;

      modal.classList.remove('hidden');

      document.getElementById('alertBtnCancel').onclick = () => modal.classList.add('hidden');
      document.getElementById('alertBtnConfirm').onclick = () => {
        modal.classList.add('hidden');
        if (typeof onConfirm === 'function') onConfirm();
      };
    }

    function confirmClearAllData() {
      customConfirm('Vuoi davvero cancellare tutti i movimenti, forzieri e traguardi? L\'operazione non è reversibile (ricordati di esportare il JSON se desideri un backup).', async () => {
        if (isServerConnected) {
          try {
            await fetch(`${API_URL}?action=clear_all`, { method: 'POST' });
          } catch (e) {
            console.warn(e);
          }
        }
        appData = {
          transactions: [],
          vaults: [
            {
              id: 'v-emergency',
              name: 'Fondo Emergenza',
              amount: 0.0,
              color: 'emerald',
              isEmergency: true
            }
          ],
          goals: []
        };
        saveLocalCache();
        renderDashboard();
        renderTransactionsTable();
        renderVaults();
        renderGoals();
        prepareReport('complete');
        showToast('Tutti i dati sono stati azzerati.', 'info');
      });
    }

    // ----------------------------------------------
    // Formatters & Utilities
    // ----------------------------------------------
    function formatCurrency(val) {
      const num = parseFloat(val) || 0;
      return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(num);
    }

    function formatDate(dateStr) {
      if (!dateStr) return '';
      const parts = dateStr.split('-');
      if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
      }
      return dateStr;
    }

    function escapeHtml(string) {
      if (!string) return '';
      return String(string)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    // ----------------------------------------------
    // Run on Load
    // ----------------------------------------------
    window.onload = function() {
      initTheme();
      initApp();
    };
  </script>
</body>
</html>
