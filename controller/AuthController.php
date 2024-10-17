<?php
class AuthController {
    private $link;

    public function __construct($link) {
        $this->link = $link;
    }

    public function register() {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
        $stmt = mysqli_prepare($this->link, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        if ($row['count'] > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Username already exists.']);
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
        $stmt = mysqli_prepare($this->link, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $hashedPassword);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
        }
    }

    public function login() {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $sql = "SELECT id,password FROM users WHERE username = ?";
        $stmt = mysqli_prepare($this->link, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $user['id'];
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
        }
    }
}

$link = mysqli_connect('localhost', 'root', '', 'budgetify');
$controller = new AuthController($link);

if ($_POST['action'] === 'register') {
    $controller->register();
} else if($_POST['action'] === 'login'){
    $controller->login();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>
