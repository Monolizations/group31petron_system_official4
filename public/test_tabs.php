<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tab Test</title>
    <style>
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .tab {
            padding: 12px 20px;
            border: none;
            background: #f8f9fa;
            color: #6c757d;
            cursor: pointer;
            border-radius: 8px 8px 0 0;
            transition: all 0.3s ease;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .tab:hover {
            background: #e9ecef;
            color: #495057;
            border-bottom-color: #6c757d;
        }
        
        .tab.active {
            background: #003d7a;
            color: white;
            border-bottom-color: #003d7a;
        }
        
        .card.hidden {
            display: none !important;
        }
        
        .card {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>Tab Test Page</h1>
    
    <div class="tabs pills">
        <button class="tab active" data-invtab="fuel">🛢️ Fuel Inventory</button>
        <button class="tab" data-invtab="merch">📦 Merchandise</button>
        <button class="tab" data-invtab="low_stock">⚠️ Low Stock Alerts</button>
        <button class="tab" data-invtab="fuel_delivery">🚛 Fuel Delivery</button>
    </div>
    
    <section class="card" id="fuelInv">
        <h2>Fuel Inventory</h2>
        <p>This is the fuel inventory tab content.</p>
    </section>
    
    <section class="card hidden" id="merchInv">
        <h2>Merchandise</h2>
        <p>This is the merchandise tab content.</p>
    </section>
    
    <section class="card hidden" id="lowStockInv">
        <h2>Low Stock Alerts</h2>
        <p>This is the low stock alerts tab content.</p>
    </section>
    
    <section class="card hidden" id="fuelDeliveryInv">
        <h2>Fuel Delivery Management</h2>
        <p>This is the fuel delivery tab content.</p>
    </section>

    <script>
        console.log('Initializing inventory tabs...');
        const invTabs = document.querySelectorAll('.tab[data-invtab]');
        console.log('Found tabs:', invTabs.length);

        function showInvTab(key){
            console.log('Switching to tab:', key);
            invTabs.forEach(b => b.classList.toggle('active', b.dataset.invtab === key));
            document.getElementById('fuelInv')?.classList.toggle('hidden', key !== 'fuel');
            document.getElementById('merchInv')?.classList.toggle('hidden', key !== 'merch');
            document.getElementById('lowStockInv')?.classList.toggle('hidden', key !== 'low_stock');
            document.getElementById('fuelDeliveryInv')?.classList.toggle('hidden', key !== 'fuel_delivery');
        }

        invTabs.forEach(btn => {
            console.log('Adding click handler for tab:', btn.dataset.invtab);
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('Tab clicked:', btn.dataset.invtab);
                showInvTab(btn.dataset.invtab);
            });
        });

        // Initialize with default tab
        showInvTab('fuel');
    </script>
</body>
</html>