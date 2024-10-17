<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgetify | Manage Apparels</title>
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
        .apparel-info {
            margin-bottom: 1rem;
        }
        .apparel-info .info-label {
            font-weight: bold;
            color: #6c757d;
        }
        .apparel-info .info-value {
            color: #343a40;
        }
        .apparel-info i {
            width: 25px;
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
        style="font-weight: bolder; padding: 10px"
      >
        Manage Apparels
      </h1>
    </div>


    <div class="container mt-4 mb-5">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
    <div class="col">
      <div class="card text-white bg-primary h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h5 class="card-title">Total Apparels</h5>
          <p class="card-text display-4 mb-0" id="totalApparels">0</p>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card text-white bg-success h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h5 class="card-title">Expensive Apparels</h5>
          <p class="card-text display-4 mb-0" id="expensiveApparels">0</p>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card text-white bg-warning h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h5 class="card-title">Apparels Purchased this Month</h5>
          <p class="card-text display-4 mb-0" id="apparelsThisMonth">0</p>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card text-white bg-info h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h5 class="card-title">Total Spending</h5>
          <p class="card-text display-4 mb-0" id="totalSpending">RM 0</p>
        </div>
      </div>
    </div>
  </div>
</div>

    <div class="container my-3">
      <div class="row">
        <div class="col">

          <button class="btn btn-info addApparel action-icon" data-action="add" style="float: right">
            Add Apparel
          </button>
        </div>
      </div>
    </div>

    <div class="pt-3 mx-5">

        <?php
        require_once "./inc/config.php";
        $userId = $_SESSION['user_id'];
        $recordsPerPage = 5;
        $totalRecordsQuery = "SELECT COUNT(*) AS total FROM apparels WHERE user_id = ?";
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

        $sql = "SELECT * FROM apparels WHERE user_id = ? LIMIT ?, ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "iii", $userId, $startRecord, $recordsPerPage);
        mysqli_stmt_execute($stmt);
        if ($result = mysqli_stmt_get_result($stmt)) {
            if (mysqli_num_rows($result) > 0) {
                echo '<table class="table table-bordered table-striped sortable">';
                echo "<thead>";
                echo "<tr>";
                echo "<th>#</th>";
                echo "<th>Type</th>";
                echo "<th>Size</th>";
                echo "<th>Price (RM)</th>";
                echo "<th>Brand</th>";
                echo "<th>Remarks</th>";
                echo "<th>Purchase Date</th>";
                echo "<th>Action</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                $counter = 0;
                while ($row = mysqli_fetch_array($result)) {
                    $counter++;
                    echo '<tr class="item">';
                    echo "<td>" . $counter . "</td>";
                    echo "<td>" . $row['type'] . "</td>";
                    echo "<td>" . $row['size'] . "</td>";
                    echo "<td>" . number_format($row['price'], 2) . "</td>";
                    echo "<td>" . $row['brand'] . "</td>";
                    echo "<td>" . $row['remarks'] . "</td>";
                    echo "<td>" . (new DateTime($row['purchase_date']))->format('d/m/Y') . "</td>";
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

        <div class="modal fade" id="add-form" tabindex="-1" aria-labelledby="addFormLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="addFormLabel">Add Apparels</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addForm" enctype="multipart/form-data">
                        <input type="hidden" name="user_id" id="user_id" value="<?php echo $userId; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type1" class="form-label"><small><b>Type</b></small></label>
                                    <select name="type1" id="type1" class="form-select form-select-sm">
                                        <option value="" hidden>Select Apparel Type</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="size1" class="form-label"><small><b>Size</b></small></label>
                                    <input type="text" name="size1" id="size1" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="color1" class="form-label"><small><b>Color</b></small></label>
                                    <input type="text" name="color1" id="color1" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="quantity1" class="form-label"><small><b>Quantity</b></small></label>
                                    <input type="number" name="quantity1" id="quantity1" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="brand1" class="form-label"><small><b>Brand</b></small></label>
                                    <select name="brand1" id="brand1" class="form-select form-select-sm">
                                        <option value="" hidden>Select Apparel Brand</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price1" class="form-label"><small><b>Price</b></small></label>
                                    <input type="number" name="price1" id="price1" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="style1" class="form-label"><small><b>Style</b></small></label>
                                    <select name="style1" id="style1" class="form-select form-select-sm">
                                        <option value="" hidden>Select Apparel Style</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="purchase_date1" class="form-label"><small><b>Purchase Date</b></small></label>
                                    <input type="date" name="purchase_date1" id="purchase_date1" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="remarks1" class="form-label"><small><b>Remarks</b></small></label>
                            <textarea name="remarks1" id="remarks1" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" id="add-apparel">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="view-modal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="viewModalLabel">
                        <i class="fas fa-tshirt me-2"></i>Apparel Details
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="apparel-info">
                                <i class="fas fa-tag"></i>
                                <span class="info-label">Type:</span>
                                <span class="info-value" id="view-type"></span>
                            </div>
                            <div class="apparel-info">
                                <i class="fas fa-ruler"></i>
                                <span class="info-label">Size:</span>
                                <span class="info-value" id="view-size"></span>
                            </div>
                            <div class="apparel-info">
                                <i class="fas fa-palette"></i>
                                <span class="info-label">Color:</span>
                                <span class="info-value" id="view-color"></span>
                            </div>
                            <div class="apparel-info">
                                <i class="fas fa-cubes"></i>
                                <span class="info-label">Quantity:</span>
                                <span class="info-value" id="view-quantity"></span>
                            </div>
                            <div class="apparel-info">
                                <i class="fas fa-trademark"></i>
                                <span class="info-label">Brand:</span>
                                <span class="info-value" id="view-brand"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="apparel-info">
                                <i class="fas fa-dollar-sign"></i>
                                <span class="info-label">Price (RM):</span>
                                <span class="info-value" id="view-price"></span>
                            </div>
                            <div class="apparel-info">
                                <i class="fas fa-vest"></i>
                                <span class="info-label">Style:</span>
                                <span class="info-value" id="view-style"></span>
                            </div>
                            <div class="apparel-info">
                                <i class="fas fa-comment"></i>
                                <span class="info-label">Remarks:</span>
                                <span class="info-value" id="view-remarks"></span>
                            </div>
                            <div class="apparel-info">
                                <i class="fas fa-calendar-alt"></i>
                                <span class="info-label">Purchase Date:</span>
                                <span class="info-value" id="view-purchase_date"></span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="apparel-info">
                                <i class="fas fa-clock"></i>
                                <span class="info-label">Created at:</span>
                                <span class="info-value" id="view-created_at"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="apparel-info">
                                <i class="fas fa-edit"></i>
                                <span class="info-label">Last Updated:</span>
                                <span class="info-value" id="view-updated_at"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="update-form" tabindex="-1" aria-labelledby="addFormLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="addFormLabel">Update Apparels</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateForm" enctype="multipart/form-data">
                        <input type="hidden" name="user_id" id="user_id" value="<?php echo $userId; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type" class="form-label"><small><b>Type</b></small></label>
                                    <select name="type" id="type" class="form-select form-select-sm">
                                        <option value="" hidden>Select Apparel Type</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="size" class="form-label"><small><b>Size</b></small></label>
                                    <input type="text" name="size" id="size" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="color" class="form-label"><small><b>Color</b></small></label>
                                    <input type="text" name="color" id="color" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="quantity" class="form-label"><small><b>Quantity</b></small></label>
                                    <input type="number" name="quantity" id="quantity" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="brand" class="form-label"><small><b>Brand</b></small></label>
                                    <select name="brand" id="brand" class="form-select form-select-sm">
                                        <option value="" hidden>Select Apparel Brand</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price" class="form-label"><small><b>Price</b></small></label>
                                    <input type="number" name="price" id="price" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="style" class="form-label"><small><b>Style</b></small></label>
                                    <select name="style" id="style" class="form-select form-select-sm">
                                        <option value="" hidden>Select Apparel Style</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="purchase_date" class="form-label"><small><b>Purchase Date</b></small></label>
                                    <input type="date" name="purchase_date" id="purchase_date" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="remarks" class="form-label"><small><b>Remarks</b></small></label>
                            <textarea name="remarks" id="remarks" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" id="saveChanges">Save changes</button>
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
        <p class="mb-0">Are you sure you want to delete this apparel?</p>
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
                          // $dropdown.select2({
                          //   theme: 'bootstrap',
                          // });
                      },
                      error: function() {
                          alert('Failed to load data.');
                      }
                  });
              }
              
              populateDropdown('getApparelType', '#type1');
              populateDropdown('getApparelBrand', '#brand1');
              populateDropdown('getApparelStyle', '#style1');
              populateDropdown('getApparelType', '#type');
              populateDropdown('getApparelBrand', '#brand');
              populateDropdown('getApparelStyle', '#style');
          });

        $('.action-icon').click(function() {
            var budgetId = $(this).data('id');
            var action = $(this).data('action');

            if (action === 'view') {
              var apparelId = Number($(this).data("id"));
              $.ajax({
                url: './controller/ApparelController.php',
                method: "GET",
                data: {
                    action: 'getApparel',
                    id: apparelId
                },
                dataType: 'json',
                success: function(data) {
                    if (data) {
                      $('#view-modal').modal('show');
                      $('#view-type').text(data.type !== null ? data.type : "");
                      $('#view-size').text(data.size !== null ? data.size : "");
                      $('#view-color').text(data.color !== null ? data.color : "");
                      $('#view-quantity').text(data.quantity !== null ? data.quantity : "");
                      $('#view-brand').text(data.brand !== null ? data.brand : "");
                      $('#view-price').text(data.price !== null ? data.price : "");
                      $('#view-style').text(data.style !== null ? data.style : "");
                      $('#view-remarks').text(data.remarks !== null ? data.remarks : "");
                      $('#view-purchase_date').text(data.purchase_date !== null ? data.purchase_date : "");
                      $('#view-created_at').text(data.created_at !== null ? data.created_at : "");
                      $('#view-updated_at').text(data.updated_at !== null ? data.updated_at : "");
                    } else {
                        console.error('No data received.');
                    }
                },
                error: function(error) {
                    console.error("There was an error fetching the apparel data:", error);
                }
            });
            } else if (action === 'update') {
              var apparelId = Number($(this).data("id"));
              $("#saveChanges").attr("data-id", apparelId);
              $.ajax({
                url: './controller/ApparelController.php',
                method: "GET",
                data: {
                    action: 'getApparel',
                    id: apparelId
                },
                dataType: 'json',
                success: function(data) {
                    if (data) {
                        $('#updateForm #type').val(data.type);
                        $('#updateForm #size').val(data.size);
                        $('#updateForm #color').val(data.color);
                        $('#updateForm #quantity').val(data.quantity);
                        $('#updateForm #brand').val(data.brand);
                        $('#updateForm #price').val(data.price);
                        $('#updateForm #style').val(data.style);
                        $('#updateForm #purchase_date').val(data.purchase_date.split(' ')[0]); 
                        $('#updateForm #remarks').val(data.remarks);
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
                  $('#confirm-modal').modal('show');
                  $('#confirmDelete').data('id', budgetId);
                } else if(action === 'add'){
                  $('#add-form').modal('show');
                }
              });

              $('#add-apparel').click(function() {
                var createdAt = new Date().toISOString();
                var formData = new FormData($('#addForm')[0]);

                formData.append('action', 'addApparel');
                formData.append('created_at', createdAt);

                for (var pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                $.ajax({
                    url: './controller/ApparelController.php',
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

            
          $("#saveChanges").click(function () {
                var apparelId = $(this).data("id");
                var updatedAt = new Date().toISOString();
                var formData = new FormData($('#updateForm')[0]);

                formData.append('action', 'updateApparel');
                formData.append('updated_at', updatedAt);
                formData.append('id', apparelId);

                for (var pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                $.ajax({
                    url: './controller/ApparelController.php',
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
            var apparelId = $(this).data('id');
            
            $.ajax({
              url: './controller/ApparelController.php',
              type: 'POST',
              data: {
                action: 'deleteApparel',
                id: apparelId,
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
        function fetchDashboardData() {
          const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

          $.ajax({
            url: './controller/ApparelController.php',
            method: 'GET',
            data: {
              action: 'getDashboardData',
              id: userId,
            },
            dataType: 'json',
            success: function(data) {
              $('#totalApparels').text(data.totalApparels);
              $('#expensiveApparels').text(data.expensiveApparels);
              $('#apparelsThisMonth').text(data.apparelsThisMonth);
              $('#totalSpending').text(`RM ${parseFloat(data.totalSpending).toFixed(2)}`);
            },
            error: function(xhr, status, error) {
              console.error('Error fetching dashboard data:', error);
            }
          });
        }

        fetchDashboardData();

        setInterval(fetchDashboardData, 10000);
      });
        </script>
</body>
</html>