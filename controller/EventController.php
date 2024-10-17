<?php
require_once '../inc/config.php';

class EventController{

    public function handleRequest(){

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
            $action = $_GET['action'];

            if ($action === 'getEvent') {
                $this->getEvent(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if($action === 'getDashboardData') {
                $this->getDashboardData(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getCalendarEvents'){
                $this->getCalendarEvents(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getUserEvents'){
                $this->getUserEvents(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getEventTransaction'){
                $this->getEventTransaction(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unknown action.'
                ]);
                exit;
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'deleteEvent') {
                $this->deleteEvent(isset($_POST['id']) ? intval($_POST['id']) : 0);
            } else if ($action === 'updateEvent') {
                $this->updateEvent(isset($_POST['id']) ? intval($_POST['id']) : 0);
            } else if ($action === 'addEvent') {
                $this->addEvent();
            } else if ($action === 'checkEvent') {
                $this->checkEvent(isset($_POST['id']) ? intval($_POST['id']) : 0);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unknown action.'
                ]);
                exit;
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method.'
            ]);
            exit;
        }
    }

    private function getDashboardData($id) {
        global $link;
    
        $data = [];
        
        $query = "SELECT COUNT(*) as totalEvents FROM events WHERE user_id = " . $id;
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['totalEvents'] = $row['totalEvents'];
        }
    
