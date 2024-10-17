<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgetify | Manage Events</title>
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
      .modal-dialog {
            display: flex;
            align-items: center;
            min-height: calc(100% - 1rem);
        }
        @media (min-width: 576px) {
            .modal-dialog {
                min-height: calc(100% - 3.5rem);
            }
        }
        .modal-content {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: none;
            padding: 1.5rem 2rem;
        }
        .modal-body {
            padding: 2rem;
        }
        .modal-footer {
            border-top: none;
            padding: 1rem 2rem 1.5rem;
        }
        .event-info {
            margin-bottom: 1rem;
        }
        .event-info .info-label {
            font-weight: bold;
            color: #6c757d;
        }
        .event-info .info-value {
            color: #343a40;
        }
        .event-info i {
            width: 25px;
            color: #007bff;
        }
        #view-attachment {
            max-width: 100%;
            height: auto;
            margin-bottom: 1rem;
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
    <script src="./public/calendar-bootstrap/jquery.bs.calendar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        Manage Events
      </h1>
    </div>

    <div class="container mt-4 mb-5">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
    <div class="col">
      <div class="card text-white bg-primary h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h5 class="card-title">Total Events</h5>
          <p class="card-text display-4 mb-0" id="totalEvents">0</p>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card text-white bg-success h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h5 class="card-title">Upcoming Events</h5>
          <p class="card-text display-4 mb-0" id="upcomingEvents">0</p>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card text-white bg-warning h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h5 class="card-title">Events This Month</h5>
          <p class="card-text display-4 mb-0" id="eventsThisMonth">0</p>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card text-white bg-info h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h5 class="card-title">Total Expenses</h5>
          <p class="card-text display-4 mb-0" id="totalExpenses">RM 0</p>
        </div>
      </div>
    </div>
  </div>
</div>

    <div class="container my-3">
      <div class="row">
        <div class="col">

          <button class="btn btn-info addBudget action-icon" data-action="add" style="float: right">
            Add Event
          </button>
          <button id="showCalendarBtn" class="btn btn-primary" style="float: right; margin-right:10px;">Show Calendar</button>
        </div>
      </div>
    </div>

<div class="modal fade" id="calendarModal" tabindex="-1" aria-labelledby="calendarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="calendarModalLabel">Calendar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-flex align-item-center justify-content-center">
                <div data-bs-toggle="calendar" data-bs-target="./events.json" id="event-calendar" style="display: none;">
                </div>
            </div>
        </div>
    </div>
