<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}
include '../api/config.php';

/* ----------  HANDLE TOGGLE  ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle'])) {
    $id = (int)$_POST['id'];
    
    try {
        $stmt = $conn->prepare("UPDATE venues SET is_available = 1 - is_available WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            $_SESSION['success'] = 'Venue status updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update venue status.';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }
    
    header("Location: admin_venue_manager.php");
    exit;
}

/* ----------  FETCH VENUES  ---------- */
$venues = $conn->query("SELECT id, name, location, capacity, is_available, image FROM venues ORDER BY name")
               ->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$totalVenues = count($venues);
$availableVenues = count(array_filter($venues, fn($v) => $v['is_available']));
$unavailableVenues = $totalVenues - $availableVenues;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venue Management - VTU Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --vtu-blue: #1e40af;
            --vtu-blue-light: #3b82f6;
            --vtu-blue-dark: #1e3a8a;
            --vtu-green: #059669;
            --vtu-green-light: #10b981;
            --vtu-orange: #ea580c;
            --vtu-red: #dc2626;
            --vtu-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--gray-50) 0%, #e6fffa 100%);
            min-height: 100vh;
            line-height: 1.6;
            color: var(--gray-900);
        }

        /* Navigation Bar - Same as index.php */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--gray-200);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: var(--gray-900);
            font-weight: 700;
            font-size: 1.25rem;
        }

        .nav-logo img {
            width: 50px;
            height: 50px;
            border-radius: 12px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .nav-btn.btn-primary {
            background: var(--vtu-gradient);
            color: white;
        }

        .nav-btn.btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .nav-btn.btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .nav-btn.btn-secondary:hover {
            background: var(--gray-200);
            transform: translateY(-1px);
        }

        /* Main Content */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }

        /* Hero Section - Similar to index.php */
        .hero-section {
            background: var(--vtu-gradient);
            border-radius: 16px;
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 6s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 0;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.2rem;
            text-align: center;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--vtu-gradient);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.8rem;
            font-size: 1.2rem;
            color: white;
        }

        .stat-icon.total { background: var(--vtu-gradient); }
        .stat-icon.available { background: linear-gradient(135deg, var(--vtu-green) 0%, var(--vtu-green-light) 100%); }
        .stat-icon.unavailable { background: linear-gradient(135deg, var(--vtu-red) 0%, #ef4444 100%); }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.3rem;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--gray-600);
            font-weight: 500;
        }

        /* Venues Grid - Similar to index.php venue cards */
        .venues-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .venues-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--vtu-gradient);
        }

        .section-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            font-size: 0.9rem;
            color: var(--gray-600);
            max-width: 500px;
            margin: 0 auto;
        }

        .venues-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        /* Venue Cards - Same style as index.php */
        .venue-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border: 1px solid var(--gray-200);
            position: relative;
        }

        .venue-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .venue-card.unavailable {
            opacity: 0.7;
            filter: grayscale(0.3);
        }

        .venue-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-500);
            font-size: 2rem;
        }

        .venue-content {
            padding: 1rem;
        }

        .venue-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.8rem;
        }

        .venue-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .venue-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .venue-status.available {
            background: #dcfce7;
            color: var(--vtu-green);
        }

        .venue-status.unavailable {
            background: #fee2e2;
            color: var(--vtu-red);
        }

        .venue-details {
            margin-bottom: 1rem;
        }

        .venue-detail {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.3rem;
            color: var(--gray-600);
            font-size: 0.85rem;
        }

        .venue-detail i {
            width: 16px;
            color: var(--vtu-blue);
        }

        .venue-actions {
            display: flex;
            gap: 0.75rem;
        }

        .venue-btn {
            flex: 1;
            padding: 0.6rem 0.8rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .venue-btn-primary {
            background: var(--vtu-gradient);
            color: white;
        }

        .venue-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .venue-btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .venue-btn-secondary:hover {
            background: var(--gray-200);
            transform: translateY(-1px);
        }

        .venue-btn-danger {
            background: #fee2e2;
            color: var(--vtu-red);
            border: 1px solid #fecaca;
        }

        .venue-btn-danger:hover {
            background: #fecaca;
            transform: translateY(-1px);
        }

        .venue-btn-success {
            background: #dcfce7;
            color: var(--vtu-green);
            border: 1px solid #bbf7d0;
        }

        .venue-btn-success:hover {
            background: #bbf7d0;
            transform: translateY(-1px);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
        }

        .alert-success {
            background: #dcfce7;
            color: var(--vtu-green);
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: var(--vtu-red);
            border: 1px solid #fecaca;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-container {
                padding: 0 1rem;
                height: 70px;
            }

            .main-container {
                padding: 1rem;
            }

            .hero-section {
                padding: 2rem 1.5rem;
            }

            .hero-title {
                font-size: 2rem;
            }

            .venues-section {
                padding: 2rem 1.5rem;
            }

            .venues-grid {
                grid-template-columns: 1fr;
            }

            .venue-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <img src="images/vtu_logo.png" alt="VTU Logo">
                <div>
                    <div style="font-size: 1.25rem; font-weight: 700;">VTU Venues</div>
                    <div style="font-size: 0.75rem; color: var(--gray-600); font-weight: 500;">Admin Panel</div>
                </div>
            </a>
            
            <div class="nav-menu">
                <a href="admin_dashboard.php" class="nav-btn btn-secondary">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin_logout.php" class="nav-btn btn-primary">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">
                    <i class="fas fa-building"></i>
                    Venue Management
                </h1>
                <p class="hero-subtitle">
                    Manage venue availability and settings. Control which venues are available for booking and monitor their status in real-time.
                </p>
            </div>
        </section>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Section -->
        <section class="stats-section">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-number"><?= $totalVenues ?></div>
                <div class="stat-label">Total Venues</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon available">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?= $availableVenues ?></div>
                <div class="stat-label">Available for Booking</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon unavailable">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-number"><?= $unavailableVenues ?></div>
                <div class="stat-label">Currently Unavailable</div>
            </div>
        </section>

        <!-- Venues Section -->
        <section class="venues-section">
            <div class="section-header">
                <h2 class="section-title">Manage Venues</h2>
                <p class="section-subtitle">
                    Toggle venue availability and manage settings. Available venues can be booked by users, while unavailable venues are hidden from booking.
                </p>
            </div>

            <div class="venues-grid">
                <?php foreach ($venues as $venue): ?>
                    <div class="venue-card <?= $venue['is_available'] ? '' : 'unavailable' ?>">
                        <div class="venue-image">
                            <?php
                            $imagePath = 'images/' . ($venue['image'] ?? 'default_venue.jpg');
                            if (file_exists($imagePath)) {
                                echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($venue['name']) . '" style="width: 100%; height: 100%; object-fit: cover;">';
                            } else {
                                echo '<i class="fas fa-building"></i>';
                            }
                            ?>
                        </div>
                        
                        <div class="venue-content">
                            <div class="venue-header">
                                <h3 class="venue-name"><?= htmlspecialchars($venue['name']) ?></h3>
                                <span class="venue-status <?= $venue['is_available'] ? 'available' : 'unavailable' ?>">
                                    <?= $venue['is_available'] ? 'Available' : 'Unavailable' ?>
                                </span>
                            </div>
                            
                            <div class="venue-details">
                                <div class="venue-detail">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?= htmlspecialchars($venue['location']) ?></span>
                                </div>
                                <div class="venue-detail">
                                    <i class="fas fa-users"></i>
                                    <span>Capacity: <?= htmlspecialchars($venue['capacity']) ?> people</span>
                                </div>
                                <div class="venue-detail">
                                    <i class="fas fa-<?= $venue['is_available'] ? 'check-circle' : 'times-circle' ?>"></i>
                                    <span><?= $venue['is_available'] ? 'Ready for bookings' : 'Booking disabled' ?></span>
                                </div>
                            </div>
                            
                            <div class="venue-actions">
                                <form method="post" style="flex: 1;">
                                    <input type="hidden" name="id" value="<?= $venue['id'] ?>">
                                    <button type="submit" name="toggle" 
                                            class="venue-btn <?= $venue['is_available'] ? 'venue-btn-danger' : 'venue-btn-success' ?>"
                                            onclick="return confirm('Are you sure you want to <?= $venue['is_available'] ? 'disable' : 'enable' ?> this venue?');">
                                        <i class="fas fa-<?= $venue['is_available'] ? 'ban' : 'check' ?>"></i>
                                        <span><?= $venue['is_available'] ? 'Make Unavailable' : 'Make Available' ?></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on scroll
            const cards = document.querySelectorAll('.venue-card, .stat-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });
            
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>