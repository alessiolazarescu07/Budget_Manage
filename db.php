<?php
declare(strict_types=1);

/**
 * Alessio Budget - Database Layer (SQLite PDO con Fallback JSON Atomico)
 * Garantisce massima affidabilità e portabilità su qualsiasi ambiente PHP.
 */

define('DATA_DIR', __DIR__ . '/data');
define('SQLITE_FILE', DATA_DIR . '/budget.sqlite');
define('JSON_FALLBACK_FILE', DATA_DIR . '/budget.json');
define('JSON_LOCK_FILE', DATA_DIR . '/.budget.lock');

class BudgetDB {
    private static ?BudgetDB $instance = null;
    private ?PDO $pdo = null;
    private bool $useJsonFallback = false;

    private function __construct() {
        if (!is_dir(DATA_DIR)) {
            mkdir(DATA_DIR, 0755, true);
        }

        // Verifica disponibilità estensione PDO SQLite
        if (extension_loaded('pdo') && in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            try {
                $this->pdo = new PDO('sqlite:' . SQLITE_FILE, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5
                ]);
                // Ottimizzazioni per prestazioni e concorrenza
                $this->pdo->exec('PRAGMA journal_mode = WAL;');
                $this->pdo->exec('PRAGMA synchronous = NORMAL;');
                $this->pdo->exec('PRAGMA foreign_keys = ON;');

                $this->initSqliteTables();
                return;
            } catch (Throwable $e) {
                error_log("Impossibile inizializzare SQLite PDO: " . $e->getMessage() . ". Attivazione fallback JSON.");
            }
        }

