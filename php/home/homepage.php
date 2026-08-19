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
            --primary: #588157;
            --primary-dark: #476644;
            --bg: #e7ebdd;
            --card: #f6f3e8;
            --text: #2f3f33;
            --muted: #6c7a63;
            --success: #7db84a;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* Topbar */
        .topbar {
            background: var(--card);
            border-bottom: 1px solid rgba(88, 129, 87, 0.15);
        }

        .topbar-inner {
            max-width: 1300px;
            margin: 0 auto;
            padding: 10px 16px;
            display: grid;
            grid-template-columns: 220px 1fr auto;
            gap: 12px;
            align-items: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 20px;
        }

        .brand img {
            height: 32px;
        }

        .search {
            display: flex;
            align-items: center;
            border: 1px solid rgba(88, 129, 87, 0.25);
            border-radius: 20px;
            padding: 6px 10px;
            background: #fff;
        }

        .search input {
            flex: 1;
            border: none;
            outline: none;
            padding: 6px 8px;
            font-size: 14px;
        }

        .search button {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 14px;
            cursor: pointer;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 14px;
        }

        .action-icon {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--muted);
        }

        .action-icon .bi {
            font-size: 18px;
        }

        /* Category bar */
        .category-bar {
            background: var(--card);
            border-bottom: 1px solid #eaeaea;
        }

        .category-inner {
            max-width: 1300px;
            margin: 0 auto;
            padding: 8px 16px;
            display: flex;
            gap: 12px;
            overflow-x: auto;
        }

        .chip {
            background: #fff;
            border: 1px solid rgba(88, 129, 87, 0.2);
            border-radius: 20px;
            padding: 6px 12px;
            white-space: nowrap;
            font-size: 13px;
            cursor: pointer;
            color: var(--text);
        }

        .chip.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        /* Main content */
        .content {
            max-width: 1300px;
            margin: 16px auto;
            padding: 0 16px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        /* Hero banners */
        .hero {
            display: grid;
            gap: 12px;
            grid-template-columns: 2fr 1fr;
        }

        .hero img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* Section header */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-title {
            font-weight: 700;
            font-size: 18px;
        }

        .section-actions a {
            color: var(--primary);
            font-size: 14px;
        }

        /* Flash deals */
        .flash-list {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: 200px;
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .card {
            background: var(--card);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid #eee;
        }

        .product {
            display: grid;
            gap: 8px;
            padding: 10px;
        }

        .product img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 6px;
        }

        .product .name {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
            min-height: 36px;
        }

        .price-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .price {
            color: var(--primary);
            font-weight: 700;
        }

        .old-price {
            text-decoration: line-through;
            color: var(--muted);
            font-size: 12px;
        }

        .discount {
            background: #ffeee9;
            color: var(--primary);
            font-weight: 700;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--muted);
            font-size: 12px;
        }

        .badge-free {
            color: var(--success);
            font-weight: 600;
        }

        /* Product grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
        }

        @media (max-width: 1200px) {
            .grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        @media (max-width: 992px) {
            .grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .topbar-inner {
                grid-template-columns: 1fr;
            }

            .actions {
                justify-content: space-between;
            }
        }

        @media (max-width: 480px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .product-card {
            display: grid;
            gap: 8px;
            padding: 10px;
        }

        .product-card img {
            width: 100%;
            height: 170px;
            object-fit: cover;
            border-radius: 6px;
        }

        .product-card .name {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
            min-height: 36px;
        }

        .product-card .price-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .product-card .price {
            color: var(--primary);
            font-weight: 700;
        }

        .product-card .old-price {
            text-decoration: line-through;
            color: var(--muted);
            font-size: 12px;
        }

        .product-card .discount {
            background: rgba(88, 129, 87, 0.12);
            color: var(--primary);
            font-weight: 700;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .product-card .meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--muted);
            font-size: 12px;
        }

        .product-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
            transition: all 0.2s ease;
        }
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
                <a class="action-icon" href="javascript:void(0)" onclick="openUserLogsModal()" style="color:var(--primary); font-weight:600;"><i class="bi bi-clock-history"></i> My Activity Logs</a>
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
            <img src="https://images.unsplash.com/photo-1599058921721-4832de22c15f?auto=format&fit=crop&w=1200&q=80" alt="Football promotion">
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
    <!-- User Activity Logs Modal -->
    <div id="userLogsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; max-width:850px; width:95%; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.15); max-height:90vh; overflow-y:auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid #e2e8f0; padding-bottom:12px;">
                <h3 style="font-size:18px; font-weight:700; color:#2f3f33; margin:0; display:flex; align-items:center; gap:8px;">
                    <i class="bi bi-clock-history" style="color:var(--primary);"></i> My Login & Activity History
                </h3>
                <button type="button" onclick="closeUserLogsModal()" style="border:none; background:none; font-size:24px; cursor:pointer; color:#64748b; line-height:1;">&times;</button>
            </div>

            <!-- Filter Controls -->
            <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:16px; background:#f8fafc; padding:12px; border-radius:8px; border:1px solid #e2e8f0;">
                <input type="text" id="userLogSearch" placeholder="Search action or details..." style="padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; flex:1; min-width:180px;" />
                
                <select id="userLogMonth" style="padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                    <option value="all" selected>All Months</option>
                    <option value="1">January</option>
                    <option value="2">February</option>
                    <option value="3">March</option>
                    <option value="4">April</option>
                    <option value="5">May</option>
                    <option value="6">June</option>
                    <option value="7">July</option>
                    <option value="8">August</option>
                    <option value="9">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>

                <select id="userLogYear" style="padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                    <option value="all" selected>All Years</option>
                    <option value="2025">2025</option>
                    <option value="2026">2026</option>
                    <option value="2027">2027</option>
                </select>

                <input type="date" id="userLogStartDate" title="From Date" style="padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;" />
                <span style="color:#64748b; font-size:12px;">to</span>
                <input type="date" id="userLogEndDate" title="To Date" style="padding:6px 8px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;" />

                <button type="button" id="userLogClearBtn" style="padding:6px 12px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-size:12px; cursor:pointer;">
                    Clear
                </button>
            </div>

            <!-- Table -->
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                    <thead>
                        <tr style="background:#f1f5f9; color:#475569; border-bottom:2px solid #cbd5e1;">
                            <th style="padding:10px 12px;">ID No</th>
                            <th style="padding:10px 12px;">Full Name</th>
                            <th style="padding:10px 12px;">Action</th>
                            <th style="padding:10px 12px;">Details</th>
                            <th style="padding:10px 12px;">Time In</th>
                            <th style="padding:10px 12px;">Time Out</th>
                        </tr>
                    </thead>
                    <tbody id="userLogsTableBody">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; font-size:13px; color:#64748b;">
                <div id="userLogPaginationInfo">Showing 0 to 0 of 0 entries</div>
                <div id="userLogPaginationControls" style="display:flex; gap:4px;"></div>
            </div>
        </div>
    </div>

    <script>
        // Disable back browser button
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };

        // User Activity Logs Modal Logic
        let userLogPage = 1;
        const userLogsModal = document.getElementById("userLogsModal");
        const userLogSearch = document.getElementById("userLogSearch");
        const userLogMonth = document.getElementById("userLogMonth");
        const userLogYear = document.getElementById("userLogYear");
        const userLogStartDate = document.getElementById("userLogStartDate");
        const userLogEndDate = document.getElementById("userLogEndDate");
        const userLogClearBtn = document.getElementById("userLogClearBtn");
        const userLogsTableBody = document.getElementById("userLogsTableBody");
        const userLogPaginationInfo = document.getElementById("userLogPaginationInfo");
        const userLogPaginationControls = document.getElementById("userLogPaginationControls");

        window.openUserLogsModal = function() {
            userLogsModal.style.display = "flex";
            userLogPage = 1;
            fetchUserLogs();
        };

        window.closeUserLogsModal = function() {
            userLogsModal.style.display = "none";
        };

        function escapeHtml(str) {
            if (!str) return "";
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function fetchUserLogs() {
            const search = userLogSearch.value.trim();
            const month = userLogMonth.value;
            const year = userLogYear.value;
            const startDate = userLogStartDate.value;
            const endDate = userLogEndDate.value;

            userLogsTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:24px; color:#64748b;">Loading logs...</td></tr>`;

            const url = `../auth/index.php?action=getMyLogs&search=${encodeURIComponent(search)}&month=${encodeURIComponent(month)}&year=${encodeURIComponent(year)}&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}&page=${userLogPage}&limit=10`;

            fetch(url)
                .then(res => res.json())
                .then(res => {
                    if (!res.success) {
                        userLogsTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:#ef4444;">${escapeHtml(res.message || 'Failed to load logs.')}</td></tr>`;
                        return;
                    }

                    renderUserLogsTable(res.data || []);
                    renderUserLogsPagination(res.totalRecords, res.totalPages, res.currentPage, res.limit);
                })
                .catch(err => {
                    console.error("Error fetching user logs:", err);
                    userLogsTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:#ef4444;">Error connecting to server.</td></tr>`;
                });
        }

        function renderUserLogsTable(data) {
            if (!data || data.length === 0) {
                userLogsTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:24px; color:#64748b;">No log records found.</td></tr>`;
                return;
            }

            let html = "";
            data.forEach(row => {
                const idNo = escapeHtml(row.id_number || '-');
                const fullName = escapeHtml(row.full_name || row.username || '-');
                const action = escapeHtml(row.action || '-');
                const details = escapeHtml(row.details || '-');
                const timeIn = escapeHtml(row.time_in || '-');
                const timeOut = escapeHtml(row.time_out || 'NULL');

                html += `<tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:10px 12px;"><strong>${idNo}</strong></td>
                    <td style="padding:10px 12px;">${fullName}</td>
                    <td style="padding:10px 12px;"><span style="background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:6px; font-weight:600; font-size:12px;">${action}</span></td>
                    <td style="padding:10px 12px; color:#475569;">${details}</td>
                    <td style="padding:10px 12px;">${timeIn}</td>
                    <td style="padding:10px 12px;">${timeOut}</td>
                </tr>`;
            });
            userLogsTableBody.innerHTML = html;
        }

        function renderUserLogsPagination(totalRecords, totalPages, curPage, limit) {
            const start = totalRecords > 0 ? (curPage - 1) * limit + 1 : 0;
            const end = Math.min(curPage * limit, totalRecords);
            userLogPaginationInfo.textContent = `Showing ${start} to ${end} of ${totalRecords} entries`;

            let controlsHtml = "";
            if (totalPages > 1) {
                if (curPage > 1) {
                    controlsHtml += `<button type="button" onclick="goToUserLogPage(${curPage - 1})" style="padding:4px 10px; border:1px solid #cbd5e1; background:#fff; border-radius:4px; cursor:pointer;">Prev</button>`;
                }
                for (let i = 1; i <= totalPages; i++) {
                    if (i === 1 || i === totalPages || (i >= curPage - 1 && i <= curPage + 1)) {
                        const active = i === curPage ? "background:var(--primary); color:#fff; font-weight:700;" : "background:#fff;";
                        controlsHtml += `<button type="button" onclick="goToUserLogPage(${i})" style="padding:4px 10px; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer; ${active}">${i}</button>`;
                    }
                }
                if (curPage < totalPages) {
                    controlsHtml += `<button type="button" onclick="goToUserLogPage(${curPage + 1})" style="padding:4px 10px; border:1px solid #cbd5e1; background:#fff; border-radius:4px; cursor:pointer;">Next</button>`;
                }
            }
            userLogPaginationControls.innerHTML = controlsHtml;
        }

        window.goToUserLogPage = function(page) {
            userLogPage = page;
            fetchUserLogs();
        };

        // Filter listeners
        let userLogDebounce;
        userLogSearch.addEventListener("input", function() {
            clearTimeout(userLogDebounce);
            userLogDebounce = setTimeout(() => {
                userLogPage = 1;
                fetchUserLogs();
            }, 300);
        });

        userLogMonth.addEventListener("change", function() { userLogPage = 1; fetchUserLogs(); });
        userLogYear.addEventListener("change", function() { userLogPage = 1; fetchUserLogs(); });
        userLogStartDate.addEventListener("change", function() { userLogPage = 1; fetchUserLogs(); });
        userLogEndDate.addEventListener("change", function() { userLogPage = 1; fetchUserLogs(); });

        userLogClearBtn.addEventListener("click", function() {
            userLogSearch.value = "";
            userLogMonth.value = "all";
            userLogYear.value = "all";
            userLogStartDate.value = "";
            userLogEndDate.value = "";
            userLogPage = 1;
            fetchUserLogs();
        });
    </script>
</body>

</html>