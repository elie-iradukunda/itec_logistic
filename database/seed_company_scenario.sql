USE logistics_mvc;

SET NAMES utf8mb4;

START TRANSACTION;

-- Rwanda company scenario:
-- Kigali Fresh Foods Ltd uses LMS to move cold-chain school feeding supplies
-- from Kigali Central Warehouse to Huye Depot on 2026-09-20.
-- The scenario uses the seeded real database users so every role has work:
-- Admin User, Aline Mukamana, Eric Murenzi, Nadine Tuyisenge,
-- Samuel Niyonzima, Emmanuel Safari and Jean Pierre Habimana.

UPDATE users
SET department = CASE email
    WHEN 'admin@itec.rw' THEN 'Kigali Fresh Foods - System Administration'
    WHEN 'aline@itec.rw' THEN 'Kigali Fresh Foods - Logistics Operations'
    WHEN 'eric@itec.rw' THEN 'Kigali Fresh Foods - Fleet'
    WHEN 'nadine@itec.rw' THEN 'Kigali Fresh Foods - Warehouse'
    WHEN 'samuel@itec.rw' THEN 'Kigali Fresh Foods - Driver Team'
    WHEN 'emmanuel@itec.rw' THEN 'Kigali Fresh Foods - Finance'
    WHEN 'jeanpierre@itec.rw' THEN 'Kigali Fresh Foods - Management'
    ELSE department
END
WHERE email IN (
    'admin@itec.rw',
    'aline@itec.rw',
    'eric@itec.rw',
    'nadine@itec.rw',
    'samuel@itec.rw',
    'emmanuel@itec.rw',
    'jeanpierre@itec.rw'
);

UPDATE drivers
SET user_id = (SELECT id FROM users WHERE email = 'samuel@itec.rw')
WHERE license_number = 'RWA-DL-0912';

UPDATE vehicles
SET assigned_driver_id = (SELECT id FROM drivers WHERE license_number = 'RWA-DL-0912'),
    mileage = 84980,
    status = 'on_trip',
    next_service_date = '2026-10-12'
WHERE plate_number = 'RAC 482D';

INSERT INTO inventory_items (warehouse_id, sku, item_name, quantity, minimum_level, unit_cost, status) VALUES
((SELECT id FROM warehouses WHERE warehouse_name = 'Kigali Central Warehouse' LIMIT 1), 'KFF-COOL-001', 'Cold-chain delivery crates', 62, 20, 45000, 'in_stock'),
((SELECT id FROM warehouses WHERE warehouse_name = 'Huye Depot' LIMIT 1), 'KFF-FLOUR-001', 'Fortified maize flour bags', 18, 30, 32000, 'reorder')
ON DUPLICATE KEY UPDATE
    warehouse_id = VALUES(warehouse_id),
    item_name = VALUES(item_name),
    quantity = VALUES(quantity),
    minimum_level = VALUES(minimum_level),
    unit_cost = VALUES(unit_cost),
    status = VALUES(status);

INSERT INTO transport_requests (reference_code, requester_id, pickup_location, destination, required_date, priority, status, notes) VALUES
('REQ-KFF-001', (SELECT id FROM users WHERE email = 'nadine@itec.rw'), 'Kigali Central Warehouse', 'Huye Depot', '2026-09-20', 'urgent', 'assigned', 'Kigali Fresh Foods Ltd scenario: Nadine requested same-day cold-chain replenishment for Huye Depot. Aline approved and assigned TRP-KFF-001.')
ON DUPLICATE KEY UPDATE
    requester_id = VALUES(requester_id),
    pickup_location = VALUES(pickup_location),
    destination = VALUES(destination),
    required_date = VALUES(required_date),
    priority = VALUES(priority),
    status = VALUES(status),
    notes = VALUES(notes);

INSERT INTO trips (reference_code, vehicle_id, driver_id, pickup_location, destination, departure_at, arrival_at, status) VALUES
('TRP-KFF-001', (SELECT id FROM vehicles WHERE plate_number = 'RAC 482D'), (SELECT id FROM drivers WHERE license_number = 'RWA-DL-0912'), 'Kigali Central Warehouse', 'Huye Depot', '2026-09-20 06:30:00', '2026-09-20 10:45:00', 'delivered')
ON DUPLICATE KEY UPDATE
    vehicle_id = VALUES(vehicle_id),
    driver_id = VALUES(driver_id),
    pickup_location = VALUES(pickup_location),
    destination = VALUES(destination),
    departure_at = VALUES(departure_at),
    arrival_at = VALUES(arrival_at),
    status = VALUES(status);

