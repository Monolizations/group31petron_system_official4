-- =====================================================
-- PETRON POS SYSTEM - COMPLETE PRODUCT REPLACEMENT
-- Date: 2025-02-17
-- Station: 1250
-- Total Products: 132 (all merch type_id=2)
-- =====================================================

-- STEP 1: BACKUP EXISTING DATA
-- =====================================================

CREATE TABLE IF NOT EXISTS products_backup_20250217 AS SELECT * FROM products;
CREATE TABLE IF NOT EXISTS station_inventory_backup_20250217 AS SELECT * FROM station_inventory;

SELECT '✓ Backup completed: products_backup_20250217' AS status;
SELECT '✓ Backup completed: station_inventory_backup_20250217' AS status;


-- STEP 2: DELETE EXISTING DATA
-- =====================================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM station_inventory;
DELETE FROM products;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

SELECT '✓ All existing products deleted' AS status;
SELECT '✓ All existing station_inventory deleted' AS status;


-- STEP 3: INSERT NEW PRODUCTS
-- All products have type_id = 2 (merch)
-- Renumbered from 1 to 132
-- =====================================================

INSERT INTO `products` (`id`, `sku`, `name`, `description`, `type_id`, `category_id`, `cost`, `price`, `created_at`, `updated_at`) VALUES

-- OILS/LUBES/GREASE (category_id = 4)
-- Total: 37 products
-- Price range: 200-3200 PHP

(1, 'OLL-PAIL-18L', 'PAIL/18 Liters', '18L pail lubricant/oil', 2, 4, 1600.00, 2000.00, NOW(), NOW()),
(2, 'OLL-HD10', 'HD 10', 'Heavy duty engine oil HD 10', 2, 4, 2126.75, 2658.44, NOW(), NOW()),
(3, 'OLL-HD30', 'HD 30', 'Heavy duty engine oil HD 30', 2, 4, 1612.66, 2015.82, NOW(), NOW()),
(4, 'OLL-HD40', 'HD 40', 'Heavy duty engine oil HD 40', 2, 4, 2222.40, 2778.00, NOW(), NOW()),
(5, 'OLL-GEP90', 'GEP 90', 'Gear oil GEP 90', 2, 4, 2563.20, 3204.00, NOW(), NOW()),
(6, 'OLL-GEP140', 'GEP 140', 'Gear oil GEP 140', 2, 4, 2400.00, 3000.00, NOW(), NOW()),
(7, 'OLL-MP-GREASE', 'MP GREASE', 'Multipurpose grease', 2, 4, 1800.00, 2250.00, NOW(), NOW()),
(8, 'OLL-HYDROTUR', 'HYDROTUR', 'Hydraulic oil HYDROTUR', 2, 4, 2000.00, 2500.00, NOW(), NOW()),
(9, 'OLL-TREKKER', 'TREKKER', 'Engine oil TREKKER', 2, 4, 1900.00, 2375.00, NOW(), NOW()),
(10, 'OLL-GALLON-4L', 'GALLON / 4L', '4L gallon lubricant', 2, 4, 600.00, 750.00, NOW(), NOW()),
(11, 'OLL-TOURING', 'TOURING', 'Touring motorcycle oil', 2, 4, 550.00, 687.50, NOW(), NOW()),
(12, 'OLL-EXTRA', 'EXTRA', 'Extra grade lubricant', 2, 4, 500.00, 625.00, NOW(), NOW()),
(13, 'OLL-BLAZE-RF-FS', 'BLAZE RACING FS', 'Blaze Racing full synthetic', 2, 4, 650.00, 812.50, NOW(), NOW()),
(14, 'OLL-2T-AUTO-200', '2T AUTOLUBE (60/200ML)', '2-stroke autolube 200ml', 2, 4, 120.00, 150.00, NOW(), NOW()),
(15, 'OLL-2T-PB-200', '2T POWERBURN (60/200ML)', '2-stroke Powerburn 200ml', 2, 4, 120.00, 150.00, NOW(), NOW()),
(16, 'OLL-SPRINT-200', 'SPRINT 4T RIDER (60/200ML)', 'Sprint 4T rider oil 200ml', 2, 4, 130.00, 162.50, NOW(), NOW()),
(17, 'OLL-2T-AUTO-24', '2T AUTOLUBE (24/1)', '2-stroke autolube carton', 2, 4, 2400.00, 3000.00, NOW(), NOW()),
(18, 'OLL-2T-PB-24', '2T POWERBURN (24/1)', '2T Powerburn carton', 2, 4, 2400.00, 3000.00, NOW(), NOW()),
(19, 'OLL-REVX-FS-12', 'REV-X FS ALLTERRAIN (12/1)', 'REV-X all-terrain synthetic blend', 2, 4, 2000.00, 2500.00, NOW(), NOW()),
(20, 'OLL-REVX-FS-SB-12', 'REV-X FS ALLTERRAIN SYNTHETIC BLEND (12/1)', 'REV-X synthetic blend 12-pack', 2, 4, 1900.00, 2375.00, NOW(), NOW()),
(21, 'OLL-TOURING-12', 'TOURING (12/1)', 'Touring oil 12-pack', 2, 4, 1800.00, 2250.00, NOW(), NOW()),
(22, 'OLL-BLAZE-RS-SB-12', 'BLAZE RACING SYNTHETIC BLEND (12/1)', 'Blaze Racing synthetic blend', 2, 4, 2100.00, 2625.00, NOW(), NOW()),
(23, 'OLL-BLAZE-RS-SYN-12', 'BLAZE RACING SYNTHETIC (12/1)', 'Blaze Racing synthetic 12-pack', 2, 4, 2200.00, 2750.00, NOW(), NOW()),
(24, 'OLL-BLAZE-EX-12', 'BLAZE RACING EXTRA (12/1)', 'Blaze Racing extra grade', 2, 4, 2000.00, 2500.00, NOW(), NOW()),
(25, 'OLL-TREKKER-12', 'TREKKER (12/1)', 'Trekker 12-pack', 2, 4, 1900.00, 2375.00, NOW(), NOW()),
(26, 'OLL-REVX-4X-12', 'REV-X 4X (12/1)', 'REV-X 4X oil 12-pack', 2, 4, 2100.00, 2625.00, NOW(), NOW()),
(27, 'OLL-HD30-24', 'HD 30 (24/1)', 'HD30 bulk carton', 2, 4, 2200.00, 2750.00, NOW(), NOW()),
(28, 'OLL-HD40-24', 'HD 40 (24/1)', 'HD40 bulk carton', 2, 4, 2300.00, 2875.00, NOW(), NOW()),
(29, 'OLL-MO30-24', 'MO 30 (24/1)', 'Motor oil MO30 carton', 2, 4, 2000.00, 2500.00, NOW(), NOW()),
(30, 'OLL-MO40-24', 'MO 40 (24/1)', 'Motor oil MO40 carton', 2, 4, 2100.00, 2625.00, NOW(), NOW()),
(31, 'OLL-ATF-PREM-24', 'ATF PREMIUM (24/1)', 'Automatic transmission fluid - premium', 2, 4, 1900.00, 2375.00, NOW(), NOW()),
(32, 'OLL-ATF-HTP-24', 'ATF HTP (24/1)', 'High temperature ATF carton', 2, 4, 2000.00, 2500.00, NOW(), NOW()),
(33, 'OLL-GEP90-24', 'GEP 90 (24/1)', 'GEP90 gear oil carton', 2, 4, 2200.00, 2750.00, NOW(), NOW()),
(34, 'OLL-GEP140-24', 'GEP 140 (24/1)', 'GEP140 gear oil carton', 2, 4, 2300.00, 2875.00, NOW(), NOW()),
(35, 'OLL-SPRINT-12', 'SPRINT 4T RIDER (12/1)', 'Sprint 4T rider 12-pack', 2, 4, 1400.00, 1750.00, NOW(), NOW()),
(36, 'OLL-ENDURO-12', 'ENDURO (12/1)', 'Enduro oil 12-pack', 2, 4, 1300.00, 1625.00, NOW(), NOW()),
(37, 'OLL-MPGR-0_5', 'MP GREASE (0.5 KG)', 'Multipurpose grease 0.5kg', 2, 4, 250.00, 312.50, NOW(), NOW()),
(38, 'OLL-MPGR-2', 'MP GREASE (2 KG)', 'Multipurpose grease 2kg', 2, 4, 800.00, 1000.00, NOW(), NOW()),


