USE logistics_mvc;

INSERT INTO vehicles (plate_number, vehicle_type, model, mileage, status, next_service_date) VALUES
('RAC 482D','Delivery truck','Toyota Dyna',84210,'on_trip','2026-10-12'),
('RAB 118K','Pickup','Toyota Hilux',62100,'available','2026-10-18'),
('RAC 901P','Box truck','Isuzu NPR',119800,'maintenance','2026-09-18'),
('RAB 332M','Delivery truck','Mitsubishi Canter',50500,'available','2026-09-22'),
('RAC 774F','Van','Toyota Hiace',73100,'on_trip','2026-11-04'),
('RAB 640C','Pickup','Ford Ranger',46800,'available','2026-09-28'),
('RAC 208L','Box truck','Hino 300',140200,'inactive','2026-12-15'),
('RAB 519T','Motorcycle','TVS King',28200,'on_trip','2026-10-30'),
('RAC 863N','Delivery truck','Fuso Fighter',93200,'available','2026-11-07'),
('RAB 407G','Van','Nissan Caravan',88400,'maintenance','2026-09-18')
ON DUPLICATE KEY UPDATE status = VALUES(status), mileage = VALUES(mileage);

INSERT INTO drivers (full_name, phone, license_number, license_expiry, status) VALUES
('Samuel Niyonzima','+250 788 632 119','RWA-DL-0912','2026-12-04','on_trip'),
('Marie Uwase','+250 788 120 442','RWA-DL-1028','2027-03-18','available'),
('Eric Murenzi','+250 783 210 084','RWA-DL-0744','2027-01-29','off_duty'),
('Aurore Kayitesi','+250 788 400 210','RWA-DL-1102','2027-05-10','available'),
('Jean Pierre Habimana','+250 788 512 030','RWA-DL-0987','2026-11-22','on_trip'),
('Patrick Rukundo','+250 783 291 006','RWA-DL-0861','2027-02-14','available');

INSERT INTO warehouses (warehouse_name, location) VALUES
('Kigali Central Warehouse','Kigali'),('Huye Depot','Huye'),('Musanze Depot','Musanze'),('Rubavu Depot','Rubavu');

INSERT INTO suppliers (supplier_name, contact_name, phone, email) VALUES
('Kigali Auto Care','Jean Bosco','+250 788 101 010','sales@kigaliautocare.rw'),
('Secure Rwanda','Aline Mukamana','+250 788 202 020','orders@securerwanda.rw'),
('Lubricants Ltd','David Habyarimana','+250 788 303 030','sales@lubricants.rw'),
('Fleet Workshop','Operations desk','+250 788 404 040','workshop@itec.rw');

INSERT INTO trips (reference_code, pickup_location, destination, departure_at, status) VALUES
('TRP-0248','Kigali','Huye','2026-09-18 08:30:00','in_transit'),
('TRP-0247','Kigali','Musanze','2026-09-18 07:00:00','delivered'),
('TRP-0246','Kigali','Rubavu','2026-09-18 10:15:00','loading'),
('TRP-0245','Kigali','Rusizi','2026-09-18 06:45:00','approved');

INSERT INTO inventory_items (warehouse_id, sku, item_name, quantity, minimum_level, unit_cost, status)
SELECT id, 'SP-BRK-001', 'Brake pads', 24, 10, 28500, 'in_stock' FROM warehouses WHERE warehouse_name = 'Kigali Central Warehouse' LIMIT 1;
