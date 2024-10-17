<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgetify | Dashboard</title>
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
    .alert-container {
      margin-top: 10px;
      max-height: 225px;
      overflow-y: auto;
    }

    .alert-item {
      margin-bottom: 10px;
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
        Dashboard
      </h1>
    </div>

    <div class="container mt-4">
        <div class="row">
            <!-- Budget vs Actual -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Budget vs. Actual</h5>
                        <canvas id="budgetVsActualChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- Over-Budget Alerts -->
            <div class="col-md-6 mb-4">
              <div class="card h-100">
                <div class="card-body">
                <h5 class="card-title">Over-Budget Alerts</h5>
                  <div id="overBudgetAlerts" class="alert-container">
                    <!-- Alerts will be dynamically inserted here -->
                  </div>
                </div>
              </div>
            </div>
        </div>
        <div class="row">
            <!-- Net Worth Over Time -->
            <div class="col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Net Worth Over Time</h5>
                        <canvas id="netWorthOverTimeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
      const userId = <?php echo $_SESSION['user_id']; ?>;

      function fetchBudgetVsActual() {
        $.ajax({
          url: './helper/dashboard_data.php',
          method: 'GET',
          data: { action: 'getBudgetVsActual', id: userId },
          dataType: 'json',
          success: function(data) {
            if (data.status === 'success') {
              const ctx = document.getElementById('budgetVsActualChart').getContext('2d');
              new Chart(ctx, {
                type: 'bar',
                data: {
                  labels: data.data.labels,
                  datasets: [{
                    label: 'Budgeted',
                    data: data.data.budgeted,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                  }, {
                    label: 'Actual',
                    data: data.data.actual,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                  }]
                },
                options: {
                  responsive: true,
                  plugins: {
                    legend: { position: 'top' }
                  }
                }
              });
            }
          },
          error: function(xhr, status, error) {
            console.error('Error fetching budget vs actual data:', error);
          }
        });
      }

      function fetchOverBudgetAlerts() {
        $.ajax({
          url: './helper/dashboard_data.php',
          method: 'GET',
          data: { action: 'getOverBudgetAlerts', id: userId },
          dataType: 'json',
          success: function(data) {
            if (data.status === 'success') {
              $('#overBudgetAlerts').html(
                data.data.map(alert => `
                  <div class="alert alert-warning alert-dismissible fade show alert-item" role="alert">
                    ${alert}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
                `).join('')
              );
            }
          },
          error: function(xhr, status, error) {
            console.error('Error fetching over-budget alerts:', error);
          }
        });
      }

      function fetchNetWorthOverTime() {
        $.ajax({
          url: './helper/dashboard_data.php',
          method: 'GET',
          data: { action: 'getNetWorthOverTime', id: userId },
          dataType: 'json',
          success: function(data) {
            if (data.status === 'success') {
              const ctx = document.getElementById('netWorthOverTimeChart').getContext('2d');
              new Chart(ctx, {
                type: 'line',
                data: {
                  labels: data.data.months,
                  datasets: [{
                    label: 'Net Worth',
                    data: data.data.netWorth,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    fill: false,
                    tension: 0.1
                  }]
                },
                options: {
                  responsive: true,
                  scales: {
                    x: {
                      title: { display: true, text: 'Month' }
                    },
                    y: {
                      title: { display: true, text: 'Net Worth' }
                    }
                  }
                }
              });
            }
          },
          error: function(xhr, status, error) {
            console.error('Error fetching net worth over time data:', error);
          }
        });
      }

      $(document).ready(function() {
        fetchBudgetVsActual();
        fetchOverBudgetAlerts();
        fetchNetWorthOverTime();
      });

    </script>

</body>
</html>