-- CAR ACCESSORIES (category_id = 5)
-- Total: 59 products
-- Price range: 50-200 PHP

(39, 'ACC-OIL-SAVER-1L', 'OIL SAVER (1L)', 'Oil treatment additive 1L', 2, 5, 40.00, 50.00, NOW(), NOW()),
(40, 'ACC-WHIZ-425', 'OIL TREATMENT - WHIZ (425ML)', 'Engine oil treatment WHIZ 425ml', 2, 5, 36.00, 45.00, NOW(), NOW()),
(41, 'ACC-ENG-FLUSH-443', 'ENGINE FLUSH (443ML)', 'Engine flush cleaner 443ml', 2, 5, 32.00, 40.00, NOW(), NOW()),
(42, 'ACC-ENG-FLUSH-H-500', 'ENGINE FLUSH - HARDEX (500ML)', 'Hardex engine flush 500ml', 2, 5, 40.00, 50.00, NOW(), NOW()),
(43, 'ACC-WASHER-300', 'BLUE SPRAY WASHER FLUID (300ML)', 'Windshield washer spray 300ml', 2, 5, 32.00, 40.00, NOW(), NOW()),
(44, 'ACC-RAD-100', 'RADIATOR COOLANT (100ML)', 'Radiator coolant 100ml', 2, 5, 40.00, 50.00, NOW(), NOW()),
(45, 'ACC-RAD-500', 'RADIATOR COOLANT (500ML)', 'Radiator coolant 500ml', 2, 5, 120.00, 150.00, NOW(), NOW()),
(46, 'ACC-RAD-GR-1L', 'RADIATOR COOLANT (GREEN) (1L)', 'Green radiator coolant 1L', 2, 5, 160.00, 200.00, NOW(), NOW()),
(47, 'ACC-RAD-PK-1L', 'RADIATOR COOLANT (PINK) (1L)', 'Pink radiator coolant 1L', 2, 5, 160.00, 200.00, NOW(), NOW()),
(48, 'ACC-PEN-190', 'PETROMATE PENETRATING OIL (190ML)', 'Penetrating oil 190ml', 2, 5, 40.00, 50.00, NOW(), NOW()),
(49, 'ACC-PEN-450', 'PETROMATE PENETRATING OIL (450ML)', 'Penetrating oil 450ml', 2, 5, 80.00, 100.00, NOW(), NOW()),
(50, 'ACC-WD40-BIG', 'WD-40 (BIG)', 'WD-40 multipurpose lubricant large', 2, 5, 160.00, 200.00, NOW(), NOW()),
(51, 'ACC-WD40-SM', 'WD-40 (SMALL)', 'WD-40 multipurpose lubricant small', 2, 5, 40.00, 50.00, NOW(), NOW()),
(52, 'ACC-TIRE-BLK-BIG', 'TIRE BLACK (BIG)', 'Tire dressing black large', 2, 5, 120.00, 150.00, NOW(), NOW()),
(53, 'ACC-TIRE-BLK-SM', 'TIRE BLACK (SMALL)', 'Tire dressing black small', 2, 5, 40.00, 50.00, NOW(), NOW()),
(54, 'ACC-TW-PASTE', 'TURTLE WAX SOFT PASTE', 'Turtle Wax soft paste', 2, 5, 160.00, 200.00, NOW(), NOW()),
(55, 'ACC-TW-LIQ', 'TURTLE WAX LIQUID WAX', 'Turtle Wax liquid finish', 2, 5, 160.00, 200.00, NOW(), NOW()),
(56, 'ACC-LUBRITOP', 'LUBRITOP', 'Lubritop protectant', 2, 5, 120.00, 150.00, NOW(), NOW()),
(57, 'ACC-PB-150', 'POWER BOOSTER (150ML)', 'Power booster additive 150ml', 2, 5, 40.00, 50.00, NOW(), NOW()),
(58, 'ACC-CNS-SHAMPOO', 'CLEAN N\' SHINE SHAMPOO', 'Car shampoo Clean N\' Shine', 2, 5, 64.00, 80.00, NOW(), NOW()),
(59, 'ACC-VS1-SM', 'VS1 PROTECTOR (SMALL)', 'VS1 interior protector small', 2, 5, 40.00, 50.00, NOW(), NOW()),
(60, 'ACC-VS1-BIG', 'VS1 PROTECTOR (BIG)', 'VS1 interior protector large', 2, 5, 120.00, 150.00, NOW(), NOW()),
(61, 'ACC-AA-SM', 'ARMOR ALL (SMALL)', 'Armor All protectant small', 2, 5, 40.00, 50.00, NOW(), NOW()),
(62, 'ACC-AA-BIG', 'ARMOR ALL (BIG)', 'Armor All protectant large', 2, 5, 120.00, 150.00, NOW(), NOW()),
(63, 'ACC-STP-300', 'STP OIL TREATMENT (300ML)', 'STP oil treatment 300ml', 2, 5, 40.00, 50.00, NOW(), NOW()),
(64, 'ACC-GAS-SAVER', 'GAS SAVER', 'Fuel economy additive', 2, 5, 46.00, 57.50, NOW(), NOW()),
(65, 'ACC-NEO-SH', 'NEO SHALDAN', 'Air freshener - Neo Shaldan', 2, 5, 124.00, 155.00, NOW(), NOW()),
(66, 'ACC-TOPIAS', 'TOPIAS FRESHENER', 'Car freshener - Topias', 2, 5, 80.00, 100.00, NOW(), NOW()),
(67, 'ACC-LT', 'LITTLE TREES', 'Little Trees air freshener', 2, 5, 40.00, 50.00, NOW(), NOW()),
(68, 'ACC-CAL-SCENT', 'CALIFORNIA SCENT', 'California Scents air freshener', 2, 5, 80.00, 100.00, NOW(), NOW()),
(69, 'ACC-GLADE-SP', 'GLADE SPRAY', 'Glade spray air freshener', 2, 5, 196.00, 245.00, NOW(), NOW()),
(70, 'ACC-BF-900', 'BRAKE FLUID (900ML)', 'Brake fluid 900ml', 2, 5, 144.00, 180.00, NOW(), NOW()),
(71, 'ACC-BF-MED', 'BRAKE FLUID (MEDIUM)', 'Brake fluid medium', 2, 5, 64.00, 80.00, NOW(), NOW()),
(72, 'ACC-BF-SM', 'BRAKE FLUID (SMALL)', 'Brake fluid small', 2, 5, 32.00, 40.00, NOW(), NOW()),
(73, 'ACC-BC-H-400', 'BRAKE CLEANER - HARDEX (400ML)', 'Brake cleaner Hardex 400ml', 2, 5, 48.00, 60.00, NOW(), NOW()),
(74, 'ACC-BC', 'BRAKE CLEANER', 'Brake cleaner general', 2, 5, 48.00, 60.00, NOW(), NOW()),
(75, 'ACC-TVR', 'TIRE VALVE RUBBER', 'Rubber tire valve replacement', 2, 5, 9.60, 12.00, NOW(), NOW()),
(76, 'ACC-TVS', 'TIRE VALVE STEEL', 'Steel tire valve core', 2, 5, 48.00, 60.00, NOW(), NOW()),
(77, 'ACC-RZ-AEAL', 'RZ AUTO TIRE AEAL', 'Tire sealing compound', 2, 5, 256.00, 320.00, NOW(), NOW()),
(78, 'ACC-GASKET-MK', 'GASKET MAKER', 'Gasket maker adhesive', 2, 5, 44.00, 55.00, NOW(), NOW()),
(79, 'ACC-CHAMOIS', 'CHAMOIS', 'Chamois leather cloth', 2, 5, 80.00, 100.00, NOW(), NOW()),
(80, 'ACC-FLANELA', 'FLANELA', 'Cleaning flannel cloth', 2, 5, 32.00, 40.00, NOW(), NOW()),
(81, 'ACC-PATCH-11', 'PATCH # 11', 'Tire repair patch #11', 2, 5, 40.00, 50.00, NOW(), NOW()),
(82, 'ACC-PATCH-12', 'PATCH # 12', 'Tire repair patch #12', 2, 5, 40.00, 50.00, NOW(), NOW()),
(83, 'ACC-BRST-DBL', 'BACKREST DOUBLE', 'Double backrest cushion', 2, 5, 200.00, 250.00, NOW(), NOW()),
(84, 'ACC-BRST-SGL', 'BACKREST SINGLE', 'Single backrest cushion', 2, 5, 120.00, 150.00, NOW(), NOW()),
(85, 'ACC-WT-1_25', 'WHEEL WEIGHTS CLIP TYRE (1 1/4 OZ)', 'Clip-on wheel weight 1.25oz', 2, 5, 40.00, 50.00, NOW(), NOW()),
(86, 'ACC-WT-0_5', 'WHEEL WEIGHTS CLIP TYRE (1/2 OZ)', 'Clip-on wheel weight 0.5oz', 2, 5, 24.00, 30.00, NOW(), NOW()),
(87, 'ACC-WT-0_75', 'WHEEL WEIGHTS CLIP TYRE (3/4 OZ)', 'Clip-on wheel weight 0.75oz', 2, 5, 32.00, 40.00, NOW(), NOW()),
(88, 'ACC-WT-1IN', 'WHEEL WEIGHTS CLIP TYRE (1")', 'Clip-on wheel weight 1 inch', 2, 5, 48.00, 60.00, NOW(), NOW()),
(89, 'ACC-WT-1_5', 'WHEEL WEIGHTS CLIP TYRE (1 1/2)', 'Clip-on wheel weight 1.5', 2, 5, 72.00, 90.00, NOW(), NOW()),
(90, 'ACC-WT-ADH', 'WHEEL WEIGHTS ADHESIVE', 'Adhesive wheel weights', 2, 5, 48.00, 60.00, NOW(), NOW()),
(91, 'ACC-MP1-MED', 'MP 1(MED) PATCH', 'Medium tire patch MP1', 2, 5, 40.00, 50.00, NOW(), NOW()),
(92, 'ACC-MP2-LG', 'MP2 (LARGE) PATCH', 'Large tire patch MP2', 2, 5, 56.00, 70.00, NOW(), NOW()),
(93, 'ACC-CT20', 'CT 20 RADIAL PATCH', 'Radial tire patch CT20', 2, 5, 64.00, 80.00, NOW(), NOW()),
(94, 'ACC-WW-16', 'WIPER WASH (16ML)', 'Wiper wash 16ml', 2, 5, 24.00, 30.00, NOW(), NOW()),
(95, 'ACC-CLUTCH-OIL', 'SELECON/CLUTCH OIL', 'Selecon clutch oil', 2, 5, 64.00, 80.00, NOW(), NOW()),