</div>

    <?php
      require_once "./inc/config.php";
      $userId = $_SESSION['user_id'];
      $recordsPerPage = 5;
      $totalRecordsQuery = "SELECT COUNT(*) AS total FROM events WHERE user_id = ?";
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

      $sql = "SELECT * FROM events WHERE user_id = ? LIMIT ?, ?";
      $stmt = mysqli_prepare($link, $sql);
      mysqli_stmt_bind_param($stmt, "iii", $userId, $startRecord, $recordsPerPage);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);

      echo '<div class="pt-3 mx-5">';
      if (mysqli_num_rows($result) > 0) {
          echo '<table class="table table-bordered table-striped sortable">';
          echo "<thead>";
          echo "<tr>";
          echo "<th>#</th>";
          echo "<th>Name</th>";
          echo "<th>Location</th>";
          echo "<th>Expenses (RM)</th>";
          echo "<th>Status</th>";
          echo "<th>Remarks</th>";
          echo "<th>Action</th>";
          echo "</tr>";
          echo "</thead>";
          echo "<tbody>";
          $counter = 0;
          while ($row = mysqli_fetch_array($result)) {
            $counter++;
              echo '<tr class="item">';
              echo "<td>" . $counter . "</td>";
              echo "<td>" . $row['name'] . "</td>";
              echo "<td>" . $row['location'] . "</td>";
              echo "<td>" . number_format($row['expenses'], 2) . "</td>";
              echo "<td>" . $row['status'] . "</td>";
              echo "<td>" . $row['remarks'] . "</td>";
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

      mysqli_close($link);
    ?>

<div class="modal fade" tabindex="-1" id="add-form">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addForm" enctype="multipart/form-data">
          <input type="number" name="user_id" id="user_id" value="<?php echo $userId; ?>" hidden>
          
          <div class="row g-3">
            <div class="col-md-6">
              <label for="name1" class="form-label">Name</label>
              <input type="text" name="name1" id="name1" class="form-control">
            </div>
            <div class="col-md-6">
              <label for="location1" class="form-label">Location</label>
              <input type="text" name="location1" id="location1" class="form-control">
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <label for="status1" class="form-label">Status</label>
              <select name="status1" id="status1" class="form-select">
                <option value="Scheduled">Scheduled</option>
                <option value="Ongoing">Ongoing</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
                <option value="Postponed">Postponed</option>
                <option value="Tentative">Tentative</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="attachment1" class="form-label">Attachment</label>
              <input type="file" name="attachment1" id="attachment1" class="form-control">
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <label for="start_date1" class="form-label">Start Date and Time</label>
              <div class="input-group">
                <input type="date" name="start_date1" id="start_date1" class="form-control">
                <input type="time" name="start_time1" id="start_time1" class="form-control">
              </div>
            </div>
            <div class="col-md-6">
              <label for="end_date1" class="form-label">End Date and Time</label>
              <div class="input-group">
                <input type="date" name="end_date1" id="end_date1" class="form-control">
                <input type="time" name="end_time1" id="end_time1" class="form-control">
              </div>
            </div>
          </div>

          <div class="mt-3">
            <label for="remarks1" class="form-label">Remarks</label>
            <textarea name="remarks1" id="remarks1" rows="3" class="form-control"></textarea>
          </div>

          <div class="mt-3">
            <div class="text-center">
              <img id="imagePreview" src="" alt="Image Preview" style="display: none; max-width: 100%; height: auto; max-height: 200px;">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="add-event">Save changes</button>
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
        <p class="mb-0">Are you sure you want to delete this event?</p>

        <div id="transaction-options" class="mt-3" style="display: none;">
          <h6>Transactions associated with this event:</h6>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="transactionOption" id="nullifyTransactions" value="nullify">
            <label class="form-check-label" for="nullifyTransactions">
              Set transactions to no associated event
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
                <h4 class="modal-title" id="viewModalLabel">
                    <i class="fas fa-calendar-alt me-2"></i>Event Details
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-center align-items-center mb-4">
                    <div id="view-attachment"></div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="event-info">
                            <i class="fas fa-signature"></i>
                            <span class="info-label">Name:</span>
                            <span class="info-value" id="view-name"></span>
                        </div>
                        <div class="event-info">
                            <i class="fas fa-map-marker-alt"></i>
                            <span class="info-label">Location:</span>
                            <span class="info-value" id="view-location"></span>
                        </div>
                        <div class="event-info">
                            <i class="fas fa-info-circle"></i>
                            <span class="info-label">Status:</span>
                            <span class="info-value" id="view-status"></span>
                        </div>
                        <div class="event-info">
                            <i class="fas fa-dollar-sign"></i>
                            <span class="info-label">Expenses (RM):</span>
                            <span class="info-value" id="view-expenses"></span>
                        </div>
                        <div class="event-info">
                            <i class="fas fa-comment"></i>
                            <span class="info-label">Remarks:</span>
                            <span class="info-value" id="view-remarks"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="event-info">
                            <i class="fas fa-hourglass-start"></i>
                            <span class="info-label">Start Timestamp:</span>
                            <span class="info-value" id="view-start_timestamp"></span>
                        </div>
                        <div class="event-info">
                            <i class="fas fa-hourglass-end"></i>
                            <span class="info-label">End Timestamp:</span>
                            <span class="info-value" id="view-end_timestamp"></span>
                        </div>
                        <div class="event-info">
                            <i class="fas fa-clock"></i>
                            <span class="info-label">Created at:</span>
                            <span class="info-value" id="view-created_at"></span>
                        </div>
                        <div class="event-info">
                            <i class="fas fa-edit"></i>
                            <span class="info-label">Last Updated:</span>
                            <span class="info-value" id="view-updated_at"></span>
                        </div>
                    </div>
                </div>
                <hr>
                <h5><i class="fas fa-exchange-alt me-2"></i>Transaction History</h5>
                <div class="transaction-list" id="transaction-list">
                    <!-- Transaction items will be dynamically added here -->
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade" tabindex="-1" id="update-form">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="updateForm" enctype="multipart/form-data">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="name" class="form-label">Name</label>
              <input type="text" name="name" id="name" class="form-control">
            </div>
            <div class="col-md-6">
              <label for="location" class="form-label">Location</label>
              <input type="text" name="location" id="location" class="form-control">
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <label for="status" class="form-label">Status</label>
              <select name="status" id="status" class="form-select">
                <option value="Scheduled">Scheduled</option>
                <option value="Ongoing">Ongoing</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
                <option value="Postponed">Postponed</option>
                <option value="Tentative">Tentative</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="attachment" class="form-label">Attachment</label>
              <input type="file" name="attachment" id="attachment" class="form-control">
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <label for="start_date" class="form-label">Start Date and Time</label>
              <div class="input-group">
                <input type="date" name="start_date" id="start_date" class="form-control">
                <input type="time" name="start_time" id="start_time" class="form-control">
              </div>
            </div>
            <div class="col-md-6">
              <label for="end_date" class="form-label">End Date and Time</label>
              <div class="input-group">
                <input type="date" name="end_date" id="end_date" class="form-control">
                <input type="time" name="end_time" id="end_time" class="form-control">
              </div>
            </div>
          </div>

          <div class="mt-3">
            <label for="remarks" class="form-label">Remarks</label>
            <textarea name="remarks" id="remarks" rows="3" class="form-control"></textarea>
          </div>

          <div class="mt-3">
            <div class="text-center">
              <img id="imagePreview1" src="" alt="Image Preview" style="display: none; max-width: 100%; height: auto; max-height: 200px;">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="saveChanges">Save changes</button>
      </div>
    </div>
  </div>
</div>

    <script>
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

        function loadEventData(eventId) {
          $.ajax({
            url: './controller/EventController.php',
            type: 'POST',
            data: { id: eventId, action: 'checkEvent' },
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
              var eventId = Number($(this).data("id"));
              $.ajax({
                url: './controller/EventController.php',
                method: "GET",
                data: {
                    action: 'getEvent',
                    id: eventId
                },
                dataType: 'json',
                success: function(data) {
                    if (data) {
                      $('#view-name').text(data.name !== null ? data.name : "");
                      $('#view-location').text(data.location !== null ? data.location : "");
                      $('#view-status').text(data.status !== null ? data.status : "");
                      $('#view-remarks').text(data.remarks !== null ? data.remarks : "");
                      $('#view-start_timestamp').text(data.start_timestamp !== null ? data.start_timestamp : "");
                      $('#view-end_timestamp').text(data.end_timestamp !== null ? data.end_timestamp : "");
                      $('#view-created_at').text(formatDateTime(data.created_at));
                      $('#view-updated_at').text(formatDateTime(data.updated_at));
                      $('#view-expenses').text(data.expenses !== null ? data.expenses : "");
                      if (data.attachment) {
                            var imageElement = '<img src="' + data.attachment + '" class="img-fluid mb-4" style="max-width: 200px; max-height: 200px;">';
                            $('#view-attachment').html(imageElement);
                        } else {
                            $('#view-attachment').html('<p>No attachment available.</p>');
                        }
                    } else {
                        console.error('No data received.');
                    }
                },
                error: function(error) {
                    console.error("There was an error fetching the event data:", error);
                }
            });

            $.ajax({
              url: './controller/EventController.php',
              method: "GET",
              data: {
                  action: 'getEventTransaction',
                  id: eventId
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
                  console.error("There was an error fetching the transaction data:", error);
              }
          });

          $('#view-modal').modal('show');

            } else if (action === 'update') {
              var eventId = Number($(this).data("id"));
              $("#saveChanges").attr("data-id", eventId);
              $.ajax({
        url: './controller/EventController.php',
        method: "GET",
        data: {
            action: 'getEvent',
            id: eventId
        },
        dataType: 'json',
        success: function(data) {
            if (data) {
                $('#updateForm #name').val(data.name);
                $('#updateForm #location').val(data.location);
                $('#updateForm #expenses').val(data.expenses);
                $('#updateForm #remarks').val(data.remarks);
                $('#updateForm #start_date').val(data.start_timestamp.split(' ')[0]); 
                $('#updateForm #start_time').val(data.start_timestamp.split(' ')[1]); 
                $('#updateForm #end_date').val(data.end_timestamp.split(' ')[0]);
                $('#updateForm #end_time').val(data.end_timestamp.split(' ')[1]);
                $('#updateForm #status').val(data.status);
                $('#updateForm #expenses').val(data.expenses);

                if (data.attachment) {
                    $('#imagePreview1').attr('src', data.attachment).show();
                } else {
                    $('#imagePreview1').hide();
                }

                $('#update-form').modal('show');
            } else {
                console.error('No data received.');
            }
        },
        error: function(error) {
            console.error("There was an error fetching the event data:", error);
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
                const eventId = $('#confirmDelete').data('id');
                if (eventId) {
                    loadEventData(eventId);
                }
              });

              $('#add-event').click(function() {
                var createdAt = new Date().toISOString();
                var formData = new FormData($('#addForm')[0]);

                var startDate = $('#start_date1').val();
                var startTime = $('#start_time1').val();
                var endDate = $('#end_date1').val();
                var endTime = $('#end_time1').val();

                var startDateTime = startDate + ' ' + startTime;
                var endDateTime = endDate + ' ' + endTime;

                formData.append('action', 'addEvent');
                formData.append('created_at', createdAt);
                formData.append('start_timestamp', startDateTime);
                formData.append('end_timestamp', endDateTime);

                for (var pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                $.ajax({
                    url: './controller/EventController.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    processData: false,
                    contentType: false,
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
                        console.error('AJAX Error:', error);
                        console.error('Response Text:', xhr.responseText);
                        alert('An error occurred while processing the request.');
                    }
                });
            });

            $(document).ready(function() {
                $('#attachment1').on('change', function(event) {
                    const file = event.target.files[0];
                    const $imagePreview = $('#imagePreview');

                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();

                        reader.onload = function(e) {
                            $imagePreview.attr('src', e.target.result);
                            $imagePreview.show();
                        };

                        reader.readAsDataURL(file);
                    } else {
                        $imagePreview.attr('src', '');
                        $imagePreview.hide();
                    }
                });

                $('#attachment').on('change', function(event) {
                    const file = event.target.files[0];
                    const $imagePreview = $('#imagePreview1');

                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();

                        reader.onload = function(e) {
                            $imagePreview.attr('src', e.target.result);
                            $imagePreview.show();
                        };

                        reader.readAsDataURL(file);
                    } else {
                        $imagePreview.attr('src', '');
                        $imagePreview.hide();
                    }
                });
            });

            $('#confirmDelete').click(function() {
            var eventId = $(this).data('id');
            const transactionOption = $('input[name="transactionOption"]:checked').val();
            
            $.ajax({
              url: './controller/EventController.php',
              type: 'POST',
              data: {
                action: 'deleteEvent',
                id: eventId,
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

          $("#saveChanges").click(function () {
            var eventId = $(this).data("id");
            const time = new Date().toISOString().split(".")[0];
            var formData = new FormData($('#updateForm')[0]);

            var startDate = $('#start_date').val();
            var startTime = $('#start_time').val();
            var endDate = $('#end_date').val();
            var endTime = $('#end_time').val();

            var startDateTime = startDate + ' ' + startTime;
            var endDateTime = endDate + ' ' + endTime;

            formData.append('action', 'updateEvent');
            formData.append('updated_at', time);
            formData.append('start_timestamp', startDateTime);
            formData.append('end_timestamp', endDateTime);
            formData.append('id', eventId);

            $.ajax({
              url: './controller/EventController.php',
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
                alert('An error occurred while processing the request.');
              }
            });
          });

          $(document).ready(function(){
            $('#showCalendarBtn').click(function(){
                $('#event-calendar').toggle();
            });

            $('#showCalendarBtn').click(function(){
                fetchCalendarEvents();
                $('#calendarModal').modal('show');
                $('#event-calendar').show(); 
            });
        });

        function fetchCalendarEvents() {
            const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

            $.ajax({
                url: './controller/EventController.php',
                method: 'GET',
                data: {
                    action: 'getCalendarEvents',
                    id: userId,
                },
                dataType: 'json',
                success: function(data) {
                  console.log(data);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching calendar data:', error);
                }
            });
        }

        const userId = <?php echo json_encode($_SESSION['user_id']); ?>;
        $('#event-calendar').bsCalendar({
          locale: 'en',
          url: null,
          width: '300px',
          icons: {
            prev: 'fa-solid fa-arrow-left fa-fw',
            next: 'fa-solid fa-arrow-right fa-fw',
            eventEdit: 'fa-solid fa-edit fa-fw',
            eventRemove: 'fa-solid fa-trash fa-fw'
          },
          showTodayHeader: true, 
          showEventEditButton: false,
          showEventRemoveButton: false,
          showPopover: false, 
          popoverConfig: {
            animation: false,
            html: true,
            delay: 400,
            placement: 'top',
            trigger: 'hover'
          },
          formatPopoverContent: function (events) {
            return '';
          },
          formatEvent: function (event) {
            return drawEvent(event);
          },
          "queryParams"(params) {
              params.id = userId;
              return params;
          }
      });

      $(document).ready(function() {
        function fetchDashboardData() {
          const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

          $.ajax({
            url: './controller/EventController.php',
            method: 'GET',
            data: {
              action: 'getDashboardData',
              id: userId,
            },
            dataType: 'json',
            success: function(data) {
              $('#totalEvents').text(data.totalEvents);
              $('#upcomingEvents').text(data.upcomingEvents);
              $('#eventsThisMonth').text(data.eventsThisMonth);
              $('#totalExpenses').text(`RM ${parseFloat(data.totalExpenses).toFixed(2)}`);
            },
            error: function(xhr, status, error) {
              console.error('Error fetching dashboard data:', error);
            }
          });
        }

        fetchDashboardData();

        setInterval(fetchDashboardData, 10000);
      });

      var previous = null;
      var current = null;
      setInterval(function() {
          $.getJSON("./events.json", function(json) {
              current = JSON.stringify(json);            
              if (previous && current && previous !== current) {
                  console.log('refresh');
                  location.reload();
              }
              previous = current;
          });                       
      }, 2000); 
    </script>
</body>
</html>