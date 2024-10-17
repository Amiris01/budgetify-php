<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgetify | Manage Wallets</title>
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
      .wallet-info {
            margin-bottom: 15px;
        }
        .wallet-info i {
            margin-right: 10px;
            width: 20px;
        }
        .info-label {
            font-weight: bold;
            margin-right: 5px;
        }
        .info-value {
            color: #6c757d;
        }
        .transaction-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .transaction-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .expense {
            color: #dc3545;
        }
        .income {
            color: #28a745;
        }
        .transaction-date {
            color: #6c757d;
            font-size: 0.9em;
        }
        .transaction-time {
            color: #6c757d;
            font-size: 0.8em;
        }
        .chart-container {
          display: flex;
          justify-content: center;
          align-items: center;
          height: 100%;
        }

        .small-chart {
          width: 300px;
          height: 300px;
        }

        .card-body {
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
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
        Manage Wallets
      </h1>
    </div>

    <div class="container mt-4 mb-5">
  <div class="row mt-4">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">Expense Distribution</h5>
          <div class="chart-container">
            <canvas id="expenseBreakdown" class="small-chart"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">Income Trend</h5>
          <canvas id="monthlySpendingTrend" class="small-chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

    <div class="container my-3">
      <div class="row">
        <div class="col">

          <button class="btn btn-info addWallet action-icon" data-action="add" style="float: right">
            Add Wallet
          </button>
        </div>
      </div>
    </div>

    <div class="pt-3 mx-5">
    <?php
        require_once "./inc/config.php";
        $userId = $_SESSION['user_id'];
        $recordsPerPage = 5;
        $totalRecordsQuery = "SELECT COUNT(*) AS total FROM wallets WHERE user_id = ?";
        $stmt = mysqli_prepare($link, $totalRecordsQuery);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $totalRecords = mysqli_fetch_assoc($result)['total'];
        mysqli_free_result($result);

        $totalPages = ceil($totalRecords / $recordsPerPage);

        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $page = ($page > 0) ? $page : 1;
        $page = ($page <= $totalPages) ? $page : $totalPages;
        $startRecord = ($page - 1) * $recordsPerPage;

        $sql = "SELECT * FROM wallets WHERE user_id = ? LIMIT ?, ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $userId, $startRecord, $recordsPerPage);
        mysqli_stmt_execute($stmt);
    if ($result = mysqli_stmt_get_result($stmt)) {
        if (mysqli_num_rows($result) > 0) {
            echo '<table class="table table-bordered table-striped">';
            echo "<thead>";
            echo "<tr>";
            echo "<th>#</th>";
            echo "<th>Name</th>";
            echo "<th>Type</th>";
            echo "<th>Currency</th>";
            echo "<th>Institution</th>";
            echo "<th>Current Balance</th>";
            echo "<th>Action</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            $counter = 0;
            while ($row = mysqli_fetch_array($result)) {
                $counter++;
                echo "<tr>";
                echo "<td>" . $counter . "</td>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . $row['wallet_type'] . "</td>";
                echo "<td>" . $row['currency'] . "</td>";
                echo "<td>" . $row['fin_institute'] . "</td>";
                echo "<td>" . number_format($row['amount'], 2) . "</td>";
                echo "<td>";
                echo '<a href="#" class="action-icon" data-id="' . $row['id'] . '" data-action="view" title="View Record" data-toggle="tooltip"><span class="fa fa-eye" style="padding-right:5px;"></span></a>';
                echo '<a href="#" class="action-icon" data-id="' . $row['id'] . '" data-action="update" title="Update Record" data-toggle="tooltip"><span class="fa fa-pencil" style="padding-right:5px;"></span></a>';
                echo '<a href="#" class="action-icon" data-id="' . $row['id'] . '" data-action="delete" title="Delete Record" data-toggle="tooltip"><span class="fa fa-trash"></span></a>';
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            mysqli_free_result($result);
        } else {
            echo '<div class="alert alert-danger"><em>No records were found.</em></div>';
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
    mysqli_close($link);

    echo '<nav aria-label="Page navigation">';
    echo '<ul class="pagination justify-content-center">';
    if ($page > 1) {
        echo '<li class="page-item"><a class="page-link" href="?page=' . ($page - 1) . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
    }
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i == $page) ? ' active' : '';
        echo '<li class="page-item' . $active . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
    }
    if ($page < $totalPages) {
        echo '<li class="page-item"><a class="page-link" href="?page=' . ($page + 1) . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
    }
    echo '</ul>';
    echo '</nav>';
    ?>

    </div>

    <div class="modal fade" id="add-form" tabindex="-1" aria-labelledby="addWalletModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addWalletModalLabel">Add New Wallet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addForm" enctype="multipart/form-data" class="needs-validation" novalidate>
          <input type="hidden" name="user_id" id="user_id" value="<?php echo $userId; ?>">
          
          <div class="mb-3">
            <label for="name1" class="form-label">Name</label>
            <input type="text" class="form-control" id="name1" name="name1" required>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="type1" class="form-label">Type</label>
              <select class="form-select" id="type1" name="type1" required>
                <option value="" selected disabled>Select Type</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="currency1" class="form-label">Currency</label>
              <select class="form-select" id="currency1" name="currency1" required>
                <option value="" selected disabled>Select Currency</option>
              </select>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="fin1" class="form-label">Financial Institution</label>
            <select class="form-select" id="fin1" name="fin1" required>
              <option value="" selected disabled>Select Institution</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="desc1" class="form-label">Description</label>
            <textarea class="form-control" id="desc1" name="desc1" rows="2"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="add-wallet">Save changes</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="update-form" tabindex="-1" aria-labelledby="updateWalletModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="updateWalletModalLabel">Update Wallet</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="updateForm" enctype="multipart/form-data" class="needs-validation" novalidate>
          <input type="hidden" name="user_id" id="user_id" value="<?php echo $userId; ?>">

          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="type" class="form-label">Type</label>
              <select class="form-select" id="type" name="type" required>
                <option value="" selected disabled>Select Type</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="currency" class="form-label">Currency</label>
              <select class="form-select" id="currency" name="currency" required>
                <option value="" selected disabled>Select Currency</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label for="fin" class="form-label">Financial Institution</label>
            <select class="form-select" id="fin" name="fin" required>
              <option value="" selected disabled>Select Institution</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="desc" class="form-label">Description</label>
            <textarea class="form-control" id="desc" name="desc" rows="2"></textarea>
          </div>

          <div class="mb-3">
            <label for="is_active" class="form-label">Is Active</label>
            <select class="form-select" id="is_active" name="is_active" required>
              <option value="" selected disabled>Select Status</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveChanges">Save changes</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="view-modal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="viewModalLabel">
                    <i class="fas fa-wallet me-2"></i>Wallet Details
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="wallet-info">
                            <i class="fas fa-user text-primary"></i>
                            <span class="info-label">Name:</span>
                            <span class="info-value" id="view-name"></span>
                        </div>
                        <div class="wallet-info">
                            <i class="fas fa-money-bill-wave text-primary"></i>
                            <span class="info-label">Total Amount:</span>
                            <span class="info-value" id="view-total_amount"></span>
                        </div>
                        <div class="wallet-info">
                            <i class="fas fa-coins text-primary"></i>
                            <span class="info-label">Currency:</span>
                            <span class="info-value" id="view-currency"></span>
                        </div>
                        <div class="wallet-info">
                            <i class="fas fa-piggy-bank text-primary"></i>
                            <span class="info-label">Wallet Type:</span>
                            <span class="info-value" id="view-type"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="wallet-info">
                            <i class="fas fa-university text-primary"></i>
                            <span class="info-label">Financial Institution:</span>
                            <span class="info-value" id="view-fin"></span>
                        </div>
                        <div class="wallet-info">
                            <i class="fas fa-info-circle text-primary"></i>
                            <span class="info-label">Description:</span>
                            <span class="info-value" id="view-desc"></span>
                        </div>
                        <div class="wallet-info">
                            <i class="fas fa-toggle-on text-primary"></i>
                            <span class="info-label">Status:</span>
                            <span class="info-value" id="view-is_active"></span>
                        </div>
                        <div class="wallet-info">
                            <i class="fas fa-calendar-plus text-primary"></i>
                            <span class="info-label">Created at:</span>
                            <span class="info-value" id="view-created_at"></span>
                        </div>
                        <div class="wallet-info">
                            <i class="fas fa-calendar-check text-primary"></i>
                            <span class="info-label">Last Updated:</span>
                            <span class="info-value" id="view-updated_at"></span>
                        </div>
                    </div>
                </div>
                <hr>
                <h5><i class="fas fa-exchange-alt me-2"></i>Recent Transactions</h5>
                <div class="transaction-list" id="transaction-list">  

                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" id="confirm-modal">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to delete this wallet?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
      </div>
    </div>
  </div>
</div>

    <script>
                $(document).ready(function() {
              function populateDropdown(action, selector) {
                  $.ajax({
                      url: './helper/populate_dropdown.php',
                      method: 'GET',
                      dataType: 'json',
                      data: {
                          action: action
                      },
                      success: function(data) {
                          let $dropdown = $(selector);
                          $.each(data, function(index, item) {
                              $dropdown.append($('<option>', {
                                  value: item.name,
                                  text: item.name
                              }));
                          });
                      },
                      error: function() {
                          alert('Failed to load data.');
                      }
                  });
              }
              
              populateDropdown('getWalletType', '#type1');
              populateDropdown('getWalletCurrency', '#currency1');
              populateDropdown('getFinInstitute', '#fin1');
              populateDropdown('getWalletType', '#type');
              populateDropdown('getWalletCurrency', '#currency');
              populateDropdown('getFinInstitute', '#fin');
          });

        $('.action-icon').click(function() {
            var walletId = $(this).data('id');
            var action = $(this).data('action');

            if (action === 'view') {
              var walletId = Number($(this).data("id"));
              $.ajax({
                url: './controller/WalletController.php',
                method: "GET",
                data: {
                    action: 'getWallet',
                    id: walletId
                },
                dataType: 'json',
                success: function(data) {
                    if (data) {
                      $('#view-name').text(data.name !== null ? data.name : "");
                      $('#view-total_amount').text(data.amount !== null ? data.amount : 0.00);
                      $('#view-currency').text(data.currency !== null ? data.currency : "");
                      $('#view-type').text(data.wallet_type !== null ? data.wallet_type : "");
                      $('#view-fin').text(data.fin_institute !== null ? data.fin_institute : "");
                      $('#view-desc').text(data.description !== null ? data.description : "");
                      $('#view-is_active').text(data.is_active === 1 ? "Active" : "Inactive");
                      $('#view-created_at').text(data.created_at !== null ? data.created_at : "");
                      $('#view-updated_at').text(data.updated_at !== null ? data.updated_at : "");
                    } else {
                        console.error('No data received.');
                    }
                },
                error: function(error) {
                    console.error("There was an error fetching the wallet data:", error);
                }
            });

            $.ajax({
              url: './controller/WalletController.php',
              method: "GET",
              data: {
                  action: 'getWalletTransaction',
                  id: walletId
              },
              dataType: 'json',
              success: function(data) {
                  if (data.status === 'success' && data.data.length > 0) {
                      var transactionList = $('#transaction-list');
                      transactionList.empty(); // Clear any existing transactions

                      data.data.forEach(function(transaction) {
                          var transactionItem = '';

                          if (transaction.trans_type === 'Income') {
                              // Template for Income
                              transactionItem = `
                                  <div class="transaction-item">
                                      <div class="d-flex justify-content-between align-items-center">
                                          <div>
                                              <i class="fas fa-arrow-up income me-2"></i>
                                              <span class="transaction-date">${new Date(transaction.created_at).toLocaleDateString()}</span>
                                              <span class="transaction-time ms-1">${new Date(transaction.created_at).toLocaleTimeString()}</span>
                                              <strong class="ms-2">${transaction.description}</strong>
                                          </div>
                                          <strong class="income">+$${transaction.amount.toFixed(2)}</strong>
                                      </div>
                                  </div>
                              `;
                          } else if (transaction.trans_type === 'Expense') {
                              // Template for Expense
                              transactionItem = `
                                  <div class="transaction-item">
                                      <div class="d-flex justify-content-between align-items-center">
                                          <div>
                                              <i class="fas fa-arrow-down expense me-2"></i>
                                              <span class="transaction-date">${new Date(transaction.created_at).toLocaleDateString()}</span>
                                              <span class="transaction-time ms-1">${new Date(transaction.created_at).toLocaleTimeString()}</span>
                                              <strong class="ms-2">${transaction.description}</strong>
                                          </div>
                                          <strong class="expense">-$${transaction.amount.toFixed(2)}</strong>
                                      </div>
                                  </div>
                              `;
                          }

                          transactionList.append(transactionItem);
                      });
                  } else {
                    console.error('No data received.');
                    $('#transaction-list').html(`
                        <div class="alert alert-warning d-flex align-items-center mt-3" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div>
                                No recent transactions found.
                            </div>
                        </div>
                    `);
                  }
              },
              error: function(error) {
                  console.error("There was an error fetching the wallet transactions:", error);
              }
          });

            $('#view-modal').modal('show');

            } else if (action === 'update') {
              var walletId = Number($(this).data("id"));
              $("#saveChanges").attr("data-id", walletId);
              $.ajax({
                url: './controller/WalletController.php',
                method: "GET",
                data: {
                    action: 'getWallet',
                    id: walletId
                },
                dataType: 'json',
                success: function(data) {
                    if (data) {
                        $('#updateForm #name').val(data.name);
                        $('#updateForm #type').val(data.wallet_type);
                        $('#updateForm #currency').val(data.currency);
                        $('#updateForm #fin').val(data.fin_institute);
                        $('#updateForm #desc').val(data.description);
                        $('#updateForm #is_active').val(data.is_active);
                        $('#update-form').modal('show');
                    } else {
                        console.error('No data received.');
                    }
                },
                error: function(error) {
                    console.error("There was an error fetching the wallet data:", error);
                }
              });
                } else if (action === 'delete') {
                  $('#confirm-modal').modal('show');
                  $('#confirmDelete').data('id', walletId);
                } else if(action === 'add'){
                  $('#add-form').modal('show');
                }
              });

        $('#add-wallet').click(function() {
            var createdAt = new Date().toISOString();
            var formData = {
              action: 'addWallet',
              name: $('#name1').val(),
              wallet_type: $('#type1').val(),
              currency: $('#currency1').val(),
              fin_institute: $('#fin1').val(),
              description: $('#desc1').val(),
              created_at: createdAt,
              user_id: $('#user_id').val(),
              is_active : 1,
            };

            $.ajax({
              url: './controller/WalletController.php',
              type: 'POST',
              data: formData,
              dataType: 'json',
              success: function(response) {
                if (response.status === 'success') {
                  alert(response.message);
                  $('#add-form').modal('hide'); 
                  location.reload();
                } else {
                  alert(response.message);
                }
              },
              error: function(xhr, status, error) {
                alert('An error occurred while processing the request.');
              }
            });
          });

          $("#saveChanges").click(function () {
                var walletId = $(this).data("id");
                var updatedAt = new Date().toISOString();
                var formData = new FormData($('#updateForm')[0]);

                formData.append('action', 'updateWallet');
                formData.append('updated_at', updatedAt);
                formData.append('id', walletId);

                for (var pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                $.ajax({
                    url: './controller/WalletController.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                            $('#update-form').modal('hide'); 
                            location.reload();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.error('Response Text:', xhr.responseText);
                        alert('An error occurred while processing the request.');
                    }
                });
          });

          $('#confirmDelete').click(function() {
            var walletId = $(this).data('id');
            
            $.ajax({
              url: './controller/WalletController.php',
              type: 'POST',
              data: {
                action: 'deleteWallet',
                id: walletId,
              },
              dataType: 'json',
              success: function(response) {
                if (response.status === 'success') {
                  alert(response.message);
                  location.reload();
                } else {
                  alert(response.message);
                }
              },
              error: function(xhr, status, error) {
                alert('An error occurred while processing the request.');
              }
            });
          });

          $(document).ready(function() {
            const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

            $.ajax({
                url: './controller/WalletController.php',
                method: 'GET',
                data: {
                    action: 'getExpenseBreakdown',
                    id: userId
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'success') {
                        const categories = data.data.map(item => item.category);
                        const amounts = data.data.map(item => parseFloat(item.total));

                        const ctx = document.getElementById('expenseBreakdown').getContext('2d');
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: categories,
                                datasets: [{
                                    label: 'Expenses by Category',
                                    data: amounts,
                                    backgroundColor: [
                                        'rgba(255, 99, 132, 0.6)',
                                        'rgba(54, 162, 235, 0.6)',
                                        'rgba(255, 206, 86, 0.6)',
                                        'rgba(75, 192, 192, 0.6)',
                                        'rgba(153, 102, 255, 0.6)',
                                        'rgba(255, 159, 64, 0.6)'
                                    ],
                                    borderColor: [
                                        'rgba(255, 99, 132, 1)',
                                        'rgba(54, 162, 235, 1)',
                                        'rgba(255, 206, 86, 1)',
                                        'rgba(75, 192, 192, 1)',
                                        'rgba(153, 102, 255, 1)',
                                        'rgba(255, 159, 64, 1)'
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    }
                                }
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching expense breakdown data:', error);
                }
            });

            $.ajax({
                url: './controller/WalletController.php',
                method: 'GET',
                data: {
                    action: 'getMonthlyIncomeTrend',
                    id: userId
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'success') {
                      console.log(data);
                        const months = data.data.map(item => item.month);
                        const totals = data.data.map(item => parseFloat(item.total));

                        const ctx = document.getElementById('monthlySpendingTrend').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: months,
                                datasets: [{
                                    label: 'Total Income by Month',
                                    data: totals,
                                    fill: false,
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    tension: 0.1
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Month'
                                        }
                                    },
                                    y: {
                                        title: {
                                            display: true,
                                            text: 'Total Income (RM)'
                                        }
                                    }
                                }
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching monthly spending trend data:', error);
                }
            });
        });

    </script>
</body>
</html>