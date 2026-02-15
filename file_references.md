# KEY CODE FILES HANDLING FUEL vs MERCHANDISE

## Database Schema Files

1. **petron_pos_db_main.sql**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/sql/petron_pos_db_main.sql`
   - Contains: All table definitions including products, product_types, fuel_types, fuel_pumps, fuel_daily_readings, fuel_reconciliation, inventory, sales

2. **fuel_pricing_table.sql**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/sql/fuel_pricing_table.sql`
   - Contains: Dynamic fuel pricing table with per-station, per-fuel-type pricing

3. **inventory_consolidation_migration.sql**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/sql/inventory_consolidation_migration.sql`
   - Contains: Migration script showing unified inventory approach

---

## Backend API Files (Type-Based Logic)

### Core Inventory Operations

1. **backend/inventory.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/backend/inventory.php`
   - Key Logic:
     - Loads JSON data as: `['fuel' => [], 'merchandise' => []]`
     - Case `fuel_stock_in`: Updates fuel level_l
     - Case `merch_add`: Adds to merchandise array
     - Case `merch_update`: Updates merchandise array
     - Case `merch_delete`: Removes from merchandise array

2. **backend/products.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/backend/products.php`
   - Key Logic:
     - Reads products.json with fuel/merchandise/services structure
     - Query parameter: `?type=fuel|merchandise|services`

3. **backend/products_db.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/backend/products_db.php`
   - Key Logic:
     - getProductsFromDB() function shows database differentiation
     - Fuel: `WHERE p.type_id = 1` (uses stock_level as level_l)
     - Merchandise: `WHERE p.type_id = 2` (uses stock_level with categories)
     - Service: `WHERE p.type_id = 3`

### Sales Operations

4. **backend/sales.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/backend/sales.php`
   - Key Logic:
     - Builds lookup: `['fuel','merchandise','services'] as $k`
     - Validates merchandise: checks `stock` field
     - Validates fuel: checks `level_l` field
     - Deducts merchandise: updates `stock`
     - Deducts fuel: updates `level_l`

5. **backend/api/sales.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/backend/api/sales.php`
   - Key Logic: (Same as backend/sales.php - database version)

---

## Public UI Files (User-Facing)

### Fuel Operations

1. **public/fuel_management.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/fuel_management.php`
   - Key Functions:
     - record_pump_reading: Inserts into fuel_daily_readings
     - record_delivery: Inserts into fuel_deliveries
     - record_adjustment: Inserts into fuel_adjustments
     - verify_reading: Updates fuel_daily_readings status
     - verify_delivery: Updates fuel_deliveries status

2. **public/fuel_types.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/fuel_types.php`
   - Key Logic: Manages fuel_types table (Gasoline, Diesel, LPG, etc.)

3. **public/fuel_monitoring.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/fuel_monitoring.php`
   - Key Logic: Real-time fuel inventory tracking

4. **public/fuel_reconciliation_finalize.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/fuel_reconciliation_finalize.php`
   - Key Logic: Finalizes fuel_reconciliation records

5. **public/fuel_reconciliation_validation.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/fuel_reconciliation_validation.php`
   - Key Logic: Validates fuel reconciliation data

6. **public/fuel_staff.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/fuel_staff.php`
   - Key Logic: Staff-level fuel operations UI

### Merchandise Operations

7. **public/merchandise_inventory.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/merchandise_inventory.php`
   - Key Logic:
     - Filters products where `type = 'merch'`
     - Groups by category
     - Displays stock (pieces)

### Unified Inventory

8. **public/inventory.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/inventory.php`
   - Key Logic:
     - Displays fuel_inventory array separately from merch_inventory
     - Uses product_types filtering:
       ```
       WHERE pt.name = 'fuel' (for fuel_inventory)
       WHERE pt.name = 'merch' (for merch_inventory)
       ```
     - Different unit displays: liters vs pieces
     - Different management operations per type

9. **public/inventory_list.php**
   - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/inventory_list.php`
   - Key Logic: Lists all inventory items

### Point of Sale

10. **public/pos.php**
    - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/pos.php`
    - Key Logic:
      - Joins products with product_types
      - Filters: WHERE pt.name IN ('fuel', 'merch')
      - Different unit retrieval per type

11. **public/pos_multi.php**
    - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/pos_multi.php`
    - Key Logic: (Same as pos.php but multi-station)

### Reports

12. **public/sales_reports.php**
    - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/sales_reports.php`
    - Key Logic: Separates total_fuel_sales and total_merch_sales

