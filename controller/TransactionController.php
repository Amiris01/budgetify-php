<?php
require_once '../inc/config.php';

class TransactionController{
    public function handleRequest(){

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
            $action = $_GET['action'];

            if ($action === 'getTransaction') {
                $this->getTransaction(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if($action == 'getDashboardData'){
                $this->getDashboardData(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if($action == 'getRecentTransaction'){
                $this->getRecentTransaction(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if($action == 'getTransactionTypeDist'){
                $this->getTransactionTypeDist(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unknown action.'
                ]);
                exit;
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'deleteTransaction') {
               $this->deleteTransaction(isset($_POST['id']) ? intval($_POST['id']) : 0);
            } else if ($action === 'updateIncome') {
                $this->updateIncome(isset($_POST['id']) ? intval($_POST['id']) : 0);
            } else if ($action === 'addIncome') {
                $this->addIncome();
            } else if ($action === 'addExpense') {
                $this->addExpense();
            }else {
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

    private function addIncome() {
        global $link;
    
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $wallet_id = isset($_POST['wallet1']) ? $_POST['wallet1'] : '';
        $amount = isset($_POST['amount1']) ? floatval($_POST['amount1']) : 0;
        $description = isset($_POST['desc1']) ? $_POST['desc1'] : '';
        $trans_date = isset($_POST['trans_date1']) ? $_POST['trans_date1'] : '';
        $created_at = isset($_POST['created_at']) ? $_POST['created_at'] : '';
        $trans_type = isset($_POST['trans_type']) ? $_POST['trans_type'] : '';
        $category = isset($_POST['category1']) ? $_POST['category1'] : '';
    
        $formattedTransDate = date('Y-m-d', strtotime($trans_date));
        $formattedCreatedAt = date('Y-m-d H:i:s', strtotime($created_at));
    
        $table_ref = isset($_POST['table_ref1']) ? $_POST['table_ref1'] : '';

        if($table_ref == "events"){
            $id_ref = isset($_POST['event1']) ? intval($_POST['event1']) : 0;
        }else if($table_ref == "apparels"){
            $id_ref = isset($_POST['apparel1']) ? intval($_POST['apparel1']) : 0;
        }

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
    
        mysqli_begin_transaction($link);
    
        try {
            $sql = "SELECT amount FROM wallets WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $wallet_id);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_bind_result($stmt, $currentBalance);
                    mysqli_stmt_fetch($stmt);
                } else {
                    throw new Exception('Failed to retrieve wallet balance.');
                }
                mysqli_stmt_close($stmt);
            } else {
                throw new Exception('Failed to prepare the select statement.');
            }
    
            if($table_ref == "normal"){
                $sql = "INSERT INTO transactions (user_id, wallet_id, amount, description, trans_date, created_at, trans_type, attachment, category, table_ref) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }else{
                $sql = "INSERT INTO transactions (user_id, wallet_id, amount, description, trans_date, created_at, trans_type, attachment, category, table_ref, id_ref) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
            }

    
            if ($stmt = mysqli_prepare($link, $sql)) {
                if($table_ref == "normal"){
                    mysqli_stmt_bind_param($stmt, "iidsssssss", $user_id, $wallet_id, $amount, $description, $formattedTransDate, $formattedCreatedAt, $trans_type, $attachment, $category, $table_ref);
                }else{
                    mysqli_stmt_bind_param($stmt, "iidsssssssi", $user_id, $wallet_id, $amount, $description, $formattedTransDate, $formattedCreatedAt, $trans_type, $attachment, $category, $table_ref, $id_ref);
                }
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to add the income. Please try again.');
                }
                mysqli_stmt_close($stmt);
            } else {
                throw new Exception('Failed to prepare the add statement.');
            }
    
            $newBalance = $currentBalance + $amount;
            $sql = "UPDATE wallets SET amount = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "di", $newBalance, $wallet_id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to update wallet balance.');
                }
                mysqli_stmt_close($stmt);
            } else {
                throw new Exception('Failed to prepare the update statement.');
            }
    
            if($table_ref == "events") {
                $sql = "UPDATE events SET income = income + ? WHERE id = ?";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "di", $amount, $id_ref);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Failed to update event expenses.');
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    throw new Exception('Failed to prepare the update event statement.');
                }
            }

            mysqli_commit($link);
    
            $response = [
                'status' => 'success',
                'message' => 'Income successfully added, and wallet balance updated.'
            ];
        } catch (Exception $e) {
            mysqli_rollback($link);
            $response = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    
        mysqli_close($link);
    
        echo json_encode($response);
        exit;
    }

    private function addExpense() {
        global $link;
    
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $table_ref = isset($_POST['table_ref']) ? $_POST['table_ref'] : '';
        $trans_type = isset($_POST['trans_type']) ? $_POST['trans_type'] : '';
        $category = isset($_POST['category']) ? $_POST['category'] : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $wallet_id = isset($_POST['wallet']) ? $_POST['wallet'] : '';
        $description = isset($_POST['desc']) ? $_POST['desc'] : '';
        $trans_date = isset($_POST['trans_date']) ? $_POST['trans_date'] : '';
        $created_at = isset($_POST['created_at']) ? $_POST['created_at'] : '';
        $budget_id = isset($_POST['budget']) ? intval($_POST['budget']) : 0;
    
        $formattedTransDate = date('Y-m-d', strtotime($trans_date));
        $formattedCreatedAt = date('Y-m-d H:i:s', strtotime($created_at));

        if($table_ref == "events"){
            $id_ref = isset($_POST['event']) ? intval($_POST['event']) : 0;
        }else if($table_ref == "apparels"){
            $id_ref = isset($_POST['apparel']) ? intval($_POST['apparel']) : 0;
        }
    
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
    
        mysqli_begin_transaction($link);
    
        try {
            $sql = "SELECT amount FROM wallets WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $wallet_id);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_bind_result($stmt, $currentBalance);
                    mysqli_stmt_fetch($stmt);
                } else {
                    throw new Exception('Failed to retrieve wallet balance.');
                }
                mysqli_stmt_close($stmt);
            } else {
                throw new Exception('Failed to prepare the select statement.');
            }
    
            if ($currentBalance < $amount) {
                throw new Exception('Insufficient funds in wallet.');
            }
    
            if($table_ref == "normal"){
                $sql = "INSERT INTO transactions (user_id, wallet_id, amount, description, trans_date, created_at, trans_type, attachment, category, table_ref, budget_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }else{
                $sql = "INSERT INTO transactions (user_id, wallet_id, amount, description, trans_date, created_at, trans_type, attachment, category, table_ref, id_ref, budget_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }

    
            if ($stmt = mysqli_prepare($link, $sql)) {
                if($table_ref == "normal"){
                    mysqli_stmt_bind_param($stmt, "iidsssssssi", $user_id, $wallet_id, $amount, $description, $formattedTransDate, $formattedCreatedAt, $trans_type, $attachment, $category, $table_ref,$budget_id);
                }else{
                    mysqli_stmt_bind_param($stmt, "iidsssssssii", $user_id, $wallet_id, $amount, $description, $formattedTransDate, $formattedCreatedAt, $trans_type, $attachment, $category, $table_ref, $id_ref,$budget_id);
                }
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to add the expense. Please try again.');
                }
                mysqli_stmt_close($stmt);
            } else {
                throw new Exception('Failed to prepare the add statement.');
            }
    
            $newBalance = $currentBalance - $amount;
            $sql = "UPDATE wallets SET amount = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "di", $newBalance, $wallet_id);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Failed to update wallet balance.');
                }
                mysqli_stmt_close($stmt);
            } else {
                throw new Exception('Failed to prepare the update statement.');
            }
    
            if($table_ref == "events") {
                $sql = "UPDATE events SET expenses = expenses + ? WHERE id = ?";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "di", $amount, $id_ref);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Failed to update event expenses.');
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    throw new Exception('Failed to prepare the update event statement.');
                }
            }

            if ($budget_id != null) {
                $sql = "SELECT current_amount FROM budgets WHERE id = ?";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "i", $budget_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_bind_result($stmt, $current_amount);
                    mysqli_stmt_fetch($stmt);
                    mysqli_stmt_close($stmt);
                    
                    if ($current_amount === null) {
                        throw new Exception('Budget ID not found.');
                    }
            
                    $total_amount = $current_amount;
                    $new_amount = $current_amount - $amount;
            
                    $sql = "UPDATE budgets SET current_amount = ? WHERE id = ?";
                    if ($stmt = mysqli_prepare($link, $sql)) {
                        mysqli_stmt_bind_param($stmt, "di", $new_amount, $budget_id);
                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception('Failed to update budget amount.');
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        throw new Exception('Failed to prepare the update budget amount statement.');
                    }
            
                    if ($table_ref == 'events') {
                        $sql = "INSERT INTO budget_log (user_id, budget_id, current_amount, amount_spent, id_ref, ref_table,created_at) VALUES (?, ?, ?, ?, ?, ?,?)";
                        if ($stmt_log = mysqli_prepare($link, $sql)) {
                            mysqli_stmt_bind_param($stmt_log, "iiddiss", $user_id, $budget_id, $total_amount, $amount, $id_ref, $table_ref, $formattedCreatedAt);
                            if (!mysqli_stmt_execute($stmt_log)) {
                                throw new Exception('Failed to insert budget log.');
                            }
                            mysqli_stmt_close($stmt_log);
                        } else {
                            throw new Exception('Failed to prepare the insert budget log statement.');
                        }
                    } else {
                        $sql = "INSERT INTO budget_log (user_id, budget_id, current_amount, amount_spent, created_at) VALUES (?, ?, ?, ?, ?)";
                        if ($stmt_log = mysqli_prepare($link, $sql)) {
                            mysqli_stmt_bind_param($stmt_log, "iidds", $user_id, $budget_id, $total_amount, $amount, $formattedCreatedAt);
                            if (!mysqli_stmt_execute($stmt_log)) {
                                throw new Exception('Failed to insert budget log.');
                            }
                            mysqli_stmt_close($stmt_log);
                        } else {
                            throw new Exception('Failed to prepare the insert budget log statement.');
                        }
                    }
                } else {
                    throw new Exception('Failed to prepare the select statement for budget amount.');
                }
            }                      

            mysqli_commit($link);
    
            $response = [
                'status' => 'success',
                'message' => 'Expense successfully added, and wallet balance updated.'
            ];
        } catch (Exception $e) {
            mysqli_rollback($link);
            $response = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    
        mysqli_close($link);
    
        echo json_encode($response);
        exit;
    }
    
    private function getDashboardData($id) {
        global $link;
        
        $data = [];
    
        $query = "SELECT COUNT(*) as totalTransaction FROM transactions WHERE user_id = " . (int)$id;
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['totalTransaction'] = $row['totalTransaction'];
        }
    
        $query = "SELECT SUM(amount) as totalIncome FROM transactions WHERE user_id = " . (int)$id . " AND trans_type = 'Income'";
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['totalIncome'] = $row['totalIncome'] ? $row['totalIncome'] : 0;
        }
    
        $query = "SELECT SUM(amount) as totalExpenses FROM transactions WHERE user_id = " . (int)$id . " AND trans_type = 'Expense'";
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['totalExpenses'] = $row['totalExpenses'] ? $row['totalExpenses'] : 0;
        }
    
        $data['totalBalance'] = $data['totalIncome'] - $data['totalExpenses'];
    
        echo json_encode($data);
        exit;
    }

    private function getRecentTransaction($id){
        global $link;
    
        $sql = "SELECT trans_type, created_at, description, amount FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 6";
    
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
                    'message' => 'No transactions found with the provided user ID.'
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
    
    private function getTransactionTypeDist($id) {
        global $link;
    
        $incomeCount = 0;
        $expenseCount = 0;
    
        $incomeQuery = "SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND trans_type = 'Income'";
        if ($stmt = mysqli_prepare($link, $incomeQuery)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $incomeCount);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the income count query.'
            ]);
            mysqli_close($link);
            exit;
        }
    
        $expenseQuery = "SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND trans_type = 'Expense'";
        if ($stmt = mysqli_prepare($link, $expenseQuery)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $expenseCount);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the expense count query.'
            ]);
            mysqli_close($link);
            exit;
        }
    
        echo json_encode([
            'status' => 'success',
            'data' => [
                'incomeCount' => $incomeCount,
                'expenseCount' => $expenseCount
            ]
        ]);
    
        mysqli_close($link);
        exit;
    }    
    
    private function getTransaction($id) {
        global $link;
    
        $sql = "
            SELECT 
                t.wallet_id, 
                w.name as wallet_name, 
                t.budget_id, 
                b.title as budget_title, 
                t.id_ref, 
                t.table_ref, 
                t.amount, 
                t.description, 
                t.category, 
                t.trans_date, 
                t.attachment, 
                t.trans_type, 
                t.created_at, 
                t.updated_at,
                CASE 
                    WHEN t.table_ref = 'events' THEN e.name
                    ELSE NULL
                END as event_name
            FROM transactions t
            LEFT JOIN wallets w ON t.wallet_id = w.id
            LEFT JOIN budgets b ON t.budget_id = b.id
            LEFT JOIN events e ON t.table_ref = 'events' AND t.id_ref = e.id
            WHERE t.id = ?
        ";
    
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
    
            mysqli_stmt_execute($stmt);
    
            mysqli_stmt_bind_result(
                $stmt, 
                $wallet_id, 
                $wallet_name, 
                $budget_id, 
                $budget_title, 
                $id_ref, 
                $table_ref, 
                $amount, 
                $description, 
                $category, 
                $trans_date,
                $attachment,
                $trans_type, 
                $created_at, 
                $updated_at,
                $event_name // Bind the event_name result
            );
    
            if (mysqli_stmt_fetch($stmt)) {
                $data = [
                    'id' => $id,
                    'wallet_id' => $wallet_id,
                    'wallet_name' => $wallet_name, 
                    'budget_id' => $budget_id,
                    'budget_title' => $budget_title,
                    'id_ref' => $id_ref,
                    'table_ref' => $table_ref,
                    'amount' => $amount,
                    'description' => $description, 
                    'category' => $category,
                    'trans_date' => $trans_date,
                    'attachment' => $attachment, 
                    'trans_type' => $trans_type,
                    'created_at' => $created_at,
                    'updated_at' => $updated_at,
                    'event_name' => $event_name
                ];
                echo json_encode($data);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No transaction found with the provided ID.'
                ]);
            }
    
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get transaction statement.'
            ]);
        }
    
        mysqli_close($link);
        exit;
    }
      
    private function deleteTransaction($id) {
        global $link;
    
        mysqli_begin_transaction($link);
    
        try {
            $fetchTransactionsSQL = "SELECT id, amount, trans_type, wallet_id, budget_id, id_ref, table_ref FROM transactions WHERE id = ?";
            $stmt = mysqli_prepare($link, $fetchTransactionsSQL);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
    
            if ($row = mysqli_fetch_assoc($result)) {
                $transactionId = $row['id'];
                $amount = $row['amount'];
                $transType = $row['trans_type'];
                $walletId = $row['wallet_id'];
                $budgetId = $row['budget_id'];
                $id_ref = $row['id_ref'];
                $table_ref = $row['table_ref'];

                if($id_ref && $table_ref == 'events'){
                    if($transType == 'Expense'){
                        $adjustEventSQL = "UPDATE events SET expenses = expenses - ? WHERE id = ?";
                    }

                    if($transType == 'Income'){
                        $adjustEventSQL = "UPDATE events SET income = income - ? WHERE id = ?";
                    }

                    $stmtUpdateEvent = mysqli_prepare($link, $adjustEventSQL);
                    mysqli_stmt_bind_param($stmtUpdateEvent, "di", $amount, $id_ref);
                    mysqli_stmt_execute($stmtUpdateEvent);
                    mysqli_stmt_close($stmtUpdateEvent);
                }
    
                $adjustWalletSQL = ($transType == 'Expense') ? 
                    "UPDATE wallets SET amount = amount + ? WHERE id = ?" : 
                    "UPDATE wallets SET amount = amount - ? WHERE id = ?";
                
                $stmtUpdateWallet = mysqli_prepare($link, $adjustWalletSQL);
                mysqli_stmt_bind_param($stmtUpdateWallet, "di", $amount, $walletId);
                mysqli_stmt_execute($stmtUpdateWallet);
                mysqli_stmt_close($stmtUpdateWallet);
    
                if ($budgetId) {
                    $adjustBudgetSQL = ($transType == 'Expense') ? 
                        "UPDATE budgets SET current_amount = current_amount + ? WHERE id = ?" : 
                        "UPDATE budgets SET current_amount = current_amount - ? WHERE id = ?";
                    
                    $stmtUpdateBudget = mysqli_prepare($link, $adjustBudgetSQL);
                    mysqli_stmt_bind_param($stmtUpdateBudget, "di", $amount, $budgetId);
                    mysqli_stmt_execute($stmtUpdateBudget);
                    mysqli_stmt_close($stmtUpdateBudget);
                }
    
                $deleteTransactionsSQL = "DELETE FROM transactions WHERE id = ?";
                $stmt = mysqli_prepare($link, $deleteTransactionsSQL);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
    
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    mysqli_commit($link);
                    $response = [
                        'status' => 'success',
                        'message' => 'Transaction successfully deleted along with associated data.'
                    ];
                } else {
                    mysqli_rollback($link);
                    $response = [
                        'status' => 'error',
                        'message' => 'No transaction found with the provided ID.'
                    ];
                }
    
                mysqli_stmt_close($stmt);
            } else {
                mysqli_rollback($link);
                $response = [
                    'status' => 'error',
                    'message' => 'Transaction not found.'
                ];
            }
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

    private function updateIncome($id) {
        global $link;
    
        $wallet_id = isset($_POST['wallet_income']) ? intval($_POST['wallet_income']) : 0;;
        $amount = isset($_POST['amount_income']) ? floatval($_POST['amount_income']) : 0;
        $category = isset($_POST['category_income']) ? $_POST['category_income'] : '';
        $description = isset($_POST['desc_income']) ? $_POST['desc_income'] : '';
        $trans_date = isset($_POST['trans_date_income']) ? $_POST['trans_date_income'] : '';
        $updated_at = isset($_POST['updated_at']) ? $_POST['updated_at'] : '';
    
        $formattedTransDate = date('Y-m-d', strtotime($trans_date));
        $formattedUpdatedAt = date('Y-m-d H:i:s', strtotime($updated_at));
    
        $id_ref = isset($_POST['event_income']) ? intval($_POST['event_income']) : 0;

        $attachment = '';
    
        if (isset($_FILES['attachment_income']) && $_FILES['attachment_income']['error'] === UPLOAD_ERR_OK) {
            $targetDir = "../public/attachments/";
            $fileName = basename($_FILES["attachment_income"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
            $allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'jfif');
            if (in_array(strtolower($fileType), $allowedTypes)) {
                if (move_uploaded_file($_FILES["attachment_income"]["tmp_name"], $targetFilePath)) {
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
    
        mysqli_begin_transaction($link);

        $sql = "SELECT wallet_id, amount FROM transactions WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $current_wallet_id, $current_amount);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }

        if ($wallet_id != $current_wallet_id) {
            $sql = "UPDATE wallets SET amount = amount - ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, 'di', $current_amount, $current_wallet_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            $sql = "UPDATE wallets SET amount = amount + ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, 'di', $amount, $wallet_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            if ($amount != $current_amount) {
                $difference = $amount - $current_amount;

                $sql = "UPDATE wallets SET amount = amount + ? WHERE id = ?";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, 'di', $difference, $wallet_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }

        $sql = "SELECT id_ref, table_ref, amount FROM transactions WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $current_id_ref, $current_table_ref, $current_amount);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }
        if ($id_ref != $current_id_ref && $current_table_ref == 'events') {
            $sql = "UPDATE events SET income = income - ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, 'di', $current_amount, $current_id_ref);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            $sql = "UPDATE events SET income = income + ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, 'di', $amount, $id_ref);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            if ($amount != $current_amount) {
                $difference = $amount - $current_amount;

                $sql = "UPDATE events SET income = income + ? WHERE id = ?";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, 'di', $difference, $id_ref);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }
    
        $sql = "UPDATE transactions SET wallet_id = ?, amount = ?, description = ?, category = ?, trans_date = ?, updated_at = ?";
        
        if ($id_ref !== '') {
            $sql .= ", id_ref = ?";
        }
        
        if ($attachment !== '') {
            $sql .= ", attachment = ?";
        }
    
        $sql .= " WHERE id = ?";
    
        if ($stmt = mysqli_prepare($link, $sql)) {
            if ($id_ref !== '' && $attachment !== '') {
                mysqli_stmt_bind_param($stmt, 'idssssisi', $wallet_id, $amount, $description, $category, $formattedTransDate, $formattedUpdatedAt, $id_ref, $attachment, $id);
            } elseif ($id_ref !== '') {
                mysqli_stmt_bind_param($stmt, 'idssssii', $wallet_id, $amount, $description, $category, $formattedTransDate, $formattedUpdatedAt, $id_ref, $id);
            } elseif ($attachment !== '') {
                mysqli_stmt_bind_param($stmt, 'idsssssi', $wallet_id, $amount, $description, $category, $formattedTransDate, $formattedUpdatedAt, $attachment, $id);
            } else {
                mysqli_stmt_bind_param($stmt, 'idssssi', $wallet_id, $amount, $description, $category, $formattedTransDate, $formattedUpdatedAt, $id);
            }
    
            if (mysqli_stmt_execute($stmt)) {
                mysqli_commit($link);
                $response = ['status' => 'success', 'message' => 'Income updated successfully.'];
            } else {
                mysqli_rollback($link); 
                $response = ['status' => 'error', 'message' => 'Failed to update income.'];
            }
            
            mysqli_stmt_close($stmt);
        } else {
            mysqli_rollback($link);
            $response = ['status' => 'error', 'message' => 'Failed to prepare the update statement.'];
        }
    
        mysqli_close($link);
        echo json_encode($response);
        exit;
    }    
    
}

$controller = new TransactionController();
$controller->handleRequest();

?>