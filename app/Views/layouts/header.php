<?php $baseUrl = config('app.base_url', ''); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="ITEC Logistics Management System">
  <title><?= htmlspecialchars(($title ?? 'Dashboard') . ' | ITEC Logistics') ?></title>
  <link rel="icon" href="<?= $baseUrl ?>/assets/img/logistics-logo.svg">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/simplebar.css">
  <link href="https://fonts.googleapis.com/css2?family=Overpass:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/feather.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dataTables.bootstrap4.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/select2.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/daterangepicker.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/app-light.css" id="lightTheme">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/app-dark.css" id="darkTheme" disabled>
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/logistics.css">
</head>
<body class="vertical">
<div class="wrapper">
  <nav class="topnav navbar navbar-light">
    <button type="button" class="navbar-toggler text-muted mt-2 p-0 mr-3 collapseSidebar"><i class="fe fe-menu navbar-toggler-icon"></i></button>
    <ul class="nav ml-auto">
      <li class="nav-item dropdown"><a class="nav-link logistics-top-control dropdown-toggle" href="#" data-toggle="dropdown"><i class="fe fe-users fe-16 mr-1"></i><span>Role</span><strong><?= htmlspecialchars(role_label()) ?></strong></a><div class="dropdown-menu dropdown-menu-right"><h6 class="dropdown-header">Switch role for testing</h6><?php foreach (role_definitions() as $roleKey => $roleDefinition): ?><a class="dropdown-item <?= current_role() === $roleKey ? 'active' : '' ?>" href="<?= $baseUrl ?>/?route=dashboard&role=<?= urlencode($roleKey) ?>"><?= htmlspecialchars($roleDefinition['label']) ?></a><?php endforeach; ?></div></li>
      <li class="nav-item"><a class="nav-link logistics-top-control logistics-theme-icon" href="#" id="modeSwitcher" data-mode="dark" aria-label="Switch light or dark theme" title="Switch theme"><i class="fe fe-sun fe-16"></i></a></li>
      <li class="nav-item dropdown"><a class="nav-link dropdown-toggle text-muted pr-0" href="#" data-toggle="dropdown"><span class="avatar avatar-sm mt-2"><img src="<?= $baseUrl ?>/assets/avatars/face-1.jpg" alt="User" class="avatar-img rounded-circle"></span></a><div class="dropdown-menu dropdown-menu-right"><a class="dropdown-item" href="#"><i class="fe fe-user fe-16 mr-2"></i> Profile</a><a class="dropdown-item" href="#"><i class="fe fe-settings fe-16 mr-2"></i> Settings</a><a class="dropdown-item" href="<?= $baseUrl ?>/?route=dashboard"><i class="fe fe-power fe-16 mr-2"></i> Logout</a></div></li>
    </ul>
  </nav>
  <aside class="sidebar-left border-right bg-white shadow" id="leftSidebar" data-simplebar>
    <nav class="vertnav navbar navbar-light">
      <div class="w-100 mb-4 d-flex"><a class="navbar-brand mx-auto mt-2 flex-fill text-center" href="<?= $baseUrl ?>/?route=dashboard"><img src="<?= $baseUrl ?>/assets/img/logistics-logo.svg" width="86%" alt="ITEC Logistics"></a></div>
      <p class="text-muted text-center small mb-4">Operations management</p>
      <ul class="navbar-nav flex-fill w-100 mb-2">
        <li class="nav-item w-100"><a class="nav-link" href="<?= $baseUrl ?>/?route=dashboard"><i class="fe fe-home fe-16"></i><span class="ml-3 item-text">Dashboard</span></a></li>
        <?php if (role_can('vehicles') || role_can('drivers') || role_can('maintenance')): ?><li class="nav-item dropdown"><a href="#fleetMenu" data-toggle="collapse" class="dropdown-toggle nav-link"><i class="fe fe-truck fe-16"></i><span class="ml-3 item-text">Fleet Management</span></a><ul class="collapse list-unstyled pl-4 w-100" id="fleetMenu"><?php if (role_can('vehicles')): ?><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=vehicles"><span class="ml-1 item-text">Vehicles</span></a></li><?php endif; ?><?php if (role_can('drivers')): ?><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=drivers"><span class="ml-1 item-text">Drivers</span></a></li><?php endif; ?><?php if (role_can('maintenance')): ?><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=maintenance"><span class="ml-1 item-text">Maintenance</span></a></li><?php endif; ?></ul></li><?php endif; ?>
        <?php if (role_can('trips') || role_can('deliveries') || role_can('requests')): ?><li class="nav-item dropdown"><a href="#transportMenu" data-toggle="collapse" class="dropdown-toggle nav-link"><i class="fe fe-map-pin fe-16"></i><span class="ml-3 item-text">Transport</span></a><ul class="collapse list-unstyled pl-4 w-100" id="transportMenu"><?php if (role_can('trips')): ?><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=trips"><span class="ml-1 item-text">Trips</span></a></li><?php endif; ?><?php if (role_can('deliveries')): ?><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=deliveries"><span class="ml-1 item-text">Deliveries</span></a></li><?php endif; ?><?php if (role_can('requests')): ?><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=requests"><span class="ml-1 item-text">Transport requests</span></a></li><?php endif; ?></ul></li><?php endif; ?>
        <?php if (role_can('fuel') || role_can('expenses')): ?><li class="nav-item dropdown"><a href="#financeMenu" data-toggle="collapse" class="dropdown-toggle nav-link"><i class="fe fe-credit-card fe-16"></i><span class="ml-3 item-text">Finance</span></a><ul class="collapse list-unstyled pl-4 w-100" id="financeMenu"><?php if (role_can('fuel')): ?><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=fuel"><span class="ml-1 item-text">Fuel management</span></a></li><?php endif; ?><?php if (role_can('expenses')): ?><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=expenses"><span class="ml-1 item-text">Logistics expenses</span></a></li><?php endif; ?></ul></li><?php endif; ?>
        <?php if (role_can('warehouse')): ?><li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/?route=warehouse"><i class="fe fe-package fe-16"></i><span class="ml-3 item-text">Warehouse</span></a></li><?php endif; ?>
        <?php if (role_can('procurement')): ?><li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/?route=procurement"><i class="fe fe-shopping-cart fe-16"></i><span class="ml-3 item-text">Procurement</span></a></li><?php endif; ?>
        <?php if (role_can('reports')): ?><li class="nav-item dropdown"><a href="#reportsMenu" data-toggle="collapse" class="dropdown-toggle nav-link"><i class="fe fe-bar-chart-2 fe-16"></i><span class="ml-3 item-text">Reports</span></a><ul class="collapse list-unstyled pl-4 w-100 logistics-report-menu" id="reportsMenu"><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=reports"><span class="ml-1 item-text">All reports</span></a></li><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=reports&report_type=Vehicle utilization"><span class="ml-1 item-text">Vehicle utilization</span></a></li><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=reports&report_type=Fuel consumption"><span class="ml-1 item-text">Fuel consumption</span></a></li><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=reports&report_type=Delivery performance"><span class="ml-1 item-text">Delivery performance</span></a></li><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=reports&report_type=Maintenance cost"><span class="ml-1 item-text">Maintenance cost</span></a></li><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=reports&report_type=Driver performance"><span class="ml-1 item-text">Driver performance</span></a></li><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=reports&report_type=Inventory movement"><span class="ml-1 item-text">Inventory movement</span></a></li><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=reports&report_type=Trip profitability"><span class="ml-1 item-text">Trip profitability</span></a></li><li class="nav-item"><a class="nav-link pl-3" href="<?= $baseUrl ?>/?route=reports&report_type=Expense summary"><span class="ml-1 item-text">Expense summary</span></a></li></ul></li><?php endif; ?>
        <?php if (role_can('users')): ?><li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/?route=users"><i class="fe fe-users fe-16"></i><span class="ml-3 item-text">Users & permissions</span></a></li><?php endif; ?>
      </ul>
      <ul class="navbar-nav flex-fill w-100 mb-2"><li class="nav-item w-100"><a class="nav-link" href="#"><i class="fe fe-power fe-16"></i><span class="ml-3 item-text">Logout</span></a></li></ul>
    </nav>
  </aside>
  <main role="main" class="main-content">