13. **public/sales_reports_export.php**
    - Path: `/opt/lampp/htdocs/group31petron_system_official4/public/sales_reports_export.php`
    - Key Logic: Exports fuel vs merchandise sales separately

---

## Database Utilities & Testing

14. **app/master_data/products/inventory.php**
    - Path: `/opt/lampp/htdocs/group31petron_system_official4/app/master_data/products/inventory.php`
    - Key Logic:
      - Fuel inventory management via database
      - Merchandise inventory management via database
      - Type-based RBAC (Role-Based Access Control)

15. **app/master_data/products/merchandise_inventory.php**
    - Path: `/opt/lampp/htdocs/group31petron_system_official4/app/master_data/products/merchandise_inventory.php`
    - Key Logic: Dedicated merchandise inventory interface

16. **backend/api/fuel_types.php**
    - Path: `/opt/lampp/htdocs/group31petron_system_official4/backend/api/fuel_types.php`
    - Key Logic:
      - CRUD operations for fuel_types table
      - Query all: SELECT * FROM fuel_types
      - Filter by ID, insert, update, delete

---

## Business Logic Examples

### Type-Based Queries - Location: public/inventory.php (lines 273-326)

```php
// FUEL INVENTORY
$stmt = $pdo->prepare("
    SELECT si.*, p.name, p.sku, pt.name as type_name
    FROM station_inventory si
    LEFT JOIN products p ON si.product_id = p.id
    LEFT JOIN product_types pt ON p.type_id = pt.id
    WHERE pt.name = 'fuel'
    AND si.station_id = ?
");
$fuel_inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// MERCHANDISE INVENTORY
$stmt = $pdo->prepare("
    SELECT si.*, p.*, pt.name as type_name, pc.name as category
    FROM station_inventory si
    LEFT JOIN products p ON si.product_id = p.id
    LEFT JOIN product_types pt ON p.type_id = pt.id
    LEFT JOIN product_categories pc ON p.category_id = pc.id
    WHERE pt.name = 'merch'
    AND si.station_id = ?
");
$merch_inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### Fuel vs Merchandise Sales Validation - Location: backend/sales.php (lines 66-78)

```php
// MERCHANDISE VALIDATION (stock in pieces)
if($type === 'merchandise' && strpos($status, 'Completed') !== false){
    $stock = (int)($p['stock'] ?? 0);
    if($stock < $qty){
        json_response(['ok'=>false,'error'=>"Insufficient stock"], 400);
    }
}

// FUEL VALIDATION (level in liters)
if($type === 'fuel' && strpos($status, 'Completed') !== false){
    $lvl = (float)($p['level_l'] ?? 0);
    if($lvl < $qty){
        json_response(['ok'=>false,'error'=>"Insufficient fuel level"], 400);
    }
}
```

### Inventory Deduction - Location: backend/sales.php (lines 104-124)

```php
// MERCHANDISE DEDUCTION (decrease stock)
if($it['type'] === 'merchandise'){
    for($i=0;$i<count($products['merchandise']);$i++){
        if($products['merchandise'][$i]['id'] === $it['id']){
            $cur = (int)($products['merchandise'][$i]['stock'] ?? 0);
            $products['merchandise'][$i]['stock'] = max(0, $cur - $it['qty']);
            break;
        }
    }
}

// FUEL DEDUCTION (decrease level_l)
if($it['type'] === 'fuel'){
    for($i=0;$i<count($products['fuel']);$i++){
        if($products['fuel'][$i]['id'] === $it['id']){
            $cur = (float)($products['fuel'][$i]['level_l'] ?? 0);
            $products['fuel'][$i]['level_l'] = max(0, $cur - $it['qty']);
            break;
        }
    }
}
```

---

## Summary Table

| Component | File | Key Tables |
|-----------|------|-----------|
| **Database Definition** | sql/petron_pos_db_main.sql | All tables |
| **Product Type Filtering** | backend/products_db.php | products, product_types |
| **Fuel Operations** | public/fuel_management.php | fuel_types, fuel_pumps, fuel_daily_readings, fuel_reconciliation, fuel_deliveries, fuel_adjustments |
| **Merchandise Inventory** | public/merchandise_inventory.php | products, station_inventory, product_categories |
| **Unified Inventory UI** | public/inventory.php | station_inventory, products, product_types |
| **Sales Processing** | backend/sales.php | sales, sale_items, products |
| **Fuel Pricing** | sql/fuel_pricing_table.sql | fuel_pricing, fuel_types |
| **Daily Reconciliation** | (multiple files) | daily_reconciliation (separates fuel/merch sales) |

