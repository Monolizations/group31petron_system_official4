# FUEL OPERATIONS HIERARCHY - QUICK REFERENCE

## VISUAL HIERARCHY

```
COMPANY LEVEL
    │
    ├─── 266 STATIONS (Physical Locations)
    │
    │       Station 1 (CDO)              Station 226 (QC-San Isidro)    Station 250 (Rizal)
    │       ├─ Pump 1 (Gasoline)         ├─ Pump 4 (Diesel)           ├─ Pump 1
    │       ├─ Pump 2 (Gasoline)         ├─ Pump 5 (Gasoline)         ├─ Pump 2
    │       └─ Pump 3 (Diesel)           └─ Pump 6 (LPG)              └─ Pump 3
    │
    │       Every Station has 3+ Pumps (typically)
    │
    ├─── DAILY OPERATIONS (Per Pump, 3 Times Per Day)
    │
    │       Morning Reading:     Pump 4: 20.00L → 30.00L = 10.00L sales
    │       Afternoon Reading:   Pump 5: 10.00L → 5.00L = -5.00L variance (?)
    │       Evening Reading:     Pump 6: [not recorded yet]
    │
    └─── RECONCILIATION (Moves to Station Level)
            Daily total for Station 226
            Aggregate all pumps
            Calculate variance
            Admin finalizes
```

---

## KEY CONCEPTS

### STATION
- **What**: Physical gas station location with a street address
- **Example**: "1 UNANG HAKBANG ST. COR. BAYANI ST., SAN ISIDRO, QUEZON CITY"
- **Database**: `stations.id` (1-266)
- **Scope**: All operations at this location

### PUMP (Fuel Dispenser)
- **What**: Individual pump/nozzle at the station
- **Example**: "Pump 1", "Pump 2", "Pump 3"
- **Database**: `fuel_pumps.id` (identifies by station_id + pump_number)
- **Fuel Type**: Each pump dispenses specific fuel (Gasoline, Diesel, LPG, etc.)
- **Reads**: 3 readings per day (Morning, Afternoon, Evening)

### READING (Daily Meter Reading)
- **What**: Fuel meter reading at a specific time
- **Example**: Previous: 20L, Current: 30L, Sales: 10L
- **Timing**: Once per shift per pump
- **Database**: `fuel_daily_readings.id`
- **Status Flow**: pending → approved → reconciled → finalized

---

## OPERATIONAL WORKFLOW

### 1. STAFF RECORDS (At Pump Level)
```
Staff at Station 226
├─ Reads Pump 4 (Diesel) Morning Meter
│  └─ Input: Previous 20L, Current 30L
│     Calculates: Sales = 30 - 20 - calibration = 10L
│     Stores: fuel_daily_readings (pump_id=4, status='pending')
│
├─ Reads Pump 5 (Gasoline) Morning Meter
│  └─ Input: Previous 10L, Current 5L
│     Calculates: Sales = 5 - 10 = -5L (loss/variance)
│     Stores: fuel_daily_readings (pump_id=5, status='pending')
│
└─ [Repeats in Afternoon and Evening]
```

### 2. MANAGER VALIDATES (Still Per Pump)
```
Manager reviews pending readings from Staff
├─ Reviews Pump 4 morning reading (10L)
│  ├─ Check: Is this reasonable?
│  ├─ Adjust: May tweak calibration
│  └─ Approve: Mark as 'approved'
│
├─ Reviews Pump 5 morning reading (-5L)
│  ├─ Flag: Negative sales? Possible theft?
│  └─ Reject or comment
│
└─ [Repeats for all pending readings]
```

### 3. ADMIN FINALIZES (Aggregates to Station Level)
```
Admin reconciles all pumps for Station 226
├─ Review: All approved pump readings
├─ Physical Count: "I counted 18L in tank today"
├─ Calculate Variance:
│  ├─ System says: Pump 4 (10L) + Pump 5 (-5L) + Pump 6 (0L) = 5L sold
│  ├─ Physical was: 18L
│  └─ Variance: 5L - 18L = -13L (loss of 13L!)
│
├─ Lock: Password-protect to prevent changes
└─ Archive: Move to finalized status
```

---

## DATABASE RELATIONSHIP MAP

