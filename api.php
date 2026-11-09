<?php
declare(strict_types=1);

/**
 * Alessio Budget - REST API Endpoint
 * Gestione centralizzata delle richieste frontend con risposte JSON robuste.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function sendResponse(bool $success, $data = null, ?string $error = null, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = BudgetDB::getInstance();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    // Estrai corpo JSON se presente
    $inputData = [];
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $inputData = $decoded;
            if (empty($action) && isset($decoded['action'])) {
                $action = (string)$decoded['action'];
            }
        }
    }

    switch ($action) {
        case 'get_all':
            $data = $db->getAllData();
            $data['engine'] = $db->isUsingJson() ? 'json' : 'sqlite';
            sendResponse(true, $data);
            break;

        case 'save_transaction':
            $tx = !empty($inputData['transaction']) ? $inputData['transaction'] : $inputData;
            if (empty($tx['title']) || !isset($tx['amount'])) {
                sendResponse(false, null, 'Titolo e importo obbligatori.', 400);
            }
            $saved = $db->saveTransaction($tx);
            sendResponse(true, $saved);
            break;

        case 'delete_transaction':
            $id = (string)($inputData['id'] ?? $_POST['id'] ?? $_GET['id'] ?? '');
            if (empty($id)) {
                sendResponse(false, null, 'ID transazione non specificato.', 400);
            }
            $deleted = $db->deleteTransaction($id);
            sendResponse($deleted, ['id' => $id]);
            break;

        case 'save_vault':
            $vault = !empty($inputData['vault']) ? $inputData['vault'] : $inputData;
            if (empty($vault['name'])) {
                sendResponse(false, null, 'Nome salvadanaio obbligatorio.', 400);
            }
            $saved = $db->saveVault($vault);
            sendResponse(true, $saved);
            break;

        case 'delete_vault':
            $id = (string)($inputData['id'] ?? $_POST['id'] ?? $_GET['id'] ?? '');
            if (empty($id)) {
                sendResponse(false, null, 'ID forziere non specificato.', 400);
            }
            $deleted = $db->deleteVault($id);
            sendResponse($deleted, ['id' => $id]);
            break;

        case 'save_goal':
            $goal = !empty($inputData['goal']) ? $inputData['goal'] : $inputData;
            if (empty($goal['name']) || empty($goal['target'])) {
                sendResponse(false, null, 'Nome e target obiettivo obbligatori.', 400);
            }
            $saved = $db->saveGoal($goal);
            sendResponse(true, $saved);
            break;

        case 'delete_goal':
            $id = (string)($inputData['id'] ?? $_POST['id'] ?? $_GET['id'] ?? '');
            if (empty($id)) {
                sendResponse(false, null, 'ID obiettivo non specificato.', 400);
            }
            $deleted = $db->deleteGoal($id);
            sendResponse($deleted, ['id' => $id]);
            break;

        case 'transfer':
            $type = (string)($inputData['type'] ?? '');
            $id = (string)($inputData['id'] ?? '');
            $transAction = (string)($inputData['transferAction'] ?? 'deposit');
            $amount = (float)($inputData['amount'] ?? 0);

            if (empty($type) || empty($id) || $amount <= 0) {
                sendResponse(false, null, 'Parametri del trasferimento non validi.', 400);
            }

            $res = $db->transferFunds($type, $id, $transAction, $amount);
            sendResponse(true, $res);
            break;

        case 'import_data':
            $importedData = !empty($inputData['data']) ? $inputData['data'] : $inputData;
            $success = $db->importAllData($importedData);
            sendResponse($success, ['message' => 'Dati importati con successo nel database.']);
            break;

        case 'export_data':
            $data = $db->getAllData();
            $exportPayload = [
                'app' => 'Alessio budget',
                'version' => '2.0-sqlite',
                'exportDate' => date('c'),
                'data' => $data
            ];
            sendResponse(true, $exportPayload);
            break;

        case 'clear_all':
            $db->clearAllData();
            sendResponse(true, ['message' => 'Database azzerato con successo.']);
            break;

        case 'set_setting':
            $key = (string)($inputData['key'] ?? '');
            $val = (string)($inputData['value'] ?? '');
            if (!empty($key)) {
                $db->setSetting($key, $val);
                sendResponse(true, ['key' => $key, 'value' => $val]);
            } else {
                sendResponse(false, null, 'Chiave impostazione mancante.', 400);
            }
            break;

        default:
            sendResponse(false, null, 'Azione richiesta non supportata: ' . htmlspecialchars($action), 404);
            break;
    }
} catch (InvalidArgumentException $e) {
    sendResponse(false, null, $e->getMessage(), 400);
} catch (Throwable $e) {
    error_log("Errore API Budget: " . $e->getMessage());
    sendResponse(false, null, "Errore interno del server: " . $e->getMessage(), 500);
}
