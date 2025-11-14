<?php
require_once __DIR__ . '/../auth/session_protect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sports Marketplace Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #ee4d2d; /* Shopee orange */
            --primary-dark: #d73f20;
            --bg: #f5f5f5;
            --card: #ffffff;
            --text: #222;
            --muted: #777;
            --success: #00bfa6;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: var(--bg); color: var(--text); }
        a { color: inherit; text-decoration: none; }

        /* Topbar */
        .topbar { background: var(--card); border-bottom: 1px solid #eaeaea; }
        .topbar-inner { max-width: 1300px; margin: 0 auto; padding: 10px 16px; display: grid; grid-template-columns: 220px 1fr auto; gap: 12px; align-items: center; }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 20px; }
        .brand img { height: 32px; }
        .search { display: flex; align-items: center; border: 1px solid #ddd; border-radius: 20px; padding: 6px 10px; background: #fff; }
        .search input { flex: 1; border: none; outline: none; padding: 6px 8px; font-size: 14px; }
        .search button { background: var(--primary); color: #fff; border: none; padding: 6px 12px; border-radius: 14px; cursor: pointer; }
        .actions { display: flex; align-items: center; gap: 16px; font-size: 14px; }
        .action-icon { display: inline-flex; align-items: center; gap: 6px; color: var(--muted); }
        .action-icon .bi { font-size: 18px; }

        /* Category bar */
        .category-bar { background: var(--card); border-bottom: 1px solid #eaeaea; }
        .category-inner { max-width: 1300px; margin: 0 auto; padding: 8px 16px; display: flex; gap: 12px; overflow-x: auto; }
        .chip { background: #fff; border: 1px solid #eee; border-radius: 20px; padding: 6px 12px; white-space: nowrap; font-size: 13px; cursor: pointer; }
        .chip.active { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* Main content */
        .content { max-width: 1300px; margin: 16px auto; padding: 0 16px; display: grid; grid-template-columns: 1fr; gap: 16px; }

        /* Hero banners */
        .hero { display: grid; gap: 12px; grid-template-columns: 2fr 1fr; }
        .hero img { width: 100%; height: 220px; object-fit: cover; border-radius: 8px; }

        /* Section header */
        .section-header { display: flex; align-items: center; justify-content: space-between; }
        .section-title { font-weight: 700; font-size: 18px; }
        .section-actions a { color: var(--primary); font-size: 14px; }

        /* Flash deals */
        .flash-list { display: grid; grid-auto-flow: column; grid-auto-columns: 200px; gap: 12px; overflow-x: auto; padding-bottom: 8px; }
        .card { background: var(--card); border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #eee; }
        .product { display: grid; gap: 8px; padding: 10px; }
        .product img { width: 100%; height: 140px; object-fit: cover; border-radius: 6px; }
        .product .name { font-size: 14px; font-weight: 600; line-height: 1.3; min-height: 36px; }
        .price-row { display: flex; align-items: center; gap: 8px; }
        .price { color: var(--primary); font-weight: 700; }
        .old-price { text-decoration: line-through; color: var(--muted); font-size: 12px; }
        .discount { background: #ffeee9; color: var(--primary); font-weight: 700; font-size: 12px; padding: 2px 6px; border-radius: 4px; }
        .meta { display: flex; align-items: center; justify-content: space-between; color: var(--muted); font-size: 12px; }
        .badge-free { color: var(--success); font-weight: 600; }

        /* Product grid */
        .grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; }
        @media (max-width: 1200px) { .grid { grid-template-columns: repeat(5, 1fr); } }
        @media (max-width: 992px) { .grid { grid-template-columns: repeat(4, 1fr); } .hero { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .grid { grid-template-columns: repeat(2, 1fr); } .topbar-inner { grid-template-columns: 1fr; } .actions { justify-content: space-between; } }
        @media (max-width: 480px) { .grid { grid-template-columns: 1fr; } }

        .product-card { display: grid; gap: 8px; padding: 10px; }
        .product-card img { width: 100%; height: 170px; object-fit: cover; border-radius: 6px; }
        .product-card .name { font-size: 14px; font-weight: 600; line-height: 1.3; min-height: 36px; }
        .product-card .price-row { display: flex; align-items: center; gap: 8px; }
        .product-card .price { color: var(--primary); font-weight: 700; }
        .product-card .old-price { text-decoration: line-through; color: var(--muted); font-size: 12px; }
        .product-card .discount { background: #ffeee9; color: var(--primary); font-weight: 700; font-size: 12px; padding: 2px 6px; border-radius: 4px; }
        .product-card .meta { display: flex; align-items: center; justify-content: space-between; color: var(--muted); font-size: 12px; }
        .product-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-2px); transition: all 0.2s ease; }
    </style>
</head>
<body>
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-inner">
            <div class="brand">
                <img src="/img/images/dowlogo2.png" alt="Logo" onerror="this.style.display='none'">
                <span>SportsMart</span>
            </div>
            <form class="search" action="#" method="GET" onsubmit="return false;">
                <i class="bi bi-search" style="color: var(--muted);"></i>
                <input type="text" name="q" placeholder="Search sports gear, shoes, apparel..." aria-label="Search" />
                <button type="submit">Search</button>
            </form>
            <div class="actions">
                <span class="action-icon"><i class="bi bi-cart3"></i> Cart</span>
                <span class="action-icon"><i class="bi bi-bell"></i> Alerts</span>
                <span>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!</span>
                <a class="action-icon" href="index.php?action=logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </div>
    </div>

    <!-- Category Bar -->
    <div class="category-bar">
        <div class="category-inner">
            <span class="chip active">All</span>
            <span class="chip">Football</span>
            <span class="chip">Basketball</span>
            <span class="chip">Running</span>
            <span class="chip">Fitness</span>
            <span class="chip">Cycling</span>
            <span class="chip">Outdoor</span>
            <span class="chip">Tennis</span>
            <span class="chip">Swimming</span>
        </div>
    </div>

    <main class="content">
        <!-- Hero banners -->
        <section class="hero">
            <img src="https://images.unsplash.com/photo-1521412644187-c49fa049e84d?auto=format&fit=crop&w=1200&q=80" alt="Football match">
            <img src="https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=800&q=80" alt="Running shoes">
        </section>

        <!-- Flash Sports Deals -->
        <section class="card">
            <div class="section-header" style="padding: 12px 12px 0 12px;">
                <div class="section-title">Flash Sports Deals</div>
                <div class="section-actions"><a href="#">View all</a></div>
            </div>
            <div class="flash-list" style="padding: 0 12px 12px 12px;">
                <div class="card product">
                    <img src="../images/product-1.jpg" alt="Running Shoes">
                    <div class="name">Pro Running Shoes</div>
                    <div class="price-row"><span class="price">$89</span><span class="old-price">$129</span><span class="discount">-31%</span></div>
                    <div class="meta"><span>Sold 2.1k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product">
                    <img src="../images/product-3.jpg" alt="Basketball">
                    <div class="name">Indoor Grip Basketball</div>
                    <div class="price-row"><span class="price">$29</span><span class="old-price">$45</span><span class="discount">-36%</span></div>
                    <div class="meta"><span>Sold 980</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product">
                    <img src="../images/product-6.jpg" alt="Fitness Tracker">
                    <div class="name">Fitness Tracker Band</div>
                    <div class="price-row"><span class="price">$49</span><span class="old-price">$79</span><span class="discount">-38%</span></div>
                    <div class="meta"><span>Sold 1.3k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product">
                    <img src="../images/product-8.jpg" alt="Football Boots">
                    <div class="name">Elite Football Boots</div>
                    <div class="price-row"><span class="price">$109</span><span class="old-price">$159</span><span class="discount">-31%</span></div>
                    <div class="meta"><span>Sold 650</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product">
                    <img src="../images/product-10.jpg" alt="Cycling Helmet">
                    <div class="name">Pro Cycling Helmet</div>
                    <div class="price-row"><span class="price">$59</span><span class="old-price">$89</span><span class="discount">-34%</span></div>
                    <div class="meta"><span>Sold 420</span><span class="badge-free">Free Shipping</span></div>
                </div>
            </div>
        </section>

        <!-- Recommended Sports Gear -->
        <section class="card" style="padding: 12px;">
            <div class="section-header">
                <div class="section-title">Recommended Sports Gear</div>
                <div class="section-actions"><a href="#">See more</a></div>
            </div>
            <div class="grid" style="margin-top: 12px;">
                <!-- 12 product cards -->
                <div class="card product-card">
                    <img src="../images/product-2.jpg" alt="Basketball Shoes">
                    <div class="name">High-Top Basketball Shoes</div>
                    <div class="price-row"><span class="price">$99</span><span class="old-price">$149</span><span class="discount">-34%</span></div>
                    <div class="meta"><span>Sold 3.4k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product-card">
                    <img src="../images/product-4.jpg" alt="Training Tee">
                    <div class="name">Dri-Fit Training Tee</div>
                    <div class="price-row"><span class="price">$25</span><span class="old-price">$39</span><span class="discount">-36%</span></div>
                    <div class="meta"><span>Sold 5.7k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product-card">
                    <img src="../images/product-5.jpg" alt="Yoga Mat">
                    <div class="name">Eco Grip Yoga Mat</div>
                    <div class="price-row"><span class="price">$35</span><span class="old-price">$49</span><span class="discount">-29%</span></div>
                    <div class="meta"><span>Sold 7.1k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product-card">
                    <img src="../images/product-7.jpg" alt="Tennis Racket">
                    <div class="name">Carbon Tennis Racket</div>
                    <div class="price-row"><span class="price">$79</span><span class="old-price">$119</span><span class="discount">-34%</span></div>
                    <div class="meta"><span>Sold 2.8k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product-card">
                    <img src="../images/product-9.jpg" alt="Goalkeeper Gloves">
                    <div class="name">Goalkeeper Pro Gloves</div>
                    <div class="price-row"><span class="price">$39</span><span class="old-price">$59</span><span class="discount">-34%</span></div>
                    <div class="meta"><span>Sold 1.1k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product-card">
                    <img src="../images/product-11.jpg" alt="Dumbbells">
                    <div class="name">Adjustable Dumbbells Set</div>
                    <div class="price-row"><span class="price">$89</span><span class="old-price">$129</span><span class="discount">-31%</span></div>
                    <div class="meta"><span>Sold 4.9k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product-card">
                    <img src="../images/product-12.jpg" alt="Sports Watch">
                    <div class="name">GPS Sports Watch</div>
                    <div class="price-row"><span class="price">$119</span><span class="old-price">$179</span><span class="discount">-34%</span></div>
                    <div class="meta"><span>Sold 3.2k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product-card">
                    <img src="../images/product-1.jpg" alt="Running Shoes">
                    <div class="name">Trail Running Shoes</div>
                    <div class="price-row"><span class="price">$79</span><span class="old-price">$119</span><span class="discount">-34%</span></div>
                    <div class="meta"><span>Sold 6.5k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product-card">
                    <img src="../images/product-2.jpg" alt="Cycling Jersey">
                    <div class="name">Breathable Cycling Jersey</div>
                    <div class="price-row"><span class="price">$49</span><span class="old-price">$79</span><span class="discount">-38%</span></div>
                    <div class="meta"><span>Sold 1.9k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product-card">
                    <img src="../images/product-3.jpg" alt="Hiking Backpack">
                    <div class="name">30L Hiking Backpack</div>
                    <div class="price-row"><span class="price">$69</span><span class="old-price">$99</span><span class="discount">-30%</span></div>
                    <div class="meta"><span>Sold 2.2k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product-card">
                    <img src="../images/product-4.jpg" alt="Swim Goggles">
                    <div class="name">Anti-Fog Swim Goggles</div>
                    <div class="price-row"><span class="price">$19</span><span class="old-price">$29</span><span class="discount">-34%</span></div>
                    <div class="meta"><span>Sold 8.1k</span><span class="badge-free">Free Shipping</span></div>
                </div>
                <div class="card product-card">
                    <img src="../images/product-5.jpg" alt="Football">
                    <div class="name">Match-Ready Football</div>
                    <div class="price-row"><span class="price">$35</span><span class="old-price">$49</span><span class="discount">-29%</span></div>
                    <div class="meta"><span>Sold 9.4k</span><span class="badge-free">Free Shipping</span></div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
