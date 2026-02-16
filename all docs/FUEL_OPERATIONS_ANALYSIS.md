# Fuel Operations: Station vs Pump Infrastructure Analysis

## 1. WHAT IS A "STATION"? (stations table)

A **"Station"** in this system represents a **physical Petron gas station location**.

### Database Definition:
```sql
CREATE TABLE `stations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
)
```

### Key Characteristics:
- Each station is a **complete service location** (e.g., "1 UNANG HAKBANG ST. COR. BAYANI ST., SAN ISIDRO, QUEZON CITY")
- Has a **geographic location** (city, address, area)
- Can be **active or inactive**
- The system has **266 distinct stations** across the Philippines
- All fuel operations are **scoped to a station_id**

### Real Example:
- Station ID 226: "1 UNANG HAKBANG ST. COR. BAYANI ST., SAN ISIDRO, QUEZON CITY, NCR NCR QUEZON CITY SERVICE STATION"
- This is ONE physical location with multiple pumps

---

## 2. WHAT IS A "PUMP"? (fuel_pumps table)

A **"Pump"** (also called a fuel dispenser) is an **individual fuel dispenser/nozzle within a station**.

### Database Definition:
```sql
CREATE TABLE `fuel_pumps` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `pump_number` varchar(20) NOT NULL,
  `fuel_type_id` int(11) NOT NULL,
  `capacity` decimal(10,2) DEFAULT 0.00,
  `status` enum('Active','Inactive','Maintenance') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp()
)
```

### Key Characteristics:
- **Foreign Key**: station_id (each pump belongs to ONE station)
- Has a **pump_number** (e.g., "Pump 1", "Pump 2", "Pump 3")
- Each pump dispenses a **specific fuel_type_id** (Gasoline, Diesel, LPG, Premium, Unleaded)
- Has a **capacity** (storage capacity of that pump)
- Can be Active, Inactive, or in Maintenance
- **Multiple pumps per station**: Station 226 has 3 pumps:
  - Pump 1: Diesel (fuel_type_id: 2)
  - Pump 2: Gasoline (fuel_type_id: 1)
  - Pump 3: LPG (fuel_type_id: 3)

### Real Example:
```
Station 226 (location: San Isidro, QC)
├── Pump 1 (Diesel)
├── Pump 2 (Gasoline)
└── Pump 3 (LPG)
```

---

## 3. RELATIONSHIP: STATION → PUMP → READINGS

### The Hierarchy:

```
STATIONS (Physical Locations)
  ├─ FUEL_PUMPS (Individual Dispensers)
  │   ├─ pump_number
  │   ├─ fuel_type_id
  │   └─ status
  │
  └─ FUEL_DAILY_READINGS (Meter Readings)
      ├─ pump_id (points to fuel_pumps)
      ├─ reading_date
      ├─ shift (Morning/Afternoon/Evening)
      ├─ previous_reading (L)
      ├─ current_reading (L)
      └─ sales_liters (calculated)
```

### Foreign Key Relationships:

```sql
-- fuel_pumps.station_id → stations.id
ALTER TABLE fuel_pumps ADD CONSTRAINT 
  FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE;

-- fuel_daily_readings.pump_id → fuel_pumps.id
ALTER TABLE fuel_daily_readings ADD CONSTRAINT 
  FOREIGN KEY (pump_id) REFERENCES fuel_pumps(id);
```

---

## 4. FUEL TRACKING: STATION-LEVEL vs PUMP-LEVEL

### **PUMP-LEVEL READINGS** (fuel_daily_readings)

Fuel is tracked at the **PUMP level** with per-shift readings:

```sql
CREATE TABLE `fuel_daily_readings` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `pump_id` int(11) NOT NULL,  -- PUMP-SPECIFIC
  `reading_date` date NOT NULL,
  `shift` enum('Morning','Afternoon','Evening') NOT NULL,
  `previous_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `calibration` decimal(10,2) DEFAULT 0.00,
  `sales_liters` decimal(10,2) DEFAULT 0.00,
  `user_id` int(11) NOT NULL,
  `status` enum('Pending','Verified','finalized') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  UNIQUE KEY unique_reading (station_id, pump_id, reading_date, shift)
)
```