```
┌─────────────────────────────┐
│  stations (266 records)     │
│  id=226, name='QC-SI'       │
└─────────────────────────────┘
            │ 1:N (Many pumps per station)
            ▼
┌─────────────────────────────┐
│  fuel_pumps (1500+ records) │
│  id=4, station_id=226       │
│  pump_number='Pump 1'       │
│  fuel_type_id=2 (Diesel)    │
└─────────────────────────────┘
            │ 1:N (Many readings per pump)
            ▼
┌──────────────────────────────┐
│ fuel_daily_readings          │
│ id=273                       │
│ pump_id=4                    │
│ reading_date='2026-02-10'    │
│ shift='Morning'              │
│ previous_reading=20.00       │
│ current_reading=30.00        │
│ sales_liters=10.00           │
│ status='approved'            │
└──────────────────────────────┘
            │
            │ (creates)
            ▼
┌──────────────────────────────┐
│ fuel_reconciliation          │
│ id=2                         │
│ station_id=226               │
│ pump_id=4                    │
│ reconciliation_date='2026-02-10'
│ present_reading=30.00        │
│ previous_reading=20.00       │
│ sales_liters=10.00           │
│ physical_stock=8.00          │
│ variance=2.00 (loss)         │
│ status='finalized'           │
└──────────────────────────────┘
            │ (uses price from)
            ▼
┌──────────────────────────────┐
│ fuel_pricing                 │
│ station_id=226               │
│ fuel_type_id=2 (Diesel)      │
│ price_per_liter=65.50        │
│ is_active=1                  │
└──────────────────────────────┘
```

---

## DATA FLOW SUMMARY

| Stage | Role | Unit | Input | Output | Status |
|-------|------|------|-------|--------|--------|
| **Record** | Staff | Pump | Meter readings per shift | fuel_daily_readings | pending |
| **Validate** | Manager | Pump | Review readings | Approve/Reject | approved/rejected |
| **Reconcile** | Admin | Station | Physical stock count | fuel_reconciliation | finalized |
| **Report** | Manager | Station | Locked records | Variance analysis | locked |

---

## REAL DATABASE EXAMPLE

### Station 226: "1 UNANG HAKBANG ST. COR. BAYANI ST., SAN ISIDRO, QUEZON CITY"

#### Pumps at this station:
- Pump 4: Diesel (fuel_type_id=2)
- Pump 5: Gasoline (fuel_type_id=1)
- Pump 6: LPG (fuel_type_id=3)

#### Fuel Pricing at this station:
- Diesel: 65.50 per liter
- Gasoline: 65.50 per liter
- LPG: 65.50 per liter

#### Daily Readings (2026-02-10):

```
Morning Shift:
  Pump 4 (Diesel):   Previous=20L, Current=30L, Sales=10L ✓
  Pump 5 (Gasoline): Previous=10L, Current=0L, Sales=-12L ⚠️
  Pump 6 (LPG):      [Not recorded yet]

Reconciliation:
  Total System Sales: ~2L (after adjustments)
  Physical Stock: 10L
  Variance: -8L (loss of 8 liters!)
  Variance Value: -8L × 65.50 = -524.00 (PHP loss)
```

---

## WHO DOES WHAT?

### STAFF (fuel_staff.php)
- Records pump meter readings 3x per day
- Records fuel deliveries
- Records adjustments
- **Cannot**: Approve or finalize

### MANAGER (fuel_reconciliation_validation.php)
- Reviews staff pump readings
- Approves or rejects readings
- Creates reconciliation records
- **Cannot**: Finalize (needs admin)

### ADMIN (fuel_reconciliation_finalize.php)
- Reviews manager-approved records
- Enters physical stock count
- Calculates variance
- Finalizes with password lock
- Generates reports

---

## KEY METRICS & CALCULATIONS

### Per Pump, Per Shift:
```
Sales Liters = Current Reading - Previous Reading - Calibration Adjustment
Sales Amount = Sales Liters × Price Per Liter
```

### Per Station, Per Day:
```
Total System Sales = SUM(all pump sales for the day)
Total Physical Stock = Physical count from receiving
Variance = Total System Sales - Total Physical Stock

Variance % = (Variance / Total System Sales) × 100
Variance Value = Variance × Average Price Per Liter
```

---

## UNIQUE CONSTRAINTS

Prevents duplicate readings:
```sql
UNIQUE KEY unique_reading (station_id, pump_id, reading_date, shift)
```
**Meaning**: Only ONE reading per pump per day per shift

---

## FILE LOCATIONS

| Function | File |
|----------|------|
| Staff Records Readings | `/public/fuel_staff.php` |
| Manager Validates | `/public/fuel_reconciliation_validation.php` |
| Admin Finalizes | `/public/fuel_reconciliation_finalize.php` |
| Monitor Progress | `/public/fuel_monitoring.php` |
| Set Pricing | `/setup_fuel_pricing.php` |

---

## CHECKLIST FOR UNDERSTANDING

- [ ] Station = Physical location (266 total)
- [ ] Pump = Individual dispenser (3+ per station)
- [ ] Readings = Per pump, per shift (Morning/Afternoon/Evening)
- [ ] Staff records at PUMP level
- [ ] Manager validates PUMP readings
- [ ] Admin reconciles at STATION level
- [ ] Variance = System Sales - Physical Stock
- [ ] Pricing = Per station per fuel type
- [ ] Unique readings = No duplicates allowed per pump/day/shift
