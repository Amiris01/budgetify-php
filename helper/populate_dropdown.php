<?php
require_once '../inc/config.php';

class DropdownHelper {

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
            $action = $_GET['action'];

            if ($action === 'getApparelType') {
                $this->getApparelType();
            } else if ($action === 'getApparelBrand'){
                $this->getApparelBrand();
            } else if ($action === 'getApparelStyle'){
                $this->getApparelStyle();
            } else if ($action === 'getWalletType'){
                $this->getWalletType();
            } else if ($action === 'getWalletCurrency'){
                $this->getWalletCurrency();
            } else if ($action === 'getFinInstitute'){
                $this->getFinInstitute();
            } else if ($action === 'getUserWallet'){
                $this->getUserWallet(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getCategory'){
                $this->getCategory($_GET['type'] ? $_GET['type'] : '');
            } else if ($action === 'getUserEvent'){
                $this->getUserEvent(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getUserBudget'){
                $this->getUserBudget(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action === 'getBudgetByCategory'){
                $this->getBudgetByCategory(isset($_GET['id']) ? intval($_GET['id']) : 0,$_GET['category'] ? $_GET['category'] : '' );
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

    private function getApparelType() {
        global $link;

        $sql = "SELECT id, name FROM apparel_type";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            echo json_encode($data);

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get apparel type statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function getApparelBrand() {
        global $link;

        $sql = "SELECT id, name FROM brands";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            echo json_encode($data);

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get apparel brand statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function getApparelStyle() {
        global $link;

        $sql = "SELECT id, name FROM style";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            echo json_encode($data);

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get apparel style statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function getWalletType() {
        global $link;

        $sql = "SELECT id, name FROM wallet_type";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            echo json_encode($data);

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get wallet type statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function getWalletCurrency() {
        global $link;

        $sql = "SELECT id, name FROM currency";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            echo json_encode($data);

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get currency statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function getFinInstitute() {
        global $link;

        $sql = "SELECT id, name FROM finance_institute";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            echo json_encode($data);

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get finance institute statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function getUserWallet($id) {
        global $link;

        $sql = "SELECT id, name, fin_institute,amount FROM wallets WHERE user_id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            echo json_encode($data);

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get user wallet statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function getCategory($type) {
        global $link;

        $sql = "SELECT id, name FROM category WHERE tag = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $type);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            echo json_encode($data);

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get category statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function getUserEvent($id) {
        global $link;

        $sql = "SELECT id, name FROM events WHERE user_id = ? AND status != 'Cancelled' ";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            echo json_encode($data);

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get user event statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function getUserBudget($id) {
        global $link;

        $sql = "SELECT id, title FROM budgets WHERE user_id = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            echo json_encode($data);

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get user event statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }

    private function getBudgetByCategory($id, $category) {
        global $link;

        $sql = "SELECT id, title FROM budgets WHERE user_id = ? AND category = ?";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "is", $id, $category);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            echo json_encode($data);

            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare the get user event statement.'
            ]);
        }

        mysqli_close($link);
        exit;
    }
}

$dropdownHelper = new DropdownHelper();
$dropdownHelper->handleRequest();
?>
