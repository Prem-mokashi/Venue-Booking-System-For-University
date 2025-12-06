<?php
session_start();
include '../api/config.php';

$user_logged_in = isset($_SESSION['user_id']);

/* ------ SHOW FEATURED VENUES ------ */
$stmt = $conn->prepare("SELECT * FROM venues WHERE is_available = 1 LIMIT 3");
$stmt->execute();
$venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ------ GET VENUE STATISTICS ------ */
$stmt = $conn->prepare("SELECT COUNT(*) as total_venues FROM venues");
$stmt->execute();
$venue_stats = $stmt->fetch();

$stmt = $conn->prepare("SELECT COUNT(*) as total_bookings FROM bookings WHERE status = 'confirmed'");
$stmt->execute();
$booking_stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VTU Venue Booking - Visvesvaraya Technological University</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <style>
        :root {
            /* VTU Primary Colors */
            --vtu-blue: #1e40af;
            --vtu-blue-light: #3b82f6;
            --vtu-green: #059669;
            --vtu-yellow: #d97706;
            --vtu-gradient: linear-gradient(135deg, #1e40af 0%, #059669 100%);

            /* Neutral Palette */
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

            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--gray-800);
            background: var(--gray-50);
            font-size: 18px;
        }

        /* Navigation Bar - Sticky Header */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }

        .nav-container {
            width: 100%;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 90px;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: var(--gray-900);
            flex-shrink: 0;
        }

        .nav-logo img {
            width: 55px;
            height: 55px;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .nav-logo-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.2;
        }

        .nav-logo-title {
            font-size: 20px;
            font-weight: 800;
            background: var(--vtu-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2px;
        }

        .nav-logo-subtitle {
            font-size: 12px;
            color: var(--gray-600);
            font-weight: 500;
            line-height: 1.1;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            min-height: 44px;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--vtu-gradient);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: white;
            color: var(--gray-700);
            border-color: var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-50);
            border-color: var(--vtu-blue);
        }

        .btn-success {
            background: var(--vtu-green);
            color: white;
        }

        .btn-warning {
            background: var(--vtu-yellow);
            color: white;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--gray-700);
            cursor: pointer;
            flex-shrink: 0;
        }

        /* Hero Section - Slideshow Background */
        .hero {
            height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .slideshow-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
        }

        .slide.active {
            opacity: 1;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.4) 0%, rgba(5, 150, 105, 0.4) 100%);
            z-index: 2;
        }

        /* Slideshow Navigation Dots */
        .slideshow-dots {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 4;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .dot.active {
            background: white;
            transform: scale(1.2);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .dot:hover {
            background: rgba(255, 255, 255, 0.8);
            transform: scale(1.1);
        }

        .hero-content {
            position: relative;
            z-index: 3;
            text-align: center;
            color: white;
            max-width: 800px;
            padding: 0 1.5rem;
        }

        .hero-title {
            font-size: clamp(48px, 8vw, 64px);
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: clamp(24px, 4vw, 32px);
            font-weight: 600;
            margin-bottom: 1rem;
            opacity: 0.95;
        }

        .hero-description {
            font-size: 20px;
            font-weight: 400;
            margin-bottom: 3rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-btn {
            padding: 1rem 2rem;
            font-size: 18px;
            font-weight: 700;
            border-radius: 16px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            min-height: 56px;
        }

        .hero-btn-primary {
            background: white;
            color: var(--vtu-blue);
            box-shadow: var(--shadow-xl);
        }

        .hero-btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        }

        .hero-btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .hero-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        /* Featured Venues Section */
        .featured-venues {
            padding: 6rem 0;
            background: white;
        }

        .section-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: clamp(40px, 6vw, 48px);
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 1rem;
        }

        .section-subtitle {
            font-size: 20px;
            color: var(--gray-600);
            font-weight: 500;
            max-width: 600px;
            margin: 0 auto;
        }

        .venues-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .venue-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
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
            height: 240px;
            object-fit: cover;
            background: var(--gray-100);
        }

        .venue-badges {
            position: absolute;
            top: 1rem;
            left: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        .venue-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 14px;
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
            padding: 2rem;
        }

        .venue-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.75rem;
        }

        .venue-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 16px;
            margin-bottom: 1rem;
        }

        .venue-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .venue-capacity {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 16px;
        }

        .venue-amenities {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .amenity-tag {
            padding: 0.25rem 0.75rem;
            background: var(--gray-100);
            color: var(--gray-700);
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
        }

        .venue-actions {
            display: flex;
            gap: 1rem;
        }

        .venue-btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .venue-btn-primary {
            background: var(--vtu-gradient);
            color: white;
        }

        .venue-btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .venue-btn:hover {
            transform: translateY(-2px);
        }

        /* Features Section */
        .features {
            padding: 6rem 0;
            background: var(--gray-50);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: white;
            padding: 3rem 2rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--vtu-gradient);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 32px;
            color: white;
        }

        .feature-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1rem;
        }

        .feature-description {
            font-size: 16px;
            color: var(--gray-600);
            line-height: 1.6;
        }

        /* About Section */
        .about {
            padding: 3rem 0;
            background: white;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .about-text h2 {
            font-size: 40px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 2rem;
        }

        .about-text p {
            font-size: 18px;
            color: var(--gray-600);
            margin-bottom: 2rem;
            line-height: 1.7;
        }

        .about-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-top: 3rem;
        }

        .stat-item {
            text-align: center;
            padding: 2rem;
            background: var(--gray-50);
            border-radius: 16px;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 900;
            background: var(--vtu-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 16px;
            color: var(--gray-600);
            font-weight: 600;
        }

        .about-image {
            position: relative;
        }

        .about-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
        }

        /* CTA Section */
        .cta {
            padding: 6rem 0;
            background: var(--vtu-gradient);
            color: white;
            text-align: center;
        }

        .cta h2 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }

        .cta p {
            font-size: 20px;
            margin-bottom: 3rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Footer */
        .footer {
            background: var(--gray-900);
            color: var(--gray-300);
            padding: 4rem 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            color: white;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .footer-section p,
        .footer-section a {
            color: var(--gray-400);
            text-decoration: none;
            margin-bottom: 0.5rem;
            display: block;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: var(--vtu-blue-light);
        }

        .footer-bottom {
            border-top: 1px solid var(--gray-700);
            padding-top: 2rem;
            text-align: center;
            color: var(--gray-500);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            overflow-y: auto;
            padding: 10px 0;
        }

        .modal-content {
            background: white;
            margin: 2% auto;
            padding: 1.5rem;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: var(--shadow-xl);
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--gray-100);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--gray-600);
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: var(--gray-200);
            color: var(--gray-800);
        }

        /* Calendar Styles */
        .calendar-day {
            background: white;
            padding: 0.3rem;
            text-align: center;
            cursor: pointer;
            min-height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            font-weight: 500;
            font-size: 12px;
            transition: all 0.2s ease;
            border-radius: 3px;
        }

        .calendar-day:hover {
            transform: scale(1.05);
            border-color: var(--vtu-blue-light);
            box-shadow: var(--shadow-md);
        }

        .calendar-day:not(.booked):not(.user-booking):not(.today):hover {
            background: var(--gray-50);
        }

        .calendar-day.other-month {
            color: var(--gray-400);
            background: var(--gray-100);
            opacity: 0.6;
        }

        .calendar-day.today {
            background: var(--vtu-gradient);
            color: white;
            font-weight: 700;
            box-shadow: var(--shadow-md);
        }

        .calendar-day.today:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-lg);
        }

        .calendar-day.booked {
            background: #dc2626 !important;
            color: white !important;
            font-weight: 700;
        }

        .calendar-day.user-booking {
            background: var(--vtu-blue) !important;
            color: white !important;
            font-weight: 700;
        }

        .calendar-day.available {
            background: var(--vtu-green);
            color: white;
        }

        .calendar-header-day {
            background: var(--gray-800);
            color: white;
            font-weight: 600;
            padding: 0.4rem 0.2rem;
            text-align: center;
            font-size: 10px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .booking-indicator {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--vtu-blue);
        }

        /* Calendar Responsive Styles */
        @media (max-width: 768px) {
            .calendar-day {
                min-height: 28px;
                font-size: 11px;
                padding: 0.2rem;
            }

            .calendar-header-day {
                padding: 0.4rem 0.2rem;
                font-size: 10px;
            }

            #calendarModal .modal-content {
                width: 350px !important;
                max-width: 95vw !important;
                margin: 10% auto !important;
                padding: 1rem !important;
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .nav-logo-title {
                font-size: 18px;
            }

            .nav-logo-subtitle {
                font-size: 11px;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .nav-container {
                height: 70px;
            }

            .nav-logo-title {
                font-size: 16px;
            }

            .nav-logo-subtitle {
                font-size: 10px;
            }

            .hero-title {
                font-size: 48px;
            }

            .hero-subtitle {
                font-size: 24px;
            }

            .hero-cta {
                flex-direction: column;
                align-items: center;
            }

            .venues-grid {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .about-content {
                grid-template-columns: 1fr;
                gap: 3rem;
            }

            .about-stats {
                grid-template-columns: 1fr;
            }

            .footer-content {
                grid-template-columns: repeat(2, 1fr);
                gap: 2rem;
            }
        }

        @media (max-width: 480px) {
            .section-container {
                padding: 0 1rem;
            }

            .venue-card {
                margin: 0 0.5rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }

            .nav-logo-text {
                display: none;
            }

            .nav-logo img {
                width: 45px;
                height: 45px;
            }
        }

        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }

        }

        /* Loading Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="#" class="nav-logo">
                <img src="images/vtu_logo.png" alt="VTU Logo">
                <div class="nav-logo-text">
                    <div class="nav-logo-title">Visvesvaraya Technological University</div>
                    <div class="nav-logo-subtitle">ವಿಶ್ವೇಶ್ವರಯ್ಯ ತಾಂತ್ರಿಕ ವಿಶ್ವವಿದ್ಯಾಲಯ</div>
                </div>
            </a>

            <div class="nav-menu">
                <?php if ($user_logged_in): ?>
                    <a href="user_dashboard.php" class="nav-btn btn-secondary">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="#" onclick="openCalendarModal()" class="nav-btn btn-primary">
                        <i class="fas fa-calendar"></i>
                        <span>Calendar</span>
                    </a>
                    <a href="logout.php" class="nav-btn btn-danger">
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
                    <a href="admin_login.php" class="nav-btn btn-warning">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin</span>
                    </a>
                <?php endif; ?>
            </div>

            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section with Slideshow Background -->
    <section class="hero">
        <!-- Slideshow Background -->
        <div class="slideshow-container">
            <div class="slide active" style="background-image: url('images/slide1.jpg.jpg')"></div>
            <div class="slide" style="background-image: url('images/slide2.jpg.jpg')"></div>
            <div class="slide" style="background-image: url('images/slide3.jpg.jpg')"></div>
            <div class="slide" style="background-image: url('images/slide4.jpg.jpg')"></div>
            <div class="slide" style="background-image: url('images/slide5.jpg.jpg')"></div>
            <div class="slide" style="background-image: url('images/slide6.jpg.jpg')"></div>
        </div>

        <div class="hero-overlay"></div>

        <!-- Navigation Dots -->
        <div class="slideshow-dots">
            <span class="dot active" onclick="currentSlide(1)"></span>
            <span class="dot" onclick="currentSlide(2)"></span>
            <span class="dot" onclick="currentSlide(3)"></span>
            <span class="dot" onclick="currentSlide(4)"></span>
            <span class="dot" onclick="currentSlide(5)"></span>
            <span class="dot" onclick="currentSlide(6)"></span>
        </div>

        <div class="hero-content fade-in-up">
            <h1 class="hero-title">VTU Venue Booking</h1>
            <p class="hero-subtitle">Visvesvaraya Technological University</p>
            <p class="hero-description">
                Book premium venues for your events, conferences, and gatherings.
                Simple, secure, and efficient booking system for the VTU community.
            </p>

            <div class="hero-cta">
                <?php if (!$user_logged_in): ?>
                    <a href="register_vtu.php" class="hero-btn hero-btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Get Started
                    </a>
                    <a href="#venues" class="hero-btn hero-btn-secondary">
                        <i class="fas fa-eye"></i>
                        Explore Venues
                    </a>
                <?php else: ?>
                    <a href="venues.php" class="hero-btn hero-btn-primary">
                        <i class="fas fa-calendar-plus"></i>
                        Book Now
                    </a>
                    <a href="#venues" class="hero-btn hero-btn-secondary">
                        <i class="fas fa-eye"></i>
                        Featured Venues
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Featured Venues Section -->
    <section id="venues" class="featured-venues">
        <div class="section-container">
            <div class="section-header fade-in-up">
                <h2 class="section-title">Featured Venues</h2>
                <p class="section-subtitle">
                    Discover our premium venues perfect for your next event
                </p>
            </div>

            <div class="venues-grid">
                <?php foreach ($venues as $venue): ?>
                    <?php $isAvailable = $venue['is_available']; ?>
                    <div class="venue-card fade-in-up <?= !$isAvailable ? 'unavailable' : '' ?>" <?= $isAvailable ? "onclick='window.location.href=\"venue_details.php?id=" . $venue['id'] . "\"'" : '' ?>>
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

            <!-- View All Venues Button -->
            <div style="text-align: center; margin-top: 3rem;">
                <a href="venues.php" class="hero-btn hero-btn-primary">
                    <i class="fas fa-building"></i>
                    View All Venues
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="section-container">
            <div class="section-header fade-in-up">
                <h2 class="section-title">Why Choose VTU Venues?</h2>
                <p class="section-subtitle">
                    Experience the best in venue booking with our comprehensive features
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">VTU Verified</h3>
                    <p class="feature-description">
                        Secure registration with VTU ID verification for authentic users only
                    </p>
                </div>

                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="feature-title">Quick Booking</h3>
                    <p class="feature-description">
                        Fast and efficient booking process with instant confirmation
                    </p>
                </div>

                <div class="feature-card fade-in-up">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="feature-title">Smart Calendar</h3>
                    <p class="feature-description">
                        View availability and manage your bookings with our smart calendar
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about">
        <div class="section-container">
            <div class="about-content">
                <div class="about-text fade-in-up">
                    <h2>About VTU Belagavi</h2>
                    <p>
                        Visvesvaraya Technological University (VTU), established in 1998, is a premier technological
                        university in Belagavi, Karnataka. Named after Sir M. Visvesvaraya, VTU serves over 200
                        engineering colleges across Karnataka.
                    </p>
                    <p>
                        Our venue booking system provides easy access to campus facilities for conferences, seminars,
                        workshops, and events.
                    </p>

                    <div class="about-stats">
                        <div class="stat-item">
                            <span class="stat-number">200+</span>
                            <span class="stat-label">Affiliated Colleges</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">4,00,000+</span>
                            <span class="stat-label">Students Enrolled</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">25+</span>
                            <span class="stat-label">Years of Excellence</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">50+</span>
                            <span class="stat-label">Academic Programs</span>
                        </div>
                    </div>
                </div>

                <div class="about-image fade-in-up">
                    <div class="map-container">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3847.0982524731847!2d74.49527731484!3d15.849747589346!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bbf66a0c0c0c0c0%3A0x1a2b3c4d5e6f7890!2sVisvesvaraya%20Technological%20University%2C%20Machhe%2C%20Belagavi%2C%20Karnataka%20590018!5e0!3m2!1sen!2sin!4v1640995200000!5m2!1sen!2sin"
                            width="100%" height="300"
                            style="border:0; border-radius: 20px; box-shadow: var(--shadow-xl);" allowfullscreen=""
                            loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="VTU Belagavi Location">
                        </iframe>
                    </div>
                    <div class="map-info"
                        style="margin-top: 1.5rem; padding: 1.5rem; background: var(--gray-50); border-radius: 16px;">
                        <h4 style="color: var(--gray-900); margin-bottom: 1rem; font-size: 20px; font-weight: 700;">📍
                            VTU Belagavi Campus</h4>
                        <p style="color: var(--gray-600); margin-bottom: 0.5rem; font-size: 16px;">Machhe, Belagavi,
                            Karnataka 590018</p>
                        <p style="color: var(--gray-600); margin-bottom: 0.5rem; font-size: 16px;">📞 +91-831-2498000
                        </p>
                        <p style="color: var(--gray-600); margin-bottom: 0; font-size: 16px;">🌐 www.vtu.ac.in</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="section-container fade-in-up">
            <h2>Ready to Book Your Venue?</h2>
            <p>
                Join thousands of satisfied users who trust VTU Venues for their event booking needs.
                Get started today and experience the difference.
            </p>

            <div class="cta-buttons">
                <?php if (!$user_logged_in): ?>
                    <a href="register_vtu.php" class="hero-btn hero-btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Register Now
                    </a>
                    <a href="login.php" class="hero-btn hero-btn-secondary">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                <?php else: ?>
                    <a href="venues.php" class="hero-btn hero-btn-primary">
                        <i class="fas fa-calendar-plus"></i>
                        Book a Venue
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="section-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>VTU Venues</h3>
                    <p>Visvesvaraya Technological University</p>
                    <p>Belagavi, Karnataka, India</p>
                    <p>Pin: 590018</p>
                </div>

                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="#venues">Browse Venues</a>
                    <a href="register_vtu.php">Register</a>
                    <a href="login.php">Login</a>
                    <a href="admin_login.php">Admin Portal</a>
                </div>

                <div class="footer-section">
                    <h3>Support</h3>
                    <a href="#">Help Center</a>
                    <a href="#">Contact Us</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Privacy Policy</a>
                </div>

                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p>📧 venues@vtu.ac.in</p>
                    <p>📞 +91-831-2498000</p>
                    <p>🌐 www.vtu.ac.in</p>
                    <p>⏰ Mon-Fri: 9AM-6PM</p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2024 Visvesvaraya Technological University. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Venue Modal -->
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
                <button onclick="closeModal()" class="nav-btn btn-secondary"
                    style="flex: 1; justify-content: center; border: none; cursor: pointer;">
                    <i class="fas fa-times"></i>
                    <span>Close</span>
                </button>
            </div>
        </div>
    </div>

    </div>

    <!-- Calendar Modal -->
    <div id="calendarModal" class="modal" style="display: none;">
        <div class="modal-content"
            style="width: 420px; max-width: 95vw; margin: 8% auto; padding: 1.5rem; border-radius: 12px;" </div>
            <button class="modal-close" onclick="closeCalendarModal()">
                <i class="fas fa-times"></i>
            </button>
            <div style="text-align: center; margin-bottom: 1rem;">
                <h3 style="color: var(--gray-900); font-size: 18px; font-weight: 700; margin-bottom: 0.25rem;">
                    📅 Venue Calendar
                </h3>
                <p style="color: var(--gray-600); font-size: 12px; margin: 0;"></p>
                View bookings and availability
                </p>
            </div>

            <div id="calendarContainer">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; padding: 0 0.25rem;">
                    <button onclick="changeMonth(-1)" class="nav-btn btn-secondary"
                        style="padding: 0.4rem 0.8rem; font-size: 12px;">
                        <i class="fas fa-chevron-left"></i>
                        <span>Prev</span>
                    </button>
                    <h4 id="calendarMonth"
                        style="font-size: 16px; font-weight: 700; color: var(--gray-900); margin: 0;">
                    </h4>
                    <button onclick="changeMonth(1)" class="nav-btn btn-secondary"
                        style="padding: 0.4rem 0.8rem; font-size: 12px;">
                        <span>Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>

                <div id="calendarGrid"
                    style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; background: var(--gray-200); border-radius: 8px; overflow: hidden; margin-bottom: 1rem; padding: 4px;">
                </div>
                <!-- Calendar will be generated here -->
            </div>

            <div
                style="display: flex; justify-content: center; gap: 1rem; margin-top: 1rem; padding: 0.75rem; background: var(--gray-50); border-radius: 8px;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 12px; height: 12px; background: var(--vtu-green); border-radius: 3px;"></div>
                    <span style="font-size: 12px; color: var(--gray-700); font-weight: 500;">Available</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 12px; height: 12px; background: var(--vtu-blue); border-radius: 3px;"></div>
                    <span style="font-size: 12px; color: var(--gray-700); font-weight: 500;">Your Bookings</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 12px; height: 12px; background: #dc2626; border-radius: 3px;"></div>
                    <span style="font-size: 12px; color: var(--gray-700); font-weight: 500;">Booked</span>
                </div>
            </div>
        </div>

        <div style="margin-top: 0.75rem; text-align: center;">
            <button onclick="closeCalendarModal()" class="nav-btn btn-primary"
                style="padding: 0.4rem 0.8rem; font-size: 12px;">
                <i class="fas fa-times"></i>
                <span>Close</span>
            </button>
        </div>
    </div>
    </div>

    <script>
        // Calendar functionality
        let currentCalendarDate = new Date();
        let bookingsData = {};

        function openCalendarModal() {
            console.log("Opening calendar modal...");
            document.getElementById("calendarModal").style.display = "block";
            currentCalendarDate = new Date();
            loadBookingsData();
        }

        function closeCalendarModal() {
            document.getElementById("calendarModal").style.display = "none";
        }

        function loadBookingsData() {
            console.log("Loading bookings data...");
            
            // Check if user is logged in
            const userLoggedIn = <?= json_encode($user_logged_in) ?>;
            if (!userLoggedIn) {
                console.log("User not logged in, showing empty calendar");
                bookingsData = {};
                renderCalendar();
                return;
            }
            
            fetch("calendar_api.php?t=" + Date.now())
                .then(response => {
                    console.log("API response status:", response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log("API response text:", text);
                    try {
                        const data = JSON.parse(text);
                        console.log("Parsed API data:", data);
                        bookingsData = data;
                        console.log("Loaded bookings data:", bookingsData);
                        console.log("Number of booking dates:", Object.keys(bookingsData).length);
                        renderCalendar();
                    } catch (parseError) {
                        console.error("JSON Parse Error:", parseError);
                        console.error("Response text:", text);
                        bookingsData = {};
                        renderCalendar();
                    }
                })
                .catch(error => {
                    console.error("Error loading bookings:", error);
                    console.error("Error details:", error.message);
                    bookingsData = {};
                    renderCalendar();
                });
        }

        function renderCalendar() {
            const year = currentCalendarDate.getFullYear();
            const month = currentCalendarDate.getMonth();

            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"];
            document.getElementById("calendarMonth").textContent = monthNames[month] + " " + year;

            const currentMonthBookings = Object.keys(bookingsData).filter(date => {
                const bookingDate = new Date(date);
                return bookingDate.getFullYear() === year && bookingDate.getMonth() === month;
            });

            if (currentMonthBookings.length > 0) {
                document.getElementById("calendarMonth").textContent += " (" + currentMonthBookings.length + " booking" + (currentMonthBookings.length > 1 ? "s" : "") + ")";
            }

            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay();

            const calendarGrid = document.getElementById("calendarGrid");
            calendarGrid.innerHTML = "";

            const dayHeaders = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            dayHeaders.forEach(day => {
                const headerDiv = document.createElement("div");
                headerDiv.className = "calendar-header-day";
                headerDiv.textContent = day;
                calendarGrid.appendChild(headerDiv);
            });

            for (let i = 0; i < startingDayOfWeek; i++) {
                const emptyDiv = document.createElement("div");
                emptyDiv.className = "calendar-day other-month";
                calendarGrid.appendChild(emptyDiv);
            }

            const today = new Date();
            for (let day = 1; day <= daysInMonth; day++) {
                const dayDiv = document.createElement("div");
                dayDiv.className = "calendar-day";
                dayDiv.textContent = day;

                const currentDate = new Date(year, month, day);
                const dateString = currentDate.toISOString().split("T")[0];

                if (currentDate.toDateString() === today.toDateString()) {
                    dayDiv.classList.add("today");
                }

                // Check for bookings on this date
                if (bookingsData[dateString]) {
                    const bookings = bookingsData[dateString];
                    const currentUserId = <?= json_encode($_SESSION['user_id'] ?? null) ?>;
                    const hasMyBooking = bookings.some(booking => booking.user_id == currentUserId);

                    if (hasMyBooking) {
                        dayDiv.classList.add("user-booking");
                        const myBooking = bookings.find(b => b.user_id == currentUserId);
                        dayDiv.title = `Your booking: ${myBooking.event_name} at ${myBooking.venue_name}`;
                    } else {
                        dayDiv.classList.add("booked");
                        dayDiv.title = `Booked: ${bookings[0].event_name} at ${bookings[0].venue_name}`;
                    }
                } else if (currentDate >= today) {
                    dayDiv.classList.add("available");
                    dayDiv.title = "Available for booking";
                }

                dayDiv.onclick = () => showDayDetails(dateString);
                calendarGrid.appendChild(dayDiv);
            }
        }

        function changeMonth(direction) {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + direction);
            renderCalendar();
        }

        function showDayDetails(dateString) {
            const bookings = bookingsData[dateString];
            const formattedDate = new Date(dateString).toLocaleDateString("en-US", {
                weekday: "long",
                year: "numeric",
                month: "long",
                day: "numeric"
            });

            if (!bookings || bookings.length === 0) {
                showDetailedModal(formattedDate, [], dateString);
                return;
            }

            showDetailedModal(formattedDate, bookings, dateString);
        }

        function showDetailedModal(formattedDate, bookings, dateString) {
            // Create modal HTML
            const modalHTML = `
                <div id="dayDetailsModal" class="modal" style="display: block; z-index: 10000;">
                    <div class="modal-content" style="width: 500px; max-width: 95vw; margin: 5% auto; padding: 0; border-radius: 12px; overflow: hidden;">
                        <div style="background: linear-gradient(135deg, var(--vtu-blue) 0%, var(--vtu-green) 100%); color: white; padding: 20px; text-align: center;">
                            <h3 style="margin: 0; font-size: 18px;">${formattedDate}</h3>
                            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Booking Schedule & Availability</p>
                        </div>
                        
                        <div style="padding: 20px;">
                            ${bookings.length === 0 ?
                    `<div style="text-align: center; padding: 20px; color: var(--vtu-green);">
                                    <i class="fas fa-calendar-check" style="font-size: 48px; margin-bottom: 16px;"></i>
                                    <h4 style="margin: 0 0 8px 0; color: var(--gray-900);">Fully Available</h4>
                                    <p style="margin: 0; color: var(--gray-600);">This date is completely free for booking</p>
                                    <div style="margin-top: 16px; padding: 12px; background: #f0fdf4; border-radius: 8px; border-left: 4px solid var(--vtu-green);">
                                        <strong>Available Time Slots:</strong><br>
                                        🕘 9:00 AM - 6:00 PM (All venues available)
                                    </div>
                                </div>`
                    :
                    `<div>
                                    <h4 style="margin: 0 0 16px 0; color: var(--gray-900); font-size: 16px;">
                                        <i class="fas fa-clock"></i> Current Bookings (${bookings.length})
                                    </h4>
                                    ${generateBookingsList(bookings)}
                                    ${generateAvailableSlots(bookings, dateString)}
                                </div>`
                }
                            
                            <div style="display: flex; gap: 12px; margin-top: 20px; justify-content: center;">
                                <button onclick="closeDayDetailsModal()" class="btn btn-secondary" style="padding: 8px 16px; border-radius: 6px; border: 1px solid var(--gray-300); background: white; color: var(--gray-700);">
                                    <i class="fas fa-times"></i> Close
                                </button>
                                <a href="venues.php" class="btn btn-primary" style="padding: 8px 16px; border-radius: 6px; background: var(--vtu-blue); color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-plus"></i> Book Venue
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }

        function generateBookingsList(bookings) {
            const currentUserId = <?= json_encode($_SESSION['user_id'] ?? null) ?>;
            
            return bookings.map(booking => {
                const startTime = formatTime(booking.start_time);
                const endTime = formatTime(booking.end_time);
                const isUserBooking = booking.user_id == currentUserId;

                return `
                    <div style="margin-bottom: 12px; padding: 12px; border-radius: 8px; border-left: 4px solid ${isUserBooking ? 'var(--vtu-blue)' : '#dc2626'}; background: ${isUserBooking ? '#dbeafe' : '#fee2e2'};">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                            <strong style="color: var(--gray-900); font-size: 14px;">${booking.venue_name}</strong>
                            <span style="background: ${isUserBooking ? 'var(--vtu-blue)' : '#dc2626'}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                ${isUserBooking ? 'YOUR BOOKING' : 'BOOKED'}
                            </span>
                        </div>
                        <div style="color: var(--gray-700); font-size: 13px; margin-bottom: 4px;">
                            <i class="fas fa-tag"></i> <strong>${booking.event_name}</strong>
                        </div>
                        <div style="color: var(--gray-700); font-size: 13px; margin-bottom: 4px;">
                            <i class="fas fa-clock"></i> <strong>${startTime} - ${endTime}</strong>
                            <span style="margin-left: 12px; color: var(--gray-600);">
                                (${calculateDuration(booking.start_time, booking.end_time)})
                            </span>
                        </div>
                        <div style="color: var(--gray-600); font-size: 12px;">
                            <i class="fas fa-user"></i> ${booking.full_name}
                            ${booking.department ? ` • ${booking.department}` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function generateAvailableSlots(bookings, dateString) {
            const availableSlots = findAvailableTimeSlots(bookings);

            if (availableSlots.length === 0) {
                return `
                    <div style="margin-top: 16px; padding: 12px; background: #fef2f2; border-radius: 8px; border-left: 4px solid #dc2626;">
                        <h5 style="margin: 0 0 8px 0; color: #991b1b;">
                            <i class="fas fa-exclamation-triangle"></i> No Available Slots
                        </h5>
                        <p style="margin: 0; color: #7f1d1d; font-size: 13px;">
                            All time slots are booked for this date. Try selecting a different date.
                        </p>
                    </div>
                `;
            }

            return `
                <div style="margin-top: 16px; padding: 12px; background: #f0fdf4; border-radius: 8px; border-left: 4px solid var(--vtu-green);">
                    <h5 style="margin: 0 0 12px 0; color: #166534;">
                        <i class="fas fa-calendar-plus"></i> Available Time Slots
                    </h5>
                    ${availableSlots.map(slot => `
                        <div style="margin-bottom: 6px; color: #15803d; font-size: 13px;">
                            <i class="fas fa-clock"></i> <strong>${slot.start} - ${slot.end}</strong>
                            <span style="margin-left: 8px; color: #166534; font-size: 12px;">(${slot.duration})</span>
                        </div>
                    `).join('')}
                    <p style="margin: 8px 0 0 0; color: #166534; font-size: 12px; font-style: italic;">
                        💡 You can book during these available time periods
                    </p>
                </div>
            `;
        }

        function findAvailableTimeSlots(bookings) {
            const workingHours = { start: '09:00', end: '18:00' };
            const slots = [];

            // Sort bookings by start time
            const sortedBookings = bookings.sort((a, b) => a.start_time.localeCompare(b.start_time));

            let currentTime = workingHours.start;

            for (const booking of sortedBookings) {
                if (currentTime < booking.start_time) {
                    slots.push({
                        start: formatTime(currentTime),
                        end: formatTime(booking.start_time),
                        duration: calculateDuration(currentTime, booking.start_time)
                    });
                }
                currentTime = booking.end_time > currentTime ? booking.end_time : currentTime;
            }

            // Check if there's time after the last booking
            if (currentTime < workingHours.end) {
                slots.push({
                    start: formatTime(currentTime),
                    end: formatTime(workingHours.end),
                    duration: calculateDuration(currentTime, workingHours.end)
                });
            }

            return slots.filter(slot => calculateDurationMinutes(slot.start.replace(/[^\d:]/g, ''), slot.end.replace(/[^\d:]/g, '')) >= 60);
        }

        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        }

        function calculateDuration(startTime, endTime) {
            const start = new Date(`2000-01-01 ${startTime}`);
            const end = new Date(`2000-01-01 ${endTime}`);
            const diffMs = end - start;
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            const diffMinutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

            if (diffHours === 0) {
                return `${diffMinutes} min`;
            } else if (diffMinutes === 0) {
                return `${diffHours} hr`;
            } else {
                return `${diffHours}h ${diffMinutes}m`;
            }
        }

        function calculateDurationMinutes(startTime, endTime) {
            const start = new Date(`2000-01-01 ${startTime}`);
            const end = new Date(`2000-01-01 ${endTime}`);
            return (end - start) / (1000 * 60);
        }

        function closeDayDetailsModal() {
            const modal = document.getElementById('dayDetailsModal');
            if (modal) {
                modal.remove();
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function (event) {
            const modal = document.getElementById('dayDetailsModal');
            if (modal && event.target === modal) {
                closeDayDetailsModal();
            }
        });

        // Slideshow functionality
        let slideIndex = 1;
        let slideInterval;

        function showSlides(n) {
            let slides = document.getElementsByClassName("slide");
            let dots = document.getElementsByClassName("dot");

            if (n > slides.length) { slideIndex = 1 }
            if (n < 1) { slideIndex = slides.length }

            for (let i = 0; i < slides.length; i++) {
                slides[i].classList.remove("active");
            }

            for (let i = 0; i < dots.length; i++) {
                dots[i].classList.remove("active");
            }

            if (slides.length > 0 && dots.length > 0) {
                slides[slideIndex - 1].classList.add("active");
                dots[slideIndex - 1].classList.add("active");
            }
        }

        function currentSlide(n) {
            clearInterval(slideInterval);
            showSlides(slideIndex = n);
            startSlideshow();
        }

        function nextSlide() {
            showSlides(slideIndex += 1);
        }

        function startSlideshow() {
            slideInterval = setInterval(nextSlide, 5000);
        }

        // Other functionality
        window.onclick = function (event) {
            const venueModal = document.getElementById("venueModal");
            const calendarModal = document.getElementById("calendarModal");

            if (event.target === venueModal) {
                closeModal();
            }
            if (event.target === calendarModal) {
                closeCalendarModal();
            }
        }

        document.querySelector(".mobile-menu-toggle")?.addEventListener("click", function () {
            console.log("Mobile menu clicked");
        });

        const observerOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = "1";
                    entry.target.style.transform = "translateY(0)";
                }
            });
        }, observerOptions);

        document.querySelectorAll(".fade-in-up").forEach(el => {
            el.style.opacity = "0";
            el.style.transform = "translateY(30px)";
            el.style.transition = "opacity 0.8s ease, transform 0.8s ease";
            observer.observe(el);
        });

        // Initialize everything
        document.addEventListener("DOMContentLoaded", function () {
            console.log("🎬 Initializing slideshow...");
            const slides = document.getElementsByClassName("slide");
            const dots = document.getElementsByClassName("dot");
            console.log("Found slides:", slides.length);
            console.log("Found dots:", dots.length);

            if (slides.length > 0) {
                showSlides(slideIndex);
                startSlideshow();
                console.log("✅ Slideshow started successfully");
            }

            console.log("📅 Testing calendar modal...");
            const calendarModal = document.getElementById("calendarModal");
            const calendarButton = document.querySelector("[onclick=\"openCalendarModal()\"]");
            console.log("Calendar modal found:", !!calendarModal);
            console.log("Calendar button found:", !!calendarButton);
        });
    </script>
</body>

</html>