<?php
$baseUrl = config('app.base_url', '');
$notifications = current_notifications();
$unreadNotifications = unread_notification_count();
$searchablePages = [
    ['Dashboard', 'dashboard', 'Overview', 'home metrics summary'],
    ['Vehicles', 'vehicles', 'Fleet Management', 'fleet trucks plate'],
    ['Drivers', 'drivers', 'Fleet Management', 'license staff'],
    ['Maintenance', 'maintenance', 'Fleet Management', 'service repair work order'],
    ['Trips', 'trips', 'Transport', 'route journey'],
    ['Deliveries', 'deliveries', 'Transport', 'proof recipient signature'],
    ['Transport requests', 'requests', 'Transport', 'approval demand'],
    ['Fuel management', 'fuel', 'Finance', 'litres station receipt'],
    ['Logistics expenses', 'expenses', 'Finance', 'cost toll allowance'],
    ['Warehouse', 'warehouse', 'Stock', 'inventory items sku stock'],
    ['Procurement', 'procurement', 'Purchasing', 'suppliers purchase requests'],
    ['Users & permissions', 'users', 'Administration', 'accounts roles access'],
    ['All reports', 'reports', 'Reports', 'export csv'],
];
$searchIndex = [];
foreach ($searchablePages as [$label, $permission, $group, $keywords]) {
    if (role_can($permission)) {
        $searchIndex[] = ['label' => $label, 'url' => url($permission), 'group' => $group, 'keywords' => $keywords];
    }
}
if (role_can('reports')) {
    foreach (['Vehicle utilization', 'Fuel consumption', 'Delivery performance', 'Maintenance cost', 'Driver performance', 'Inventory movement', 'Trip profitability', 'Expense summary'] as $reportType) {
        $searchIndex[] = ['label' => $reportType, 'url' => url('reports', ['report_type' => $reportType]), 'group' => 'Reports', 'keywords' => 'report'];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="LMS - Logistics Management System">
  <title><?= htmlspecialchars(($title ?? 'Dashboard') . ' | LMS') ?></title>
  <link rel="icon" href="<?= $baseUrl ?>/assets/img/logistics-logo.svg">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/simplebar.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/feather.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dataTables.bootstrap4.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/select2.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/daterangepicker.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/app-light.css" id="lightTheme">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/app-dark.css" id="darkTheme" disabled>
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/logistics.css">
</head>
<body class="vertical">
<script>try { if (localStorage.getItem('mode') === 'dark') { document.body.classList.add('dark'); } } catch (e) {}</script>
<div class="wrapper">
  <nav class="topnav navbar navbar-light">
    <button type="button" class="navbar-toggler text-muted mt-2 p-0 mr-3 collapseSidebar"><i class="fe fe-menu navbar-toggler-icon"></i></button>
    <form class="lms-search" role="search" autocomplete="off" onsubmit="return false" data-pages="<?= htmlspecialchars(json_encode($searchIndex), ENT_QUOTES) ?>">
      <i class="fe fe-search fe-16"></i>
      <input type="search" class="form-control" placeholder="Search pages... (press /)" aria-label="Search pages">
      <ul class="lms-search-results" hidden></ul>
    </form>
    <ul class="nav ml-auto">
      <li class="nav-item dropdown lms-notify">
        <a class="nav-link logistics-top-control lms-bell dropdown-toggle" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Updates" title="Updates">
          <i class="fe fe-bell fe-16"></i><?php if ($unreadNotifications > 0): ?><em class="lms-bell-badge"><?= $unreadNotifications > 9 ? '9+' : (int) $unreadNotifications ?></em><?php endif; ?>
        </a>
        <div class="dropdown-menu dropdown-menu-right lms-notify-menu">
          <div class="lms-notify-head"><strong>Updates</strong><?php if ($unreadNotifications > 0): ?><em><?= (int) $unreadNotifications ?> new</em><?php endif; ?></div>
          <div class="lms-notify-list">
            <?php if ($notifications === []): ?>
              <div class="lms-notify-empty"><i class="fe fe-bell-off fe-24"></i><span>You're all caught up.</span></div>
            <?php endif; ?>
            <?php foreach ($notifications as $notification): ?>
              <?php
              $route = (string) ($notification['link_route'] ?? '');
              $canOpen = $route !== '' && role_can($route);
              $severity = in_array($notification['severity'], ['danger', 'warning', 'success'], true) ? $notification['severity'] : 'primary';
              ?>
              <a class="lms-notify-item<?= empty($notification['is_read']) ? ' is-unread' : '' ?>" href="<?= $canOpen ? htmlspecialchars(url($route)) : '#' ?>">
                <i class="lms-notify-dot lms-notify-dot-<?= $severity ?>"></i>
                <span class="lms-notify-body">
                  <strong><?= htmlspecialchars($notification['title']) ?></strong>
                  <small><?= htmlspecialchars($notification['message']) ?></small>
                  <em><?= htmlspecialchars(time_ago($notification['created_at'] ?? null)) ?></em>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </li>
      <?php if (can_switch_role()): ?>
      <li class="nav-item dropdown"><a class="nav-link logistics-top-control dropdown-toggle" href="#" data-toggle="dropdown"><i class="fe fe-users fe-16 mr-1"></i><span>Role</span><strong><?= htmlspecialchars(role_label()) ?></strong></a><div class="dropdown-menu dropdown-menu-right"><h6 class="dropdown-header">Switch role</h6><?php foreach (role_definitions() as $roleKey => $roleDefinition): ?><a class="dropdown-item <?= current_role() === $roleKey ? 'active' : '' ?>" href="<?= url('dashboard', ['role' => $roleKey]) ?>"><?= htmlspecialchars($roleDefinition['label']) ?></a><?php endforeach; ?></div></li>
      <?php else: ?>
      <li class="nav-item"><span class="nav-link logistics-top-control lms-role-static" title="Your role"><i class="fe fe-users fe-16 mr-1"></i><span>Role</span><strong><?= htmlspecialchars(role_label()) ?></strong></span></li>
      <?php endif; ?>
      <li class="nav-item"><a class="nav-link logistics-top-control logistics-theme-icon" href="#" id="modeSwitcher" data-mode="dark" aria-label="Switch light or dark theme" title="Switch theme"><i class="fe fe-sun fe-16"></i></a></li>
      <li class="nav-item dropdown"><a class="nav-link dropdown-toggle text-muted pr-0" href="#" data-toggle="dropdown"><span class="avatar avatar-sm mt-2"><img src="<?= $baseUrl ?>/assets/avatars/face-1.jpg" alt="User" class="avatar-img rounded-circle"></span></a><div class="dropdown-menu dropdown-menu-right"><h6 class="dropdown-header"><?= htmlspecialchars(current_user_name()) ?><small class="d-block text-muted"><?= htmlspecialchars(current_user_email()) ?></small></h6><a class="dropdown-item" href="#"><i class="fe fe-user fe-16 mr-2"></i> Profile</a><a class="dropdown-item" href="#"><i class="fe fe-settings fe-16 mr-2"></i> Settings</a><a class="dropdown-item" href="<?= url('logout') ?>"><i class="fe fe-power fe-16 mr-2"></i> Logout</a></div></li>
    </ul>
  </nav>
  <aside class="sidebar-left border-right bg-white shadow" id="leftSidebar" data-simplebar>
    <nav class="vertnav navbar navbar-light">
      <div class="w-100 mb-4 d-flex"><a class="navbar-brand mx-auto mt-2 flex-fill text-center" href="<?= url('dashboard') ?>"><img src="<?= $baseUrl ?>/assets/img/logistics-logo.svg" width="86%" alt="LMS"></a></div>
      <ul class="navbar-nav flex-fill w-100 mb-2">
        <li class="nav-item w-100"><a class="nav-link<?= nav_active('dashboard') ?>" href="<?= url('dashboard') ?>"><i class="fe fe-home fe-16"></i><span class="ml-3 item-text">Dashboard</span></a></li>
        <?php if (role_can('vehicles') || role_can('drivers') || role_can('maintenance')): ?><li class="nav-item dropdown"><a href="#fleetMenu" data-toggle="collapse" class="dropdown-toggle nav-link<?= nav_group(['vehicles', 'drivers', 'maintenance']) ? ' has-active' : '' ?>"><i class="fe fe-truck fe-16"></i><span class="ml-3 item-text">Fleet Management</span></a><ul class="collapse<?= nav_group(['vehicles', 'drivers', 'maintenance']) ? ' show' : '' ?> list-unstyled pl-4 w-100" id="fleetMenu"><?php if (role_can('vehicles')): ?><li class="nav-item"><a class="nav-link pl-3<?= nav_active('vehicles') ?>" href="<?= url('vehicles') ?>"><span class="ml-1 item-text">Vehicles</span></a></li><?php endif; ?><?php if (role_can('drivers')): ?><li class="nav-item"><a class="nav-link pl-3<?= nav_active('drivers') ?>" href="<?= url('drivers') ?>"><span class="ml-1 item-text">Drivers</span></a></li><?php endif; ?><?php if (role_can('maintenance')): ?><li class="nav-item"><a class="nav-link pl-3<?= nav_active('maintenance') ?>" href="<?= url('maintenance') ?>"><span class="ml-1 item-text">Maintenance</span></a></li><?php endif; ?></ul></li><?php endif; ?>
        <?php if (role_can('trips') || role_can('deliveries') || role_can('requests')): ?><li class="nav-item dropdown"><a href="#transportMenu" data-toggle="collapse" class="dropdown-toggle nav-link<?= nav_group(['trips', 'deliveries', 'requests']) ? ' has-active' : '' ?>"><i class="fe fe-map-pin fe-16"></i><span class="ml-3 item-text">Transport</span></a><ul class="collapse<?= nav_group(['trips', 'deliveries', 'requests']) ? ' show' : '' ?> list-unstyled pl-4 w-100" id="transportMenu"><?php if (role_can('trips')): ?><li class="nav-item"><a class="nav-link pl-3<?= nav_active('trips') ?>" href="<?= url('trips') ?>"><span class="ml-1 item-text">Trips</span></a></li><?php endif; ?><?php if (role_can('deliveries')): ?><li class="nav-item"><a class="nav-link pl-3<?= nav_active('deliveries') ?>" href="<?= url('deliveries') ?>"><span class="ml-1 item-text">Deliveries</span></a></li><?php endif; ?><?php if (role_can('requests')): ?><li class="nav-item"><a class="nav-link pl-3<?= nav_active('requests') ?>" href="<?= url('requests') ?>"><span class="ml-1 item-text">Transport requests</span></a></li><?php endif; ?></ul></li><?php endif; ?>
        <?php if (role_can('fuel') || role_can('expenses')): ?><li class="nav-item dropdown"><a href="#financeMenu" data-toggle="collapse" class="dropdown-toggle nav-link<?= nav_group(['fuel', 'expenses']) ? ' has-active' : '' ?>"><i class="fe fe-credit-card fe-16"></i><span class="ml-3 item-text">Finance</span></a><ul class="collapse<?= nav_group(['fuel', 'expenses']) ? ' show' : '' ?> list-unstyled pl-4 w-100" id="financeMenu"><?php if (role_can('fuel')): ?><li class="nav-item"><a class="nav-link pl-3<?= nav_active('fuel') ?>" href="<?= url('fuel') ?>"><span class="ml-1 item-text">Fuel management</span></a></li><?php endif; ?><?php if (role_can('expenses')): ?><li class="nav-item"><a class="nav-link pl-3<?= nav_active('expenses') ?>" href="<?= url('expenses') ?>"><span class="ml-1 item-text">Logistics expenses</span></a></li><?php endif; ?></ul></li><?php endif; ?>
        <?php if (role_can('warehouse')): ?><li class="nav-item"><a class="nav-link<?= nav_active('warehouse') ?>" href="<?= url('warehouse') ?>"><i class="fe fe-package fe-16"></i><span class="ml-3 item-text">Warehouse</span></a></li><?php endif; ?>
        <?php if (role_can('procurement')): ?><li class="nav-item"><a class="nav-link<?= nav_active('procurement') ?>" href="<?= url('procurement') ?>"><i class="fe fe-shopping-cart fe-16"></i><span class="ml-3 item-text">Procurement</span></a></li><?php endif; ?>
        <?php if (role_can('reports')): ?><li class="nav-item dropdown"><a href="#reportsMenu" data-toggle="collapse" class="dropdown-toggle nav-link<?= nav_group(['reports']) ? ' has-active' : '' ?>"><i class="fe fe-bar-chart-2 fe-16"></i><span class="ml-3 item-text">Reports</span></a><ul class="collapse<?= nav_group(['reports']) ? ' show' : '' ?> list-unstyled pl-4 w-100 logistics-report-menu" id="reportsMenu"><li class="nav-item"><a class="nav-link pl-3<?= nav_active('reports', '') ?>" href="<?= url('reports') ?>"><span class="ml-1 item-text">All reports</span></a></li><li class="nav-item"><a class="nav-link pl-3<?= nav_active('reports', 'Vehicle utilization') ?>" href="<?= url('reports', ['report_type' => 'Vehicle utilization']) ?>"><span class="ml-1 item-text">Vehicle utilization</span></a></li><li class="nav-item"><a class="nav-link pl-3<?= nav_active('reports', 'Fuel consumption') ?>" href="<?= url('reports', ['report_type' => 'Fuel consumption']) ?>"><span class="ml-1 item-text">Fuel consumption</span></a></li><li class="nav-item"><a class="nav-link pl-3<?= nav_active('reports', 'Delivery performance') ?>" href="<?= url('reports', ['report_type' => 'Delivery performance']) ?>"><span class="ml-1 item-text">Delivery performance</span></a></li><li class="nav-item"><a class="nav-link pl-3<?= nav_active('reports', 'Maintenance cost') ?>" href="<?= url('reports', ['report_type' => 'Maintenance cost']) ?>"><span class="ml-1 item-text">Maintenance cost</span></a></li><li class="nav-item"><a class="nav-link pl-3<?= nav_active('reports', 'Driver performance') ?>" href="<?= url('reports', ['report_type' => 'Driver performance']) ?>"><span class="ml-1 item-text">Driver performance</span></a></li><li class="nav-item"><a class="nav-link pl-3<?= nav_active('reports', 'Inventory movement') ?>" href="<?= url('reports', ['report_type' => 'Inventory movement']) ?>"><span class="ml-1 item-text">Inventory movement</span></a></li><li class="nav-item"><a class="nav-link pl-3<?= nav_active('reports', 'Trip profitability') ?>" href="<?= url('reports', ['report_type' => 'Trip profitability']) ?>"><span class="ml-1 item-text">Trip profitability</span></a></li><li class="nav-item"><a class="nav-link pl-3<?= nav_active('reports', 'Expense summary') ?>" href="<?= url('reports', ['report_type' => 'Expense summary']) ?>"><span class="ml-1 item-text">Expense summary</span></a></li></ul></li><?php endif; ?>
        <?php if (role_can('users')): ?><li class="nav-item"><a class="nav-link<?= nav_active('users') ?>" href="<?= url('users') ?>"><i class="fe fe-users fe-16"></i><span class="ml-3 item-text">Users & permissions</span></a></li><?php endif; ?>
      </ul>
      <ul class="navbar-nav flex-fill w-100 mb-2"><li class="nav-item w-100"><a class="nav-link<?= nav_active('logout') ?>" href="<?= url('logout') ?>"><i class="fe fe-power fe-16"></i><span class="ml-3 item-text">Logout</span></a></li></ul>
    </nav>
  </aside>
  <main role="main" class="main-content">
