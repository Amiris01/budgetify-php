<?php
require_once '../inc/config.php';

class WalletController{

    public function handleRequest(){

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
            $action = $_GET['action'];

            if ($action === 'getWallet') {
                $this->getWallet(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getWalletTransaction') {
                $this->getWalletTransaction(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getExpenseBreakdown') {
                $this->getExpenseBreakdown(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getMonthlyIncomeTrend') {
                $this->getMonthlyIncomeTrend(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unknown action.'
                ]);
                exit;
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'deleteWallet') {
               $this->deleteWallet(isset($_POST['id']) ? intval($_POST['id']) : 0);
            } else if ($action === 'updateWallet') {
                $this->updateWallet(isset($_POST['id']) ? intval($_POST['id']) : 0);
            } else if ($action === 'addWallet') {
                $this->addWallet();
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

    private function addWallet() {
        global $link;
    
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : '';
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        $wallet_type = isset($_POST['wallet_type']) ? $_POST['wallet_type'] : '';
        $currency = isset($_POST['currency']) ? $_POST['currency'] : '';
        $fin_institute = isset($_POST['fin_institute']) ? $_POST['fin_institute'] : '';
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $is_active = isset($_POST['is_active']) ? $_POST['is_active'] : '';
        $created_at = isset($_POST['created_at']) ? $_POST['created_at'] : '';
        $amount = 0;
    
        $formattedCreatedAt = date('Y-m-d H:i:s', strtotime($created_at));
    
        $sql = "INSERT INTO wallets (user_id, name, currency, is_active, wallet_type, fin_institute, description, created_at, amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ississssd", $user_id, $name, $currency, $is_active, $wallet_type, $fin_institute, $description, $formattedCreatedAt, $amount);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['status' => 'success', 'message' => 'Wallet successfully added.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to add the wallet. Please try again.'];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to prepare the add statement.'];
        }
    
        mysqli_close($link);
    
        echo json_encode($response);
        exit;
    }

    private function getWallet($id) {
        global $link;

        $sql = "SELECT name,amount,currency,is_active,wallet_type,fin_institute,description,created_at,updated_at FROM wallets WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);

            mysqli_stmt_execute($stmt);

            mysqli_stmt_bind_result($stmt, $name, $amount, $currency, $is_active, $wallet_type, $fin_institute, $description, $created_at, $updated_at);

            if (mysqli_stmt_fetch($stmt)) {
                $data = [
                    'id' => $id,
                    'name' => $name,
                    'amount' => $amount,
                    'currency' => $currency,
                    'is_active' => $is_active,
                    'wallet_type' => $wallet_type,
                    'fin_institute' => $fin_institute,
                    'description' => $description,
                    'created_at' => $created_at,
                    'updated_at' => $updated_at,
                ];
                echo json_encode($data);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No wallet found with the provided ID.'
                ]);
            }

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get wallet statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function updateWallet($id) {
        global $link; 

        $name = isset($_POST['name']) ? $_POST['name'] : '';
        $wallet_type = isset($_POST['type']) ? $_POST['type'] : '';
        $currency = isset($_POST['currency']) ? $_POST['currency'] : '';
        $fin_institute = isset($_POST['fin']) ? $_POST['fin'] : '';
        $description = isset($_POST['desc']) ? $_POST['desc'] : '';
        $is_active = isset($_POST['is_active']) ? $_POST['is_active'] : '';
        $updated_at = isset($_POST['updated_at']) ? $_POST['updated_at'] : '';

        $formattedUpdatedAt = date('Y-m-d H:i:s', strtotime($updated_at));

        $sql = "UPDATE wallets SET name = ?, wallet_type = ?, currency = ?, fin_institute = ?, description = ?, is_active = ?, updated_at = ? WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssisi", $name, $wallet_type, $currency, $fin_institute, $description, $is_active, $formattedUpdatedAt, $id);

            if (mysqli_stmt_execute($stmt)) {
                $response = [
                    'status' => 'success',
                    'message' => 'Wallet successfully updated.'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to update the wallet. Please try again.'
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

    private function deleteWallet($walletId) {
        global $link;
    
        if ($this->hasActiveTransactions($walletId)) {
            $response = [
                'status' => 'error',
                'message' => 'Cannot delete the wallet. It has active transactions.'
            ];
            echo json_encode($response);
            mysqli_close($link);
            exit;
        }
    
        $deleteWalletSQL = "DELETE FROM wallets WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $deleteWalletSQL)) {
            mysqli_stmt_bind_param($stmt, "i", $walletId);
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Wallet successfully deleted.'
                    ];
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'No wallet found with the provided ID.'
                    ];
                }
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to delete the wallet. Please try again.'
                ];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Failed to prepare the delete statement for the wallet.'
            ];
        }
    
        mysqli_close($link);
        echo json_encode($response);
        exit;
    }    

    private function getWalletTransaction($id) {
        global $link;
    
        $sql = "SELECT trans_type, created_at, description, amount FROM transactions WHERE wallet_id = ? ORDER BY created_at DESC LIMIT 5";
    
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
                    'message' => 'No transactions found with the provided wallet ID.'
                ]);
            }
    
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get wallet statement.'
            ]);
        }
    
        mysqli_close($link);
        exit;
    }
    
    private function getExpenseBreakdown($id) {
        global $link;
    
        $query = "SELECT category, SUM(amount) as total FROM transactions WHERE trans_type = 'Expense' AND user_id = ? GROUP BY category";
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

    private function getMonthlyIncomeTrend($id) {
        global $link;
    
        $query = "SELECT DATE_FORMAT(trans_date, '%Y-%m') as month, SUM(amount) as total 
                  FROM transactions 
                  WHERE trans_type = 'Income' AND user_id = ? 
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

    private function hasActiveTransactions($walletId) {
        global $link;
    
        $checkTransactionsSQL = "SELECT COUNT(*) FROM transactions WHERE wallet_id = ? ";
        if ($stmt = mysqli_prepare($link, $checkTransactionsSQL)) {
            mysqli_stmt_bind_param($stmt, "i", $walletId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $count);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
    
            return $count > 0;
        }
    
        return false; 
    }
    
    
}

$controller = new WalletController();
$controller->handleRequest();

?>