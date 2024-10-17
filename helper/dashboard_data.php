<?php
require_once '../inc/config.php';


class DashboardData
{
    public function handleRequest()
    {

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
            $action = $_GET['action'];

            if ($action == 'getBudgetVsActual') {
                $this->getBudgetVsActual(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action == 'getOverBudgetAlerts') {
                $this->getOverBudgetAlerts(isset($_GET['id']) ? intval($_GET['id']) : 0);
            } else if ($action == 'getNetWorthOverTime') {
                $this->getNetWorthOverTime(isset($_GET['id']) ? intval($_GET['id']) : 0);
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

    private function getBudgetVsActual($id)
    {
        global $link;

        $query = "
        SELECT 
            c.name AS category, 
            IFNULL(SUM(b.total_amount), 0) AS budgeted, 
            IFNULL(SUM(t.amount), 0) AS actual
        FROM 
            category c
        LEFT JOIN 
            budgets b ON c.name = b.category AND b.user_id = ?
        LEFT JOIN 
            transactions t ON c.name = t.category AND t.user_id = ?
        WHERE 
            c.tag = 'Expense'
        GROUP BY 
            c.name
    ";


        if ($stmt = mysqli_prepare($link, $query)) {
            mysqli_stmt_bind_param($stmt, 'ii', $id, $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $data = ['labels' => [], 'budgeted' => [], 'actual' => []];

            while ($row = mysqli_fetch_assoc($result)) {
                $data['labels'][] = $row['category'];
                $data['budgeted'][] = (float) $row['budgeted'];
                $data['actual'][] = (float) $row['actual'];
            }

            mysqli_stmt_close($stmt);

            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database query failed']);
        }
    }

    private function getOverBudgetAlerts($id)
    {
        global $link;

        $query = "
            SELECT c.name as category, 
                   IFNULL(SUM(t.amount), 0) as actual, 
                   b.total_amount as budgeted
            FROM category c
            LEFT JOIN transactions t ON c.name = t.category AND t.user_id = ?
            INNER JOIN budgets b ON c.name = b.category AND b.user_id = ?
            GROUP BY c.name
            HAVING actual > budgeted
        ";

        if ($stmt = mysqli_prepare($link, $query)) {
            mysqli_stmt_bind_param($stmt, 'ii', $id, $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $alerts = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $amountOverBudget = number_format($row['actual'] - $row['budgeted'], 2);
                $alerts[] = "You are over-budget in " . $row['category'] .
                    " by RM " . $amountOverBudget;
            }

            mysqli_stmt_close($stmt);

            echo json_encode(['status' => 'success', 'data' => $alerts]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database query failed']);
        }
    }

    private function getNetWorthOverTime($id)
    {
        global $link;

        $query = "
        SELECT DATE_FORMAT(t.trans_date, '%Y-%m') as month, 
               SUM(CASE WHEN t.trans_type = 'Income' THEN t.amount ELSE -t.amount END) as net_worth
        FROM transactions t
        WHERE t.user_id = ?
        GROUP BY DATE_FORMAT(t.trans_date, '%Y-%m')
        ORDER BY t.trans_date
    ";

        if ($stmt = mysqli_prepare($link, $query)) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $data = ['months' => [], 'netWorth' => []];

            while ($row = mysqli_fetch_assoc($result)) {
                $data['months'][] = $row['month'];
                $data['netWorth'][] = (float) $row['net_worth'];
            }

            mysqli_stmt_close($stmt);

            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database query failed']);
        }
    }
}

$controller = new DashboardData();
$controller->handleRequest();
