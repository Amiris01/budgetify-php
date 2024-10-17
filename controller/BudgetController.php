<?php
require_once '../inc/config.php';

class BudgetController {

    public function handleRequest() {

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
            $action = $_GET['action'];

            if ($action === 'getBudget') {
                $this->getBudget(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getDashboardData'){
                $this->getDashboardData(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getBudgetBreakdown') {
                $this->getBudgetBreakdown(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getMonthlySpendingTrend') {
                $this->getMonthlySpendingTrend(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getBudgetTransaction') {
                $this->getBudgetTransaction(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unknown action.'
                ]);
                exit;
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'deleteBudget') {
                $this->deleteBudget(isset($_POST['id']) ? intval($_POST['id']) : 0);
            } else if ($action === 'updateBudget') {
                $this->updateBudget(isset($_POST['id']) ? intval($_POST['id']) : 0);
            } else if ($action === 'addBudget') {
                $this->addBudget();
            } else if ($action === 'checkBudget') {
                $this->checkBudget(isset($_POST['id']) ? intval($_POST['id']) : 0);
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

    private function deleteBudget($id) {
        global $link;

        $transactionOption = isset($_POST['transactionOption']) ? $_POST['transactionOption'] : 'retain';
    
        mysqli_begin_transaction($link);

        try {
            if ($transactionOption === 'nullify') {
                $updateTransactionsSQL = "UPDATE transactions SET budget_id = NULL WHERE budget_id = ?";
                $stmt = mysqli_prepare($link, $updateTransactionsSQL);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } elseif ($transactionOption === 'delete') {
                $fetchTransactionsSQL = "SELECT id, amount, trans_type, wallet_id FROM transactions WHERE budget_id = ?";
                $stmt = mysqli_prepare($link, $fetchTransactionsSQL);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
    
                while ($row = mysqli_fetch_assoc($result)) {
                    $transactionId = $row['id'];
                    $amount = $row['amount'];
                    $transType = $row['trans_type'];
                    $walletId = $row['wallet_id'];
    
                    if ($transType == 'Expense') {
                        $adjustWalletSQL = "UPDATE wallets SET amount = amount + ? WHERE id = ?";
                    } elseif ($transType == 'Income') {
                        $adjustWalletSQL = "UPDATE wallets SET amount = amount - ? WHERE id = ?";
                    }
    
                    $stmtUpdateWallet = mysqli_prepare($link, $adjustWalletSQL);
                    mysqli_stmt_bind_param($stmtUpdateWallet, "di", $amount, $walletId);
                    mysqli_stmt_execute($stmtUpdateWallet);
                    mysqli_stmt_close($stmtUpdateWallet);
                }
                mysqli_stmt_close($stmt);
    
                $deleteTransactionsSQL = "DELETE FROM transactions WHERE budget_id = ?";
                $stmt = mysqli_prepare($link, $deleteTransactionsSQL);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
    
            $sql = "DELETE FROM budgets WHERE id = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
    
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                mysqli_commit($link);
                $response = [
                    'status' => 'success',
                    'message' => 'Budget successfully deleted along with associated data based on your choices.'
                ];
            } else {
                mysqli_rollback($link);
                $response = [
                    'status' => 'error',
                    'message' => 'No budget found with the provided ID.'
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

    private function addBudget() {
        global $link;
    
        $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
        $title = isset($_POST['title']) ? $_POST['title'] : '';
        $category = isset($_POST['category']) ? $_POST['category'] : '';
        $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
        $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
        $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
        $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
        $created_at = isset($_POST['created_at']) ? $_POST['created_at'] : '';
    
        $formattedStartDate = date('Y-m-d', strtotime($start_date));
        $formattedEndDate = date('Y-m-d', strtotime($end_date));
        $formattedCreatedAt = date('Y-m-d H:i:s', strtotime($created_at));
    
        $sql = "INSERT INTO budgets (user_id, title, category, total_amount, remarks, start_date, end_date, created_at, current_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "issdssssd", $user_id, $title, $category, $total_amount, $remarks, $formattedStartDate, $formattedEndDate, $formattedCreatedAt, $total_amount);
            if (mysqli_stmt_execute($stmt)) {
                $budget_id = mysqli_insert_id($link);
    
                $sql = "INSERT INTO budget_log (user_id, budget_id, current_amount, created_at) VALUES (?, ?, ?, ?)";
                if ($stmt_log = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt_log, "iids", $user_id, $budget_id, $total_amount, $formattedCreatedAt);
                    if (mysqli_stmt_execute($stmt_log)) {
                        $response = [
                            'status' => 'success',
                            'message' => 'Budget and budget log successfully added.'
                        ];
                    } else {
                        $response = [
                            'status' => 'error',
                            'message' => 'Failed to add the budget log. Please try again.'
                        ];
                    }
                    mysqli_stmt_close($stmt_log);
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to prepare the budget log statement.'
                    ];
                }
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to add the budget. Please try again.'
                ];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Failed to prepare the add statement.'
            ];
        }
    
        mysqli_close($link);
    
        echo json_encode($response);
        exit;
    }      

    private function getBudget($id) {
        global $link;

        $sql = "SELECT id,title,category,total_amount,remarks,start_date,end_date,created_at,updated_at FROM budgets WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);

            mysqli_stmt_execute($stmt);

            mysqli_stmt_bind_result($stmt, $id, $title, $category, $total_amount, $remarks, $start_date, $end_date, $created_at, $updated_at);

            if (mysqli_stmt_fetch($stmt)) {
                $data = [
                    'id' => $id,
                    'title' => $title,
                    'category' => $category,
                    'total_amount' => $total_amount,
                    'remarks' => $remarks,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'created_at' => $created_at,
                    'updated_at' => $updated_at,
                ];
                echo json_encode($data);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No budget found with the provided ID.'
                ]);
            }

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get budget statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function updateBudget($id) {
        global $link; 

        $title = isset($_POST['title']) ? $_POST['title'] : '';
        $category = isset($_POST['category']) ? $_POST['category'] : '';
        //$total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
        $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
        $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
        $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
        $updated_at = isset($_POST['updated_at']) ? $_POST['updated_at'] : '';

        $formattedStartDate = date('Y-m-d', strtotime($start_date));
        $formattedEndDate = date('Y-m-d', strtotime($end_date));
        $formattedUpdatedAt = date('Y-m-d H:i:s', strtotime($updated_at));

        $sql = "UPDATE budgets SET title = ?, category = ?, remarks = ?, start_date = ?, end_date = ?, updated_at = ? WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssi", $title, $category, $remarks, $formattedStartDate, $formattedEndDate, $formattedUpdatedAt, $id);

            if (mysqli_stmt_execute($stmt)) {
                $response = [
                    'status' => 'success',
                    'message' => 'Budget successfully updated.'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to update the budget. Please try again.'
                ];
            }

            mysqli_stmt_close($stmt);
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Failed to prepare the update statement.'
            ];
        }

        mysqli_close($link);

        echo json_encode($response);
        exit;
    }

    private function getDashboardData($id) {
        global $link;
        
        $data = [];
    
        $query = "SELECT SUM(amount) as totalIncome FROM transactions WHERE user_id = " . (int)$id . " AND trans_type = 'Income'";
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['totalIncome'] = $row['totalIncome'] ? $row['totalIncome'] : 0;
        } else {
            $data['totalIncome'] = 0;
        }
    
        $query = "SELECT SUM(amount) as totalExpenses FROM transactions WHERE user_id = " . (int)$id . " AND trans_type = 'Expense' AND budget_id IS NOT NULL";
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['totalExpenses'] = $row['totalExpenses'] ? $row['totalExpenses'] : 0;
        } else {
            $data['totalExpenses'] = 0;
        }

