# Rwanda Company Scenario - Kigali Fresh Foods Ltd

This scenario explains how one Rwanda company can use LMS from the first logistics request to final management reporting. It is backed by `database/seed_company_scenario.sql`, so the records described here are real database rows after running the migration command.

Kigali Fresh Foods Ltd is a fictional Rwanda company used for testing and demonstration. The company distributes cold-chain school feeding supplies from Kigali Central Warehouse to regional depots. The seeded scenario follows one same-day replenishment movement from Kigali to Huye on 20 September 2026.

## Company Users

All users already exist in the main seed and use the password `password`.

| User | Login email | Role | Work in this scenario |
| --- | --- | --- | --- |
| Admin User | admin@itec.rw | Super Admin | Confirms company users, roles and permissions are active. |
| Aline Mukamana | aline@itec.rw | Logistics Manager | Approves the Huye transport request and dispatches the trip. |
| Eric Murenzi | eric@itec.rw | Fleet Manager | Checks the vehicle, pre-trip maintenance and fuel record. |
| Nadine Tuyisenge | nadine@itec.rw | Warehouse Manager | Raises the request, checks stock and starts procurement. |
| Samuel Niyonzima | samuel@itec.rw | Driver | Receives the assigned trip and completes proof of delivery. |
| Emmanuel Safari | emmanuel@itec.rw | Finance | Reviews fuel, route cost and procurement spending. |
| Jean Pierre Habimana | jeanpierre@itec.rw | Management | Reviews the delivery performance report. |

## Seeded Scenario Records

| Area | Record | Meaning |
| --- | --- | --- |
| Warehouse | KFF-COOL-001 | Cold-chain delivery crates ready in Kigali. |
| Warehouse | KFF-FLOUR-001 | Fortified maize flour below minimum at Huye Depot. |
| Request | REQ-KFF-001 | Nadine requests urgent replenishment from Kigali to Huye. |
| Trip | TRP-KFF-001 | Aline assigns RAC 482D and Samuel to the Huye route. |
| Delivery | DEL-KFF-001 | Samuel delivers to Huye Depot with proof and signature paths. |
| Maintenance | MNT-KFF-001 | Eric confirms pre-trip cold-chain inspection is complete. |
| Fuel | FUE-KFF-001 | RAC 482D receives fuel before departure. |
| Expense | EXP-KFF-001 | Emmanuel approves the route cost. |
| Procurement | PR-KFF-001 | Nadine records received cold-chain seals and reusable crates. |
| Report | KFF Huye delivery performance | Management report for the route. |
| Notifications | NOTIF-KFF-* | One workflow update for every role. |
| Audit | KFF-2026-09-20 | Audit entry proving the scenario seed loaded. |

## End-To-End Process

1. Admin prepares access

   The Super Admin logs in as `admin@itec.rw`, opens Users, and confirms all seven seeded accounts are active. The admin has privilege `1`, so this account can switch roles and test the full company workflow.

2. Warehouse creates demand

   Nadine logs in as `nadine@itec.rw`. In Warehouse, she sees `KFF-COOL-001` available in Kigali and `KFF-FLOUR-001` below minimum at Huye. She creates the urgent transport request `REQ-KFF-001` and the procurement record `PR-KFF-001` for cold-chain seals and reusable crates.

3. Logistics approves and dispatches

   Aline logs in as `aline@itec.rw`. In Transport Requests, she reviews `REQ-KFF-001`, confirms the priority is urgent, and assigns the movement to trip `TRP-KFF-001`. The trip connects Kigali Central Warehouse, Huye Depot, vehicle `RAC 482D`, and driver Samuel.

4. Fleet prepares the vehicle

   Eric logs in as `eric@itec.rw`. In Vehicles, he checks `RAC 482D`. In Maintenance, he confirms `MNT-KFF-001` is completed. In Fuel, he checks `FUE-KFF-001`, the fuel record created before the route starts.

5. Driver completes delivery

   Samuel logs in as `samuel@itec.rw`. The driver account is linked to his driver profile through `drivers.user_id`. In Trips, he sees `TRP-KFF-001`. In Deliveries, he sees `DEL-KFF-001`, marked delivered with proof file `proofs/kff-huye-delivery.pdf` and signature file `signatures/kff-huye-recipient.png`.

6. Finance approves cost

   Emmanuel logs in as `emmanuel@itec.rw`. In Fuel, Expenses and Procurement, he reviews `FUE-KFF-001`, `EXP-KFF-001` and `PR-KFF-001`. The route cost is approved and linked back to the trip and vehicle.

7. Management reviews results

   Jean Pierre logs in as `jeanpierre@itec.rw`. In Reports, he reviews `KFF Huye delivery performance`, which represents the final management view for the scenario.

## What The Scenario Now Also Covers

The 2026-09-20 rebuild gave the scenario the parts a real movement has, so each role
sees more than a status column.

| Area | Record | What it shows |
| --- | --- | --- |
| Customer | Rwanda Education Board (`CUS-2026-0001`) | Who the Huye run is actually for, on 45-day terms with a credit limit. |
| Rate card | `RATE-2026-0001` | The agreed price for Kigali to Huye on a refrigerated truck. |
| Shipment | `SHP-2026-0001` | The cargo itself: 52 packages, 1,320 kg, held between 2 and 8 degrees. |
| Route stops | Four stops on `TRP-0248` | Pickup, the Muhanga weighbridge, Huye Depot and Huye District Hospital, each with a planned arrival. |
| Vehicle document | `DOC-2026-0002` | RAC 482D inspection expiring, so it appears on the fleet alert list. |
| Stock ledger | `MOV-2026-0009` to `MOV-2026-0012` | The crates and flour leaving Kigali, and why `KFF-FLOUR-001` fell below its minimum. |
| Maintenance parts | `MNT-KFF-001` | The cold-chain inspection and call-out that make up its actual cost. |
| Invoice | `INV-2026-0002` | The Huye run billed with VAT, which is what gives that trip a margin. |

Because of this, the scenario now exercises the guards too. Dispatching the Huye run
only works because RAC 482D has a cooling unit fitted and enough payload for 1,320 kg;
a vehicle without one is refused.

## How To Load It

Run the normal migration command. It loads the base seed and this company scenario seed:

```text
C:\xampp\php\php.exe scripts\migrate.php
```

The migration output should include:

```text
Loaded database/seed.sql
Loaded database/seed_company_scenario.sql
Loaded database/seed_extended.sql
Database setup complete.
```

Then open the system:

```text
http://localhost/logistics-mvc/
```

## How To Test It

Run the scenario test:

```text
C:\xampp\php\php.exe tests\company_scenario.php
```

The expected output is:

```text
Company scenario tests passed.
```

This test imports the schema, loads both seed files twice to prove they are idempotent, confirms the KFF records exist, checks every role receives its scenario notification, and verifies each allowed sidebar module can see the scenario data needed for that role.

Run the whole suite when you want to prove the rest of the system with it:

```text
C:
mpp\php\php.exe tests\workflow.php
C:
mpp\php\php.exe tests\security.php
C:
mpp\php\php.exe tests\http.php
```

## Production Notes

The scenario is good for demonstration, training and workflow testing. CSRF protection, login lockout, private file downloads and self-service password
changes are now built in. Before real production use, still change all seeded
passwords, configure HTTPS, use a restricted database user, configure backups, and
wire the password reset page to a real mail transport.