        $query = "SELECT COUNT(*) as upcomingEvents FROM events WHERE start_timestamp >= CURDATE() AND user_id = " . $id;
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['upcomingEvents'] = $row['upcomingEvents'];
        }
    
        $query = "SELECT COUNT(*) as eventsThisMonth FROM events WHERE MONTH(start_timestamp) = MONTH(CURDATE()) AND YEAR(start_timestamp) = YEAR(CURDATE()) AND user_id = " . $id;
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['eventsThisMonth'] = $row['eventsThisMonth'];
        }
    
        $query = "SELECT SUM(expenses) as totalExpenses FROM events WHERE user_id = " . $id;
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['totalExpenses'] = $row['totalExpenses'] ?? 0;
        }
    
        echo json_encode($data);
        exit;
    }
    
    private function getCalendarEvents($id) {
        global $link;
        
        $data = [];
        
        $query = "SELECT id, name, location, start_timestamp AS start, end_timestamp AS end FROM events WHERE user_id = " . $id;
        $result = $link->query($query);
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id' => (int) $row['id'],
                'title' => $row['name'],
                'description' => $row['location'],
                'start' => $row['start'],
                'end' => $row['end'],
                'link' => ""
            ];
        }
        
        $filePath = '../events.json';
        
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        
        if (file_put_contents($filePath, $jsonData) === false) {
            echo json_encode(['status' => 'error', 'message' => 'Unable to write to file.']);
            exit;
        }
        
        echo json_encode(['status' => 'success', 'message' => 'File updated successfully.']);
        exit;
    }    

    private function addEvent() {
        global $link;
    
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : '';
        $name = isset($_POST['name1']) ? $_POST['name1'] : '';
        $location = isset($_POST['location1']) ? $_POST['location1'] : '';
        $status = isset($_POST['status1']) ? $_POST['status1'] : '';
        $remarks = isset($_POST['remarks1']) ? $_POST['remarks1'] : '';
        $start_timestamp = isset($_POST['start_timestamp']) ? $_POST['start_timestamp'] : '';
        $end_timestamp = isset($_POST['end_timestamp']) ? $_POST['end_timestamp'] : '';
        $created_at = isset($_POST['created_at']) ? $_POST['created_at'] : '';
        $expenses =  0;
        $income =  0;
    
        $attachment = '';
        if (isset($_FILES['attachment1']) && $_FILES['attachment1']['error'] === UPLOAD_ERR_OK) {
            $targetDir = "../public/attachments/";
            $fileName = basename($_FILES["attachment1"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
            $allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'jfif');
            if (in_array(strtolower($fileType), $allowedTypes)) {
                if (move_uploaded_file($_FILES["attachment1"]["tmp_name"], $targetFilePath)) {
                    $attachment = './public/attachments/' . $fileName;
                } else {
                    echo json_encode(["status" => "error", "message" => "Failed to upload attachment."]);
                    exit;
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid file type."]);
                exit;
            }
        }
    
        $formattedCreatedAt = date('Y-m-d H:i:s', strtotime($created_at));
    
        $sql = "INSERT INTO events (user_id, name, location, status, remarks, attachment, start_timestamp, end_timestamp, created_at, expenses,income) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "issssssssdd", $user_id, $name, $location, $status, $remarks, $attachment, $start_timestamp, $end_timestamp, $formattedCreatedAt, $expenses, $income);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['status' => 'success', 'message' => 'Event successfully added.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to add the event. Please try again.'];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to prepare the add statement.'];
        }
    
        mysqli_close($link);
    
        echo json_encode($response);
        exit;
    }

    private function getEvent($id){
        global $link;

        $sql = "SELECT id,name,location,status,remarks,attachment, start_timestamp,end_timestamp,created_at,updated_at,expenses FROM events WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);

            mysqli_stmt_execute($stmt);

            mysqli_stmt_bind_result($stmt, $id, $name, $location, $status, $remarks, $attachment, $start_timestamp, $end_timestamp, $created_at, $updated_at, $expenses);

            if (mysqli_stmt_fetch($stmt)) {
                $data = [
                    'id' => $id,
                    'name' => $name,
                    'location' => $location,
                    'status' => $status,
                    'remarks' => $remarks,
                    'attachment' => $attachment,
                    'start_timestamp' => $start_timestamp,
                    'end_timestamp' => $end_timestamp,
                    'created_at' => $created_at,
                    'updated_at' => $updated_at,
                    'expenses' => $expenses,
                ];
                echo json_encode($data);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No event found with the provided ID.'
                ]);
            }

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get event statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function deleteEvent($id) {
        global $link;
    
        $transactionOption = isset($_POST['transactionOption']) ? $_POST['transactionOption'] : 'retain';
    
        mysqli_begin_transaction($link);
    
        try {
            if ($transactionOption === 'nullify') {
                $updateTransactionsSQL = "UPDATE transactions SET id_ref = NULL, table_ref = NULL WHERE table_ref = 'events' AND id_ref = ?";
                $stmt = mysqli_prepare($link, $updateTransactionsSQL);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } elseif ($transactionOption === 'delete') {
                $fetchTransactionsSQL = "SELECT id, amount, trans_type, wallet_id, budget_id FROM transactions WHERE table_ref = 'events' AND id_ref = ?";
                $stmt = mysqli_prepare($link, $fetchTransactionsSQL);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
    
                while ($row = mysqli_fetch_assoc($result)) {
                    $transactionId = $row['id'];
                    $amount = $row['amount'];
                    $transType = $row['trans_type'];
                    $walletId = $row['wallet_id'];
                    $budgetId = $row['budget_id'];
    
                    if ($transType == 'Expense') {
                        $adjustWalletSQL = "UPDATE wallets SET amount = amount + ? WHERE id = ?";
                        $adjustBudgetSQL = "UPDATE budgets SET current_amount = current_amount + ? WHERE id = ?";
                    } elseif ($transType == 'Income') {
                        $adjustWalletSQL = "UPDATE wallets SET amount = amount - ? WHERE id = ?";
                        $adjustBudgetSQL = "UPDATE budgets SET current_amount = current_amount - ? WHERE id = ?";
                    }
    
                    $stmtUpdateWallet = mysqli_prepare($link, $adjustWalletSQL);
                    mysqli_stmt_bind_param($stmtUpdateWallet, "di", $amount, $walletId);
                    mysqli_stmt_execute($stmtUpdateWallet);
                    mysqli_stmt_close($stmtUpdateWallet);
    
                    if ($budgetId) {
                        $stmtUpdateBudget = mysqli_prepare($link, $adjustBudgetSQL);
                        mysqli_stmt_bind_param($stmtUpdateBudget, "di", $amount, $budgetId);
                        mysqli_stmt_execute($stmtUpdateBudget);
                        mysqli_stmt_close($stmtUpdateBudget);
                    }
                }
                mysqli_stmt_close($stmt);
    
                $deleteTransactionsSQL = "DELETE FROM transactions WHERE table_ref = 'events' AND id_ref = ?";
                $stmt = mysqli_prepare($link, $deleteTransactionsSQL);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
    
            $sql = "DELETE FROM events WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
    
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                mysqli_commit($link);
                $response = [
                    'status' => 'success',
                    'message' => 'Event successfully deleted along with associated data based on your choices.'
                ];
            } else {
                mysqli_rollback($link);
                $response = [
                    'status' => 'error',
                    'message' => 'No event found with the provided ID.'
                ];
            }
            mysqli_stmt_close($stmt);
        } catch (Exception $e) {
            mysqli_rollback($link);
            $response = [
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ];
        }
    
        mysqli_close($link);
        echo json_encode($response);
        exit;
    } 

    private function updateEvent() {
        global $link;
    
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        $location = isset($_POST['location']) ? $_POST['location'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
        $start_timestamp = isset($_POST['start_timestamp']) ? $_POST['start_timestamp'] : '';
        $end_timestamp = isset($_POST['end_timestamp']) ? $_POST['end_timestamp'] : '';
        $updated_at = isset($_POST['updated_at']) ? $_POST['updated_at'] : '';
    
        $attachment = '';
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $targetDir = "../public/attachments/";
            $fileName = basename($_FILES["attachment"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
            $allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'jfif');
            if (in_array(strtolower($fileType), $allowedTypes)) {
                if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $targetFilePath)) {
                    $attachment = './public/attachments/' . $fileName;
                } else {
                    echo json_encode(["status" => "error", "message" => "Failed to upload attachment."]);
                    exit;
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid file type."]);
                exit;
            }
        }
    
        $formattedUpdatedAt = date('Y-m-d H:i:s', strtotime($updated_at));
    
        $sql = "UPDATE events SET name = ?, location = ?, status = ?, remarks = ?, start_timestamp = ?, end_timestamp = ?, updated_at = ?";
    
        if ($attachment !== '') {
            $sql .= ", attachment = ?";
        }
    
        $sql .= " WHERE id = ?";
    
        if ($stmt = mysqli_prepare($link, $sql)) {
            if ($attachment !== '') {
                mysqli_stmt_bind_param($stmt, "ssssssssi", $name, $location, $status, $remarks, $start_timestamp, $end_timestamp, $formattedUpdatedAt, $attachment, $id);
            } else {
                mysqli_stmt_bind_param($stmt, "sssssssi", $name, $location, $status, $remarks, $start_timestamp, $end_timestamp, $formattedUpdatedAt, $id);
            }

            if (mysqli_stmt_execute($stmt)) {
                $response = ['status' => 'success', 'message' => 'Event successfully updated.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to update the event. Please try again.'];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to prepare the update statement.'];
        }
    
        mysqli_close($link);
    
        echo json_encode($response);
        exit;
    }
    
    function getUserEvents($id) {
        global $link;
        
        $userId = isset($id) ? intval($id) : 0;
        
        $query = "SELECT id, name, location, status, expenses, remarks, start_timestamp, end_timestamp, created_at, updated_at
                  FROM events
                  WHERE user_id = $userId";
        
        $result = $link->query($query);
        
        if (!$result) {
            echo json_encode(['error' => $link->error]);
            exit;
        }
    
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'location' => $row['location'],
                'status' => $row['status'],
                'expenses' => (float)$row['expenses'],
                'remarks' => $row['remarks'],
                'start_timestamp' => $row['start_timestamp'],
                'end_timestamp' => $row['end_timestamp'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }
    
        $response = [
            'data' => $data,
            'totalCount' => count($data),
            'summary' => [],
            'groupCount' => 0,
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    private function getEventTransaction($id) {
        global $link;
    
        $sql = "SELECT trans_type, created_at, description, amount FROM transactions WHERE id_ref = ? AND table_ref = 'events' ORDER BY created_at DESC LIMIT 5";
    
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
    
            mysqli_stmt_execute($stmt);
    
            mysqli_stmt_bind_result($stmt, $trans_type, $created_at, $description, $amount);
    
            $data = [];
            while (mysqli_stmt_fetch($stmt)) {
                $data[] = [
                    'trans_type' => $trans_type,
                    'created_at' => $created_at,
                    'description' => $description,
                    'amount' => $amount,
                ];
            }
    
            if (!empty($data)) {
                echo json_encode([
                    'status' => 'success',
                    'data' => $data
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No transactions found with the provided event ID.'
                ]);
            }
    
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get event transaction statement.'
            ]);
        }
    
        mysqli_close($link);
        exit;
    }
    
    private function checkEvent($id) {
        global $link;
    
        $response = [
            'hasTransactions' => false
        ];
        
        $checkTransactionsSQL = "SELECT COUNT(*) as transaction_count FROM transactions WHERE table_ref = 'events' AND id_ref = ?";
        if ($stmt = mysqli_prepare($link, $checkTransactionsSQL)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_bind_result($stmt, $transaction_count);
                mysqli_stmt_fetch($stmt);
                if ($transaction_count > 0) {
                    $response['hasTransactions'] = true;
                }
            }
            mysqli_stmt_close($stmt);
        }
    
        mysqli_close($link);
        echo json_encode($response);
        exit;
    }    

}

$controller = new EventController();
$controller->handleRequest();
?>