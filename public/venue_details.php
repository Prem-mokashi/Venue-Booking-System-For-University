<?php
session_start();
include '../api/config.php';

$user_logged_in = isset($_SESSION['user_id']);

// Get venue ID from URL parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: venues.php");
    exit;
}

$venue_id = $_GET['id'];

// Fetch venue details
$stmt = $conn->prepare("SELECT * FROM venues WHERE id = :id");
$stmt->bindParam(':id', $venue_id, PDO::PARAM_INT);
$stmt->execute();
$venue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venue) {
    header("Location: venues.php");
    exit;
}

// Get venue statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total_bookings FROM bookings WHERE venue_id = :id AND status = 'approved'");
$stmt->bindParam(':id', $venue_id, PDO::PARAM_INT);
$stmt->execute();
$venue_stats = $stmt->fetch();

// Check if venue is available today
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as today_bookings FROM bookings WHERE venue_id = :id AND event_date = :today AND status IN ('approved', 'pending')");
$stmt->bindParam(':id', $venue_id, PDO::PARAM_INT);
$stmt->bindParam(':today', $today);
$stmt->execute();
$today_availability = $stmt->fetch();
$available_today = $today_availability['today_bookings'] == 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($venue['name']) ?> - VTU Venue Booking</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --vtu-blue: #2563eb;
            --vtu-blue-dark: #1d4ed8;
            --vtu-blue-light: #3b82f6;
            --vtu-green: #16a34a;
            --vtu-green-light: #22c55e;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--gray-900);
            background: var(--gray-50);
        }

        /* Header Navigation */
        .header-nav {
            background: white;
            box-shadow: var(--shadow-sm);
            padding: 16px 0;
            margin-bottom: 32px;
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--vtu-blue);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
            min-height: 44px;
        }

        .back-link:hover {
            color: var(--vtu-blue-dark);
        }

        .back-link i {
            font-size: 20px;
        }

        /* Main Layout */
        .main-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 16px 32px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
        }

        .content-section {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .sidebar {
            position: sticky;
            top: 32px;
            height: fit-content;
        }

        /* Image Gallery */
        .image-gallery {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .hero-image-container {
            position: relative;
            width: 100%;
            height: 320px;
            overflow: hidden;
        }

        .hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .type-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--vtu-blue);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            text-transform: capitalize;
            font-size: 14px;
        }

        .thumbnail-gallery {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            padding: 16px;
        }

        .thumbnail {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.2s ease;
            border: 2px solid transparent;
        }

        .thumbnail:hover {
            opacity: 0.8;
        }

        .thumbnail.active {
            border-color: var(--vtu-blue);
        }

        /* Venue Info Section */
        .venue-info {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-md);
            padding: 24px;
        }

        .venue-title {
            font-size: 30px;
            font-weight: bold;
            color: var(--gray-900);
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .venue-description {
            font-size: 16px;
            color: var(--gray-600);
            line-height: 1.625;
            margin-bottom: 24px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .metric-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-600);
            font-size: 16px;
        }

        .metric-item i {
            color: var(--vtu-blue);
            font-size: 20px;
            width: 20px;
        }

        /* Amenities Section */
        .amenities-section {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-md);
            padding: 24px;
        }

        .section-title {
            font-size: 24px;
            font-weight: bold;
            color: var(--gray-900);
            margin-bottom: 24px;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .amenity-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }

        .amenity-card:hover {
            background: var(--gray-100);
        }

        .amenity-card i {
            color: var(--vtu-blue);
            font-size: 20px;
            width: 20px;
        }

        .amenity-info h4 {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 2px;
        }

        .amenity-info p {
            font-size: 14px;
            color: var(--gray-600);
        }

        /* Technical Specifications */
        .specs-section {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-md);
            padding: 24px;
        }

        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .spec-item {
            border-bottom: 1px solid var(--gray-200);
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .spec-key {
            font-weight: 600;
            color: var(--gray-900);
            font-size: 16px;
            margin-bottom: 4px;
        }

        .spec-value {
            color: var(--gray-600);
            font-size: 16px;
            line-height: 1.5;
        }

        /* Booking Guidelines */
        .guidelines-section {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-md);
            padding: 24px;
        }

        .guidelines-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .guideline-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .bullet-point {
            width: 8px;
            height: 8px;
            background: var(--vtu-blue);
            border-radius: 50%;
            margin-top: 8px;
            flex-shrink: 0;
        }

        .guideline-text {
            color: var(--gray-700);
            font-size: 16px;
            line-height: 1.5;
        }

        /* Sidebar Styling */
        .booking-sidebar {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-md);
            padding: 24px;
        }

        .pricing-display {
            text-align: center;
            margin-bottom: 24px;
        }

        .main-price {
            font-size: 30px;
            font-weight: bold;
            color: var(--vtu-blue);
            margin-bottom: 8px;
        }

        .rate-suffix {
            font-size: 18px;
            color: var(--gray-600);
            font-weight: normal;
        }

        .price-description {
            font-size: 16px;
            color: var(--gray-600);
            margin-top: 8px;
        }

        .quick-info-cards {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 24px;
        }

        .info-card {
            padding: 16px;
            border-radius: 8px;
        }

        .info-card.booking {
            background: #dbeafe;
        }

        .info-card.availability {
            background: #dcfce7;
        }

        .info-card.unavailable {
            background: #fee2e2;
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .info-card.booking .info-card-header {
            color: var(--vtu-blue);
        }

        .info-card.availability .info-card-header {
            color: var(--vtu-green);
        }

        .info-card.unavailable .info-card-header {
            color: #dc2626;
        }

        .info-card-title {
            font-weight: 600;
        }

        .info-card-description {
            font-size: 14px;
        }

        .info-card.booking .info-card-description {
            color: #1d4ed8;
        }

        .info-card.availability .info-card-description {
            color: #15803d;
        }

        .info-card.unavailable .info-card-description {
            color: #b91c1c;
        }

        .book-button {
            width: 100%;
            background: var(--vtu-blue);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            display: block;
            text-decoration: none;
            margin-bottom: 16px;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .book-button:hover {
            background: var(--vtu-blue-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
            color: white;
        }

        .book-button:disabled {
            background: var(--gray-600);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .help-text {
            text-align: center;
            font-size: 14px;
            color: var(--gray-600);
            margin-bottom: 8px;
        }

        .contact-link {
            color: var(--vtu-blue);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .contact-link:hover {
            color: var(--vtu-blue-dark);
        }

        .contact-info {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
        }

        .contact-header {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 12px;
        }

        .contact-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 14px;
            color: var(--gray-600);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            .sidebar {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 0 16px 24px;
            }

            .hero-image-container {
                height: 256px;
            }

            .venue-title {
                font-size: 24px;
            }

            .section-title {
                font-size: 20px;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .amenities-grid {
                grid-template-columns: 1fr;
            }

            .specs-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading States */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Focus States for Accessibility */
        .back-link:focus,
        .thumbnail:focus,
        .book-button:focus,
        .contact-link:focus {
            outline: 2px solid var(--vtu-blue);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <!-- Header Navigation -->
    <header class="header-nav">
        <div class="nav-container">
            <a href="venues.php" class="back-link" aria-label="Back to venues list">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span>Back to Venues</span>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <!-- Content Section -->
        <div class="content-section">
            <!-- Image Gallery -->
            <section class="image-gallery" role="region" aria-label="Venue photos">
                <div class="hero-image-container">
                    <?php
                    $imagePath = 'images/' . ($venue['image'] ?? 'default_venue.jpg');
                    if (!file_exists($imagePath)) {
                        $imagePath = 'https://via.placeholder.com/800x320/2563eb/ffffff?text=' . urlencode($venue['name']);
                    }
                    ?>
                    <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($venue['name']) ?> - main view" class="hero-image" id="mainImage">
                    <div class="type-badge"><?= ucfirst($venue['type'] ?? 'Venue') ?></div>
                </div>
                
                <div class="thumbnail-gallery">
                    <?php for($i = 1; $i <= 2; $i++): ?>
                        <?php
                        $baseImageName = pathinfo($venue['image'] ?? 'default_venue.jpg', PATHINFO_FILENAME);
                        
                        if ($i === 1) {
                            $thumbPath = 'images/' . ($venue['image'] ?? 'default_venue.jpg');
                        } else {
                            // Try different extensions for the second image
                            $possibleExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                            $thumbPath = '';
                            
                            foreach ($possibleExtensions as $ext) {
                                $testPath = 'images/' . $baseImageName . '_' . $i . '.' . $ext;
                                if (file_exists($testPath)) {
                                    $thumbPath = $testPath;
                                    break;
                                }
                            }
                            
                            // If no image found, use placeholder
                            if (empty($thumbPath)) {
                                $thumbPath = 'https://via.placeholder.com/200x80/2563eb/ffffff?text=View+' . $i;
                            }
                        }
                        
                        // Final check for first image
                        if ($i === 1 && !file_exists($thumbPath)) {
                            $thumbPath = 'https://via.placeholder.com/200x80/2563eb/ffffff?text=View+' . $i;
                        }
                        ?>
                        <img src="<?= $thumbPath ?>" alt="<?= htmlspecialchars($venue['name']) ?> - view <?= $i ?>" 
                             class="thumbnail <?= $i === 1 ? 'active' : '' ?>" 
                             onclick="changeMainImage('<?= $thumbPath ?>', <?= $i ?>)"
                             tabindex="0"
                             onkeydown="handleThumbnailKeydown(event, '<?= $thumbPath ?>', <?= $i ?>)">
                    <?php endfor; ?>
                </div>
            </section>

            <!-- Venue Information -->
            <section class="venue-info">
                <h1 class="venue-title"><?= htmlspecialchars($venue['name']) ?></h1>
                <p class="venue-description">
                    <?= htmlspecialchars($venue['description'] ?? 'Experience this premium venue with modern amenities and professional setup. Perfect for conferences, seminars, cultural events, and academic gatherings. Our state-of-the-art facility ensures your event runs smoothly with all necessary technical support and comfortable seating arrangements.') ?>
                </p>

                <div class="metrics-grid">
                    <div class="metric-item" id="capacity-info">
                        <i class="fas fa-users" aria-hidden="true"></i>
                        <span><strong>Capacity:</strong> <?= htmlspecialchars($venue['capacity']) ?> people</span>
                    </div>
                    <div class="metric-item" id="location-info">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <span><strong>Location:</strong> <?= htmlspecialchars($venue['location']) ?></span>
                    </div>
                    <div class="metric-item" id="booking-info">
                        <i class="fas fa-calendar-check" aria-hidden="true"></i>
                        <span><strong>Booking:</strong> Available for VTU Community</span>
                    </div>
                </div>
            </section>

            <!-- Amenities & Features -->
            <section class="amenities-section">
                <h2 class="section-title">Amenities & Features</h2>
                <div class="amenities-grid" role="list">
                    <div class="amenity-card" role="listitem">
                        <i class="fas fa-video" aria-hidden="true"></i>
                        <div class="amenity-info">
                            <h4>Digital Projector</h4>
                            <p>4K projection capability</p>
                        </div>
                    </div>
                    <div class="amenity-card" role="listitem">
                        <i class="fas fa-volume-up" aria-hidden="true"></i>
                        <div class="amenity-info">
                            <h4>Sound System</h4>
                            <p>Professional audio setup</p>
                        </div>
                    </div>
                    <div class="amenity-card" role="listitem">
                        <i class="fas fa-lightbulb" aria-hidden="true"></i>
                        <div class="amenity-info">
                            <h4>Stage Lighting</h4>
                            <p>Professional lighting setup</p>
                        </div>
                    </div>
                    <div class="amenity-card" role="listitem">
                        <i class="fas fa-snowflake" aria-hidden="true"></i>
                        <div class="amenity-info">
                            <h4>Central AC</h4>
                            <p>Climate controlled environment</p>
                        </div>
                    </div>
                    <div class="amenity-card" role="listitem">
                        <i class="fas fa-wifi" aria-hidden="true"></i>
                        <div class="amenity-info">
                            <h4>High-Speed WiFi</h4>
                            <p>Gigabit internet access</p>
                        </div>
                    </div>
                    <div class="amenity-card" role="listitem">
                        <i class="fas fa-broadcast-tower" aria-hidden="true"></i>
                        <div class="amenity-info">
                            <h4>Live Streaming Setup</h4>
                            <p>Built-in streaming equipment</p>
                        </div>
                    </div>
                    <div class="amenity-card" role="listitem">
                        <i class="fas fa-car" aria-hidden="true"></i>
                        <div class="amenity-info">
                            <h4>Parking Available</h4>
                            <p>100+ vehicle capacity</p>
                        </div>
                    </div>
                    <div class="amenity-card" role="listitem">
                        <i class="fas fa-door-open" aria-hidden="true"></i>
                        <div class="amenity-info">
                            <h4>Green Rooms</h4>
                            <p>Preparation spaces available</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Technical Specifications -->
            <section class="specs-section">
                <h2 class="section-title">Technical Specifications</h2>
                <div class="specs-grid" role="table">
                    <div class="spec-item">
                        <div class="spec-key">Seating Arrangement</div>
                        <div class="spec-value">Theater Style (Fixed seating)</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-key">Stage Size</div>
                        <div class="spec-value">40ft × 20ft elevated platform</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-key">Audio System</div>
                        <div class="spec-value">Professional sound with wireless mics</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-key">Video Equipment</div>
                        <div class="spec-value">4K Projection System with backup</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-key">Lighting System</div>
                        <div class="spec-value">Professional stage lighting with presets</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-key">HVAC System</div>
                        <div class="spec-value">Central Air Conditioning (22°C maintained)</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-key">Internet Connectivity</div>
                        <div class="spec-value">Gigabit WiFi + Ethernet ports</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-key">Accessibility Features</div>
                        <div class="spec-value">Wheelchair accessible with ramps</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-key">Power Supply</div>
                        <div class="spec-value">Dedicated power lines + backup generator</div>
                    </div>
                    <div class="spec-item">
                        <div class="spec-key">Security Features</div>
                        <div class="spec-value">CCTV monitoring + access control</div>
                    </div>
                </div>
            </section>


        </div>

        <!-- Booking Sidebar -->
        <aside class="sidebar" role="complementary">
            <div class="booking-sidebar">
                <!-- Booking Notice -->
                <div class="pricing-display">
                    <div class="main-price" style="color: var(--vtu-blue);">
                        BOOK NOW
                    </div>
                    <div class="price-description">Available for VTU Community</div>
                </div>

                <!-- Quick Info Cards -->
                <div class="quick-info-cards">
                    <div class="info-card booking">
                        <div class="info-card-header">
                            <i class="fas fa-calendar-plus" aria-hidden="true"></i>
                            <span class="info-card-title">Quick Booking</span>
                        </div>
                        <div class="info-card-description">Book this venue instantly for your next event</div>
                    </div>

                    <?php if ($available_today): ?>
                        <div class="info-card availability">
                            <div class="info-card-header">
                                <i class="fas fa-check-circle" aria-hidden="true"></i>
                                <span class="info-card-title">Available Today</span>
                            </div>
                            <div class="info-card-description">Check real-time availability</div>
                        </div>
                    <?php else: ?>
                        <div class="info-card unavailable">
                            <div class="info-card-header">
                                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                                <span class="info-card-title">Booked Today</span>
                            </div>
                            <div class="info-card-description">Check other available dates</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <?php if ($venue['is_available']): ?>
                    <?php if ($user_logged_in): ?>
                        <a href="book_venue.php?id=<?= $venue['id'] ?>" class="book-button">
                            <i class="fas fa-calendar-plus" aria-hidden="true"></i>
                            Book This Venue
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="book-button">
                            <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                            Login to Book
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="book-button" disabled>
                        <i class="fas fa-ban" aria-hidden="true"></i>
                        Currently Unavailable
                    </button>
                <?php endif; ?>

                <div class="help-text">Need help with booking?</div>
                <div style="text-align: center;">
                    <a href="tel:+918312419831" class="contact-link">Call us now</a>
                </div>

                <!-- Contact Information -->
                <div class="contact-info">
                    <div class="contact-header">Contact Information</div>
                    <div class="contact-details">
                        <div>Venue Booking Office</div>
                        <div>VTU Administrative Block</div>
                        <div>Belagavi - 590018</div>
                        <div>Email: venues@vtu.ac.in</div>
                    </div>
                </div>
            </div>
        </aside>
    </main>

    <script>
        // Image Gallery Functionality
        function changeMainImage(imageSrc, index) {
            const mainImage = document.getElementById('mainImage');
            const thumbnails = document.querySelectorAll('.thumbnail');
            
            // Update main image
            mainImage.src = imageSrc;
            mainImage.alt = `<?= htmlspecialchars($venue['name']) ?> - view ${index + 1}`;
            
            // Update active thumbnail
            thumbnails.forEach(thumb => thumb.classList.remove('active'));
            thumbnails[index].classList.add('active');
        }

        // Keyboard navigation for thumbnails
        function handleThumbnailKeydown(event, imageSrc, index) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                changeMainImage(imageSrc, index);
            }
        }

        // Smooth scroll behavior for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Loading state management
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                if (!img.complete) {
                    img.classList.add('loading-skeleton');
                    img.addEventListener('load', function() {
                        this.classList.remove('loading-skeleton');
                    });
                }
            });
        });

        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe sections for animation
        document.querySelectorAll('.venue-info, .amenities-section, .specs-section, .guidelines-section').forEach(section => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(section);
        });
    </script>
</body>
</html>