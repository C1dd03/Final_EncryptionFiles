<?php
require_once __DIR__ . '/../auth/session_protect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sportswear Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background-color: #f5f5f5; color: #333; }
        .container { max-width: 1400px; margin: 20px auto; padding: 10px; }

        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        header h1 { font-size: 28px; color: #111; }
        header a { text-decoration: none; color: #007bff; font-weight: bold; }

        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }

        .card { background-color: #fff; border-radius: 8px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card h3 { margin-bottom: 15px; font-size: 18px; color: #222; }
        .card .highlight { font-size: 22px; font-weight: bold; color: #ff5722; }

        .product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; }
        .product-card { background: #fff; border-radius: 8px; padding: 10px; text-align: center; box-shadow: 0 1px 4px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .product-card:hover { transform: translateY(-5px); }
        .product-card img { max-width: 100%; border-radius: 6px; margin-bottom: 8px; }
        .product-card .name { font-size: 14px; font-weight: bold; margin-bottom: 5px; }
        .product-card .price { color: #ff5722; font-weight: bold; }
        .product-card .stock { font-size: 12px; color: #555; }
        .product-card .low-stock { color: #d9534f; font-weight: bold; }

        .chart { height: 120px; background: #e0e0e0; border-radius: 6px; display: flex; justify-content: center; align-items: center; font-weight: bold; color: #666; margin-bottom: 10px; }

        .promo-grid { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; }
        .promo-grid img { height: 120px; border-radius: 8px; }

        .notifications { list-style: none; padding-left: 0; }
        .notifications li { margin-bottom: 8px; font-size: 14px; }

        @media (max-width: 768px) { .dashboard-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Sportswear Dashboard</h1>
            <div>
                <span>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!</span> |
                <a href="index.php?action=logout">Logout</a>
            </div>
        </header>

        <div class="dashboard-grid">
            <!-- Sales Overview -->
            <div class="card">
                <h3>Sales Overview</h3>
                <p>Total Sales:</p>
                <p class="highlight">$42,580</p>
                <div class="chart">Revenue Trend (Line Chart)</div>
                <div class="chart">Top Products (Bar Chart)</div>
            </div>

            <!-- Top Products -->
            <div class="card">
                <h3>Top Products</h3>
                <div class="product-grid">
                    <div class="product-card">
                        <img src="https://images.unsplash.com/photo-1600180758895-2b3a41f37ec3?auto=format&fit=crop&w=300&q=80" alt="Running Shoes">
                        <div class="name">Running Shoes</div>
                        <div class="price">$120</div>
                        <div class="stock">Stock: 25</div>
                    </div>
                    <div class="product-card">
                        <img src="https://images.unsplash.com/photo-1594737625785-4bda5a6a58cd?auto=format&fit=crop&w=300&q=80" alt="Sports T-shirt">
                        <div class="name">Sports T-shirt</div>
                        <div class="price">$45</div>
                        <div class="stock">Stock: 50</div>
                    </div>
                    <div class="product-card">
                        <img src="https://images.unsplash.com/photo-1618354695401-7fae7a7c9c3b?auto=format&fit=crop&w=300&q=80" alt="Sneakers">
                        <div class="name">Sneakers</div>
                        <div class="price">$90</div>
                        <div class="stock low-stock">Stock: 5</div>
                    </div>
                    <div class="product-card">
                        <img src="https://images.unsplash.com/photo-1574368396036-33f4735bce39?auto=format&fit=crop&w=300&q=80" alt="Basketball Shoes">
                        <div class="name">Basketball Shoes</div>
                        <div class="price">$150</div>
                        <div class="stock">Stock: 20</div>
                    </div>
                </div>
            </div>

            <!-- Inventory -->
            <div class="card">
                <h3>Inventory</h3>
                <div class="product-grid">
                    <div class="product-card">
                        <img src="https://images.unsplash.com/photo-1606813902812-2f1a226a15e3?auto=format&fit=crop&w=300&q=80" alt="Hoodie">
                        <div class="name">Hoodie</div>
                        <div class="price">$60</div>
                        <div class="stock">Stock: 40</div>
                    </div>
                    <div class="product-card">
                        <img src="https://images.unsplash.com/photo-1616813903210-1f1e226a11e3?auto=format&fit=crop&w=300&q=80" alt="Cap">
                        <div class="name">Sports Cap</div>
                        <div class="price">$25</div>
                        <div class="stock">Stock: 60</div>
                    </div>
                </div>
            </div>

            <!-- Promotions -->
            <div class="card">
                <h3>Promotions</h3>
                <div class="promo-grid">
                    <img src="https://images.unsplash.com/photo-1591047139829-3b62b6a0c6c8?auto=format&fit=crop&w=300&q=80" alt="Promo Banner 1">
                    <img src="https://images.unsplash.com/photo-1600180758895-2b3a41f37ec3?auto=format&fit=crop&w=300&q=80" alt="Promo Banner 2">
                    <img src="https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&w=300&q=80" alt="Promo Banner 3">
                </div>
            </div>

            <!-- Notifications -->
            <div class="card">
                <h3>Notifications & Alerts</h3>
                <ul class="notifications">
                    <li class="low-stock">Low stock on Sneakers</li>
                    <li>Pending Orders: 3</li>
                    <li>Unusual sales spike in Apparel</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