        // Se SQLite non è disponibile, usa persistenza JSON con locking
        $this->useJsonFallback = true;
        $this->initJsonStorage();
    }

    public static function getInstance(): BudgetDB {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isUsingJson(): bool {
        return $this->useJsonFallback;
    }

    /**
     * Inizializzazione schema SQLite
     */
    private function initSqliteTables(): void {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS transactions (
                id TEXT PRIMARY KEY,
                type TEXT NOT NULL CHECK(type IN ('income', 'expense')),
                title TEXT NOT NULL,
                amount REAL NOT NULL,
                category TEXT NOT NULL,
                date TEXT NOT NULL,
                account TEXT NOT NULL DEFAULT 'Conto Principale',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS vaults (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                amount REAL NOT NULL DEFAULT 0.0,
                color TEXT NOT NULL DEFAULT 'indigo',
                is_emergency INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS goals (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                target REAL NOT NULL,
                current REAL NOT NULL DEFAULT 0.0,
                deadline TEXT NOT NULL,
                icon TEXT NOT NULL DEFAULT 'fa-piggy-bank',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            );

            CREATE INDEX IF NOT EXISTS idx_tx_date ON transactions(date);
            CREATE INDEX IF NOT EXISTS idx_tx_type ON transactions(type);
        ");

        // Crea forziere "Fondo Emergenza" predefinito se la tabella vaults è vuota
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM vaults");
        if ((int)$stmt->fetchColumn() === 0) {
            $insert = $this->pdo->prepare("
                INSERT INTO vaults (id, name, amount, color, is_emergency)
                VALUES ('v-emergency', 'Fondo Emergenza', 0.0, 'emerald', 1)
            ");
            $insert->execute();
        }
    }

    /**
     * Inizializzazione schema fallback JSON
     */
    private function initJsonStorage(): void {
        if (!file_exists(JSON_FALLBACK_FILE)) {
            $initial = [
                'transactions' => [],
                'vaults' => [
                    [
                        'id' => 'v-emergency',
                        'name' => 'Fondo Emergenza',
                        'amount' => 0.0,
                        'color' => 'emerald',
                        'isEmergency' => true,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ],
                'goals' => [],
                'settings' => [
                    'theme' => 'light'
                ]
            ];
            file_put_contents(JSON_FALLBACK_FILE, json_encode($initial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Gestione atomica lettura/scrittura con lock su JSON
     */
    private function withJsonLock(callable $callback) {
        $lockFp = fopen(JSON_LOCK_FILE, 'c+');
        if (!$lockFp) {
            throw new RuntimeException("Impossibile aprire il file di blocco.");
        }
        flock($lockFp, LOCK_EX);

        try {
            $content = file_exists(JSON_FALLBACK_FILE) ? file_get_contents(JSON_FALLBACK_FILE) : '{}';
            $data = json_decode($content, true);
            if (!is_array($data)) {
                $data = ['transactions' => [], 'vaults' => [], 'goals' => [], 'settings' => []];
            }
            $result = $callback($data);
            return $result;
        } finally {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
    }

    private function writeJsonAtomic(array $data): void {
        $tmpFile = JSON_FALLBACK_FILE . '.' . uniqid('tmp_', true);
        file_put_contents($tmpFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        rename($tmpFile, JSON_FALLBACK_FILE);
    }

    // =========================================================================
    // API Metodi Pubblici
    // =========================================================================

    /**
     * Recupera tutti i dati dell'applicazione
     */
    public function getAllData(): array {
        if ($this->useJsonFallback) {
            return $this->withJsonLock(function(array $data) {
                return [
                    'transactions' => $data['transactions'] ?? [],
                    'vaults' => $data['vaults'] ?? [],
                    'goals' => $data['goals'] ?? [],
                    'settings' => $data['settings'] ?? []
                ];
            });
        }

        $txStmt = $this->pdo->query("SELECT * FROM transactions ORDER BY date DESC, created_at DESC");
        $transactions = $txStmt->fetchAll();
        foreach ($transactions as &$t) {
            $t['amount'] = (float)$t['amount'];
        }

        $vStmt = $this->pdo->query("SELECT * FROM vaults ORDER BY created_at ASC");
        $vaults = $vStmt->fetchAll();
        foreach ($vaults as &$v) {
            $v['amount'] = (float)$v['amount'];
            $v['isEmergency'] = ((int)$v['is_emergency']) === 1;
        }

        $gStmt = $this->pdo->query("SELECT * FROM goals ORDER BY deadline ASC");
        $goals = $gStmt->fetchAll();
        foreach ($goals as &$g) {
            $g['target'] = (float)$g['target'];
            $g['current'] = (float)$g['current'];
        }

        $sStmt = $this->pdo->query("SELECT key, value FROM settings");
        $settingsRows = $sStmt->fetchAll();
        $settings = [];
        foreach ($settingsRows as $row) {
            $settings[$row['key']] = $row['value'];
        }

        return [
            'transactions' => $transactions,
            'vaults' => $vaults,
            'goals' => $goals,
            'settings' => $settings
        ];
    }

    /**
     * Salva o aggiorna una transazione
     */
    public function saveTransaction(array $tx): array {
        $id = !empty($tx['id']) ? (string)$tx['id'] : 'tx-' . round(microtime(true) * 1000);
        $type = in_array($tx['type'] ?? '', ['income', 'expense'], true) ? $tx['type'] : 'expense';
        $title = trim((string)($tx['title'] ?? 'Senza Titolo'));
        $amount = abs((float)($tx['amount'] ?? 0));
        $category = trim((string)($tx['category'] ?? 'Altro'));
        $date = !empty($tx['date']) ? (string)$tx['date'] : date('Y-m-d');
        $account = trim((string)($tx['account'] ?? 'Conto Principale'));

        if ($this->useJsonFallback) {
            return $this->withJsonLock(function(array &$data) use ($id, $type, $title, $amount, $category, $date, $account) {
                if (!isset($data['transactions']) || !is_array($data['transactions'])) {
                    $data['transactions'] = [];
                }

                $record = [
                    'id' => $id,
                    'type' => $type,
                    'title' => $title,
                    'amount' => $amount,
                    'category' => $category,
                    'date' => $date,
                    'account' => $account,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $found = false;
                foreach ($data['transactions'] as $i => $item) {
                    if ($item['id'] === $id) {
                        $data['transactions'][$i] = $record;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    array_unshift($data['transactions'], $record);
                }

                $this->writeJsonAtomic($data);
                return $record;
            });
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO transactions (id, type, title, amount, category, date, account)
            VALUES (:id, :type, :title, :amount, :category, :date, :account)
            ON CONFLICT(id) DO UPDATE SET
                type = excluded.type,
                title = excluded.title,
                amount = excluded.amount,
                category = excluded.category,
                date = excluded.date,
                account = excluded.account
        ");

        $stmt->execute([
            ':id' => $id,
            ':type' => $type,
            ':title' => $title,
            ':amount' => $amount,
            ':category' => $category,
            ':date' => $date,
            ':account' => $account
        ]);

        return [
            'id' => $id,
            'type' => $type,
            'title' => $title,
            'amount' => $amount,
            'category' => $category,
            'date' => $date,
            'account' => $account
        ];
    }

    /**
     * Elimina una transazione
     */
    public function deleteTransaction(string $id): bool {
        if ($this->useJsonFallback) {
            return $this->withJsonLock(function(array &$data) use ($id) {
                $initialCount = count($data['transactions'] ?? []);
                $data['transactions'] = array_values(array_filter($data['transactions'] ?? [], fn($t) => $t['id'] !== $id));
                $this->writeJsonAtomic($data);
                return count($data['transactions']) < $initialCount;
            });
        }

        $stmt = $this->pdo->prepare("DELETE FROM transactions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Salva o crea un salvadanaio (vault)
     */
    public function saveVault(array $vault): array {
        $id = !empty($vault['id']) ? (string)$vault['id'] : 'v-' . round(microtime(true) * 1000);
        $name = trim((string)($vault['name'] ?? 'Nuovo Salvadanaio'));
        $amount = max(0, (float)($vault['amount'] ?? 0));
        $color = trim((string)($vault['color'] ?? 'indigo'));
        $isEmergency = !empty($vault['isEmergency']) ? 1 : 0;

        if ($this->useJsonFallback) {
            return $this->withJsonLock(function(array &$data) use ($id, $name, $amount, $color, $isEmergency) {
                if (!isset($data['vaults']) || !is_array($data['vaults'])) {
                    $data['vaults'] = [];
                }

                $record = [
                    'id' => $id,
                    'name' => $name,
                    'amount' => $amount,
                    'color' => $color,
                    'isEmergency' => (bool)$isEmergency,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $found = false;
                foreach ($data['vaults'] as $i => $item) {
                    if ($item['id'] === $id) {
                        $data['vaults'][$i] = $record;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $data['vaults'][] = $record;
                }

                $this->writeJsonAtomic($data);
                return $record;
            });
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO vaults (id, name, amount, color, is_emergency)
            VALUES (:id, :name, :amount, :color, :is_emergency)
            ON CONFLICT(id) DO UPDATE SET
                name = excluded.name,
                amount = excluded.amount,
                color = excluded.color,
                is_emergency = excluded.is_emergency
        ");

        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':amount' => $amount,
            ':color' => $color,
            ':is_emergency' => $isEmergency
        ]);

        return [
            'id' => $id,
            'name' => $name,
            'amount' => $amount,
            'color' => $color,
            'isEmergency' => (bool)$isEmergency
        ];
    }

    /**
     * Elimina un salvadanaio (non è possibile eliminare il fondo emergenza di default)
     */
    public function deleteVault(string $id): bool {
        if ($this->useJsonFallback) {
            return $this->withJsonLock(function(array &$data) use ($id) {
                $initialCount = count($data['vaults'] ?? []);
                $data['vaults'] = array_values(array_filter($data['vaults'] ?? [], function($v) use ($id) {
                    if ($v['id'] === $id && !empty($v['isEmergency'])) {
                        return true; // Protetto
                    }
                    return $v['id'] !== $id;
                }));
                $this->writeJsonAtomic($data);
                return count($data['vaults']) < $initialCount;
            });
        }

        $stmt = $this->pdo->prepare("DELETE FROM vaults WHERE id = :id AND is_emergency = 0");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Salva o crea un obiettivo (goal)
     */
    public function saveGoal(array $goal): array {
        $id = !empty($goal['id']) ? (string)$goal['id'] : 'g-' . round(microtime(true) * 1000);
        $name = trim((string)($goal['name'] ?? 'Nuovo Obiettivo'));
        $target = max(1, (float)($goal['target'] ?? 1000));
        $current = max(0, (float)($goal['current'] ?? 0));
        $deadline = !empty($goal['deadline']) ? (string)$goal['deadline'] : date('Y-m-d', strtotime('+1 year'));
        $icon = trim((string)($goal['icon'] ?? 'fa-piggy-bank'));

        if ($this->useJsonFallback) {
            return $this->withJsonLock(function(array &$data) use ($id, $name, $target, $current, $deadline, $icon) {
                if (!isset($data['goals']) || !is_array($data['goals'])) {
                    $data['goals'] = [];
                }

                $record = [
                    'id' => $id,
                    'name' => $name,
                    'target' => $target,
                    'current' => $current,
                    'deadline' => $deadline,
                    'icon' => $icon,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $found = false;
                foreach ($data['goals'] as $i => $item) {
                    if ($item['id'] === $id) {
                        $data['goals'][$i] = $record;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $data['goals'][] = $record;
                }

                $this->writeJsonAtomic($data);
                return $record;
            });
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO goals (id, name, target, current, deadline, icon)
            VALUES (:id, :name, :target, :current, :deadline, :icon)
            ON CONFLICT(id) DO UPDATE SET
                name = excluded.name,
                target = excluded.target,
                current = excluded.current,
                deadline = excluded.deadline,
                icon = excluded.icon
        ");

        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':target' => $target,
            ':current' => $current,
            ':deadline' => $deadline,
            ':icon' => $icon
        ]);

        return [
            'id' => $id,
            'name' => $name,
            'target' => $target,
            'current' => $current,
            'deadline' => $deadline,
            'icon' => $icon
        ];
    }

    /**
     * Elimina un obiettivo
     */
    public function deleteGoal(string $id): bool {
        if ($this->useJsonFallback) {
            return $this->withJsonLock(function(array &$data) use ($id) {
                $initialCount = count($data['goals'] ?? []);
                $data['goals'] = array_values(array_filter($data['goals'] ?? [], fn($g) => $g['id'] !== $id));
                $this->writeJsonAtomic($data);
                return count($data['goals']) < $initialCount;
            });
        }

        $stmt = $this->pdo->prepare("DELETE FROM goals WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Esegue un versamento o un prelievo da forziere o traguardo in modo atomico
     */
    public function transferFunds(string $type, string $id, string $action, float $amount): array {
        if ($amount <= 0) {
            throw new InvalidArgumentException("L'importo deve essere positivo.");
        }

        if ($this->useJsonFallback) {
            return $this->withJsonLock(function(array &$data) use ($type, $id, $action, $amount) {
                if ($type === 'vault') {
                    $targetList = &$data['vaults'];
                    $field = 'amount';
                } elseif ($type === 'goal') {
                    $targetList = &$data['goals'];
                    $field = 'current';
                } else {
                    throw new InvalidArgumentException("Tipo di destinazione non valido.");
                }

                $foundIndex = -1;
                foreach ($targetList as $idx => $item) {
                    if ($item['id'] === $id) {
                        $foundIndex = $idx;
                        break;
                    }
                }

                if ($foundIndex === -1) {
                    throw new RuntimeException("Elemento non trovato.");
                }

                $currentVal = (float)$targetList[$foundIndex][$field];
                if ($action === 'deposit') {
                    $newVal = $currentVal + $amount;
                } elseif ($action === 'withdraw') {
                    if ($currentVal < $amount) {
                        throw new RuntimeException("Fondi insufficienti per il prelievo.");
                    }
                    $newVal = $currentVal - $amount;
                } else {
                    throw new InvalidArgumentException("Azione non supportata.");
                }

                $targetList[$foundIndex][$field] = $newVal;
                $this->writeJsonAtomic($data);

                return [
                    'id' => $id,
                    'type' => $type,
                    'newBalance' => $newVal,
                    'action' => $action,
                    'amount' => $amount
                ];
            });
        }

        $this->pdo->beginTransaction();
        try {
            if ($type === 'vault') {
                $stmt = $this->pdo->prepare("SELECT amount FROM vaults WHERE id = :id FOR UPDATE");
                $stmt->execute([':id' => $id]);
                $row = $stmt->fetch();
                if (!$row) throw new RuntimeException("Salvadanaio non trovato.");

                $cur = (float)$row['amount'];
                if ($action === 'deposit') {
                    $new = $cur + $amount;
                } else {
                    if ($cur < $amount) throw new RuntimeException("Fondi insufficienti nel forziere.");
                    $new = $cur - $amount;
                }

                $up = $this->pdo->prepare("UPDATE vaults SET amount = :amt WHERE id = :id");
                $up->execute([':amt' => $new, ':id' => $id]);

            } elseif ($type === 'goal') {
                $stmt = $this->pdo->prepare("SELECT current FROM goals WHERE id = :id FOR UPDATE");
                $stmt->execute([':id' => $id]);
                $row = $stmt->fetch();
                if (!$row) throw new RuntimeException("Traguardo non trovato.");

                $cur = (float)$row['current'];
                if ($action === 'deposit') {
                    $new = $cur + $amount;
                } else {
                    if ($cur < $amount) throw new RuntimeException("L'importo supera il saldo accumulato.");
                    $new = $cur - $amount;
                }

                $up = $this->pdo->prepare("UPDATE goals SET current = :cur WHERE id = :id");
                $up->execute([':cur' => $new, ':id' => $id]);
            } else {
                throw new InvalidArgumentException("Tipo non valido.");
            }

            $this->pdo->commit();
            return [
                'id' => $id,
                'type' => $type,
                'newBalance' => $new,
                'action' => $action,
                'amount' => $amount
            ];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Importazione massiva e atomica di tutti i dati (JSON Backup)
     */
    public function importAllData(array $imported): bool {
        $transactions = $imported['transactions'] ?? [];
        $vaults = $imported['vaults'] ?? [];
        $goals = $imported['goals'] ?? [];

        if (!is_array($transactions) || !is_array($vaults) || !is_array($goals)) {
            throw new InvalidArgumentException("Formato dati di importazione non valido.");
        }

        if ($this->useJsonFallback) {
            return $this->withJsonLock(function(array &$data) use ($transactions, $vaults, $goals) {
                $data['transactions'] = $transactions;
                $data['vaults'] = $vaults;
                $data['goals'] = $goals;
                $this->writeJsonAtomic($data);
                return true;
            });
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec("DELETE FROM transactions");
            $this->pdo->exec("DELETE FROM vaults");
            $this->pdo->exec("DELETE FROM goals");

            $txInsert = $this->pdo->prepare("
                INSERT INTO transactions (id, type, title, amount, category, date, account)
                VALUES (:id, :type, :title, :amount, :category, :date, :account)
            ");
            foreach ($transactions as $t) {
                $txInsert->execute([
                    ':id' => $t['id'] ?? ('tx-' . uniqid()),
                    ':type' => in_array($t['type'] ?? '', ['income', 'expense'], true) ? $t['type'] : 'expense',
                    ':title' => (string)($t['title'] ?? 'Movimento'),
                    ':amount' => (float)($t['amount'] ?? 0),
                    ':category' => (string)($t['category'] ?? 'Altro'),
                    ':date' => (string)($t['date'] ?? date('Y-m-d')),
                    ':account' => (string)($t['account'] ?? 'Conto Principale')
                ]);
            }

            $vInsert = $this->pdo->prepare("
                INSERT INTO vaults (id, name, amount, color, is_emergency)
                VALUES (:id, :name, :amount, :color, :is_emergency)
            ");
            foreach ($vaults as $v) {
                $vInsert->execute([
                    ':id' => $v['id'] ?? ('v-' . uniqid()),
                    ':name' => (string)($v['name'] ?? 'Salvadanaio'),
                    ':amount' => (float)($v['amount'] ?? 0),
                    ':color' => (string)($v['color'] ?? 'indigo'),
                    ':is_emergency' => !empty($v['isEmergency']) || !empty($v['is_emergency']) ? 1 : 0
                ]);
            }

            $gInsert = $this->pdo->prepare("
                INSERT INTO goals (id, name, target, current, deadline, icon)
                VALUES (:id, :name, :target, :current, :deadline, :icon)
            ");
            foreach ($goals as $g) {
                $gInsert->execute([
                    ':id' => $g['id'] ?? ('g-' . uniqid()),
                    ':name' => (string)($g['name'] ?? 'Obiettivo'),
                    ':target' => (float)($g['target'] ?? 1000),
                    ':current' => (float)($g['current'] ?? 0),
                    ':deadline' => (string)($g['deadline'] ?? date('Y-m-d')),
                    ':icon' => (string)($g['icon'] ?? 'fa-piggy-bank')
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Ripristino / azzeramento completo dei dati
     */
    public function clearAllData(): bool {
        if ($this->useJsonFallback) {
            return $this->withJsonLock(function(array &$data) {
                $data['transactions'] = [];
                $data['vaults'] = [
                    [
                        'id' => 'v-emergency',
                        'name' => 'Fondo Emergenza',
                        'amount' => 0.0,
                        'color' => 'emerald',
                        'isEmergency' => true,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ];
                $data['goals'] = [];
                $this->writeJsonAtomic($data);
                return true;
            });
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec("DELETE FROM transactions");
            $this->pdo->exec("DELETE FROM vaults");
            $this->pdo->exec("DELETE FROM goals");

            $insert = $this->pdo->prepare("
                INSERT INTO vaults (id, name, amount, color, is_emergency)
                VALUES ('v-emergency', 'Fondo Emergenza', 0.0, 'emerald', 1)
            ");
            $insert->execute();

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Salva impostazione (es. dark mode)
     */
    public function setSetting(string $key, string $value): bool {
        if ($this->useJsonFallback) {
            return $this->withJsonLock(function(array &$data) use ($key, $value) {
                if (!isset($data['settings']) || !is_array($data['settings'])) {
                    $data['settings'] = [];
                }
                $data['settings'][$key] = $value;
                $this->writeJsonAtomic($data);
                return true;
            });
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO settings (key, value) VALUES (:key, :value)
            ON CONFLICT(key) DO UPDATE SET value = excluded.value
        ");
        return $stmt->execute([':key' => $key, ':value' => $value]);
    }
}
