<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgetify | Login</title>
    <?php
    include_once('./inc/asset.php');
    ?>
    <style>
      .rainbow_text_animated {
        background: linear-gradient(
          to right,
          #6666ff,
          #0099ff,
          #00ff00,
          #ff3399,
          #6666ff
        );
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        animation: rainbow_animation 6s ease-in-out infinite;
        background-size: 400% 100%;
      }

      @keyframes rainbow_animation {
        0%,
        100% {
          background-position: 0 0;
        }

        50% {
          background-position: 100% 0;
        }
      }
      .container {
        max-width: 500px;
    }
    .card {
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }
    .card-body {
        padding: 30px;
    }
    .form-control {
        border-radius: 5px;
    }
    .btn-primary {
        background-color: #6666ff;
        border: none;
        border-radius: 5px;
        padding: 10px 20px;
    }
    .btn-primary:hover {
        background-color: #5555dd;
    }
    </style>
</head>
<body>

    <?php
        include_once('./public/layout/navbar.php');
    ?>

    <div class="d-flex justify-content-center pt-3">
      <h1
        class="rainbow_text_animated"
        style="font-weight: bolder; padding: 10px"
      >
        Login Page
      </h1>
    </div>

    <div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-center">Login</h4>
                    <form id="loginForm" method="POST" action="./controller/AuthController.php">
                        <div class="form-group mb-3">
                            <label for="username"><b>Username</b></label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password"><b>Password</b></label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <!-- <a href="./forgot_password.php" class="text-decoration-none">Forgot Password?</a> -->
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: './controller/AuthController.php',
                method: 'POST',
                data: $(this).serialize() + '&action=login',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        window.location.href = './dashboard.php';
                    } else {
                        alert(response.message || 'Login failed, please try again.');
                    }
                },
                error: function() {
                    alert('An error occurred while processing the request.');
                }
            });
        });
    });
</script>

</body>
</html>