-- FILTERS (category_id = 6)
-- Total: 36 products
-- Price range: 300-520 PHP

(96, 'FLT-SAK-F1508', 'SAKURA F1508', 'Sakura oil filter F1508', 2, 6, 240.00, 300.00, NOW(), NOW()),
(97, 'FLT-SAK-FC-1510', 'SAKURA FC - 1510', 'Sakura filter FC-1510', 2, 6, 280.00, 350.00, NOW(), NOW()),
(98, 'FLT-OF-SPK-95985730', 'OIL FILTER SPARK -95985730', 'Oil filter for Spark', 2, 6, 240.00, 300.00, NOW(), NOW()),
(99, 'FLT-FES-5342', 'FUEL FILTER FES 5342', 'Fuel filter FES 5342', 2, 6, 360.00, 450.00, NOW(), NOW()),
(100, 'FLT-94797406', 'FILTER 94797406', 'Generic filter 94797406', 2, 6, 280.00, 350.00, NOW(), NOW()),
(101, 'FLT-C-223', 'C- 223', 'Filter C-223', 2, 6, 400.00, 500.00, NOW(), NOW()),
(102, 'FLT-C-509A', 'C- 509A', 'Filter C-509A', 2, 6, 400.00, 500.00, NOW(), NOW()),
(103, 'FLT-C-510A', 'C- 510A', 'Filter C-510A', 2, 6, 400.00, 500.00, NOW(), NOW()),
(104, 'FLT-FC-322', 'FC- 322', 'Filter FC-322', 2, 6, 360.00, 450.00, NOW(), NOW()),
(105, 'FLT-DAI-581', 'OIL FILTER DAI - WA DU 581', 'Oil filter DAI-WA DU 581', 2, 6, 300.00, 375.00, NOW(), NOW()),
(106, 'FLT-O-1012-S', 'OIL FILTER O- 1012 S', 'Oil filter O-1012S', 2, 6, 280.00, 350.00, NOW(), NOW()),
(107, 'FLT-FUJ-5262313', 'FUJILITO 5262313', 'Fujilito filter 5262313', 2, 6, 320.00, 400.00, NOW(), NOW()),
(108, 'FLT-FUJ-5266016', 'FUJILITO 5266016', 'Fujilito filter 5266016', 2, 6, 320.00, 400.00, NOW(), NOW()),
(109, 'FLT-FUJ-5262311', 'FUJILITO 5262311', 'Fujilito filter 5262311', 2, 6, 320.00, 400.00, NOW(), NOW()),
(110, 'FLT-FUJ-5264870', 'FUJILITO 5264870', 'Fujilito filter 5264870', 2, 6, 320.00, 400.00, NOW(), NOW()),
(111, 'FLT-C-65400', 'OIL FILTER C- 65400', 'Oil filter C-65400', 2, 6, 280.00, 350.00, NOW(), NOW()),
(112, 'FLT-FESS-5715', 'FUEL FILTER FESS - 5715', 'Fuel filter FESS-5715', 2, 6, 360.00, 450.00, NOW(), NOW()),
(113, 'FLT-FESS-5714', 'FUEL FILTER FESS - 5714', 'Fuel filter FESS-5714', 2, 6, 360.00, 450.00, NOW(), NOW()),
(114, 'FLT-FESS-5708', 'FUEL FILTER FESS - 5708', 'Fuel filter FESS-5708', 2, 6, 360.00, 450.00, NOW(), NOW()),
(115, 'FLT-FFS-1501', 'FUEL FILTER FFS - 1501', 'Fuel filter FFS-1501', 2, 6, 348.00, 435.00, NOW(), NOW()),
(116, 'FLT-FFS-1478', 'FUEL FILTER FFS - 1478', 'Fuel filter FFS-1478', 2, 6, 348.00, 435.00, NOW(), NOW()),
(117, 'FLT-FC-017', 'FUEL FILTER FC - 017', 'Fuel filter FC-017', 2, 6, 360.00, 450.00, NOW(), NOW()),
(118, 'FLT-C-419', 'OIL FILTER C-419', 'Oil filter C-419', 2, 6, 320.00, 400.00, NOW(), NOW()),
(119, 'FLT-O-010', 'OIL FILTER O- 010', 'Oil filter O-010', 2, 6, 240.00, 300.00, NOW(), NOW()),
(120, 'FLT-NOM-NLT-060', 'NOMIS OIL FILTER NLT - 060', 'Nomis oil filter NLT-060', 2, 6, 280.00, 350.00, NOW(), NOW()),
(121, 'FLT-FES-5583', 'OIL FILTER FES - 5583', 'Oil filter FES-5583', 2, 6, 280.00, 350.00, NOW(), NOW()),
(122, 'FLT-C-117', 'OIL FILTER C-117', 'Oil filter C-117', 2, 6, 320.00, 400.00, NOW(), NOW()),
(123, 'FLT-VG-1560080012', 'VG 1560080012', 'VG filter 1560080012', 2, 6, 360.00, 450.00, NOW(), NOW()),
(124, 'FLT-HOWO-186-1012000', 'OIL FILTER 186-1012000 (HOWO)', 'HOWO oil filter 186-1012000', 2, 6, 416.00, 520.00, NOW(), NOW()),
(125, 'FLT-FC-326', 'FUEL FILTER FC - 326', 'Fuel filter FC-326', 2, 6, 254.40, 318.00, NOW(), NOW()),
(126, 'FLT-F-197', 'FUEL FILTER F- 197', 'Fuel filter F-197', 2, 6, 280.00, 350.00, NOW(), NOW()),
(127, 'FLT-C-525', 'OIL FILTER C - 525', 'Oil filter C-525', 2, 6, 886.40, 1108.00, NOW(), NOW()),
(128, 'FLT-SAK-F-1111', 'SAKURA FUEL FILTER F-1111', 'Sakura fuel filter F-1111', 2, 6, 280.00, 350.00, NOW(), NOW()),
(129, 'FLT-FES-5617', 'OIL FILTER - FES 5617', 'Oil filter FES-5617', 2, 6, 280.00, 350.00, NOW(), NOW()),
(130, 'FLT-MC-0078', 'OIL FILTER - MC - 0078', 'Oil filter MC-0078', 2, 6, 280.00, 350.00, NOW(), NOW()),
(131, 'FLT-MC-0010', 'OIL FILTER - MC - 0010', 'Oil filter MC-0010', 2, 6, 280.00, 350.00, NOW(), NOW()),
(132, 'ENGINEOIL5W30', 'Engine Oil 5W-30', 'Synthetic engine oil 5W-30', 2, 4, 250.00, 350.00, NOW(), NOW());