**Key Point**: Each pump has **3 readings per day** (Morning, Afternoon, Evening shifts)

### Real Example from Database:
```
Station 226, Pump 4 (Diesel):
  - 2026-02-10, Morning:   20.00L → 30.00L = 10.00L sales
  - 2026-02-10, Afternoon: [next staff will record]
  - 2026-02-10, Evening:   [next staff will record]

Station 226, Pump 5 (Gasoline):
  - 2026-02-10, Morning:   [no reading yet]
  - 2026-02-10, Afternoon: 10.00L → 0.00L = -12.00L sales
  - 2026-02-10, Evening:   [no reading yet]
```

### STATION-LEVEL AGGREGATIONS (daily_reconciliation, fuel_reconciliation)

While readings are pump-level, **reconciliation aggregates to station level**:

```sql
CREATE TABLE `fuel_reconciliation` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,  -- STATION-LEVEL
  `reconciliation_date` date NOT NULL,
  `fuel_type_id` int(11) NOT NULL,
  `pump_id` int(11) NOT NULL,  -- But STILL tracks which pump
  `previous_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `present_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `calibration` decimal(10,2) DEFAULT 0.00,
  `sales_liters` decimal(10,2) GENERATED ALWAYS AS (...) STORED,
  `physical_stock` decimal(10,2) DEFAULT NULL,
  `variance_liters` decimal(10,2) GENERATED ALWAYS AS (...) STORED,
  `status` enum('Pending','Verified','finalized') DEFAULT 'Pending',
  ...
)
```

**Key Point**: Reconciliation is **per pump per day per fuel type**, then can be aggregated to station level.

---

## 5. FUEL PRICING

### Price is Set Per-STATION:

```sql
CREATE TABLE `fuel_pricing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,  -- STATION-LEVEL PRICING
  `fuel_type_id` int(11) NOT NULL COMMENT 'Reference to fuel_types table',
  `price_per_liter` decimal(10,2) NOT NULL DEFAULT 0.00,
  `effective_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`station_id`) REFERENCES `stations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types`(`id`) ON DELETE CASCADE
)
```

**Key Point**: Different stations can have **different prices** for the same fuel type.

---

## 6. DATA FLOW: HOW FUEL IS MANAGED THROUGH PUMP/STATION HIERARCHY

### Complete Workflow:

```
1. STAFF RECORDS PUMP READING
   │
   ├─ Reads: Station 226, Pump 4 (Diesel pump)
   ├─ Input: previous_reading=20L, current_reading=30L
   ├─ Calculated: sales_liters = 30 - 20 - calibration = 10L
   └─ Stored in: fuel_daily_readings (pump_id=4, station_id=226)
      Status: "pending"

2. MANAGER VALIDATES
   │
   ├─ Review the pump reading
   ├─ May adjust calibration
   └─ Approve or Reject
      Status: "approved" or "rejected"

3. RECONCILIATION CREATED
   │
   ├─ Create reconciliation record from fuel_daily_readings
   ├─ Link to: fuel_reconciliation (pump_id=4, fuel_type_id=2, station_id=226)
   ├─ Add: price_per_liter (from fuel_pricing table for station 226, diesel)
   ├─ Calculate: sales_amount = 10L * 65.50 = 655.00
   └─ Status: "Verified" (by manager)

4. ADMIN FINALIZES
   │
   ├─ Verify manager approval
   ├─ Enter physical stock count
   ├─ Calculate variance:
   │    variance = system_stock - physical_stock
   │    variance = 10L - 8L = 2L (loss/theft)
   │
   └─ Status: "finalized" (locked with password)
```

