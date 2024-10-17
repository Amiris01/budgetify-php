<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['user_id']); 
?>

<nav class="navbar navbar-expand-lg bg-body-tertiary" data-bs-theme="dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">
      <img src="./public/images/favicon.ico" alt="Logo" width="30" class="d-inline-block align-text-top">
      Budgetify
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
      <div class="navbar-nav">
        <?php if (!$is_logged_in): ?>
          <a class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" href="../crud-php/index.php" <?= ($current_page == 'index.php') ? 'aria-current="page"' : '' ?>>Home</a>
        <?php endif; ?>
        <?php if ($is_logged_in): ?>
          <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" href="../crud-php/dashboard.php" <?= ($current_page == 'dashboard.php') ? 'aria-current="page"' : '' ?>>Dashboard</a>
          <a class="nav-link <?= ($current_page == 'apparels.php') ? 'active' : '' ?>" href="../crud-php/apparels.php" <?= ($current_page == 'apparels.php') ? 'aria-current="page"' : '' ?>>Apparels</a>
          <a class="nav-link <?= ($current_page == 'events.php') ? 'active' : '' ?>" href="../crud-php/events.php" <?= ($current_page == 'events.php') ? 'aria-current="page"' : '' ?>>Events</a>
          <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= in_array($current_page, ['wallets.php', 'budgets.php', 'transactions.php']) ? 'active' : '' ?>" 
               href="#" id="financeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Finance
            </a>
            <ul class="dropdown-menu" aria-labelledby="financeDropdown">
              <li><a class="dropdown-item <?= ($current_page == 'wallets.php') ? 'active' : '' ?>" href="../crud-php/wallets.php">Wallets</a></li>
              <li><a class="dropdown-item <?= ($current_page == 'budgets.php') ? 'active' : '' ?>" href="../crud-php/budgets.php">Budgets</a></li>
              <li><a class="dropdown-item <?= ($current_page == 'transactions.php') ? 'active' : '' ?>" href="../crud-php/transactions.php">Transactions</a></li>
            </ul>
          </div>
        <?php endif; ?>
      </div>
      <div class="navbar-nav ms-auto">
        <?php if ($is_logged_in): ?>
          <a class="nav-link <?= ($current_page == 'logout.php') ? 'active' : '' ?>" href="../crud-php/logout.php" <?= ($current_page == 'logout.php') ? 'aria-current="page"' : '' ?>>Logout</a>
        <?php else: ?>
          <a class="nav-link <?= ($current_page == 'login.php') ? 'active' : '' ?>" href="../crud-php/login.php" <?= ($current_page == 'login.php') ? 'aria-current="page"' : '' ?>>Login</a>
          <a class="nav-link <?= ($current_page == 'register.php') ? 'active' : '' ?>" href="../crud-php/register.php" <?= ($current_page == 'register.php') ? 'aria-current="page"' : '' ?>>Register</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