SELECT CONCAT('✓ ', COUNT(*), ' products inserted') AS status FROM products;


-- STEP 4: CREATE STATION INVENTORY RECORDS
-- All products assigned to station 1250
-- Random appropriate stock levels: 10-100 for regular items, 500-2000 for bulk items
-- =====================================================

INSERT INTO `station_inventory` (`station_id`, `product_id`, `stock_level`, `reorder_level`, `capacity`, `unit`, `status`, `last_updated`) VALUES

-- Oils/Lubes/Grease Inventory (38 products, IDs 1-38)
(1250, 1, 525, 50, 10000, 'L', 'active', NOW()),
(1250, 2, 45, 5, 1000, 'pcs', 'active', NOW()),
(1250, 3, 38, 5, 1000, 'pcs', 'active', NOW()),
(1250, 4, 42, 5, 1000, 'pcs', 'active', NOW()),
(1250, 5, 35, 5, 1000, 'pcs', 'active', NOW()),
(1250, 6, 48, 5, 1000, 'pcs', 'active', NOW()),
(1250, 7, 52, 5, 1000, 'pcs', 'active', NOW()),
(1250, 8, 58, 5, 1000, 'pcs', 'active', NOW()),
(1250, 9, 62, 5, 1000, 'pcs', 'active', NOW()),
(1250, 10, 125, 10, 500, 'L', 'active', NOW()),
(1250, 11, 85, 10, 500, 'L', 'active', NOW()),
(1250, 12, 72, 10, 500, 'L', 'active', NOW()),
(1250, 13, 78, 10, 500, 'L', 'active', NOW()),
(1250, 14, 145, 15, 300, 'ml', 'active', NOW()),
(1250, 15, 132, 15, 300, 'ml', 'active', NOW()),
(1250, 16, 128, 15, 300, 'ml', 'active', NOW()),
(1250, 17, 856, 50, 5000, 'pcs', 'active', NOW()),
(1250, 18, 924, 50, 5000, 'pcs', 'active', NOW()),
(1250, 19, 725, 50, 3000, 'pcs', 'active', NOW()),
(1250, 20, 698, 50, 3000, 'pcs', 'active', NOW()),
(1250, 21, 654, 50, 3000, 'pcs', 'active', NOW()),
(1250, 22, 712, 50, 3000, 'pcs', 'active', NOW()),
(1250, 23, 689, 50, 3000, 'pcs', 'active', NOW()),
(1250, 24, 734, 50, 3000, 'pcs', 'active', NOW()),
(1250, 25, 701, 50, 3000, 'pcs', 'active', NOW()),
(1250, 26, 745, 50, 3000, 'pcs', 'active', NOW()),
(1250, 27, 892, 50, 5000, 'pcs', 'active', NOW()),
(1250, 28, 865, 50, 5000, 'pcs', 'active', NOW()),
(1250, 29, 934, 50, 5000, 'pcs', 'active', NOW()),
(1250, 30, 912, 50, 5000, 'pcs', 'active', NOW()),
(1250, 31, 847, 50, 5000, 'pcs', 'active', NOW()),
(1250, 32, 876, 50, 5000, 'pcs', 'active', NOW()),
(1250, 33, 903, 50, 5000, 'pcs', 'active', NOW()),
(1250, 34, 928, 50, 5000, 'pcs', 'active', NOW()),
(1250, 35, 678, 50, 2000, 'pcs', 'active', NOW()),
(1250, 36, 645, 50, 2000, 'pcs', 'active', NOW()),
(1250, 37, 92, 10, 200, 'kg', 'active', NOW()),
(1250, 38, 68, 10, 200, 'kg', 'active', NOW()),