INSERT INTO deliveries (delivery_code, trip_id, recipient_name, destination, status, proof_file, recipient_signature, delivered_at) VALUES
('DEL-KFF-001', (SELECT id FROM trips WHERE reference_code = 'TRP-KFF-001'), 'Huye Depot - Kigali Fresh Foods Ltd', 'Huye Depot', 'delivered', 'proofs/kff-huye-delivery.pdf', 'signatures/kff-huye-recipient.png', '2026-09-20 10:45:00')
ON DUPLICATE KEY UPDATE
    trip_id = VALUES(trip_id),
    recipient_name = VALUES(recipient_name),
    destination = VALUES(destination),
    status = VALUES(status),
    proof_file = VALUES(proof_file),
    recipient_signature = VALUES(recipient_signature),
    delivered_at = VALUES(delivered_at);

INSERT INTO maintenance_orders (work_order_code, vehicle_id, service_name, provider_name, priority, estimated_cost, status, due_date, completed_at) VALUES
('MNT-KFF-001', (SELECT id FROM vehicles WHERE plate_number = 'RAC 482D'), 'Pre-trip cold-chain inspection', 'Fleet Workshop', 'normal', 85000, 'completed', '2026-09-20', '2026-09-20 05:50:00')
ON DUPLICATE KEY UPDATE
    vehicle_id = VALUES(vehicle_id),
    service_name = VALUES(service_name),
    provider_name = VALUES(provider_name),
    priority = VALUES(priority),
    estimated_cost = VALUES(estimated_cost),
    status = VALUES(status),
    due_date = VALUES(due_date),
    completed_at = VALUES(completed_at);

INSERT INTO fuel_records (reference_code, vehicle_id, station_name, litres, unit_price, mileage, purchased_at, receipt_file) VALUES
('FUE-KFF-001', (SELECT id FROM vehicles WHERE plate_number = 'RAC 482D'), 'SP Kigali', 75.00, 1540.00, 84980, '2026-09-20 05:40:00', 'receipts/kff-fuel-001.pdf')
ON DUPLICATE KEY UPDATE
    vehicle_id = VALUES(vehicle_id),
    station_name = VALUES(station_name),
    litres = VALUES(litres),
    unit_price = VALUES(unit_price),
    mileage = VALUES(mileage),
    purchased_at = VALUES(purchased_at),
    receipt_file = VALUES(receipt_file);

INSERT INTO expenses (reference_code, trip_id, vehicle_id, category, amount, submitted_by, status, expense_date, notes) VALUES
('EXP-KFF-001', (SELECT id FROM trips WHERE reference_code = 'TRP-KFF-001'), (SELECT id FROM vehicles WHERE plate_number = 'RAC 482D'), 'Route cost', 170500.00, (SELECT id FROM users WHERE email = 'samuel@itec.rw'), 'approved', '2026-09-20', 'Kigali Fresh Foods Ltd Huye route cost: fuel, toll and driver allowance approved by Finance.')
ON DUPLICATE KEY UPDATE
    trip_id = VALUES(trip_id),
    vehicle_id = VALUES(vehicle_id),
    category = VALUES(category),
    amount = VALUES(amount),
    submitted_by = VALUES(submitted_by),
    status = VALUES(status),
    expense_date = VALUES(expense_date),
    notes = VALUES(notes);

INSERT INTO purchase_requests (request_code, supplier_id, requested_by, description, amount, status) VALUES
('PR-KFF-001', (SELECT id FROM suppliers WHERE supplier_name = 'Secure Rwanda' LIMIT 1), (SELECT id FROM users WHERE email = 'nadine@itec.rw'), 'Cold-chain security seals and reusable delivery crates for Huye replenishment route.', 620000, 'received')
ON DUPLICATE KEY UPDATE
    supplier_id = VALUES(supplier_id),
    requested_by = VALUES(requested_by),
    description = VALUES(description),
    amount = VALUES(amount),
    status = VALUES(status);

