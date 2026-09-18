<?php

namespace Controllers;

class DashboardController
{
    public function index(): void
    {
        $role = \current_role();
        $roleData = [
            'super_admin' => ['title' => 'Executive control center', 'subtitle' => 'Full system visibility across fleet, transport, warehouse and finance.', 'metrics' => [['Total vehicles', '48', '+6.4%', 'truck'], ['Active trips', '17', '+3 today', 'navigation'], ['Pending requests', '12', '4 high priority', 'clipboard'], ['Monthly expenses', 'RWF 18.4M', '-3.2%', 'credit-card']]],
            'logistics_manager' => ['title' => 'Operations dashboard', 'subtitle' => 'Coordinate transport requests, trips, deliveries and daily logistics performance.', 'metrics' => [['Fleet vehicles', '48', '+6.4%', 'truck'], ['Active trips', '17', '+3 today', 'navigation'], ['Deliveries in progress', '29', '82% on time', 'package'], ['Open maintenance', '08', '2 urgent', 'tool']]],
            'fleet_manager' => ['title' => 'Fleet performance', 'subtitle' => 'Monitor vehicle utilization, driver assignments, maintenance and fuel consumption.', 'metrics' => [['Fleet vehicles', '48', '42 active', 'truck'], ['Available vehicles', '16', '+4 today', 'check'], ['Due for service', '08', '2 urgent', 'tool'], ['Fuel this month', '18,420 L', '-4.2%', 'droplet']]],
            'warehouse_manager' => ['title' => 'Warehouse control', 'subtitle' => 'Keep stock levels healthy and procurement moving for every depot.', 'metrics' => [['Stock items', '326', '+18 this month', 'package'], ['Low stock alerts', '14', '5 urgent', 'alert-triangle'], ['Open requests', '12', '4 awaiting approval', 'clipboard'], ['Stock value', 'RWF 42.8M', '+8.1%', 'layers']]],
            'driver' => ['title' => 'Driver workspace', 'subtitle' => 'See your assigned trips, delivery instructions and proof of delivery tasks.', 'metrics' => [['Assigned trips', '04', '2 today', 'navigation'], ['Deliveries today', '03', '1 pending proof', 'package'], ['Completed trips', '28', '+5 this month', 'check'], ['Safety score', '96%', '+2.4%', 'shield']]],
            'finance' => ['title' => 'Finance dashboard', 'subtitle' => 'Control logistics spending, fuel costs, approvals and monthly financial reports.', 'metrics' => [['Monthly expenses', 'RWF 18.4M', '-3.2%', 'credit-card'], ['Pending approvals', '07', 'RWF 2.1M', 'clock'], ['Fuel spend', 'RWF 8.6M', '-4.2%', 'droplet'], ['Cost per trip', 'RWF 184K', '-6.1%', 'trending-down']]],
            'management' => ['title' => 'Management overview', 'subtitle' => 'Track the operational and financial indicators that need leadership attention.', 'metrics' => [['Fleet utilization', '82%', '+5.6%', 'truck'], ['On-time delivery', '91%', '+3.1%', 'check'], ['Monthly logistics cost', 'RWF 18.4M', '-3.2%', 'credit-card'], ['Open risks', '06', '2 critical', 'alert-triangle']]],
        ][$role];

        \view('dashboard/index', [
            'title' => $roleData['title'],
            'roleSubtitle' => $roleData['subtitle'],
            'metrics' => array_map(static fn (array $metric): array => ['label' => $metric[0], 'value' => $metric[1], 'trend' => $metric[2], 'icon' => $metric[3], 'tone' => 'blue'], $roleData['metrics']),
            'role' => $role,
            'denied' => ($_GET['denied'] ?? '') === '1',
            'recentTrips' => [
                ['reference' => 'TRP-0248', 'route' => 'Kigali - Huye', 'vehicle' => 'RAC 482D', 'driver' => 'Samuel N.', 'status' => 'In transit'],
                ['reference' => 'TRP-0247', 'route' => 'Kigali - Musanze', 'vehicle' => 'RAB 118K', 'driver' => 'Marie U.', 'status' => 'Delivered'],
                ['reference' => 'TRP-0246', 'route' => 'Kigali - Rubavu', 'vehicle' => 'RAC 901P', 'driver' => 'Eric M.', 'status' => 'Loading'],
            ],
        ]);
    }
}
