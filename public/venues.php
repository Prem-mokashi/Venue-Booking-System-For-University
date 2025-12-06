<?php
session_start();
include '../api/config.php';

$user_logged_in = isset($_SESSION['user_id']);

/* ------ SHOW ALL VENUES ------ */
$stmt = $conn->prepare("SELECT * FROM venues ORDER BY name");
$stmt->execute();
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ------ GET VENUE STATISTICS ------ */
$stmt = $conn->prepare("SELECT COUNT(*) as total_venues FROM venues");
$stmt->execute();
$venue_stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Venues - VTU Venue Booking</title>
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
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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

        /* Navigation Bar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--gray-200);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
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

        /* Page Header */
        .page-header {
            background: var(--vtu-gradient);
            border-radius: 16px;
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: shimmer 6s ease-in-out infinite;
        }

        @keyframes shimmer {

            0%,
            100% {
                transform: rotate(0deg);
            }

            50% {
                transform: rotate(180deg);
            }
        }

        .page-header-content {
            position: relative;
            z-index: 1;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 0.95rem;
            opacity: 0.9;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Venues Grid */
        .venues-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            align-items: stretch;
        }

        .venue-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .venue-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-xl);
            border-color: var(--vtu-blue-light);
        }

        .venue-card.unavailable {
            opacity: 0.7;
            filter: grayscale(0.5);
            cursor: not-allowed;
            border-color: var(--vtu-red);
        }

        .venue-card.unavailable:hover {
            transform: none;
            box-shadow: var(--shadow);
            border-color: var(--vtu-red);
        }

        .venue-card.unavailable .venue-image {
            filter: grayscale(0.8);
        }

        .venue-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: var(--gray-100);
        }

        .venue-badges {
            position: absolute;
            top: 0.8rem;
            left: 0.8rem;
            display: flex;
            gap: 0.4rem;
        }

        .venue-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .badge-type {
            background: rgba(30, 64, 175, 0.9);
            color: white;
        }

        .badge-unavailable {
            background: rgba(220, 38, 38, 0.9);
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .venue-content {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .venue-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }

        .venue-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }

        .venue-details {
            margin-bottom: 1rem;
            flex-grow: 1;
        }

        .venue-capacity {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }

        .venue-amenities {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .amenity-tag {
            background: var(--gray-100);
            color: var(--gray-700);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .venue-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: auto;
        }

        .venue-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            min-height: 44px;
        }

        .venue-btn-primary {
            background: var(--vtu-gradient);
            color: white;
        }

        .venue-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
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

        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--gray-100);
            color: var(--gray-700);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .back-btn:hover {
            background: var(--gray-200);
            transform: translateY(-1px);
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

            .page-header {
                padding: 1.5rem 1rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .venues-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .venue-content {
                padding: 1rem;
            }

            .venue-actions {
                flex-direction: column;
                gap: 0.5rem;
            }

            .venue-btn {
                font-size: 0.9rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .venues-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1025px) {
            .venues-grid {
                grid-template-columns: repeat(3, 1fr);
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
                    <div style="font-size: 0.75rem; color: var(--gray-600); font-weight: 500;">Booking System</div>
                </div>
            </a>

            <div class="nav-menu">
                <?php if ($user_logged_in): ?>
                    <a href="user_dashboard.php" class="nav-btn btn-secondary">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="index.php#calendar" onclick="setTimeout(() => openCalendarModal(), 100)"
                        class="nav-btn btn-secondary">
                        <i class="fas fa-calendar"></i>
                        <span>Calendar</span>
                    </a>
                    <a href="logout.php" class="nav-btn btn-primary"></a>
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="nav-btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                    <a href="register_vtu.php" class="nav-btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        <span>Register</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Back Button -->
        <a href="index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>

        <!-- Page Header -->
        <section class="page-header">
            <div class="page-header-content">
                <h1 class="page-title">
                    <i class="fas fa-building"></i>
                    All Venues
                </h1>
                <p class="page-subtitle">
                    Browse all available venues and find the perfect space for your event.
                    Click on any venue to view details and make a booking.
                </p>
            </div>
        </section>

        <!-- Venues Grid -->
        <div class="venues-grid">
            <?php foreach ($venues as $venue): ?>
                <?php $isAvailable = $venue['is_available']; ?>
                <div class="venue-card <?= !$isAvailable ? 'unavailable' : '' ?>" <?= $isAvailable ? "onclick='window.location.href=\"venue_details.php?id=" . $venue['id'] . "\"'" : '' ?>>
                    <?php
                    $imagePath = 'images/' . ($venue['image'] ?? 'default_venue.jpg');
                    if (!file_exists($imagePath)) {
                        $imagePath = 'https://via.placeholder.com/400x240/1e40af/ffffff?text=' . urlencode($venue['name']);
                    }
                    ?>
                    <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($venue['name']) ?>" class="venue-image">

                    <div class="venue-badges">
                        <?php if (!$isAvailable): ?>
                            <div class="venue-badge badge-unavailable">
                                <i class="fas fa-ban"></i>
                                Unavailable
                            </div>
                        <?php endif; ?>
                        <div class="venue-badge badge-type">
                            <?= ucfirst($venue['type'] ?? 'Venue') ?>
                        </div>
                    </div>

                    <div class="venue-content">
                        <h3 class="venue-title"><?= htmlspecialchars($venue['name']) ?></h3>

                        <div class="venue-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($venue['location']) ?></span>
                        </div>

                        <div class="venue-details">
                            <div class="venue-capacity">
                                <i class="fas fa-users"></i>
                                <span><?= htmlspecialchars($venue['capacity']) ?> people</span>
                            </div>
                        </div>

                        <div class="venue-amenities">
                            <div class="amenity-tag">WiFi</div>
                            <div class="amenity-tag">AC</div>
                            <div class="amenity-tag">Projector</div>
                        </div>

                        <div class="venue-actions">
                            <a href="venue_details.php?id=<?= $venue['id'] ?>" onclick="event.stopPropagation()"
                                class="venue-btn venue-btn-secondary">
                                <i class="fas fa-eye"></i>
                                View Details
                            </a>
                            <?php if ($isAvailable): ?>
                                <a href="book_venue.php?id=<?= $venue['id'] ?>" onclick="event.stopPropagation()"
                                    class="venue-btn venue-btn-primary">
                                    <i class="fas fa-calendar-plus"></i>
                                    Book Now
                                </a>
                            <?php else: ?>
                                <span class="venue-btn venue-btn-secondary" style="opacity: 0.6; cursor: not-allowed;">
                                    <i class="fas fa-ban"></i>
                                    Unavailable
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal (same as index.php) -->
    <div id="venueModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
            <h3 id="modalVenueName"></h3>
            <img id="modalVenueImage" src="" alt="Venue Image"
                style="width: 100%; height: 200px; object-fit: cover; border-radius: 12px; margin: 1rem 0;">
            <p id="modalVenueDesc"></p>
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                <a id="bookLink" href="#" class="nav-btn btn-primary" style="flex: 1; justify-content: center;">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Book Now</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality (same as index.php)
        function openModal(venue) {
            document.getElementById('modalVenueName').textContent = venue.name;
            document.getElementById('modalVenueDesc').textContent = venue.description || 'Experience this premium venue with modern amenities and professional setup.';

            const imagePath = venue.image ? 'images/' + venue.image : 'https://via.placeholder.com/400x200/1e40af/ffffff?text=' + encodeURIComponent(venue.name);
            document.getElementById('modalVenueImage').src = imagePath;

            const bookLink = document.getElementById('bookLink');
            const isAvailable = venue.is_available == 1;

            if (isAvailable) {
                const bookingUrl = 'book_venue.php?id=' + venue.id;
                bookLink.href = bookingUrl;
                bookLink.classList.remove('btn-secondary');
                bookLink.classList.add('btn-primary');
                bookLink.innerHTML = '<i class="fas fa-calendar-plus"></i><span>Book Now</span>';
                bookLink.onclick = function (e) {
                    window.location.href = bookingUrl;
                };
            } else {
                bookLink.href = '#';
                bookLink.classList.remove('btn-primary');
                bookLink.classList.add('btn-secondary');
                bookLink.innerHTML = '<i class="fas fa-ban"></i><span>Unavailable</span>';
                bookLink.style.opacity = '0.6';
                bookLink.style.cursor = 'not-allowed';
                bookLink.onclick = function (e) {
                    e.preventDefault();
                    return false;
                };
            }

            document.getElementById('venueModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('venueModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('venueModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            box-shadow: var(--shadow-xl);
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--gray-100);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: var(--gray-200);
            transform: scale(1.1);
        }
    </style>
</body>

</html>