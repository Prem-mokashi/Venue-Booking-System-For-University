<?php
session_start();
include '../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Venue not selected.";
    exit;
}

$venue_id = $_GET['id'];

// Fetch venue details
$stmt = $conn->prepare("SELECT * FROM venues WHERE id = :id AND is_available = 1");
$stmt->bindParam(':id', $venue_id, PDO::PARAM_INT);
$stmt->execute();
$venue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venue) {
    echo "Venue not found or currently unavailable for booking.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Venue - <?= htmlspecialchars($venue['name']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #48bb78;
            --error: #f56565;
            --warning: #ed8936;
            --bg-light: #f7fafc;
            --bg-white: #ffffff;
            --text-dark: #2d3748;
            --text-light: #4a5568;
            --text-muted: #718096;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--bg-light) 0%, #e6fffa 100%);
            min-height: 100vh;
            line-height: 1.6;
            color: var(--text-dark);
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            background: var(--bg-white);
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            position: relative;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .venue-showcase {
            padding: 30px;
            background: linear-gradient(145deg, #f8f9fa, #ffffff);
        }

        .venue-card {
            background: var(--bg-white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .venue-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .venue-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .venue-card:hover .venue-img {
            transform: scale(1.05);
        }

        .venue-info {
            padding: 25px;
        }

        .venue-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .venue-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: var(--bg-light);
            border-radius: 10px;
            font-weight: 500;
        }

        .detail-item i {
            color: var(--primary);
            width: 20px;
        }

        .form-section {
            padding: 40px;
            background: var(--bg-white);
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .form-group {
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        label i {
            color: var(--primary);
            width: 16px;
        }

        .required::after {
            content: '*';
            color: var(--error);
            margin-left: 4px;
        }

        input, textarea, select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            font-family: inherit;
            background: var(--bg-white);
            transition: all 0.3s ease;
            position: relative;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        input:hover, textarea:hover, select:hover {
            border-color: var(--primary-dark);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .time-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .submit-section {
            padding: 30px 40px;
            background: linear-gradient(145deg, #f8f9fa, #ffffff);
            border-top: 1px solid var(--border);
        }

        .submit-btn {
            width: 100%;
            padding: 18px 30px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .error {
            background: linear-gradient(135deg, #fed7d7, #feb2b2);
            color: #742a2a;
            border-left: 4px solid var(--error);
        }

        .success {
            background: linear-gradient(135deg, #c6f6d5, #9ae6b4);
            color: #22543d;
            border-left: 4px solid var(--success);
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-3px);
        }

        .form-tips {
            background: linear-gradient(135deg, #e6fffa, #b2f5ea);
            border: 1px solid #81e6d9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .form-tips h4 {
            color: #234e52;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-tips ul {
            color: #2d3748;
            margin-left: 20px;
        }

        .form-tips li {
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }

            .header {
                padding: 25px 20px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .venue-showcase, .form-section, .submit-section {
                padding: 25px 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .time-grid {
                grid-template-columns: 1fr;
            }

            .venue-details {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.8rem;
            }

            .venue-img {
                height: 200px;
            }

            input, textarea, select {
                padding: 12px 15px;
            }

            .submit-btn {
                padding: 15px 25px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back
            </a>
            <h1><i class="fas fa-calendar-plus"></i> Book Venue</h1>
            <p>Reserve your perfect space for your event</p>
        </div>

        <!-- Venue Showcase -->
        <div class="venue-showcase">
            <div class="venue-card">
                <?php 
                $imagePath = 'images/' . ($venue['image'] ?? 'default_venue.jpg');
                if (!file_exists($imagePath)) {
                    $imagePath = 'https://via.placeholder.com/800x250/667eea/ffffff?text=' . urlencode($venue['name']);
                }
                ?>
                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($venue['name']) ?>" class="venue-img">
                <div class="venue-info">
                    <h2 class="venue-title">
                        <i class="fas fa-building"></i>
                        <?= htmlspecialchars($venue['name']) ?>
                    </h2>
                    <p style="color: var(--text-light); margin-bottom: 20px;">
                        <?= htmlspecialchars($venue['description'] ?? 'Professional venue space perfect for your events.') ?>
                    </p>
                    <div class="venue-details">
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($venue['location'] ?? 'VTU Campus') ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-users"></i>
                            <span>Capacity: <?= htmlspecialchars($venue['capacity'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <div class="form-section">
            <!-- Messages -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <h2 class="form-title">Booking Information</h2>

            <div class="form-tips">
                <h4><i class="fas fa-lightbulb"></i> Booking Guidelines</h4>
                <ul>
                    <li>All fields marked with <span style="color: var(--error);">*</span> are required</li>
                    <li>Bookings must be made at least 24 hours in advance</li>
                    <li>You will receive a confirmation email once your booking is approved</li>
                    <li>Contact admin for any special requirements or changes</li>
                </ul>
            </div>

            <form action="process_booking.php" method="post" id="bookingForm">
                <input type="hidden" name="venue_id" value="<?= $venue['id'] ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label class="required">
                            <i class="fas fa-user"></i>
                            Your Full Name
                        </label>
                        <input type="text" name="full_name" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label class="required">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input type="email" name="email" placeholder="your.email@example.com" required>
                    </div>

                    <div class="form-group">
                        <label class="required">
                            <i class="fas fa-phone"></i>
                            Phone Number
                        </label>
                        <input type="tel" id="phone" name="phone" placeholder="10-digit phone number"
                               pattern="[0-9]{10}" maxlength="10" title="Please enter exactly 10 digits" required>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-building"></i>
                            Department
                        </label>
                        <input type="text" name="department" placeholder="Your department (optional)">
                    </div>



                    <div class="form-group">
                        <label class="required">
                            <i class="fas fa-calendar-alt"></i>
                            Event Date
                        </label>
                        <input type="date" name="event_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label class="required">
                            <i class="fas fa-tag"></i>
                            Event Name
                        </label>
                        <input type="text" name="event_name" placeholder="Enter your event name" required>
                    </div>

                    <div class="form-group full-width">
                        <div class="time-grid">
                            <div>
                                <label class="required">
                                    <i class="fas fa-clock"></i>
                                    Start Time
                                </label>
                                <select name="start_time" required>
                                    <option value="">Select start time</option>
                                    <?php for ($hour = 8; $hour <= 17; $hour++): ?>
                                        <option value="<?= sprintf('%02d:00', $hour) ?>">
                                            <?= sprintf('%02d:00 %s', $hour > 12 ? $hour - 12 : ($hour == 0 ? 12 : $hour), $hour >= 12 ? 'PM' : 'AM') ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="required">
                                    <i class="fas fa-clock"></i>
                                    End Time
                                </label>
                                <select name="end_time" required>
                                    <option value="">Select end time</option>
                                    <?php for ($hour = 9; $hour <= 18; $hour++): ?>
                                        <option value="<?= sprintf('%02d:00', $hour) ?>">
                                            <?= sprintf('%02d:00 %s', $hour > 12 ? $hour - 12 : ($hour == 0 ? 12 : $hour), $hour >= 12 ? 'PM' : 'AM') ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label class="required">
                            <i class="fas fa-align-left"></i>
                            Event Details
                        </label>
                        <textarea name="event_details" placeholder="Describe your event, special requirements, expected attendees, etc." required></textarea>
                    </div>
                </div>
            </form>
        </div>

        <!-- Submit Section -->
        <div class="submit-section">
            <button type="submit" form="bookingForm" class="submit-btn">
                <i class="fas fa-paper-plane"></i>
                Submit Booking Request
            </button>
        </div>
    </div>

    <script>
        // Back button functionality
        function goBack() {
            // Try to go back in history, fallback to index page
            if (document.referrer && document.referrer !== window.location.href) {
                window.history.back();
            } else {
                // Fallback to index page if no referrer
                window.location.href = 'index.php';
            }
        }

        // Enhanced form validation and UX
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('bookingForm');
            const phoneInput = document.getElementById('phone');
            const startTimeSelect = document.querySelector('select[name="start_time"]');
            const endTimeSelect = document.querySelector('select[name="end_time"]');
            const eventDateInput = document.querySelector('input[name="event_date"]');
            const submitBtn = document.querySelector('.submit-btn');

            // Phone number validation
            phoneInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
                
                // Visual feedback
                if (this.value.length === 10) {
                    this.style.borderColor = 'var(--success)';
                } else if (this.value.length > 0) {
                    this.style.borderColor = 'var(--warning)';
                } else {
                    this.style.borderColor = 'var(--border)';
                }
            });

            // Time validation
            function validateTimes() {
                const startTime = startTimeSelect.value;
                const endTime = endTimeSelect.value;
                
                if (startTime && endTime) {
                    const start = new Date(`2000-01-01 ${startTime}`);
                    const end = new Date(`2000-01-01 ${endTime}`);
                    
                    if (end <= start) {
                        endTimeSelect.style.borderColor = 'var(--error)';
                        showTooltip(endTimeSelect, 'End time must be after start time');
                        return false;
                    } else {
                        endTimeSelect.style.borderColor = 'var(--success)';
                        hideTooltip(endTimeSelect);
                        return true;
                    }
                }
                return true;
            }

            startTimeSelect.addEventListener('change', validateTimes);
            endTimeSelect.addEventListener('change', validateTimes);

            // Date validation
            eventDateInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                tomorrow.setHours(0, 0, 0, 0);
                
                if (selectedDate < tomorrow) {
                    this.style.borderColor = 'var(--error)';
                    showTooltip(this, 'Event date must be at least 24 hours in advance');
                } else {
                    this.style.borderColor = 'var(--success)';
                    hideTooltip(this);
                }
            });

            // Form submission with loading state
            form.addEventListener('submit', function(e) {
                if (!validateTimes()) {
                    e.preventDefault();
                    return;
                }

                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;
                
                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Booking Request';
                    submitBtn.disabled = false;
                }, 10000);
            });

            // Tooltip functions
            function showTooltip(element, message) {
                hideTooltip(element); // Remove existing tooltip
                
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = message;
                tooltip.style.cssText = `
                    position: absolute;
                    background: var(--error);
                    color: white;
                    padding: 8px 12px;
                    border-radius: 6px;
                    font-size: 0.85rem;
                    top: 100%;
                    left: 0;
                    margin-top: 5px;
                    z-index: 1000;
                    box-shadow: var(--shadow-md);
                    animation: slideIn 0.2s ease;
                `;
                
                element.parentNode.style.position = 'relative';
                element.parentNode.appendChild(tooltip);
            }

            function hideTooltip(element) {
                const tooltip = element.parentNode.querySelector('.tooltip');
                if (tooltip) {
                    tooltip.remove();
                }
            }

            // Auto-fill current user info if available
            const userInfo = <?= json_encode([
                'name' => $_SESSION['username'] ?? '',
                'email' => $_SESSION['email'] ?? ''
            ]) ?>;
            
            if (userInfo.name) {
                document.querySelector('input[name="full_name"]').value = userInfo.name;
            }
            if (userInfo.email) {
                document.querySelector('input[name="email"]').value = userInfo.email;
            }

            // Smooth scroll to form on page load
            setTimeout(() => {
                document.querySelector('.form-section').scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }, 500);
        });

        // Add floating animation to form elements
        document.querySelectorAll('input, textarea, select').forEach(element => {
            element.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 8px 25px rgba(102, 126, 234, 0.15)';
            });
            
            element.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    </script>
</body>
</html>
