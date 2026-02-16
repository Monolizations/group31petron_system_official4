# FUEL AND MERCHANDISE PRODUCT STORAGE AND MANAGEMENT ANALYSIS

## EXECUTIVE SUMMARY

The Petron POS system uses a **HYBRID APPROACH** for managing fuel and merchandise:

1. **Database Layer (Primary)**: Uses shared tables (`products`, `product_types`, `station_inventory`) with type differentiation via `product_types` table
2. **Code Layer (Secondary)**: Uses type-based filtering and separate business logic for fuel vs. merchandise operations
3. **Fuel Tracking (Separate)**: Has dedicated tables for fuel-specific operations (`fuel_pumps`, `fuel_daily_readings`, `fuel_reconciliation`, `fuel_deliveries`, `fuel_adjustments`, `fuel_types`)

---

## 1. TABLE STRUCTURES

### 1.1 PRODUCTS TABLE STRUCTURE

```sql
CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type_id` int(11) NOT NULL,              -- FUEL/MERCH/SERVICE DIFFERENTIATION
  `category_id` int(11) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key Distinction**: The `type_id` field links to `product_types` table to classify products

---

### 1.2 PRODUCT_TYPES TABLE

```sql
CREATE TABLE `product_types` (
  `id` int(11) NOT NULL,
  `name` enum('fuel','merch','service') NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Data**:
- ID 1: `fuel` - Fuel products
- ID 2: `merch` - Merchandise products  
- ID 3: `service` - Service products

---

### 1.3 PRODUCT_CATEGORIES TABLE

```sql
CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Fuel Categories** (1): Fuel Products
**Merchandise Categories** (Multiple): 
- Oils/Lubes/Grease (4)
- Car Accessories (5)
- Filters (6)
- Drinks/Food (7)
- Snacks (8)
- VIC Filters (9)

---

## 2. FUEL MANAGEMENT - SEPARATE DEDICATED TABLES

### 2.1 FUEL_TYPES TABLE

```sql
CREATE TABLE `fuel_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Data**:
- ID 1: Gasoline
- ID 2: Diesel
- ID 3: LPG
- ID 4: Premium
- ID 5: Unleaded

**Purpose**: Tracks different fuel product variants (separate from product_types table)

---

### 2.2 FUEL_PUMPS TABLE

```sql
CREATE TABLE `fuel_pumps` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `pump_number` varchar(20) NOT NULL,
  `fuel_type_id` int(11) NOT NULL,         -- LINKS TO FUEL_TYPES
  `capacity` decimal(10,2) DEFAULT 0.00,
  `status` enum('Active','Inactive','Maintenance') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose**: Physical pump configuration for fuel dispensing

---

### 2.3 FUEL_DAILY_READINGS TABLE

```sql
CREATE TABLE `fuel_daily_readings` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `pump_id` int(11) NOT NULL,              -- LINKS TO FUEL_PUMPS
  `reading_date` date NOT NULL,
  `shift` enum('Morning','Afternoon','Evening') NOT NULL,
  `previous_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `calibration` decimal(10,2) DEFAULT 0.00,
  `sales_liters` decimal(10,2) DEFAULT 0.00,
  `user_id` int(11) NOT NULL,
  `status` enum('Pending','Verified','finalized') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose**: Tracks daily fuel pump readings by shift for inventory reconciliation

---

### 2.4 FUEL_RECONCILIATION TABLE

```sql
CREATE TABLE `fuel_reconciliation` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `reconciliation_date` date NOT NULL,
  `fuel_type_id` int(11) NOT NULL,        -- LINKS TO FUEL_TYPES
  `pump_id` int(11) NOT NULL,              -- LINKS TO FUEL_PUMPS
  `previous_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `present_reading` decimal(10,2) NOT NULL DEFAULT 0.00,
  `calibration` decimal(10,2) DEFAULT 0.00,
  `price_per_liter` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sales_liters` decimal(10,2) GENERATED ALWAYS AS (...) STORED,
  `sales_amount` decimal(12,2) GENERATED ALWAYS AS (...) STORED,
  `physical_stock` decimal(10,2) DEFAULT NULL,
  `variance_liters` decimal(10,2) GENERATED ALWAYS AS (...) STORED,
  `variance_percent` decimal(5,2) GENERATED ALWAYS AS (...) STORED,
  `status` enum('Pending','Verified','finalized') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `variance` decimal(10,2) DEFAULT NULL,
  `finalized_by` int(11) DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `admin_notes` longtext DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose**: Comprehensive fuel reconciliation with variance tracking and calculated fields