        $query = "SELECT SUM(current_amount) as totalBalance FROM budgets WHERE user_id = " . (int)$id . "";
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['totalBalance'] = $row['totalBalance'] ? $row['totalBalance'] : 0;
        } else {
            $data['totalBalance'] = 0;
        }
    
        if ($data['totalIncome'] > 0) {
            $data['savingsRate'] = (($data['totalIncome'] - $data['totalExpenses']) / $data['totalIncome']) * 100;
        } else {
            $data['savingsRate'] = 0;
        }
    
        echo json_encode($data);
        exit;
    }

    private function getBudgetBreakdown($id) {
        global $link;
    
        $query = "SELECT category, SUM(total_amount) as total FROM budgets WHERE user_id = ? GROUP BY category";
        $stmt = $link->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }
    
    private function getMonthlySpendingTrend($id) {
        global $link;
    
        $query = "SELECT DATE_FORMAT(trans_date, '%Y-%m') as month, SUM(amount) as total 
                  FROM transactions 
                  WHERE trans_type = 'Expense' AND user_id = ? 
                  GROUP BY month 
                  ORDER BY month ASC";
    
        $stmt = $link->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }

    private function getBudgetTransaction($id) {
        global $link;
    
        $sql = "SELECT trans_type, created_at, description, amount FROM transactions WHERE budget_id = ? ORDER BY created_at DESC LIMIT 5";
    
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
                    'message' => 'No transactions found with the provided budget ID.'
                ]);
            }
    
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get budget statement.'
            ]);
        }
    
        mysqli_close($link);
        exit;
    }

    private function checkBudget($id) {
        global $link;
    
        $response = [
            'hasTransactions' => false
        ];
        
        $checkTransactionsSQL = "SELECT COUNT(*) as transaction_count FROM transactions WHERE budget_id = ?";
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

$controller = new BudgetController();
$controller->handleRequest();
?>