---

## 7. CODE EXAMPLES

### Example 1: Recording a Pump Reading (Staff Role)

From `/public/fuel_staff.php`:

```php
// STAFF RECORDS PUMP READING
if ($action === 'record_pump_reading') {
    $pump_id = $_POST['pump_id'];          // Which pump? (e.g., 4)
    $reading_date = $_POST['reading_date']; // Date
    $shift = $_POST['shift'];               // "Morning", "Afternoon", "Evening"
    $previous_reading = (float)$_POST['previous_reading'];
    $current_reading = (float)$_POST['current_reading'];
    $calibration = (float)($_POST['calibration'] ?? 0);
    
    // Calculate sales liters
    $sales_liters = $current_reading - $previous_reading - $calibration;
    
    // Insert pump reading
    $stmt = $pdo->prepare("
        INSERT INTO fuel_daily_readings 
        (station_id, pump_id, reading_date, shift, 
         previous_reading, current_reading, calibration, 
         sales_liters, user_id, notes, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $station_id,    // From user's assigned station
        $pump_id,       // Specific pump (e.g., 4)
        $reading_date,
        $shift,
        $previous_reading,
        $current_reading,
        $calibration,
        $sales_liters,
        $me['id'],      // User who recorded it
        $notes
    ]);
}
```

### Example 2: Getting Pumps for a Station (Manager Role)

From `/public/fuel_monitoring.php`:

```php
// Get all pumps at a station
$stmt = $pdo->prepare("
    SELECT id, fuel_type, pump_number, status 
    FROM fuel_pumps 
    WHERE station_id = ? 
    ORDER BY pump_number
");
$stmt->execute([$station_id]);
$fuel_stations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Result: Array of pumps at this station
// [
//   ['id' => 4, 'pump_number' => 'Pump 1', 'fuel_type' => 'Diesel', 'status' => 'Active'],
//   ['id' => 5, 'pump_number' => 'Pump 2', 'fuel_type' => 'Gasoline', 'status' => 'Active'],
//   ['id' => 6, 'pump_number' => 'Pump 3', 'fuel_type' => 'LPG', 'status' => 'Active']
// ]
```

### Example 3: Creating Reconciliation (Manager Role)

From `/public/fuel_reconciliation_validation.php`:

```php
// Manager approves a pump reading and moves it to reconciliation
if ($approved) {
    // Get the staff's pump reading
    $stmt = $pdo->prepare("
        SELECT fdr.*, fp.fuel_type_id 
        FROM fuel_daily_readings fdr 
        LEFT JOIN fuel_pumps fp ON fdr.pump_id = fp.id 
        WHERE fdr.id=?
    ");
    $stmt->execute([$reading_id]);
    $reading = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create reconciliation record
    $insert_stmt = $pdo->prepare("
        INSERT INTO fuel_reconciliation 
        (station_id, fuel_type_id, pump_id, reconciliation_date, 
         previous_reading, present_reading, calibration, sales_liters, 
         price_per_liter, status, notes, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Verified', ?, NOW())
    ");
    $insert_stmt->execute([
        $reading['station_id'],      // Station: 226
        $reading['fuel_type_id'],    // Fuel type: 2 (Diesel)
        $reading['pump_id'],         // Pump: 4
        $reading['reading_date'],    // Date: 2026-02-10
        $reading['previous_reading'],// 20.00
        $reading['current_reading'], // 30.00
        $reading['calibration'],     // 0.00
        $reading['sales_liters'],    // 10.00
        65.50,                       // Price per liter
        $manager_notes
    ]);
}
```

### Example 4: Fetching Daily Readings with Pump Info (Manager Role)

From `/public/fuel_monitoring.php`:

```php
// Get daily readings with pump and fuel type info
$sql = "
    SELECT 
        fdr.id, fdr.reading_date, fdr.shift, 
        fdr.previous_reading, fdr.current_reading, fdr.sales_liters,
        fdr.status, fdr.notes, fdr.created_at,
        fs.fuel_type, fs.pump_number,
        u.username as recorded_by
    FROM fuel_daily_readings fdr
    JOIN fuel_pumps fs ON fdr.pump_id = fs.id
    LEFT JOIN users u ON fdr.user_id = u.id
    WHERE fdr.station_id = ?
    ORDER BY fdr.reading_date DESC, fdr.shift DESC, fs.pump_number
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$station_id]);
$daily_readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Result: Readings grouped by pump and shift
// [
//   ['pump_number' => 'Pump 1', 'fuel_type' => 'Diesel', 'shift' => 'Morning', 
//    'previous_reading' => 20.00, 'current_reading' => 30.00, 'sales_liters' => 10.00],
//   ...
// ]
```

---

## 8. RELATIONSHIP DIAGRAM

```
┌─────────────────────────────────────────────────────────────────┐
│                          STATIONS (266 total)                   │
│ id | name | location | status                                  │
└─────────────────────────────────────────────────────────────────┘
                              │ 1:N
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        FUEL_PUMPS                               │
│ id | station_id | pump_number | fuel_type_id | capacity | status
└─────────────────────────────────────────────────────────────────┘
                              │ 1:N
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    FUEL_DAILY_READINGS                          │
│ id | station_id | pump_id | reading_date | shift |             │
│ previous_reading | current_reading | sales_liters | status      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ (referenced in)
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    FUEL_RECONCILIATION                          │
│ id | station_id | fuel_type_id | pump_id |                     │
│ previous_reading | present_reading | physical_stock | variance  │
└─────────────────────────────────────────────────────────────────┘
                              │
                       (uses price from)
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     FUEL_PRICING                                │
│ id | station_id | fuel_type_id | price_per_liter | status      │
└─────────────────────────────────────────────────────────────────┘
```

---

## 9. KEY FINDINGS

### Summary:

| Aspect | Answer |
|--------|--------|
| **What is a Station?** | A physical gas station location with geographic address |
| **What is a Pump?** | An individual fuel dispenser within a station |
| **Pump-to-Station Relationship** | Many pumps per station (1:N) via `pump.station_id` |
| **Fuel Tracking Level** | **PUMP-LEVEL** with per-shift readings |
| **Pricing Model** | Per-station (can vary by location) |
| **Readings Per Pump Per Day** | 3 (Morning, Afternoon, Evening) |
| **Reconciliation Level** | Per pump per day per fuel type |
| **Data Flow** | Staff reads pump → Manager validates → Reconciliation created → Admin finalizes |
| **Variance Calculation** | Per pump: system_stock - physical_stock |

### Architecture Pattern:

```
OPERATIONAL LEVEL (Pumps)     ← Where data is collected
      ▲                          (Readings per pump per shift)
      │
      │ AGGREGATION
      │
      ▼
MANAGEMENT LEVEL (Station)    ← Where decisions are made
                                (Variance analysis, approvals)
```

---

## 10. CODE FILES IMPLEMENTING THIS HIERARCHY

1. **Staff (Recording)**: `/public/fuel_staff.php`
   - Records pump readings at pump level
   - Inputs to: fuel_daily_readings (pump_id, station_id, shift)

2. **Manager (Validation)**: `/public/fuel_reconciliation_validation.php`
   - Reviews pump readings
   - Creates fuel_reconciliation records per pump

3. **Admin (Finalization)**: `/public/fuel_reconciliation_finalize.php`
   - Finalizes reconciliation with physical stock
   - Calculates variance
   - Locks record with password

4. **Monitoring**: `/public/fuel_monitoring.php`
   - Views readings grouped by pump
   - Shift comparisons
   - Calibration logs

5. **Pricing Setup**: `/setup_fuel_pricing.php`
   - Sets prices per station per fuel type