---

### 2.5 FUEL_DELIVERIES TABLE

```sql
CREATE TABLE `fuel_deliveries` (
  `id` int(11) NOT NULL,
  `station_id` int(11) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `delivery_liters` decimal(10,2) DEFAULT NULL,
  `tanker_number` varchar(50) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose**: Tracks incoming fuel deliveries from suppliers

---

### 2.6 FUEL_ADJUSTMENTS TABLE

```sql
CREATE TABLE `fuel_adjustments` (
  `id` int(11) NOT NULL,
  `station_id` int(11) DEFAULT NULL,
  `adjustment_date` date DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `adjustment_type` varchar(50) DEFAULT NULL,  -- Add/Remove/Loss/Gain
  `liters` decimal(10,2) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose**: Adjustments to fuel inventory (evaporation, theft, calibration loss)

---

### 2.7 FUEL_PRICING TABLE

```sql
CREATE TABLE IF NOT EXISTS `fuel_pricing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `station_id` int(11) NOT NULL,
  `fuel_type_id` int(11) NOT NULL,        -- LINKS TO FUEL_TYPES
  `price_per_liter` decimal(10,2) NOT NULL DEFAULT 0.00,
  `effective_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_station_fuel` (`station_id`, `fuel_type_id`, `is_active`),
  FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`station_id`) REFERENCES `stations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose**: Dynamic fuel pricing per station per fuel type

---

## 3. INVENTORY MANAGEMENT - SHARED TABLES

### 3.1 STATION_INVENTORY TABLE

```sql
CREATE TABLE `station_inventory` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `stock_level` decimal(12,2) DEFAULT 0.00,
  `reorder_level` int(11) DEFAULT 0,
  `capacity` decimal(12,2) DEFAULT 10000.00,
  `unit` varchar(50) DEFAULT NULL,          -- 'liters' for fuel, 'pieces' for merch
  `status` enum('active','inactive') DEFAULT 'active',
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key Features**:
- **Unified inventory table** for both fuel and merchandise
- Differentiation via `product_id` -> `products.type_id`
- `unit` field determines measurement (liters vs pieces)
- Per-station tracking via `station_id`

---

### 3.2 INVENTORY_TRANSACTIONS TABLE

