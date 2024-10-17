<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Budgetify | Manage Transactions</title>
  <?php
  include_once('./inc/asset.php');
  ?>
  <style>
    .rainbow_text_animated {
      background: linear-gradient(to right,
          #6666ff,
          #0099ff,
          #00ff00,
          #ff3399,
          #6666ff);
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

    .transaction-item {
      padding: 10px 0;
      border-bottom: 1px solid #ddd;
    }

    .transaction-item:last-child {
      border-bottom: none;
    }

    .income {
      color: green;
    }

    .expense {
      color: red;
    }

    .transaction-date {
      font-weight: bold;
      font-size: 0.9rem;
    }

    .transaction-time {
      font-size: 0.8rem;
      color: #888;
    }

    .transaction-list {
      max-height: 300px;
      overflow-y: auto;
    }

    .transaction-amount {
      font-size: 2rem;
      font-weight: bold;
    }

    .info-section {
      background-color: #f8f9fa;
      border-radius: 0.5rem;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .details-section {
      background-color: #fff;
      border: 1px solid #dee2e6;
      border-radius: 0.5rem;
      padding: 1.5rem;
    }

    #view-modal .modal-body .fas {
      color: #007bff;
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
      style="font-weight: bolder; padding: 10px">
      Manage Transactions
    </h1>
  </div>

  <div class="container-fluid mt-4 px-5">
    <div class="row">
      <div class="col-md-6 col-lg-3 mb-4">
        <div class="card text-white bg-primary h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <h5 class="card-title">Total Transactions</h5>
            <h2 class="card-text display-4 mb-0" id="total-transactions">0</h2>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3 mb-4">
        <div class="card text-white bg-success h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <h5 class="card-title">Total Income</h5>
            <h2 class="card-text display-4 mb-0" id="total-income">RM 0</h2>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3 mb-4">
        <div class="card text-white bg-danger h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <h5 class="card-title">Total Expenses</h5>
            <h2 class="card-text display-4 mb-0" id="total-expenses">RM 0</h2>
          </div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3 mb-4">
        <div class="card text-white bg-info h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <h5 class="card-title">Balance</h5>
            <h2 class="card-text display-4 mb-0" id="balance">RM 0</h2>
          </div>
        </div>
      </div>
    </div>

    <div class="row d-flex align-items-stretch">
      <div class="col-md-8 mb-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title mb-4">Transaction History</h5>
            <div id="transaction-list" class="transaction-list">
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Transaction Types</h5>
            <canvas id="transactionTypeChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="container my-3">
    <div class="row">
      <div class="col">
        <button class="btn btn-info addTransaction action-icon" data-action="add" style="float: right">
          Add Transaction
        </button>
      </div>
    </div>
  </div>

  <div class="pt-3 mx-5">

    <?php
    require_once "./inc/config.php";
    $userId = $_SESSION['user_id'];
    $recordsPerPage = 5;
    $totalRecordsQuery = "SELECT COUNT(*) AS total FROM transactions WHERE user_id = ?";
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

    $sql = "SELECT * FROM transactions WHERE user_id = ? LIMIT ?, ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $userId, $startRecord, $recordsPerPage);
    mysqli_stmt_execute($stmt);
    if ($result = mysqli_stmt_get_result($stmt)) {
      if (mysqli_num_rows($result) > 0) {
        echo '<table class="table table-bordered table-striped">';
        echo "<thead>";
        echo "<tr>";
        echo "<th>#</th>";
        echo "<th>Type</th>";
        echo "<th>Category</th>";
        echo "<th>Amount</th>";
        echo "<th>Description</th>";
        echo "<th>Date</th>";
        echo "<th>Action</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        $counter = 0;
        while ($row = mysqli_fetch_array($result)) {
          $counter++;
          echo "<tr>";
          echo "<td>" . $counter . "</td>";
          echo "<td>" . $row['trans_type'] . "</td>";
          echo "<td>" . $row['category'] . "</td>";
          echo "<td>" . number_format($row['amount'], 2) . "</td>";
          echo "<td>" . $row['description'] . "</td>";
          echo "<td>" . (new DateTime($row['trans_date']))->format('d/m/Y') . "</td>";
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

  <div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header pb-4 pt-3 px-4">
          <h5 class="modal-title" id="transactionModalLabel">Record a Transaction</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center pb-5 pt-4 px-4">
          <div class="d-flex justify-content-around">
            <button type="button" class="btn btn-outline-success btn-lg rounded-pill" data-bs-toggle="modal" data-bs-target="#incomeAllocationModal">
              <i class="fas fa-plus-circle me-2"></i> Record Income
            </button>
            <button type="button" class="btn btn-outline-danger btn-lg rounded-pill" data-bs-toggle="modal" data-bs-target="#allocationModal">
              <i class="fas fa-minus-circle me-2"></i> Record Expense
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal" tabindex="-1" id="incomeModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Income</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="addIncomeForm" enctype="multipart/form-data">
            <input type="hidden" name="user_id" id="user_id" value="<?php echo $userId; ?>">
            <input type="hidden" name="table_ref1" id="table_ref1">
            <div id="eventSection">
              <label for="event1" class="form-label"><b>Event Name</b></label>
              <select class="form-select" id="event1" name="event1" required>
                <option value="" selected disabled>Select Event</option>
              </select>
            </div>
            <div>
              <label for="type1" class="form-label"><b>Wallet</b></label>
              <select class="form-select" id="wallet1" name="wallet1" required>
                <option value="" selected disabled>Select Wallet</option>
              </select>
            </div>
            <div>
              <label for="amount1" class="form-label"><b>Amount</b></label>
              <input
                type="number"
                name="amount1"
                id="amount1"
                class="form-control" />
            </div>
            <div>
              <label for="category1" class="form-label"><b>Category</b></label>
              <select class="form-select" id="category1" name="category1" required>
                <option value="" selected disabled>Select Category</option>
              </select>
            </div>
            <div>
              <label for="desc1" class="form-label"><b>Description</b></label>
              <textarea
                name="desc1"
                id="desc1"
                cols="5"
                class="form-control"></textarea>
            </div>
            <div>
              <label for="trans_date1" class="form-label"><b>Transaction Date</b></label>
              <input
                type="date"
                name="trans_date1"
                id="trans_date1"
                class="form-control" />
            </div>
            <div>
              <label for="attachment1" class="form-label"><b>Attachment</b></label>
              <input type="file" name="attachment1" id="attachment1" class="form-control">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal">
            Close
          </button>
          <button type="button" class="btn btn-primary" id="add-income">
            Save changes
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="allocationModal" tabindex="-1" aria-labelledby="allocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header pb-4 pt-3 px-3">
          <h5 class="modal-title" id="allocationModalLabel">Allocate a Transaction</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center pb-5 pt-4 px-3">
          <div class="d-flex justify-content-around">
            <button type="button" class="btn btn-outline-warning btn-lg rounded-pill" data-category="event">
              <i class="fas fa-calendar-alt me-2"></i> Events
            </button>
            <button type="button" class="btn btn-outline-secondary btn-lg rounded-pill" data-category="expense">
              <i class="fas fa-wallet me-2"></i> Normal Expenses
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="incomeAllocationModal" tabindex="-1" aria-labelledby="incomeAllocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header pb-4 pt-3 px-3">
          <h5 class="modal-title" id="incomeAllocationModalLabel">Allocate a Transaction</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center pb-5 pt-4 px-3">
          <div class="d-flex justify-content-around">
            <button type="button" class="btn btn-outline-warning btn-lg rounded-pill" data-income_category="event">
              <i class="fas fa-calendar-alt me-2"></i> Events
            </button>
            <button type="button" class="btn btn-outline-secondary btn-lg rounded-pill" data-income_category="income">
              <i class="fas fa-wallet me-2"></i> Normal Income
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal" tabindex="-1" id="expenseModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Expense</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="addExpenseForm" enctype="multipart/form-data">
            <input type="hidden" name="user_id" id="user_id" value="<?php echo $userId; ?>">
            <input type="hidden" name="table_ref" id="table_ref">
            <input type="hidden" name="trans_type" id="trans_type" value="Expense">
            <div id="eventSection1">
              <label for="event" class="form-label"><b>Event Name</b></label>
              <select class="form-select" id="event" name="event" required>
                <option value="" selected disabled>Select Event</option>
              </select>
            </div>
            <div>
              <label for="category" class="form-label"><b>Category</b></label>
              <select class="form-select" id="category" name="category" required>
                <option value="" selected disabled>Select Category</option>
              </select>
            </div>
            <div>
              <label for="amount" class="form-label"><b>Amount</b></label>
              <input
                type="number"
                name="amount"
                id="amount"
                class="form-control" />
            </div>
            <div>
              <label for="type" class="form-label"><b>Wallet</b></label>
              <select class="form-select" id="wallet" name="wallet" required>
                <option value="" selected disabled>Select Wallet</option>
              </select>
            </div>
            <div>
              <label for="desc" class="form-label"><b>Description</b></label>
              <textarea
                name="desc"
                id="desc"
                cols="5"
                class="form-control"></textarea>
            </div>
            <div>
              <label for="trans_date" class="form-label"><b>Transaction Date</b></label>
              <input
                type="date"
                name="trans_date"
                id="trans_date"
                class="form-control" />
            </div>
            <div>
              <label for="attachment" class="form-label"><b>Attachment</b></label>
              <input type="file" name="attachment" id="attachment" class="form-control">
            </div>
          </form>
        </div>
        <div class="container mt-2">
          <div class="row mb-3">
            <div class="col-md-12">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="allocate_budget" name="allocate_budget" />
                <label class="form-check-label" for="allocate_budget"><b>Allocate to Budget?</b></label>
              </div>
            </div>
          </div>
          <div class="row mb-3" id="budgetSection" style="display: none;">
            <div class="col-md-12">
              <div class="form-group">
                <label for="budget" class="form-label"><b>Budget</b></label>
                <select class="form-select" id="budget" name="budget">
                  <option value="" selected disabled>Select Budget</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal">
            Close
          </button>
          <button type="button" class="btn btn-primary" id="add-expense">
            Save changes
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="view-modal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="viewModalLabel">
            <i class="fas fa-file-invoice-dollar me-2"></i>Transaction Details
          </h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="info-section">
            <div class="row mb-4">
              <div class="col-md-4 text-center">
                <h5 class="mb-3">
                  <i class="fas fa-wallet me-2"></i>Wallet
                </h5>
                <p id="modal-wallet-name" class="fs-5"></p>
              </div>
              <div class="col-md-4 text-center">
                <h5 class="mb-3">
                  <i class="fas fa-bullseye me-2"></i>Budget
                </h5>
                <p id="modal-budget-title" class="fs-5"></p>
              </div>
              <div class="col-md-4 text-center">
                <h5 class="mb-3">
                  <i class="fas fa-calendar me-2"></i>Date
                </h5>
                <p id="modal-transaction-date" class="fs-5"></p>
              </div>
            </div>
            <div class="text-center">
              <span id="modal-transaction-amount" class="transaction-amount">
                <i id="modal-transaction-icon" class="fas me-2"></i>
              </span>
              <p id="modal-transaction-type" class="text-muted mb-0"></p>
            </div>
          </div>
          <div class="details-section">
            <div class="row">
              <div class="col-md-6">
                <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Details</h5>
                <ul id="modal-transaction-details" class="list-group list-group-flush">
                </ul>
              </div>
              <div class="col-md-6">
                <h5 class="mb-3"><i class="fas fa-paperclip me-2"></i>Attachment</h5>
                <div id="view-attachment" class="text-center">
                </div>
              </div>
            </div>
          </div>
          <div class="text-muted text-end mt-3">
            <small id="modal-creation-date"></small>
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
          <p class="mb-0">Are you sure you want to delete this transaction?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal" tabindex="-1" id="updateIncomeModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Update Income</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="updateIncomeForm" enctype="multipart/form-data">
            <input type="hidden" name="user_id" id="user_id" value="<?php echo $userId; ?>">
            <div id="eventSection_income">
              <label for="event_income" class="form-label"><b>Event Name</b></label>
              <select class="form-select" id="event_income" name="event_income" required>
                <option value="" selected disabled>Select Event</option>
              </select>
            </div>
            <div>
              <label for="wallet_income" class="form-label"><b>Wallet</b></label>
              <select class="form-select" id="wallet_income" name="wallet_income" required>
                <option value="" selected disabled>Select Wallet</option>
              </select>
            </div>
            <div>
              <label for="amount_income" class="form-label"><b>Amount</b></label>
              <input
                type="number"
                name="amount_income"
                id="amount_income"
                class="form-control" />
            </div>
            <div>
              <label for="category_income" class="form-label"><b>Category</b></label>
              <select class="form-select" id="category_income" name="category_income" required>
                <option value="" selected disabled>Select Category</option>
              </select>
            </div>
            <div>
              <label for="desc_income" class="form-label"><b>Description</b></label>
              <textarea
                name="desc_income"
                id="desc_income"
                cols="5"
                class="form-control"></textarea>
            </div>
            <div>
              <label for="trans_date_income" class="form-label"><b>Transaction Date</b></label>
              <input
                type="date"
                name="trans_date_income"
                id="trans_date_income"
                class="form-control" />
            </div>
            <div>
              <label for="attachment_income" class="form-label"><b>Attachment</b></label>
              <input type="file" name="attachment_income" id="attachment_income" class="form-control">
            </div>
            <div id="attachment_preview_income" class="mt-2 d-flex justify-content-center align-items-center" style="display: none; height: 200px;">
              <img id="attachment_image_income" src="" alt="Attachment Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal">
            Close
          </button>
          <button type="button" class="btn btn-primary" id="update-income">
            Save changes
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal" tabindex="-1" id="updateExpenseModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Update Expense</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="updateExpenseForm" enctype="multipart/form-data">
            <input type="hidden" name="user_id" id="user_id" value="<?php echo $userId; ?>">
            <div id="eventSection_expense">
              <label for="event_expense" class="form-label"><b>Event Name</b></label>
              <select class="form-select" id="event_expense" name="event_expense" required>
                <option value="" selected disabled>Select Event</option>
              </select>
            </div>
            <div>
              <label for="category_expense" class="form-label"><b>Category</b></label>
              <select class="form-select" id="category_expense" name="category_expense" required>
                <option value="" selected disabled>Select Category</option>
              </select>
            </div>
            <div>
              <label for="amount_expense" class="form-label"><b>Amount</b></label>
              <input
                type="number"
                name="amount_expense"
                id="amount_expense"
                class="form-control" />
            </div>
            <div>
              <label for="wallet_expense" class="form-label"><b>Wallet</b></label>
              <select class="form-select" id="wallet_expense" name="wallet_expense" required>
                <option value="" selected disabled>Select Wallet</option>
              </select>
            </div>
            <div>
              <label for="desc_expense" class="form-label"><b>Description</b></label>
              <textarea
                name="desc_expense"
                id="desc_expense"
                cols="5"
                class="form-control"></textarea>
            </div>
            <div>
              <label for="trans_date_expense" class="form-label"><b>Transaction Date</b></label>
              <input
                type="date"
                name="trans_date_expense"
                id="trans_date_expense"
                class="form-control" />
            </div>
            <div>
              <label for="attachment_expense" class="form-label"><b>Attachment</b></label>
              <input type="file" name="attachment_expense" id="attachment_expense" class="form-control">
            </div>
          </form>
        </div>
        <div class="container mt-2">
          <div class="row mb-3">
            <div class="col-md-12">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="allocate_budget_update" name="allocate_budget_update" />
                <label class="form-check-label" for="allocate_budget_update"><b>Allocate to Budget?</b></label>
              </div>
            </div>
          </div>
          <div class="row mb-3" id="budgetSection" style="display: none;">
            <div class="col-md-12">
              <div class="form-group">
                <label for="update_budget" class="form-label"><b>Budget</b></label>
                <select class="form-select" id="update_budget" name="update_budget">
                  <option value="" selected disabled>Select Budget</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            data-bs-dismiss="modal">
            Close
          </button>
          <button type="button" class="btn btn-primary" id="update-expense">
            Save changes
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    $('.action-icon').click(function() {
      var budgetId = $(this).data('id');
      var action = $(this).data('action');

      if (action === 'view') {

        function populateTransactionModal(data) {
          $('#modal-wallet-name').text(data.wallet_name);
          $('#modal-budget-title').text(data.budget_title ? data.budget_title : 'No Allocation');

          var amountSpan = $('#modal-transaction-amount');
          amountSpan.text('$' + parseFloat(data.amount).toFixed(2));

          if (data.trans_type === 'Expense') {
            amountSpan.removeClass('income').addClass('expense');
            $('#modal-transaction-icon').removeClass('fa-arrow-up').addClass('fa-arrow-down');
            $('#modal-transaction-type').text('Expense');
          } else {
            amountSpan.removeClass('expense').addClass('income');
            $('#modal-transaction-icon').removeClass('fa-arrow-down').addClass('fa-arrow-up');
            $('#modal-transaction-type').text('Income');
          }

          var formattedDate = new Date(data.trans_date).toLocaleDateString('en-GB', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          }).split('/').join('/');

          $('#modal-transaction-date').text(formattedDate);

          if (data.table_ref != null) {
            var capitalizedTableRef = data.table_ref.charAt(0).toUpperCase() + data.table_ref.slice(1);
          }

          var detailsList = $('#modal-transaction-details');
          detailsList.empty();
          detailsList.append('<li class="list-group-item"><strong>Description:</strong> ' + data.description + '</li>');
          detailsList.append('<li class="list-group-item"><strong>Category:</strong> ' + data.category + '</li>');
          if (capitalizedTableRef != null) {
            detailsList.append('<li class="list-group-item"><strong>Reference:</strong> ' + capitalizedTableRef + '</li>');
          }
          if (capitalizedTableRef == 'Events') {
            detailsList.append('<li class="list-group-item"><strong>Event Name:</strong> ' + data.event_name + '</li>');
          }

          var attachmentDiv = $('#view-attachment');
          if (data.attachment && data.attachment !== './public/attachments/no images.jpg') {
            attachmentDiv.html('<img src="' + data.attachment + '" alt="Transaction Attachment" class="img-fluid rounded" style="max-width: 100%; height: 200px; object-fit: cover;" />');
          } else {
            attachmentDiv.html('<p>No attachment available</p>');
          }

          $('#modal-creation-date').text('Created: ' + data.created_at);
        }

        var transactionId = Number($(this).data("id"));
        $.ajax({
          url: './controller/TransactionController.php',
          method: "GET",
          data: {
            action: 'getTransaction',
            id: transactionId
          },
          dataType: 'json',
          success: function(data) {
            if (data) {
              populateTransactionModal(data);
              $('#view-modal').modal('show');
            } else {
              console.error('No data received.');
            }
          },
          error: function(error) {
            console.error("There was an error fetching the transaction data:", error);
          }
        });
      } else if (action === 'update') {
        var transactionId = Number($(this).data("id"));
        $("#update-income").attr("data-id", transactionId);
        $.ajax({
          url: './controller/TransactionController.php',
          method: "GET",
          data: {
            action: 'getTransaction',
            id: transactionId
          },
          dataType: 'json',
          success: function(data) {
            if (data) {
              console.log(data);
              if (data.trans_type === 'Income') {
                $('#updateIncomeModal').modal('show');
                $('#wallet_income').val(data.wallet_id !== null ? data.wallet_id : "");
                $('#amount_income').val(data.amount !== null ? data.amount : "");
                $('#category_income').val(data.category !== null ? data.category : "");
                $('#desc_income').val(data.description !== null ? data.description : "");
                $('#trans_date_income').val(data.trans_date !== null ? data.trans_date : "");
                $('#eventSection_income').addClass('d-none');
                if (data.table_ref === 'events' && data.id_ref !== null) {
                  $('#eventSection_income').removeClass('d-none');
                  $('#event_income').val(data.id_ref);
                }
                if (data.attachment) {
                  $('#attachment_image_income').attr('src', data.attachment);
                  $('#attachment_preview_income').show();
                } else {
                  $('#attachment_preview_income').hide();
                }
              } else if (data.trans_type === 'Expense') {
                $('#updateExpenseModal').modal('show');
                $('#wallet_expense').val(data.wallet_id !== null ? data.wallet_id : "");
                $('#amount_expense').val(data.amount !== null ? data.amount : "");
                $('#category_expense').val(data.category !== null ? data.category : "");
                $('#desc_expense').val(data.description !== null ? data.description : "");
                $('#trans_date_expense').val(data.trans_date !== null ? data.trans_date : "");
                $('#eventSection_expense').addClass('d-none');
                if (data.table_ref === 'events' && data.id_ref !== null) {
                  $('#eventSection_expense').removeClass('d-none');
                  $('#event_expense').val(data.id_ref);
                }
                if (data.attachment) {
                  $('#attachment_image_expense').attr('src', data.attachment);
                  $('#attachment_preview_expense').show();
                } else {
                  $('#attachment_preview_expense').hide();
                }
              }
            } else {
              console.error('No data received.');
            }
          },
          error: function(error) {
            console.error("There was an error fetching the budget data:", error);
          }
        });
      } else if (action === 'delete') {
        $('#confirm-modal').modal('show');
        $('#confirmDelete').data('id', budgetId);
      } else if (action === 'add') {
        $('#transactionModal').modal('show');
      }
    });

    $('#confirmDelete').click(function() {
      var budgetId = $(this).data('id');

      $.ajax({
        url: './controller/TransactionController.php',
        type: 'POST',
        data: {
          action: 'deleteTransaction',
          id: budgetId,
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
      function populateWalletDropdown(action, selector) {
        $.ajax({
          url: './helper/populate_dropdown.php',
          method: 'GET',
          dataType: 'json',
          data: {
            action: action,
            id: <?php echo $userId; ?>
          },
          success: function(data) {
            let $dropdown = $(selector);
            $.each(data, function(index, item) {
              $dropdown.append($('<option>', {
                value: item.id,
                text: item.name + " (" + item.fin_institute + ")"
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

      function populateDropdown(action, selector, type) {
        $.ajax({
          url: './helper/populate_dropdown.php',
          method: 'GET',
          dataType: 'json',
          data: {
            action: action,
            id: <?php echo $userId; ?>,
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

      function populateUserDropdown(action, selector) {
        $.ajax({
          url: './helper/populate_dropdown.php',
          method: 'GET',
          dataType: 'json',
          data: {
            action: action,
            id: <?php echo $userId; ?>
          },
          success: function(data) {
            let $dropdown = $(selector);
            $.each(data, function(index, item) {
              $dropdown.append($('<option>', {
                value: item.id,
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

      populateDropdown('getCategory', '#category1', 'Income');
      populateWalletDropdown('getUserWallet', '#wallet1');
      populateDropdown('getCategory', '#category', 'Expense');
      populateWalletDropdown('getUserWallet', '#wallet');
      populateUserDropdown('getUserEvent', '#event');
      populateUserDropdown('getUserEvent', '#event1');

      populateDropdown('getCategory', '#category_income', 'Income');
      populateWalletDropdown('getUserWallet', '#wallet_income');
      populateUserDropdown('getUserEvent', '#event_income');
      populateDropdown('getCategory', '#category_expense', 'Expense');
      populateWalletDropdown('getUserWallet', '#wallet_expense');
      populateUserDropdown('getUserEvent', '#event_expense');
    });

    $('#add-income').click(function() {
      var createdAt = new Date().toISOString();
      var formData = new FormData($('#addIncomeForm')[0]);

      formData.append('action', 'addIncome');
      formData.append('created_at', createdAt);
      formData.append('trans_type', 'Income');

      for (var pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
      }

      $.ajax({
        url: './controller/TransactionController.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        processData: false,
        contentType: false,
        success: function(response) {
          if (response.status === 'success') {
            alert(response.message);
            $('#incomeModal').modal('hide');
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

    $('#add-expense').click(function() {
      var createdAt = new Date().toISOString();
      var formData = new FormData($('#addExpenseForm')[0]);

      if ($('#allocate_budget').is(':checked')) {
        var selectedBudget = $('#budget').val();
        formData.append('budget', selectedBudget);
      } else {
        formData.append('budget', null);
      }

      formData.append('action', 'addExpense');
      formData.append('created_at', createdAt);

      for (var pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
      }

      $.ajax({
        url: './controller/TransactionController.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        processData: false,
        contentType: false,
        success: function(response) {
          if (response.status === 'success') {
            alert(response.message);
            $('#expenseModal').modal('hide');
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

    $("#update-income").click(function() {
      var transactionId = $(this).data("id");
      const time = new Date().toISOString().split(".")[0];
      var formData = new FormData($('#updateIncomeForm')[0]);

      formData.append('action', 'updateIncome');
      formData.append('updated_at', time);
      formData.append('id', transactionId);

      for (var pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
      }

      $.ajax({
        url: './controller/TransactionController.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        processData: false,
        contentType: false,
        success: function(response) {
          if (response.status === 'success') {
            alert(response.message);
            $('#updateIncomeModal').modal('hide');
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

    $("#update-expense").click(function() {
      var transactionId = $(this).data("id");
      const time = new Date().toISOString().split(".")[0];
      var formData = new FormData($('#updateExpenseForm')[0]);

      formData.append('action', 'updateExpense');
      formData.append('updated_at', time);
      formData.append('id', transactionId);

      for (var pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
      }

      // $.ajax({
      //   url: './controller/TransactionController.php',
      //   type: 'POST',
      //   data: formData,
      //   dataType: 'json',
      //   processData: false,
      //   contentType: false,
      //   success: function(response) {
      //     if (response.status === 'success') {
      //       alert(response.message);
      //       $('#updateIncomeModal').modal('hide'); 
      //       location.reload();
      //     } else {
      //       alert(response.message);
      //     }
      //   },
      //   error: function(xhr, status, error) {
      //     alert('An error occurred while processing the request.');
      //   }
      // });
    });

    $(document).ready(function() {

      $('#attachment_income').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
          var reader = new FileReader();
          reader.onload = function(e) {
            $('#attachment_image_income').attr('src', e.target.result);
            $('#attachment_preview_income').show();
          }
          reader.readAsDataURL(file);
        } else {
          $('#attachment_preview_income').hide();
        }
      });

      $('#attachment_expense').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
          var reader = new FileReader();
          reader.onload = function(e) {
            $('#attachment_image_expense').attr('src', e.target.result);
            $('#attachment_preview_expense').show();
          }
          reader.readAsDataURL(file);
        } else {
          $('#attachment_preview_expense').hide();
        }
      });

      function populateBudgetDropdown(action, selector, category) {
        $.ajax({
          url: './helper/populate_dropdown.php',
          method: 'GET',
          dataType: 'json',
          data: {
            action: action,
            id: <?php echo $userId; ?>,
            category: category
          },
          success: function(data) {
            let $dropdown = $(selector);
            $dropdown.empty();
            $dropdown.append($('<option>', {
              value: '',
              text: 'Select Budget',
              selected: true,
              disabled: true
            }));
            $.each(data, function(index, item) {
              $dropdown.append($('<option>', {
                value: item.id,
                text: item.title
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

      $('#allocate_budget').closest('.form-check').hide();
      $('#budgetSection').hide();

      $('#category').change(function() {
        const selectedCategory = $(this).val();
        if (selectedCategory) {
          $('#allocate_budget').closest('.form-check').show();
        } else {
          $('#allocate_budget').closest('.form-check').hide();
          $('#budgetSection').hide();
        }
      });

      $('#category').change(function() {
        const selectedCategory = $(this).val();
        if (selectedCategory) {
          $('#allocate_budget').closest('.form-check').show();
          if ($('#allocate_budget').is(':checked')) {
            populateBudgetDropdown('getBudgetByCategory', '#budget', selectedCategory);
          }
        } else {
          $('#allocate_budget').closest('.form-check').hide();
          $('#budgetSection').hide();
        }
      });

      $('#allocate_budget').change(function() {
        if ($(this).is(':checked')) {
          $('#budgetSection').show();
          const selectedCategory = $('#category').val();
          populateBudgetDropdown('getBudgetByCategory', '#budget', selectedCategory);
        } else {
          $('#budgetSection').hide();
        }
      });

      $('[data-category]').on('click', function() {
        var category = $(this).data('category');
        var modal = new bootstrap.Modal($('#expenseModal')[0]);

        $('#apparelSection').addClass('d-none');
        $('#eventSection1').addClass('d-none');
        $('#expenseSection').addClass('d-none');

        if (category === 'apparel') {
          $('#apparelSection').removeClass('d-none');
          $('#table_ref').val('apparels');
        } else if (category === 'event') {
          $('#eventSection1').removeClass('d-none');
          $('#table_ref').val('events');
        } else if (category === 'expense') {
          $('#expenseSection').removeClass('d-none');
          $('#table_ref').val('normal');
        }

        $('#allocationModal').modal('hide');

        modal.show();
      });

      $('[data-income_category]').on('click', function() {
        var category = $(this).data('income_category');
        var modal = new bootstrap.Modal($('#incomeModal')[0]);

        $('#apparelSection').addClass('d-none');
        $('#eventSection').addClass('d-none');
        $('#expenseSection').addClass('d-none');

        if (category === 'apparel') {
          $('#apparelSection').removeClass('d-none');
          $('#table_ref1').val('apparels');
        } else if (category === 'event') {
          $('#eventSection').removeClass('d-none');
          $('#table_ref1').val('events');
        } else if (category === 'expense') {
          $('#expenseSection').removeClass('d-none');
          $('#table_ref1').val('normal');
        }

        $('#incomeAllocationModal').modal('hide');

        modal.show();
      });
    });

    $(document).ready(function() {
      function fetchDashboardData() {
        const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

        $.ajax({
          url: './controller/TransactionController.php',
          method: 'GET',
          data: {
            action: 'getDashboardData',
            id: userId,
          },
          dataType: 'json',
          success: function(data) {
            $('#total-transactions').text(data.totalTransaction);
            $('#total-income').text(`RM ${parseFloat(data.totalIncome).toFixed(2)}`);
            $('#total-expenses').text(`RM ${parseFloat(data.totalExpenses).toFixed(2)}`);
            $('#balance').text(`RM ${parseFloat(data.totalBalance).toFixed(2)}`);
          },
          error: function(xhr, status, error) {
            console.error('Error fetching dashboard data:', error);
          }
        });
      }

      const userId = <?php echo json_encode($_SESSION['user_id']); ?>;
      $.ajax({
        url: './controller/TransactionController.php',
        method: "GET",
        data: {
          action: 'getRecentTransaction',
          id: userId
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
                                        <strong class="income">+$${parseFloat(transaction.amount).toFixed(2)}</strong>
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
                                        <strong class="expense">-$${parseFloat(transaction.amount).toFixed(2)}</strong>
                                    </div>
                                </div>
                            `;
              }

              transactionList.append(transactionItem);
            });
          } else {
            // Display a message if there are no transactions
            $('#transaction-list').html('<p class="text-center text-muted">No recent transactions found.</p>');
          }
        },
        error: function(xhr, status, error) {
          console.error('Error fetching transactions:', error);
          $('#transaction-list').html('<p class="text-center text-danger">Error fetching transactions. Please try again later.</p>');
        }
      });

      $.ajax({
        url: './controller/TransactionController.php',
        method: "GET",
        data: {
          action: 'getTransactionTypeDist',
          id: userId
        },
        dataType: 'json',
        success: function(data) {
          if (data.status === 'success') {
            var incomeCount = data.data.incomeCount;
            var expenseCount = data.data.expenseCount;

            var ctx = document.getElementById('transactionTypeChart').getContext('2d');
            var transactionTypeChart = new Chart(ctx, {
              type: 'pie',
              data: {
                labels: ['Income', 'Expense'],
                datasets: [{
                  label: 'Transaction Types',
                  data: [incomeCount, expenseCount],
                  backgroundColor: [
                    'rgba(75, 192, 192, 0.6)', // Color for Income
                    'rgba(255, 99, 132, 0.6)' // Color for Expense
                  ],
                  borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)'
                  ],
                  borderWidth: 1
                }]
              },
              options: {
                responsive: true,
                plugins: {
                  legend: {
                    position: 'top',
                  },
                  tooltip: {
                    callbacks: {
                      label: function(tooltipItem) {
                        var label = tooltipItem.label || '';
                        if (label) {
                          label += ': ';
                        }
                        label += tooltipItem.raw;
                        label += ` (${((tooltipItem.raw / (incomeCount + expenseCount)) * 100).toFixed(2)}%)`;
                        return label;
                      }
                    }
                  }
                }
              }
            });
          } else {
            // If no data is found
            $('#transactionTypeChart').replaceWith('<p class="text-center text-muted">No transactions found to display.</p>');
          }
        },
        error: function(xhr, status, error) {
          console.error('Error fetching transactions:', error);
          $('#transactionTypeChart').replaceWith('<p class="text-center text-danger">Error fetching transaction types. Please try again later.</p>');
        }
      });

      fetchDashboardData();

      setInterval(fetchDashboardData, 10000);
    });
  </script>
</body>

</html>