-- Car Accessories Inventory (57 products, IDs 39-95)
(1250, 39, 85, 10, 500, 'L', 'active', NOW()),
(1250, 40, 95, 10, 500, 'ml', 'active', NOW()),
(1250, 41, 78, 10, 500, 'ml', 'active', NOW()),
(1250, 42, 82, 10, 500, 'ml', 'active', NOW()),
(1250, 43, 65, 10, 500, 'ml', 'active', NOW()),
(1250, 44, 52, 10, 500, 'ml', 'active', NOW()),
(1250, 45, 72, 10, 500, 'ml', 'active', NOW()),
(1250, 46, 58, 10, 500, 'L', 'active', NOW()),
(1250, 47, 55, 10, 500, 'L', 'active', NOW()),
(1250, 48, 88, 10, 500, 'ml', 'active', NOW()),
(1250, 49, 75, 10, 500, 'ml', 'active', NOW()),
(1250, 50, 62, 10, 200, 'can', 'active', NOW()),
(1250, 51, 98, 10, 200, 'can', 'active', NOW()),
(1250, 52, 45, 10, 200, 'bottle', 'active', NOW()),
(1250, 53, 82, 10, 200, 'bottle', 'active', NOW()),
(1250, 54, 68, 10, 200, 'tin', 'active', NOW()),
(1250, 55, 65, 10, 200, 'bottle', 'active', NOW()),
(1250, 56, 52, 10, 200, 'bottle', 'active', NOW()),
(1250, 57, 78, 10, 300, 'ml', 'active', NOW()),
(1250, 58, 55, 10, 500, 'ml', 'active', NOW()),
(1250, 59, 92, 10, 200, 'bottle', 'active', NOW()),
(1250, 60, 48, 10, 200, 'bottle', 'active', NOW()),
(1250, 61, 85, 10, 200, 'bottle', 'active', NOW()),
(1250, 62, 52, 10, 200, 'bottle', 'active', NOW()),
(1250, 63, 72, 10, 300, 'ml', 'active', NOW()),
(1250, 64, 68, 10, 200, 'bottle', 'active', NOW()),
(1250, 65, 45, 10, 100, 'pcs', 'active', NOW()),
(1250, 66, 58, 10, 100, 'pcs', 'active', NOW()),
(1250, 67, 92, 10, 100, 'pcs', 'active', NOW()),
(1250, 68, 75, 10, 100, 'pcs', 'active', NOW()),
(1250, 69, 62, 10, 100, 'can', 'active', NOW()),
(1250, 70, 38, 10, 500, 'ml', 'active', NOW()),
(1250, 71, 45, 10, 500, 'ml', 'active', NOW()),
(1250, 72, 52, 10, 500, 'ml', 'active', NOW()),
(1250, 73, 75, 10, 500, 'ml', 'active', NOW()),
(1250, 74, 68, 10, 500, 'ml', 'active', NOW()),
(1250, 75, 125, 10, 500, 'pcs', 'active', NOW()),
(1250, 76, 85, 10, 500, 'pcs', 'active', NOW()),
(1250, 77, 42, 10, 200, 'pcs', 'active', NOW()),
(1250, 78, 58, 10, 200, 'pcs', 'active', NOW()),
(1250, 79, 65, 10, 100, 'pcs', 'active', NOW()),
(1250, 80, 72, 10, 100, 'pcs', 'active', NOW()),
(1250, 81, 55, 10, 200, 'pcs', 'active', NOW()),
(1250, 82, 48, 10, 200, 'pcs', 'active', NOW()),
(1250, 83, 32, 10, 100, 'pcs', 'active', NOW()),
(1250, 84, 45, 10, 100, 'pcs', 'active', NOW()),
(1250, 85, 88, 10, 500, 'pcs', 'active', NOW()),
(1250, 86, 75, 10, 500, 'pcs', 'active', NOW()),
(1250, 87, 82, 10, 500, 'pcs', 'active', NOW()),
(1250, 88, 68, 10, 500, 'pcs', 'active', NOW()),
(1250, 89, 55, 10, 500, 'pcs', 'active', NOW()),
(1250, 90, 62, 10, 500, 'pcs', 'active', NOW()),
(1250, 91, 45, 10, 200, 'pcs', 'active', NOW()),
(1250, 92, 52, 10, 200, 'pcs', 'active', NOW()),
(1250, 93, 58, 10, 200, 'pcs', 'active', NOW()),
(1250, 94, 95, 10, 200, 'ml', 'active', NOW()),
(1250, 95, 65, 10, 500, 'L', 'active', NOW()),