```sql
CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `transaction_type` enum('addition','deduction','adjustment','transfer') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,    -- 'job_order', 'sale', etc
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose**: Audit trail for all inventory changes (both fuel and merchandise)

---

## 4. SALES TRACKING - UNIFIED APPROACH

### 4.1 SALES TABLE

```sql
CREATE TABLE `sales` (
  `id` varchar(64) NOT NULL,
  `sale_date` date NOT NULL,
  `sale_time` time NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `station_id` int(11) DEFAULT NULL,
  `payment_method` varchar(32) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `amount_received` decimal(12,2) DEFAULT 0.00,
  `change_amount` decimal(12,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Completed',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose**: Header record for all sales (fuel + merchandise + services)

---

### 4.2 SALE_ITEMS TABLE

```sql
CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` varchar(64) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Purpose**: Line items for all sales - product type determined by joining to products.type_id

---

### 4.3 DAILY_RECONCILIATION TABLE

```sql
CREATE TABLE `daily_reconciliation` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `reconciliation_date` date NOT NULL,
  `shift_report_id` int(11) DEFAULT NULL,
  `total_sales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cash_sales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `card_sales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `credit_sales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_fuel_sales` decimal(12,2) NOT NULL DEFAULT 0.00,    -- FUEL SPECIFIC
  `total_merch_sales` decimal(12,2) NOT NULL DEFAULT 0.00,   -- MERCH SPECIFIC
  `total_service_sales` decimal(12,2) NOT NULL DEFAULT 0.00, -- SERVICE SPECIFIC
  `variance_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('Pending','Verified','finalized') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key Feature**: Separates sales by type (`total_fuel_sales`, `total_merch_sales`, `total_service_sales`)

---

## 5. HOW FUEL AND MERCHANDISE ARE DIFFERENTIATED

### 5.1 DATABASE LEVEL DIFFERENTIATION

**Method**: `product_types` Table with Type ID

```
products table:
├── type_id = 1 → product_types.name = 'fuel'
├── type_id = 2 → product_types.name = 'merch'
└── type_id = 3 → product_types.name = 'service'
```

**SQL Query Example**:
```sql
-- Get all fuel products
SELECT p.* FROM products p
INNER JOIN product_types pt ON p.type_id = pt.id
WHERE pt.name = 'fuel'

-- Get all merchandise products
SELECT p.* FROM products p
INNER JOIN product_types pt ON p.type_id = pt.id
WHERE pt.name = 'merch'
```

---

### 5.2 INVENTORY LEVEL DIFFERENTIATION

**Method**: Query Products then Join with Station Inventory

```sql
-- Fuel Inventory
SELECT si.*, p.*, pt.name as type_name
FROM station_inventory si
LEFT JOIN products p ON si.product_id = p.id
LEFT JOIN product_types pt ON p.type_id = pt.id
WHERE pt.name = 'fuel'
AND si.station_id = ?

-- Merchandise Inventory
SELECT si.*, p.*, pt.name as type_name, pc.name as category
FROM station_inventory si
LEFT JOIN products p ON si.product_id = p.id
LEFT JOIN product_types pt ON p.type_id = pt.id
LEFT JOIN product_categories pc ON p.category_id = pc.id
WHERE pt.name = 'merch'
AND si.station_id = ?
```

---

### 5.3 FUEL - SEPARATE TRACKING VIA FUEL_PUMPS

**Fuel has additional dedicated tracking**:

```
Fuel Pumps → Fuel Daily Readings → Fuel Reconciliation
    ↓               ↓                    ↓
pump_id       pump_id             pump_id
fuel_type_id  reading_date        fuel_type_id
status        shift               variance_liters
              sales_liters        physical_stock
```

**This is NOT linked to product_id in products table**
- Fuel pumps track physical dispensing
- Products table tracks price/cost
- Separate reconciliation process for fuel vs merchandise

---

## 6. CODE IMPLEMENTATION - BUSINESS LOGIC SEPARATION

### 6.1 BACKEND FILE: inventory.php

```php
$data = loadData($dataFile);
// Returns structure: ['fuel' => [], 'merchandise' => []]

// FUEL operations
case 'fuel_stock_in':
    foreach ($data['fuel'] as &$fuel) {
        if ($fuel['id'] === $id) {
            $fuel['level_l'] = ($fuel['level_l'] ?? 0) + $liters;
        }
    }

// MERCHANDISE operations
case 'merch_add':
    $data['merchandise'][] = $newItem;
```

---

### 6.2 BACKEND FILE: sales.php

```php
// BUILD LOOKUP BY TYPE
$lookup = [];
foreach(['fuel','merchandise','services'] as $k){
    foreach($products[$k] as $p){ 
        $lookup[$p['id']] = [$k, $p]; 
    }
}

// DIFFERENT VALIDATION BY TYPE
if($type === 'merchandise' && strpos($status, 'Completed') !== false){
    $stock = (int)($p['stock'] ?? 0);
    if($stock < $qty){
        json_response(['error'=>"Insufficient stock for {$p['name']}"], 400);
    }
}
if($type === 'fuel' && strpos($status, 'Completed') !== false){
    $lvl = (float)($p['level_l'] ?? 0);
    if($lvl < $qty){
        json_response(['error'=>"Insufficient fuel level for {$p['name']}"], 400);
    }
}

// DIFFERENT INVENTORY DEDUCTIONS
if($it['type'] === 'merchandise'){
    $products['merchandise'][$i]['stock'] = max(0, $cur - $it['qty']);
}
if($it['type'] === 'fuel'){
    $products['fuel'][$i]['level_l'] = max(0, $cur - $it['qty']);
}
```

---

### 6.3 BACKEND FILE: products_db.php

```php
// SEPARATE QUERIES BY TYPE

// Fuel: Uses stock_level and capacity from station_inventory
$fuelStmt = $pdo->query("
    SELECT p.id, p.sku, p.name, p.price,
           COALESCE(si.stock_level, 0) as level_l,
           COALESCE(si.capacity, 0) as capacity_l
    FROM products p
    LEFT JOIN station_inventory si ON p.id = si.product_id
    WHERE p.type_id = 1  -- FUEL
");

// Merchandise: Uses stock_level with category information
$merchStmt = $pdo->query("
    SELECT p.sku, p.name, p.price, p.cost,
           pc.name as category,
           COALESCE(si.stock_level, 0) as stock
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    LEFT JOIN station_inventory si ON p.id = si.product_id
    WHERE p.type_id = 2  -- MERCHANDISE
");
```

---

## 7. KEY DIFFERENCES SUMMARY

| Aspect | Fuel | Merchandise |
|--------|------|-------------|
| **Product Type** | type_id = 1 | type_id = 2 |
| **Inventory Unit** | Liters (stock_level) | Pieces (stock_level) |
| **Tracking Table** | station_inventory + fuel_pumps | station_inventory only |
| **Daily Readings** | fuel_daily_readings (per pump, per shift) | None (integrated into sales) |
| **Reconciliation** | fuel_reconciliation (complex with variance) | daily_reconciliation (simple) |
| **Pricing Model** | fuel_pricing table (per station, per fuel_type) | Fixed in products.price |
| **Deliveries** | fuel_deliveries table (tracked) | Received via purchase_orders + received_items |
| **Adjustments** | fuel_adjustments table (explicit) | inventory_transactions (implicit) |
| **Sales Recording** | Stored in sales (fuel line items) | Stored in sales (merch line items) |
| **Variance Tracking** | YES (fuel_reconciliation.variance_liters) | NO |
| **Physical Count** | YES (fuel_reconciliation.physical_stock) | Implicit in inventory |
| **Shift-based** | YES (morning/afternoon/evening) | NO |
| **Admin Approval** | YES (fuel_reconciliation.finalized_by) | Part of daily_reconciliation |

---

## 8. SEPARATION STATUS

### SHARED COMPONENTS
- **products table**: Stores definitions for both fuel and merchandise
- **product_categories table**: Categories for organization
- **station_inventory table**: Central inventory for both types
- **sales table**: Records all sales regardless of type
- **sale_items table**: All line items (fuel, merch, service)
- **daily_reconciliation table**: Summary but tracks separately

### SEPARATE COMPONENTS
- **fuel_types**: Fuel-specific variant types
- **fuel_pumps**: Physical dispensing infrastructure
- **fuel_daily_readings**: Per-shift pump readings
- **fuel_reconciliation**: Complex fuel-specific reconciliation
- **fuel_deliveries**: Fuel-specific receiving
- **fuel_adjustments**: Fuel-specific adjustments
- **fuel_pricing**: Fuel-specific dynamic pricing

### CODE SEPARATION
- **backend/inventory.php**: Separates fuel_stock_in vs merch_add operations
- **backend/sales.php**: Type-specific validation and deduction logic
- **backend/products_db.php**: Separate queries (type_id filtering)
- **public/inventory.php**: Displays fuel_inventory and merch_inventory separately
- **public/fuel_management.php**: Dedicated UI for fuel operations
- **public/merchandise_inventory.php**: Dedicated UI for merchandise

---

## 9. CONCLUSION

**The Petron POS system uses a HYBRID ARCHITECTURE**:

1. **Database**: Shared foundational structure (products, station_inventory, sales)
2. **Fuel Operations**: Dedicated tables for fuel-specific workflows (pumps, readings, reconciliation)
3. **Code Logic**: Type-based differentiation using product_types for business rules
4. **Reporting**: Separate views and calculations (fuel vs merchandise sales)

This design allows:
- ✓ Single product table managing all types
- ✓ Unified inventory tracking with type awareness
- ✓ Fuel-specific operational workflows
- ✓ Different validation and reconciliation rules per type
- ✓ Per-station, per-type dynamic pricing for fuel
- ✓ Detailed variance tracking for fuel only
- ✓ Shift-based fuel monitoring
- ✓ Simplified merchandise management

