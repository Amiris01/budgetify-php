<?php
require_once '../inc/config.php';

class ApparelController {

    public function handleRequest() {

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
            $action = $_GET['action'];

            if ($action === 'getApparel') {
                $this->getApparel(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if($action === 'getDashboardData'){
                $this->getDashboardData(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Unknown action.'
                ]);
                exit;
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'deleteApparel') {
                $this->deleteApparel(isset($_POST['id']) ? intval($_POST['id']) : 0);
            } else if ($action === 'updateApparel') {
                $this->updateApparel(isset($_POST['id']) ? intval($_POST['id']) : 0);
            } else if ($action === 'addApparel') {
                $this->addApparel();
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

    private function deleteApparel($id) {
        global $link;

        $sql = "DELETE FROM apparels WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Apparel successfully deleted.'
                    ];
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'No apparel found with the provided ID.'
                    ];
                }
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to delete the apparel. Please try again.'
                ];
            }
            mysqli_stmt_close($stmt);
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Failed to prepare the delete statement.'
            ];
        }

        mysqli_close($link);
        echo json_encode($response);
        exit;
    }

    private function addApparel() {
        global $link;

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $type = isset($_POST['type1']) ? $_POST['type1'] : '';
        $size = isset($_POST['size1']) ? $_POST['size1'] : '';
        $color = isset($_POST['color1']) ? $_POST['color1'] : '';
        $quantity = isset($_POST['quantity1']) ? intval($_POST['quantity1']) : 0;
        $brand = isset($_POST['brand1']) ? $_POST['brand1'] : '';
        $price = isset($_POST['price1']) ? floatval($_POST['price1']) : 0;
        $style = isset($_POST['style1']) ? $_POST['style1'] : '';
        $remarks = isset($_POST['remarks1']) ? $_POST['remarks1'] : '';
        $purchase_date = isset($_POST['purchase_date1']) ? $_POST['purchase_date1'] : '';
        $created_at = isset($_POST['created_at']) ? $_POST['created_at'] : '';

        $formattedPurchaseDate = date('Y-m-d', strtotime($purchase_date));
        $formattedCreatedAt = date('Y-m-d H:i:s', strtotime($created_at));

        $sql = "INSERT INTO apparels (user_id, type, size, color, quantity, brand, price, style, remarks, purchase_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "isssisdssss", $user_id, $type, $size, $color, $quantity, $brand, $price, $style, $remarks, $formattedPurchaseDate, $formattedCreatedAt);
            if (mysqli_stmt_execute($stmt)) {
                $response = [
                    'status' => 'success',
                    'message' => 'Apparel successfully added.'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to add the apparel. Please try again.'
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

    private function getApparel($id) {
        global $link;

        $sql = "SELECT id, type, size, color, quantity, brand, price, style, remarks, purchase_date, created_at, updated_at FROM apparels WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);

            mysqli_stmt_execute($stmt);

            mysqli_stmt_bind_result($stmt, $id, $type, $size, $color, $quantity, $brand, $price, $style, $remarks, $purchase_date, $created_at, $updated_at);

            if (mysqli_stmt_fetch($stmt)) {
                $data = [
                    'id' => $id,
                    'type' => $type,
                    'size' => $size,
                    'color' => $color,
                    'quantity' => $quantity,
                    'brand' => $brand,
                    'price' => $price,
                    'style' => $style,
                    'remarks' => $remarks,
                    'purchase_date' => $purchase_date,
                    'created_at' => $created_at,
                    'updated_at' => $updated_at,
                ];
                echo json_encode($data);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No apparel found with the provided ID.'
                ]);
            }

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get apparel statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function updateApparel($id) {
        global $link; 

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $type = isset($_POST['type']) ? $_POST['type'] : '';
        $size = isset($_POST['size']) ? $_POST['size'] : '';
        $color = isset($_POST['color']) ? $_POST['color'] : '';
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
        $brand = isset($_POST['brand']) ? $_POST['brand'] : '';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $style = isset($_POST['style']) ? $_POST['style'] : '';
        $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
        $purchase_date = isset($_POST['purchase_date']) ? $_POST['purchase_date'] : '';
        $updated_at = isset($_POST['updated_at']) ? $_POST['updated_at'] : '';

        $formattedPurchaseDate = date('Y-m-d', strtotime($purchase_date));
        $formattedUpdatedAt = date('Y-m-d H:i:s', strtotime($updated_at));

        $sql = "UPDATE apparels SET type = ?, size = ?, color = ?, quantity = ?, brand = ?, price = ?, style = ?, remarks = ?, purchase_date = ?, updated_at = ? WHERE id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssisdssssi", $type, $size, $color, $quantity, $brand, $price, $style, $remarks, $formattedPurchaseDate, $formattedUpdatedAt, $id);

            if (mysqli_stmt_execute($stmt)) {
                $response = [
                    'status' => 'success',
                    'message' => 'Apparel successfully updated.'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to update the apparel. Please try again.'
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
        
        $query = "SELECT COUNT(*) as totalApparels FROM apparels WHERE user_id = " . $id;
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['totalApparels'] = $row['totalApparels'];
        }
    
        $query = "SELECT COUNT(*) as expensiveApparels FROM apparels WHERE price >= 200 AND user_id = " . $id;
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['expensiveApparels'] = $row['expensiveApparels'];
        }
    
        $query = "SELECT COUNT(*) as apparelsThisMonth FROM apparels WHERE MONTH(purchase_date) = MONTH(CURDATE()) AND YEAR(purchase_date) = YEAR(CURDATE()) AND user_id = " . $id;
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['apparelsThisMonth'] = $row['apparelsThisMonth'];
        }
    
        $query = "SELECT SUM(price) as totalSpending FROM apparels WHERE user_id = " . $id;
        $result = $link->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $data['totalSpending'] = $row['totalSpending'] ?? 0;
        }
    
        echo json_encode($data);
        exit;
    }
}

$controller = new ApparelController();
$controller->handleRequest();
?>