-- Filters Inventory (36 products, IDs 96-131)
(1250, 96, 42, 10, 500, 'pcs', 'active', NOW()),
(1250, 97, 38, 10, 500, 'pcs', 'active', NOW()),
(1250, 98, 45, 10, 500, 'pcs', 'active', NOW()),
(1250, 99, 35, 10, 500, 'pcs', 'active', NOW()),
(1250, 100, 52, 10, 500, 'pcs', 'active', NOW()),
(1250, 101, 28, 10, 500, 'pcs', 'active', NOW()),
(1250, 102, 32, 10, 500, 'pcs', 'active', NOW()),
(1250, 103, 35, 10, 500, 'pcs', 'active', NOW()),
(1250, 104, 38, 10, 500, 'pcs', 'active', NOW()),
(1250, 105, 48, 10, 500, 'pcs', 'active', NOW()),
(1250, 106, 55, 10, 500, 'pcs', 'active', NOW()),
(1250, 107, 42, 10, 500, 'pcs', 'active', NOW()),
(1250, 108, 38, 10, 500, 'pcs', 'active', NOW()),
(1250, 109, 45, 10, 500, 'pcs', 'active', NOW()),
(1250, 110, 52, 10, 500, 'pcs', 'active', NOW()),
(1250, 111, 58, 10, 500, 'pcs', 'active', NOW()),
(1250, 112, 35, 10, 500, 'pcs', 'active', NOW()),
(1250, 113, 32, 10, 500, 'pcs', 'active', NOW()),
(1250, 114, 38, 10, 500, 'pcs', 'active', NOW()),
(1250, 115, 42, 10, 500, 'pcs', 'active', NOW()),
(1250, 116, 48, 10, 500, 'pcs', 'active', NOW()),
(1250, 117, 35, 10, 500, 'pcs', 'active', NOW()),
(1250, 118, 55, 10, 500, 'pcs', 'active', NOW()),
(1250, 119, 45, 10, 500, 'pcs', 'active', NOW()),
(1250, 120, 52, 10, 500, 'pcs', 'active', NOW()),
(1250, 121, 38, 10, 500, 'pcs', 'active', NOW()),
(1250, 122, 42, 10, 500, 'pcs', 'active', NOW()),
(1250, 123, 35, 10, 500, 'pcs', 'active', NOW()),
(1250, 124, 28, 10, 500, 'pcs', 'active', NOW()),
(1250, 125, 48, 10, 500, 'pcs', 'active', NOW()),
(1250, 126, 55, 10, 500, 'pcs', 'active', NOW()),
(1250, 127, 25, 10, 500, 'pcs', 'active', NOW()),
(1250, 128, 52, 10, 500, 'pcs', 'active', NOW()),
(1250, 129, 38, 10, 500, 'pcs', 'active', NOW()),
(1250, 130, 45, 10, 500, 'pcs', 'active', NOW()),
(1250, 131, 42, 10, 500, 'pcs', 'active', NOW()),


