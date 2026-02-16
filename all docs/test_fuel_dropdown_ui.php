<?php
/**
 * Integration Test: Verify POS Fuel Dropdown Works
 */

require_once __DIR__ . '/public/db_connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>POS Fuel Dropdown Integration Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        select { width: 100%; padding: 10px; font-size: 14px; margin: 10px 0; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        h2 { color: #333; }
        small { color: #666; }
    </style>
</head>
<body>
<div class='container'>
    <h1>POS Fuel Dropdown Test</h1>
    
    <h2>Step 1: Select Product Type</h2>
    <label>Product Type:</label>
    <select id='product_type' onchange='loadProducts()'>
        <option value=''>Select Type</option>
        <option value='fuel'>Fuel</option>
        <option value='merch'>Merchandise</option>
    </select>
    
    <h2>Step 2: Select Fuel Product</h2>
    <label>Product:</label>
    <select id='product_id' onchange='updatePrice()'>
        <option value=''>Select Product</option>
    </select>
    <small id='stock_info'>Select a product type first</small>
    
    <h2>Step 3: View Details</h2>
    <div id='details'></div>
    
    <div id='message'></div>
</div>

<script>
// Get inventory data from POS PHP
const inventoryDataJson = '" . addslashes(json_encode(['fuel' => [], 'merch' => []])) . "';
const inventoryData = JSON.parse(inventoryDataJson);

// Load data via fetch
fetch('test_fuel_dropdown_api.php')
    .then(r => r.json())
    .then(data => {
        window.inventoryData = data.inventory;
        window.fuelSyncStatus = data.fuelSyncStatus;
        showMessage('✓ Data loaded successfully: ' + data.inventory.fuel.length + ' fuel types', 'success');
    })
    .catch(e => {
        showMessage('✗ Error loading data: ' + e.message, 'error');
    });

function loadProducts() {
    const type = document.getElementById('product_type').value;
    const productSelect = document.getElementById('product_id');
    const stockInfo = document.getElementById('stock_info');
    const details = document.getElementById('details');
    
    productSelect.innerHTML = '<option value=\"\">Select Product</option>';
    details.innerHTML = '';
    
    if (type && inventoryData && inventoryData[type]) {
        productSelect.innerHTML = '';
        const optGroup = document.createElement('optgroup');
        optGroup.label = type === 'fuel' ? 'Fuel Types' : 'Merchandise';
        
        productSelect.appendChild(document.createElement('option')).textContent = 'Select Product';
        
        inventoryData[type].forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            
            const stockLevel = parseFloat(product.stock_level) || 0;
            const stockClass = stockLevel <= 0 ? 'color: #dc3545; font-weight: bold;' : '';
            
            // Format fuel product display
            let optionText;
            if (type === 'fuel') {
                const fuelName = product.fuel_type_name || product.name;
                const price = parseFloat(product.price_per_liter) || 0;
                const priceText = price > 0 ? '₱' + price.toFixed(2) + '/L' : 'Price TBD';
                const stockText = 'Stock: ' + stockLevel + ' ' + (product.unit || 'L');
                optionText = fuelName + ' - ' + priceText + ' (' + stockText + ')';
            } else {
                const stockText = stockLevel <= 0 ? ' (OUT OF STOCK)' : ' (Stock: ' + stockLevel + ' ' + (product.unit || 'pc') + ')';
                optionText = product.name + stockText;
            }
            
            option.textContent = optionText;
            option.dataset.price = product.price_per_liter || product.price || 0;
            option.dataset.stock = stockLevel;
            option.dataset.unit = product.unit || (type === 'fuel' ? 'L' : 'pc');
            option.style = stockClass;
            
            productSelect.appendChild(option);
        });
        
        stockInfo.textContent = 'Found ' + inventoryData[type].length + ' products';
    } else {
        stockInfo.textContent = 'Select a product type first';
    }
    
    updatePrice();
}

function updatePrice() {
    const productSelect = document.getElementById('product_id');
    const details = document.getElementById('details');
    
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        const price = parseFloat(selectedOption.dataset.price) || 0;
        const stock = parseFloat(selectedOption.dataset.stock) || 0;
        const unit = selectedOption.dataset.unit || '';
        
        details.innerHTML = '<div class=\"status success\"><strong>Selected:</strong><br>' +
            'Price: ₱' + price.toFixed(2) + '<br>' +
            'Stock: ' + stock + ' ' + unit + '<br>' +
            'Ready to add to sale</div>';
    } else {
        details.innerHTML = '';
    }
}

function showMessage(msg, type) {
    const el = document.getElementById('message');
    el.className = 'status ' + type;
    el.textContent = msg;
}
</script>
</body>
</html>";

?>
