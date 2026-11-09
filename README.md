# 💰 Alessio Budget - Controllo Finanze Personali & Wealth Hub

Applicazione web moderna, fluida e robusta per la gestione completa delle finanze personali, budget, risparmi segregati (forzieri), traguardi di vita e rendicontazione contabile in PDF.

---

## ✨ Funzionalità Principali

1. **📊 Panoramica & Dashboard**:
   - Indicatori KPI in tempo reale: Patrimonio Netto, Entrate del Mese, Uscite del Mese, Totale Salvadanai e Tasso di Risparmio %.
   - **Grafico a Ciambella (Chart.js)**: Ripartizione percentuale delle spese per categoria con evidenziazione della prima voce di spesa.
   - **Grafico a Barre (Chart.js)**: Andamento entrate vs uscite degli ultimi 6 mesi con stima del risparmio netto cumulato.
   - Widget rapidi: Avanzamento traguardi prioritari e registro degli ultimi 5 movimenti.

2. **💳 Movimenti & Transazioni**:
   - Registrazione entrate e uscite con importo, causale, categoria, data e metodo di pagamento (Conto Corrente, Carta di Credito, Contanti, Revolut/PayPal).
   - Ricerca istantanea testuale (titolo, categoria, note).
   - Filtro per tipo (Tutti, Entrate, Uscite) e per categoria.
   - **Esportazione CSV** per fogli di calcolo Excel o Numbers.

3. **🪙 Salvadanaio Digitale & Fondi Riservati (Vaults)**:
   - Creazione forzieri segregati (Fondo Emergenza, Tasse, Riserva Liquidità, ecc.) con colori personalizzati.
   - Modalità **Gestione Fondi**: Deposita e preleva direttamente dal forziere con ricalcolo immediato del saldo.
   - Supporto per la regola finanziaria 50/30/20.

4. **🎯 Traguardi & Obiettivi di Vita (Goals)**:
   - Definizione target di accumulo (es. Viaggio, Acquisto Casa, Auto Nuova, Matrimonio, Formazione).
   - Barra di progressione percentuale, capitale mancante e data di scadenza target.
   - Versamento incrementale dedicato.

5. **📄 Centro Stampa & Report PDF Ufficiali**:
   - 3 modelli di report generati in alta definizione tramite `html2pdf.js`:
     - **Rendiconto Finanziario Completo (Consolidato)**
     - **Estratto Conto Transazioni**
     - **Piano Salvadanaio & Traguardi**
   - Anteprima in tempo reale e supporto alla stampa di sistema.

6. **🎨 Interfaccia Fluida & Responsive**:
   - Adattamento perfetto su smartphone, tablet e schermi desktop (Sidebar a cassetto touch-friendly).
   - **Tema Scuro / Chiaro**: Attivabile con un clic o con la combinazione da tastiera `Ctrl + D` / `Cmd + D`.
   - Notifiche toast animate e modali di conferma integrate (nessun popup nativo invadente).

7. **💾 Archiviazione Robusta (SQLite + Fallback JSON)**:
   - **SQLite PDO** con modalità WAL (*Write-Ahead Logging*) per massime performance e concorrenza.
   - **Fallback automatico a JSON atomico con file locking (`flock`)**: se per qualsiasi motivo l'estensione SQLite non è attiva nel server PHP, l'app passa in automatico a un file JSON sicuro senza mai rompersi.
   - **Backup & Ripristino**: 1-click **Esporta JSON** e 1-click **Importa JSON**.
   - Sicurezza: cartella `data/` protetta da `.htaccess` contro download esterni diretti.

---

## 🚀 Come Avviare l'Applicazione

### Opzione 1: Con server PHP nativo (Consigliato per produzione / hosting)
Se hai PHP installato sul tuo computer o utilizzi XAMPP / MAMP / Apache / hosting web:
```bash
# Avvio server di sviluppo integrato di PHP:
php -S localhost:8080
```
Poi apri il browser su: `http://localhost:8080`

### Opzione 2: Avvio immediato con `server.py` (Zero configurazione su macOS)
Poiché macOS include già Python 3 con SQLite integrato, puoi avviare subito l'applicazione eseguendo:
```bash
python3 server.py
```
Il server rileverà automaticamente se PHP è presente; in caso contrario, avvierà il backend SQLite direttamente tramite Python sulla porta `8080`.

---

## 📂 Struttura del Progetto

```
Budget_Manage/
├── api.php             # Endpoint REST API per tutte le operazioni CRUD
├── db.php              # Database layer (SQLite PDO con fallback a JSON con flock)
├── index.php           # Pagina principale dell'applicazione (versione PHP)
├── index.html          # Versione standalone utilizzabile anche offline
├── server.py           # Script di avvio automatico (PHP / Python SQLite)
├── data/               # Directory dei dati (protetta)
│   ├── .htaccess       # Blocco accessi HTTP diretti
│   └── budget.sqlite   # Database SQLite dell'applicazione
├── soldi.png           # Icona / Favicon
└── README.md           # Documentazione del progetto
```
