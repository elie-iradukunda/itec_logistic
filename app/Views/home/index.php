<?php $baseUrl = config('app.base_url', ''); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ITEC Logistics | Logistics made visible</title>
  <link rel="icon" href="<?= $baseUrl ?>/assets/img/logistics-logo.svg">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/app-light.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/logistics.css">
</head>
<body class="logistics-home">
  <header class="home-nav"><a href="<?= $baseUrl ?>/?route=home"><img src="<?= $baseUrl ?>/assets/img/logistics-logo.svg" alt="ITEC Logistics" width="190"></a><nav><a href="#capabilities">Capabilities</a><a href="#workflow">Workflow</a><a class="button button-primary" href="<?= $baseUrl ?>/?route=dashboard">Open workspace</a></nav></header>
  <main>
    <section class="home-hero"><div class="hero-copy"><p class="home-kicker">ITEC logistics operations platform</p><h1>Every vehicle.<br><em>Every delivery.</em><br>One clear view.</h1><p>Plan transport, protect fleet uptime, control inventory and turn every movement into an accountable operation.</p><a class="button button-primary" href="<?= $baseUrl ?>/?route=dashboard">Enter operations workspace <span>→</span></a></div><div class="hero-board"><div class="board-top"><span>Live operations</span><span class="live-dot">● Online</span></div><div class="route-card"><div class="route-line"><span class="route-node">KGL</span><span class="route-track"><i></i><b></b><i></i></span><span class="route-node destination">HYE</span></div><strong>TRP-0248 · Kigali to Huye</strong><small>RAC 482D · Samuel N. · In transit</small></div><div class="board-stats"><div><small>On-time delivery</small><strong>91%</strong><span>+3.1% this month</span></div><div><small>Active fleet</small><strong>42</strong><span>of 48 vehicles</span></div></div><div class="board-footer"><span>Next service</span><strong>2 vehicles due today</strong></div></div></section>
    <section class="home-capabilities" id="capabilities"><div><p class="home-kicker">One operating picture</p><h2>Built for movement at every scale.</h2></div><div class="capability-grid"><article><span>01</span><h3>Fleet intelligence</h3><p>Vehicle health, drivers, maintenance and fuel in one operational record.</p></article><article><span>02</span><h3>Trip control</h3><p>Move requests from approval to assignment, tracking and proof of delivery.</p></article><article><span>03</span><h3>Cost discipline</h3><p>Connect expenses, procurement and warehouse stock to every decision.</p></article></div></section>
    <section class="home-workflow" id="workflow"><p class="home-kicker">A connected workflow</p><div class="workflow-steps"><span>Request</span><b>→</b><span>Approve</span><b>→</b><span>Assign</span><b>→</b><span>Deliver</span><b>→</b><span>Report</span></div></section>
  </main>
</body>
</html>