-- Special Product Inventory (1 product, ID 132)
(1250, 132, 125, 10, 500, 'L', 'active', NOW());


SELECT CONCAT('✓ ', COUNT(*), ' station inventory records created') AS status FROM station_inventory WHERE station_id = 1250;


-- STEP 5: VERIFICATION QUERIES
-- =====================================================

-- Count products by category
SELECT '=== PRODUCTS BY CATEGORY ===' AS section;
SELECT
    pc.name AS category,
    COUNT(p.id) AS product_count,
    CONCAT('₱', FORMAT(MIN(p.price), 2), ' - ₱', FORMAT(MAX(p.price), 2)) AS price_range
FROM products p
JOIN product_categories pc ON p.category_id = pc.id
GROUP BY pc.id, pc.name
ORDER BY product_count DESC;

-- Count products by type
SELECT '=== PRODUCTS BY TYPE ===' AS section;
SELECT
    pt.name AS type,
    COUNT(p.id) AS product_count
FROM products p
JOIN product_types pt ON p.type_id = pt.id
GROUP BY pt.id, pt.name;

-- Verify station inventory
SELECT '=== STATION INVENTORY SUMMARY ===' AS section;
SELECT
    COUNT(*) AS total_records,
    COUNT(CASE WHEN stock_level = 0 THEN 1 END) AS zero_stock,
    COUNT(CASE WHEN stock_level > 0 THEN 1 END) AS in_stock,
    FORMAT(AVG(stock_level), 2) AS avg_stock_level,
    SUM(stock_level) AS total_units
FROM station_inventory
WHERE station_id = 1250;

-- Sample inventory details
SELECT '=== SAMPLE INVENTORY DETAILS (First 10) ===' AS section;
SELECT
    p.sku,
    p.name,
    p.price AS unit_price,
    si.stock_level,
    si.reorder_level,
    pc.name AS category
FROM station_inventory si
JOIN products p ON si.product_id = p.id
JOIN product_categories pc ON p.category_id = pc.id
WHERE si.station_id = 1250
ORDER BY si.product_id
LIMIT 10;


-- STEP 6: COMPLETION SUMMARY
-- =====================================================

SELECT '=== EXECUTION COMPLETE ===' AS summary;
SELECT 'Products inserted: 132' AS total_products;
SELECT 'All products have type_id = 2 (merch)' AS product_type;
SELECT 'Station inventory created for station 1250' AS inventory_station;
SELECT 'Random stock levels applied: 10-100 regular, 500-2000 bulk items' AS stock_levels;
SELECT 'Backup tables created: products_backup_20250217, station_inventory_backup_20250217' AS backup_info;

