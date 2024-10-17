<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Budgetify | Manage Budget</title>
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
        Manage Budgets
      </h1>
    </div>

    <div class="container mt-4 mb-5">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
    <div class="col">
      <div class="card text-white bg-primary h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h5 class="card-title">Current Total Income</h5>
          <p class="card-text display-4 mb-0" id="totalIncome">RM 0</p>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card text-white bg-danger h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h5 class="card-title">Total Expenses</h5>
          <p class="card-text display-4 mb-0" id="totalExpenses">RM 0</p>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card text-white bg-success h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h5 class="card-title">Savings Rate</h5>
          <p class="card-text display-4 mb-0" id="savingsRate">0%</p>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card text-white bg-info h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h5 class="card-title">Remaining Budget</h5>
          <p class="card-text display-4 mb-0" id="remainingBudget">RM 0</p>
        </div>
      </div>
    </div>
  </div>
  
  <div class="row mt-4">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">Budget Breakdown</h5>
          <div class="chart-container">
            <canvas id="budgetBreakdown" class="small-chart"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">Monthly Spending Trend</h5>
          <canvas id="monthlySpendingTrend" class="small-chart"></canvas>
        </div>
      </div>
    </div>
  </div>

    <div class="container my-3">
      <div class="row">
        <div class="col">

          <button class="btn btn-info addBudget action-icon" data-action="add" style="float: right">
            Add Budget
          </button>
        </div>
      </div>
    </div>

    <div class="pt-3 mx-5">

    <?php
        require_once "./inc/config.php";
        $userId = $_SESSION['user_id'];
        $recordsPerPage = 5;
        $totalRecordsQuery = "SELECT COUNT(*) AS total FROM budgets WHERE user_id = ?";
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

        $sql = "SELECT * FROM budgets WHERE user_id = ? LIMIT ?, ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $userId, $startRecord, $recordsPerPage);
        mysqli_stmt_execute($stmt);
      if ($result = mysqli_stmt_get_result($stmt)) {
          if (mysqli_num_rows($result) > 0) {
              echo '<table class="table table-bordered table-striped">';
              echo "<thead>";
              echo "<tr>";
              echo "<th>#</th>";
              echo "<th>Title</th>";
              echo "<th>Category</th>";
              echo "<th>Total Amount (RM)</th>";
              echo "<th>Remarks</th>";
              echo "<th>Start Date</th>";
              echo "<th>End Date</th>";
              echo "<th>Action</th>";
              echo "</tr>";
              echo "</thead>";
              echo "<tbody>";
              $counter = 0;
              while ($row = mysqli_fetch_array($result)) {
                $counter++;
                  echo "<tr>";
                  echo "<td>" . $counter . "</td>";
                  echo "<td>" . $row['title'] . "</td>";
                  echo "<td>" . $row['category'] . "</td>";
                  echo "<td>" . number_format($row['total_amount'], 2) . "</td>";
                  echo "<td>" . $row['remarks'] . "</td>";
                  echo "<td>" . (new DateTime($row['start_date']))->format('d/m/Y') . "</td>";
                  echo "<td>" . (new DateTime($row['end_date']))->format('d/m/Y') . "</td>";
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

    <div class="modal" tabindex="-1" id="update-form">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Update Budget</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <form id="updateForm" enctype="multipart/form-data">
            <input type="hidden" name="user_id" id="user_id" value="<?php echo $userId; ?>">
              <div>
                <label for="title"><b>Title</b></label>
                <input
                  type="text"
                  name="title"
                  id="title"
                  class="form-control"
                />
              </div>
              <div>
                <label for="category"><b>Category</b></label>
                <select class="form-select" id="category" name="category" required>
                <option value="" selected disabled>Select Category</option>
              </select>
              </div>
              <div style="display: none;">
                <label for="total_amount"><b>Total Amount</b></label>
                <input
                  type="number"
                  name="total_amount"
                  id="total_amount"
                  class="form-control"
                />
              </div>
              <div>
                <label for="remarks"><b>Remarks</b></label>
                <textarea
                  name="remarks"
                  id="remarks"
                  cols="5"
                  class="form-control"
                ></textarea>
              </div>
              <div>
                <label for="start_date"><b>Start Date</b></label>
                <input
                  type="date"
                  name="start_date"
                  id="start_date"
                  class="form-control"
                />
              </div>
              <div>
                <label for="end_date"><b>End Date</b></label>
                <input
                  type="date"
                  name="end_date"
                  id="end_date"
                  class="form-control"
                />
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              data-bs-dismiss="modal"
            >
              Close
            </button>
            <button type="button" class="btn btn-primary" id="saveChanges">
              Save changes
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal" tabindex="-1" id="add-form">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Budget</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <form id="updateForm" enctype="multipart/form-data">
            <input type="hidden" name="user_id" id="user_id" value="<?php echo $userId; ?>">
              <div>
                <label for="title"><b>Title</b></label>
                <input
                  type="text"
                  name="title1"
                  id="title1"
                  class="form-control"
                />
              </div>
              <div>
                <label for="category"><b>Category</b></label>
                <select class="form-select" id="category1" name="category1" required>
                <option value="" selected disabled>Select Category</option>
              </select>
              </div>
              <div>
                <label for="total_amount"><b>Total Amount</b></label>
                <input
                  type="number"
                  name="total_amount1"
                  id="total_amount1"
                  class="form-control"
                />
              </div>
              <div>
                <label for="remarks"><b>Remarks</b></label>
                <textarea
                  name="remarks1"
                  id="remarks1"
                  cols="5"
                  class="form-control"
                ></textarea>
              </div>
              <div>
                <label for="start_date"><b>Start Date</b></label>
                <input
                  type="date"
                  name="start_date1"
                  id="start_date1"
                  class="form-control"
                />
              </div>
              <div>
                <label for="end_date"><b>End Date</b></label>
                <input
                  type="date"
                  name="end_date1"
                  id="end_date1"
                  class="form-control"
                />
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              data-bs-dismiss="modal"
            >
              Close
            </button>
            <button type="button" class="btn btn-primary" id="add-budget">
              Save changes
            </button>
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
        <p class="mb-0">Are you sure you want to delete this budget?</p>

        <div id="transaction-options" class="mt-3" style="display: none;">
          <h6>Transactions associated with this budget:</h6>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="transactionOption" id="nullifyTransactions" value="nullify">
            <label class="form-check-label" for="nullifyTransactions">
              Set transactions to no associated budget
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="transactionOption" id="deleteTransactions" value="delete">
            <label class="form-check-label" for="deleteTransactions">
              Delete associated transactions
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
      </div>
    </div>
  </div>
</div>

      <div class="modal fade" id="view-modal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewModalLabel">
          <i class="fas fa-money-bill-wave me-2"></i>View Budget
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <div class="card">
              <div class="card-body">
                <h6 class="card-title"><i class="fas fa-info-circle me-2" style="color: blue;"></i>Basic Information</h6>
                <ul class="list-group list-group-flush" style="margin-bottom: 40px;">
                  <li class="list-group-item d-flex justify-content-between align-items-center" style="gap: 120px;">
                    <span><i class="fas fa-heading me-2" style="color: blue;"></i>Title</span>
                    <span id="view-title" class="fw-bold"></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-tag me-2" style="color: blue;"></i>Category</span>
                    <span id="view-category" class="badge bg-secondary"></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-coins me-2" style="color: blue;"></i>Total Amount</span>
                    <span id="view-total_amount" class="fw-bold text-success"></span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <div class="card">
              <div class="card-body">
                <h6 class="card-title"><i class="fas fa-calendar-alt me-2" style="color: blue;"></i>Date Information</h6>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item d-flex justify-content-between align-items-center" style="gap: 120px;">
                    <span><i class="fas fa-play me-2" style="color: blue;"></i>Start Date</span>
                    <span id="view-start_date"></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-stop me-2" style="color: blue;"></i>End Date</span>
                    <span id="view-end_date"></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-clock me-2" style="color: blue;"></i>Created at</span>
                    <span id="view-created_at"></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-sync me-2" style="color: blue;"></i>Last Updated</span>
                    <span id="view-updated_at"></span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <h6 class="card-title"><i class="fas fa-comment-alt me-2" style="color: blue;"></i>Remarks</h6>
                <p id="view-remarks" class="card-text"></p>
              </div>
            </div>
          </div>
        </div>
        <hr>
          <h5><i class="fas fa-exchange-alt fa-black me-2" style="color: black;"></i>Recent Transactions</h5>
          <div class="transaction-list" id="transaction-list">  
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Close
        </button>
      </div>
    </div>
  </div>
</div>

      <script>
        $(document).ready(function() {

          function populateDropdown(action, selector, type) {
                  $.ajax({
                      url: './helper/populate_dropdown.php',
                      method: 'GET',
                      dataType: 'json',
                      data: {
                          action: action,
                          id : <?php echo $userId; ?>,
                          type: type
                      },
                      success: function(data) {
                          let $dropdown = $(selector);
                          $.each(data, function(index, item) {
                              $dropdown.append($('<option>', {
                                  value: item.name,
                                  text: item.name
                              }));
                          });
                          // $dropdown.select2({
                          //   theme: 'bootstrap',
                          // });
                      },
                      error: function() {
                          alert('Failed to load data.');
                      }
                  });
              }

              populateDropdown('getCategory', '#category', 'Expense');
              populateDropdown('getCategory', '#category1', 'Expense');

          function fetchDashboardData() {
          const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

          $.ajax({
            url: './controller/BudgetController.php',
            method: 'GET',
            data: {
              action: 'getDashboardData',
              id: userId,
            },
            dataType: 'json',
            success: function(data) {
              $('#totalIncome').text(`RM ${parseFloat(data.totalIncome).toFixed(2)}`);
              $('#totalExpenses').text(`RM ${parseFloat(data.totalExpenses).toFixed(2)}`);
              $('#savingsRate').text(`${parseFloat(data.savingsRate).toFixed(2)}%`);
              $('#remainingBudget').text(`RM ${parseFloat(data.totalBalance).toFixed(2)}`);
            },
            error: function(xhr, status, error) {
              console.error('Error fetching dashboard data:', error);
            }
          });
        }

        fetchDashboardData();
        setInterval(fetchDashboardData, 10000);

          function formatDate(dateString) {
            if (!dateString) return ""; 
            
            const date = new Date(dateString);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0'); 
            const year = date.getFullYear();

            return `${day}/${month}/${year}`;
        }

        function formatDateTime(dateString) {
          if (!dateString) return "";
          const date = new Date(dateString);

          const day = String(date.getDate()).padStart(2, '0');
          const month = String(date.getMonth() + 1).padStart(2, '0');
          const year = date.getFullYear();
          const hours = String(date.getHours()).padStart(2, '0');
          const minutes = String(date.getMinutes()).padStart(2, '0');
          const seconds = String(date.getSeconds()).padStart(2, '0');

          return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
        }

        function formatDate(dateString) {
          if (!dateString) return "";
          const date = new Date(dateString);

          const day = String(date.getDate()).padStart(2, '0');
          const month = String(date.getMonth() + 1).padStart(2, '0');
          const year = date.getFullYear();

          return `${day}/${month}/${year}`;
        }

        function loadEventData(budgetId) {
          $.ajax({
            url: './controller/BudgetController.php',
            type: 'POST',
            data: { id: budgetId, action: 'checkBudget' },
            success: function (response) {
              const data = JSON.parse(response);
              if (data.hasTransactions) {
                $('#transaction-options').show();
              } else {
                $('#transaction-options').hide();
              }
            }
          });
        }

          $('.action-icon').click(function() {
            var budgetId = $(this).data('id');
            var action = $(this).data('action');

            if (action === 'view') {
              var budgetId = Number($(this).data("id"));
              $.ajax({
                url: './controller/BudgetController.php',
                method: "GET",
                data: {
                    action: 'getBudget',
                    id: budgetId
                },
                dataType: 'json',
                success: function(data) {
                    if (data) {
                      console.log(data);
                      $('#view-title').text(data.title !== null ? data.title : "");
                      $('#view-category').text(data.category !== null ? data.category : "");
                      $('#view-total_amount').text(data.total_amount !== null ? data.total_amount : "");
                      $('#view-remarks').text(data.remarks !== null ? data.remarks : "");
                      $('#view-start_date').text(data.start_date !== null ? formatDate(data.start_date) : "");
                      $('#view-end_date').text(data.end_date !== null ? formatDate(data.end_date) : "");
                      $('#view-created_at').text(formatDate(data.created_at));
                      $('#view-updated_at').text(formatDate(data.updated_at));
                    } else {
                        console.error('No data received.');
                    }
                },
                error: function(error) {
                    console.error("There was an error fetching the budget data:", error);
                }
            });

            $.ajax({
              url: './controller/BudgetController.php',
              method: "GET",
              data: {
                  action: 'getBudgetTransaction',
                  id: budgetId
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
              var budgetId = Number($(this).data("id"));
              $("#saveChanges").attr("data-id", budgetId);
              $.ajax({
                url: './controller/BudgetController.php',
                method: "GET",
                data: {
                    action: 'getBudget',
                    id: budgetId
                },
                dataType: 'json',
                success: function(data) {
                    if (data) {
                      console.log(data);
                      $('#update-form').modal('show');
                      $('#title').val(data.title !== null ? data.title : "");
                      $('#category').val(data.category !== null ? data.category : "");
                      $('#total_amount').val(data.total_amount !== null ? data.total_amount : "");
                      $('#remarks').val(data.remarks !== null ? data.remarks : "");
                      $('#start_date').val(data.start_date !== null ? data.start_date : "");
                      $('#end_date').val(data.end_date !== null ? data.end_date : "");
                    } else {
                        console.error('No data received.');
                    }
                },
                error: function(error) {
                    console.error("There was an error fetching the budget data:", error);
                }
            });
                } else if (action === 'delete') {
                  $('#confirmDelete').data('id', budgetId);
                  $('#confirm-modal').modal('show');
                } else if(action === 'add'){
                  $('#add-form').modal('show');
                }
              });

              $('#confirm-modal').on('show.bs.modal', function () {
                const budgetId = $('#confirmDelete').data('id');
                console.log(budgetId);
                if (budgetId) {
                    loadEventData(budgetId);
                }
              });

          $('#confirmDelete').click(function() {
            var budgetId = $(this).data('id');
            const transactionOption = $('input[name="transactionOption"]:checked').val();
            
            $.ajax({
              url: './controller/BudgetController.php',
              type: 'POST',
              data: {
                action: 'deleteBudget',
                id: budgetId,
                transactionOption: transactionOption
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

          $('#add-budget').click(function() {
            var createdAt = new Date().toISOString();
            var formData = {
              action: 'addBudget',
              title: $('#title1').val(),
              category: $('#category1').val(),
              total_amount: $('#total_amount1').val(),
              remarks: $('#remarks1').val(),
              start_date: $('#start_date1').val(),
              end_date: $('#end_date1').val(),
              created_at: createdAt,
              user_id: $('#user_id').val(),
            };

            $.ajax({
              url: './controller/BudgetController.php',
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
            var budgetId = $(this).data("id");
            const time = new Date().toISOString().split(".")[0];

            var formData = {
              action: 'updateBudget',
              title: $("#title").val(),
              category: $("#category").val(),
              total_amount: $("#total_amount").val(),
              remarks: $("#remarks").val(),
              updated_at: time,
              start_date: $("#start_date").val(),
              end_date: $("#end_date").val(),
              id: budgetId,
            };

            $.ajax({
              url: './controller/BudgetController.php',
              type: 'POST',
              data: formData,
              dataType: 'json',
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
                alert('An error occurred while processing the request.');
              }
            });
          });

        });

        $(document).ready(function() {
          const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

          $.ajax({
              url: './controller/BudgetController.php',
              method: 'GET',
              data: {
                  action: 'getBudgetBreakdown',
                  id: userId
              },
              dataType: 'json',
              success: function(data) {
                  if (data.status === 'success') {
                      const categories = data.data.map(item => item.category);
                      const amounts = data.data.map(item => parseFloat(item.total));

                      const ctx = document.getElementById('budgetBreakdown').getContext('2d');
                      new Chart(ctx, {
                          type: 'pie',
                          data: {
                              labels: categories,
                              datasets: [{
                                  label: 'Budgets by Category',
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
                  console.error('Error fetching budget breakdown data:', error);
              }
          });

          $.ajax({
              url: './controller/BudgetController.php',
              method: 'GET',
              data: {
                  action: 'getMonthlySpendingTrend',
                  id: userId
              },
              dataType: 'json',
              success: function(data) {
                  if (data.status === 'success') {
                      const months = data.data.map(item => item.month);
                      const totals = data.data.map(item => parseFloat(item.total));

                      const ctx = document.getElementById('monthlySpendingTrend').getContext('2d');
                      new Chart(ctx, {
                          type: 'line',
                          data: {
                              labels: months,
                              datasets: [{
                                  label: 'Total Expenses by Month',
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
                                          text: 'Total Expenses (RM)'
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
