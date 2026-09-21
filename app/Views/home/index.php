<?php
$baseUrl = config('app.base_url', '');
$roleDefinitions = role_definitions();
$accountRoles = array_intersect_key($roleDefinitions, $accounts);
$selectedRole = (string) ($_GET['role'] ?? 'logistics_manager');
if (!isset($accountRoles[$selectedRole])) {
    $selectedRole = array_key_first($accountRoles) ?: '';
}
$selectedEmail = $selectedRole !== '' ? ($accounts[$selectedRole]['email'] ?? '') : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS | Logistics made visible</title>
  <link rel="icon" href="<?= $baseUrl ?>/assets/img/logistics-logo.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/feather.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/app-light.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/logistics.css">
</head>
<body class="logistics-home">
  <header class="home-nav">
    <a class="home-brand" href="<?= url('') ?>" aria-label="LMS home">
      <img src="<?= $baseUrl ?>/assets/img/logistics-logo.svg" alt="LMS" width="150">
    </a>
    <nav aria-label="Home navigation">
      <a href="#capabilities">Capabilities</a>
      <a href="#workflow">Workflow</a>
      <a href="#login">Login</a>
      <?php if (is_logged_in()): ?>
        <a class="button button-primary" href="<?= url('dashboard') ?>"><i class="fe fe-grid"></i> Workspace</a>
      <?php endif; ?>
    </nav>
  </header>

  <main>
    <section class="home-hero">
      <div class="hero-copy">
        <p class="home-kicker">LMS logistics management system</p>
        <h1>Every vehicle.<br><em>Every delivery.</em><br>One clear view.</h1>
        <p>Plan transport, protect fleet uptime, control inventory and turn every movement into an accountable operation.</p>
        <div class="hero-actions">
          <a class="button button-primary" href="#login"><i class="fe fe-log-in"></i> Sign in</a>
          <a class="button button-outline" href="#capabilities"><i class="fe fe-layers"></i> View modules</a>
        </div>
        <div class="home-snapshot" aria-label="Operations snapshot">
          <span><strong>48</strong> fleet vehicles</span>
          <span><strong>17</strong> active trips</span>
          <span><strong>91%</strong> on-time delivery</span>
        </div>
      </div>

      <aside class="login-panel" id="login" aria-label="Login">
        <div class="login-panel-header">
          <span class="panel-icon"><i class="fe fe-shield"></i></span>
          <div>
            <p class="home-kicker">Secure access</p>
            <h2>Open your workspace</h2>
          </div>
        </div>

        <?php if ($loginError === 'locked'): ?>
          <div class="home-alert home-alert-danger">
            Too many failed sign-ins. This account is locked
            <?= isset($_GET['minutes']) && (int) $_GET['minutes'] > 0 ? 'for about ' . (int) $_GET['minutes'] . ' more minute(s)' : 'until an administrator unlocks it' ?>.
          </div>
        <?php elseif ($loginError === 'token'): ?>
          <div class="home-alert home-alert-danger">Your session expired before the form was sent. Please try again.</div>
        <?php elseif ($loginError !== ''): ?>
          <div class="home-alert home-alert-danger">That email and password do not match an active account.</div>
        <?php elseif ($loginRequired): ?>
          <div class="home-alert">Please sign in before opening the operations workspace.</div>
        <?php elseif ($loggedOut): ?>
          <div class="home-alert home-alert-success">You have been logged out.</div>
        <?php endif; ?>

        <?php if ($accountRoles === []): ?>
          <div class="home-alert home-alert-danger">No active database users were found. Run the schema and seed files, then refresh this page.</div>
        <?php endif; ?>

        <form class="home-login-form" method="post" action="<?= url('login') ?>">
          <?= csrf_field() ?>
          <label for="loginRole">Role</label>
          <select id="loginRole" name="role" required <?= $accountRoles === [] ? 'disabled' : '' ?>>
            <?php foreach ($accountRoles as $roleKey => $definition): ?>
              <option value="<?= htmlspecialchars($roleKey) ?>" data-email="<?= htmlspecialchars($accounts[$roleKey]['email'] ?? '') ?>" <?= $selectedRole === $roleKey ? 'selected' : '' ?>>
                <?= htmlspecialchars($definition['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label for="loginEmail">Email</label>
          <input id="loginEmail" name="email" type="email" value="<?= htmlspecialchars($selectedEmail) ?>" autocomplete="username" required <?= $accountRoles === [] ? 'disabled' : '' ?>>

          <label for="loginPassword">Password</label>
          <input id="loginPassword" name="password" type="password" autocomplete="current-password" required <?= $accountRoles === [] ? 'disabled' : '' ?>>

          <button class="button button-primary button-block" type="submit" <?= $accountRoles === [] ? 'disabled' : '' ?>><i class="fe fe-log-in"></i> Login to dashboard</button>
          <p class="login-help"><a href="<?= url('forgot-password') ?>">Forgotten your password?</a></p>
        </form>

        <div class="demo-accounts">
          <span>Active database users</span>
          <ul>
            <?php foreach ($accounts as $roleKey => $account): ?>
              <?php if (!isset($roleDefinitions[$roleKey])) { continue; } ?>
              <li>
                <a href="<?= url('', ['role' => $roleKey]) ?>#login">
                  <?= htmlspecialchars($roleDefinitions[$roleKey]['label']) ?>
                </a>
                <small><?= htmlspecialchars($account['email']) ?></small>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </aside>
    </section>

    <section class="home-capabilities" id="capabilities">
      <div class="section-heading">
        <p class="home-kicker">One operating picture</p>
        <h2>Core modules ready for daily logistics work.</h2>
      </div>
      <div class="capability-grid">
        <article><span>01</span><h3>Fleet and drivers</h3><p>Vehicles, driver availability, maintenance work orders and fuel records.</p></article>
        <article><span>02</span><h3>Transport control</h3><p>Requests, approvals, trip assignments, delivery status and proof records.</p></article>
        <article><span>03</span><h3>Stock and spend</h3><p>Warehouses, procurement, suppliers, expenses and operational reports.</p></article>
      </div>
    </section>

    <section class="home-workflow" id="workflow">
      <div>
        <p class="home-kicker">A connected workflow</p>
        <h2>Request, approve, assign, deliver, report.</h2>
      </div>
      <div class="workflow-steps">
        <span>Request</span><b>&rarr;</b><span>Approve</span><b>&rarr;</b><span>Assign</span><b>&rarr;</b><span>Deliver</span><b>&rarr;</b><span>Report</span>
      </div>
    </section>
  </main>
  <footer class="lms-footer">Powered by ITEC LTD &copy; <?= date('Y') ?></footer>
  <script>
    var roleSelect = document.getElementById('loginRole');
    var emailInput = document.getElementById('loginEmail');
    if (roleSelect && emailInput) {
      roleSelect.addEventListener('change', function () {
        var selected = roleSelect.options[roleSelect.selectedIndex];
        emailInput.value = selected.getAttribute('data-email') || '';
      });
    }
  </script>
</body>
</html>
