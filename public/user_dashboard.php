<?php
session_start();
include '../api/config.php';

// Set proper content type
header('Content-Type: text/html; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all bookings (newest first)
$stmt = $conn->prepare("SELECT b.*, v.name AS venue_name 
                        FROM bookings b 
                        JOIN venues v ON b.venue_id = v.id 
                        WHERE b.user_id = ? 
                        ORDER BY b.created_at DESC, b.id DESC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Find upcoming booking (approved + future + not started yet)
$currentDateTime = new DateTime();
$upcoming = null;

foreach ($bookings as $booking) {
    if ($booking['status'] === 'approved') {
        $eventDateTime = new DateTime($booking['event_date'] . ' ' . $booking['start_time']);
        if ($currentDateTime < $eventDateTime) {
            $upcoming = $booking;
            break;
        }
    }
}

// Calculate statistics
$stats = [
    'total' => count($bookings),
    'approved' => 0,
    'pending' => 0,
    'rejected' => 0,
    'cancelled' => 0,
    'completed' => 0
];

foreach ($bookings as $booking) {
    // Safely increment status count
    if (isset($stats[$booking['status']])) {
        $stats[$booking['status']]++;
    }

    // Check if event is completed
    if ($booking['status'] === 'approved') {
        $eventEndDateTime = new DateTime($booking['event_date'] . ' ' . $booking['end_time']);
        if ($currentDateTime >= $eventEndDateTime) {
            $stats['completed']++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - VTU Booking</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2c3e50;
            --accent: #3498db;
            --light-bg: #f0f2f5;
            --white: #ffffff;
            --success: #28a745;
            --error: #dc3545;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--light-bg);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 95%;
            margin: auto;
            padding: 30px 15px;
            animation: fadeIn 0.8s ease-in;
        }

        h2 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .top-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .top-links a {
            text-decoration: none;
            color: var(--primary);
            background: var(--white);
            padding: 10px 20px;
            border-radius: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: 0.3s ease;
            font-weight: bold;
        }

        .top-links a:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.05);
        }

        .upcoming-reminder {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 800px;
            font-size: 16px;
            animation: slideUp 0.7s ease;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.6s ease;
        }

        th,
        td {
            padding: 14px 16px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--primary);
            color: white;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            color: white;
            animation: pulse 1.5s infinite;
        }

        .pending {
            background-color: #f39c12;
        }

        .approved {
            background-color: var(--success);
        }

        .rejected {
            background-color: var(--error);
        }

        .cancelled {
            background-color: #7f8c8d;
        }

        .cancel-btn {
            display: inline-block;
            margin-top: 8px;
            color: white;
            background-color: var(--error);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            transition: 0.3s;
        }

        .cancel-btn:hover {
            background-color: #a71d2a;
        }

        .event-status {
            color: #666;
            font-size: 12px;
            font-style: italic;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            display: inline-block;
            margin-top: 5px;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: center;
        }

        .pdf-btn {
            display: inline-block;
            color: white;
            background: linear-gradient(135deg, #1e40af, #059669);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .pdf-btn:hover {
            background: linear-gradient(135deg, #1d4ed8, #047857);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .pdf-btn:active {
            transform: translateY(0);
        }

        .pdf-btn i {
            margin-right: 4px;
        }

        .bulk-download-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .bulk-download-section h3 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 18px;
        }

        .bulk-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .bulk-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .bulk-btn:hover {
            background: linear-gradient(135deg, #1a252f, #2980b9);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 62, 80, 0.3);
        }

        .bulk-btn.secondary {
            background: linear-gradient(135deg, #059669, #16a34a);
        }

        .bulk-btn.secondary:hover {
            background: linear-gradient(135deg, #047857, #15803d);
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .approved .stat-number {
            color: var(--success);
        }

        .pending .stat-number {
            color: #f39c12;
        }

        .rejected .stat-number {
            color: var(--error);
        }

        .msg-success,
        .msg-error {
            max-width: 600px;
            margin: 20px auto;
            padding: 12px 20px;
            border-radius: 6px;
            text-align: center;
            font-weight: bold;
        }

        .msg-success {
            background: #d4edda;
            color: #155724;
        }

        .msg-error {
            background: #f8d7da;
            color: #721c24;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.03);
            }

            100% {
                transform: scale(1);
            }
        }

        .no-booking {
            text-align: center;
            font-size: 16px;
            color: #666;
            margin-top: 40px;
            animation: fadeIn 1s ease;
        }

        @media screen and (max-width: 768px) {

            th,
            td {
                font-size: 13px;
                padding: 10px;
            }

            .top-links {
                flex-direction: column;
                align-items: center;
            }

            .top-links a {
                width: 90%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>📅 My Bookings Dashboard</h2>

        <div class="top-links">
            <a href="index.php">🏠 Home</a>
            <a href="my_bookings.php">📋 Manage Bookings</a>
            <a href="venues.php">🏢 Book Venue</a>
            <a href="profile.php">👤 My Profile</a>
            <a href="logout.php">🚪 Logout</a>
        </div>

        <!-- Statistics Section -->
        <?php if (count($bookings) > 0): ?>
            <div class="stats-section">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card approved">
                    <div class="stat-number"><?= $stats['approved'] ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-number"><?= $stats['pending'] ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card rejected">
                    <div class="stat-number"><?= $stats['rejected'] ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>

            <!-- Bulk Download Section -->
            <div class="bulk-download-section">
                <h3><i class="fas fa-download"></i> Download Booking Confirmations</h3>
                <p style="color: #666; margin-bottom: 15px;">Download PDF confirmations for your bookings</p>
                <div class="bulk-actions">
                    <button onclick="downloadAllPDFs()" class="bulk-btn">
                        <i class="fas fa-file-pdf"></i>
                        Download All PDFs (<?= $stats['total'] ?>)
                    </button>
                    <button onclick="downloadApprovedPDFs()" class="bulk-btn secondary">
                        <i class="fas fa-check-circle"></i>
                        Download Approved Only (<?= $stats['approved'] ?>)
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($upcoming): ?>
            <div class="upcoming-reminder">
                📢 <strong>Upcoming Booking:</strong>
                You have booked <b><?= htmlspecialchars($upcoming['venue_name']) ?></b> on
                <b><?= date("d M Y", strtotime($upcoming['event_date'])) ?></b> from
                <b><?= htmlspecialchars($upcoming['start_time']) ?> – <?= htmlspecialchars($upcoming['end_time']) ?></b>.
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="msg-error">
                <?= $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="msg-success">
                <?= $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (count($bookings) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Venue</th>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Start - End</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['venue_name']) ?></td>
                            <td><?= htmlspecialchars($booking['event_name']) ?></td>
                            <td><?= date('d M Y', strtotime($booking['event_date'])) ?></td>
                            <td><?= date('g:i A', strtotime($booking['start_time'])) ?> -
                                <?= date('g:i A', strtotime($booking['end_time'])) ?>
                            </td>
                            <td>
                                <span class="status-badge <?= htmlspecialchars($booking['status']) ?>">
                                    <?= ucfirst($booking['status']) ?>
                                </span>
                                <?php
                                // Check if event has started or completed
                                $currentDateTime = new DateTime();
                                $eventDateTime = new DateTime($booking['event_date'] . ' ' . $booking['start_time']);
                                $eventEndDateTime = new DateTime($booking['event_date'] . ' ' . $booking['end_time']);

                                if (
                                    ($booking['status'] === 'pending' || $booking['status'] === 'approved') &&
                                    $currentDateTime >= $eventDateTime
                                ): ?>
                                    <br>
                                    <?php if ($currentDateTime >= $eventEndDateTime): ?>
                                        <span class="event-status">
                                            ✅ Event Completed
                                        </span>
                                    <?php else: ?>
                                        <span class="event-status">
                                            🔴 Event In Progress
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <!-- PDF Download Button -->
                                    <a href="generate_booking_pdf.php?booking_id=<?= $booking['id'] ?>" class="pdf-btn"
                                        target="_blank" title="Download PDF Confirmation">
                                        <i class="fas fa-file-pdf"></i>
                                        Download PDF
                                    </a>

                                    <?php
                                    $canCancel = ($booking['status'] === 'pending' || $booking['status'] === 'approved') &&
                                        $currentDateTime < $eventDateTime;

                                    if ($canCancel): ?>
                                        <a href="cancel_booking.php?id=<?= $booking['id'] ?>" class="cancel-btn"
                                            onclick="return confirm('Are you sure you want to cancel this booking?');"
                                            title="Cancel Booking">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-booking">You have no bookings yet. Start by booking a venue!</p>
        <?php endif; ?>
    </div>

    <script>
        // Bulk PDF download functionality
        function downloadAllPDFs() {
            const bookingIds = <?= json_encode(array_column($bookings, 'id')) ?>;

            if (bookingIds.length === 0) {
                alert('No bookings available to download.');
                return;
            }

            const confirmDownload = confirm(`This will download ${bookingIds.length} PDF confirmation files. Continue?`);
            if (!confirmDownload) return;

            downloadPDFs(bookingIds, 'Downloading all PDFs...');
        }

        function downloadApprovedPDFs() {
            const approvedBookings = <?= json_encode(array_filter($bookings, function ($b) {
                return $b['status'] === 'approved';
            })) ?>;
            const bookingIds = Object.values(approvedBookings).map(booking => booking.id);

            if (bookingIds.length === 0) {
                alert('No approved bookings available to download.');
                return;
            }

            const confirmDownload = confirm(`This will download ${bookingIds.length} approved booking PDF files. Continue?`);
            if (!confirmDownload) return;

            downloadPDFs(bookingIds, 'Downloading approved PDFs...');
        }

        function downloadPDFs(bookingIds, loadingMessage) {
            // Show loading state
            const buttons = document.querySelectorAll('.bulk-btn');
            const originalTexts = Array.from(buttons).map(btn => btn.innerHTML);

            buttons.forEach(btn => {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + loadingMessage;
                btn.disabled = true;
            });

            // Download each PDF with staggered timing
            let downloadCount = 0;
            bookingIds.forEach((bookingId, index) => {
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = `generate_booking_pdf.php?booking_id=${bookingId}`;
                    link.target = '_blank';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    downloadCount++;

                    // Update progress
                    buttons.forEach(btn => {
                        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Downloaded ${downloadCount}/${bookingIds.length}...`;
                    });

                    // Reset buttons after all downloads
                    if (downloadCount === bookingIds.length) {
                        setTimeout(() => {
                            buttons.forEach((btn, btnIndex) => {
                                btn.innerHTML = originalTexts[btnIndex];
                                btn.disabled = false;
                            });

                            // Show success message
                            showNotification(`Successfully downloaded ${bookingIds.length} PDF files!`, 'success');
                        }, 1000);
                    }
                }, index * 500); // Stagger downloads by 500ms
            });
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `msg-${type}`;
            notification.innerHTML = message;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '10000';
            notification.style.maxWidth = '300px';
            notification.style.animation = 'slideIn 0.3s ease';

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Add CSS for notification animations
        const style = document.createElement('style');
        style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
        document.head.appendChild(style);

        // Add click tracking for individual PDF downloads
        document.querySelectorAll('.pdf-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });
    </script>

</body>

</html>