INSERT INTO reports (report_name, period_label, owner_name, last_generated_at, format_label, action_label) VALUES
('KFF Huye delivery performance', '20 September 2026', 'Management', '2026-09-20 11:30:00', 'PDF', 'View')
ON DUPLICATE KEY UPDATE
    period_label = VALUES(period_label),
    owner_name = VALUES(owner_name),
    last_generated_at = VALUES(last_generated_at),
    format_label = VALUES(format_label),
    action_label = VALUES(action_label);

INSERT INTO notifications (notification_key, user_id, role_key, title, message, link_route, severity, is_read, created_at) VALUES
('NOTIF-KFF-ADMIN', (SELECT id FROM users WHERE email = 'admin@itec.rw'), 'super_admin', 'KFF scenario users ready', 'Kigali Fresh Foods Ltd has seven active users mapped to the seeded logistics roles.', 'users', 'info', 0, '2026-09-20 05:00:00'),
('NOTIF-KFF-LOGISTICS', (SELECT id FROM users WHERE email = 'aline@itec.rw'), 'logistics_manager', 'KFF Huye route assigned', 'REQ-KFF-001 has been approved and assigned to TRP-KFF-001 for same-day delivery.', 'trips', 'success', 0, '2026-09-20 05:20:00'),
('NOTIF-KFF-FLEET', (SELECT id FROM users WHERE email = 'eric@itec.rw'), 'fleet_manager', 'Pre-trip inspection complete', 'MNT-KFF-001 and FUE-KFF-001 are ready for vehicle RAC 482D before the Huye route.', 'maintenance', 'success', 0, '2026-09-20 05:55:00'),
('NOTIF-KFF-WAREHOUSE', (SELECT id FROM users WHERE email = 'nadine@itec.rw'), 'warehouse_manager', 'Huye stock replenishment dispatched', 'KFF-COOL-001 crates are prepared and KFF-FLOUR-001 remains below minimum at Huye Depot.', 'warehouse', 'warning', 0, '2026-09-20 06:05:00'),
('NOTIF-KFF-DRIVER', (SELECT id FROM users WHERE email = 'samuel@itec.rw'), 'driver', 'Delivery proof uploaded', 'DEL-KFF-001 for TRP-KFF-001 was delivered at Huye Depot with proof and signature files.', 'deliveries', 'success', 0, '2026-09-20 10:50:00'),
('NOTIF-KFF-FINANCE', (SELECT id FROM users WHERE email = 'emmanuel@itec.rw'), 'finance', 'KFF route cost approved', 'EXP-KFF-001 records the approved route cost for the Kigali to Huye delivery.', 'expenses', 'success', 0, '2026-09-20 11:05:00'),
('NOTIF-KFF-MANAGEMENT', (SELECT id FROM users WHERE email = 'jeanpierre@itec.rw'), 'management', 'KFF delivery report ready', 'KFF Huye delivery performance is ready for leadership review.', 'reports', 'success', 0, '2026-09-20 11:35:00')
ON DUPLICATE KEY UPDATE
    user_id = VALUES(user_id),
    role_key = VALUES(role_key),
    title = VALUES(title),
    message = VALUES(message),
    link_route = VALUES(link_route),
    severity = VALUES(severity),
    is_read = VALUES(is_read),
    created_at = VALUES(created_at);

INSERT INTO audit_logs (user_id, action_name, entity_type, entity_id, reason, metadata)
SELECT (SELECT id FROM users WHERE email = 'aline@itec.rw'), 'scenario.loaded', 'company_scenario', 'KFF-2026-09-20', 'Kigali Fresh Foods Ltd end-to-end logistics scenario loaded.', '{"company":"Kigali Fresh Foods Ltd","request":"REQ-KFF-001","trip":"TRP-KFF-001","delivery":"DEL-KFF-001"}'
WHERE NOT EXISTS (
    SELECT 1 FROM audit_logs
    WHERE action_name = 'scenario.loaded'
      AND entity_type = 'company_scenario'
      AND entity_id = 'KFF-2026-09-20'
);

COMMIT;
