<?php
session_start();
include '../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$venue_filter = $_GET['venue'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build dynamic query based on filters
$where_conditions = ["bookings.user_id = :user_id"];
$params = [':user_id' => $user_id];

if ($status_filter !== 'all') {
    $where_conditions[] = "bookings.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "bookings.event_date >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "bookings.event_date <= :date_to";
    $params[':date_to'] = $date_to;
}

if ($venue_filter !== 'all') {
    $where_conditions[] = "venues.id = :venue_id";
    $params[':venue_id'] = $venue_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(bookings.event_name LIKE :search OR venues.name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$where_clause = implode(' AND ', $where_conditions);

$stmt = $conn->prepare("SELECT 
                            bookings.id, 
                            bookings.event_name,
                            bookings.event_date,
                            bookings.start_time,
                            bookings.end_time,
                            bookings.status,
                            bookings.created_at,
                            venues.id as venue_id,
                            venues.name AS venue_name,
                            venues.location AS venue_location
                        FROM bookings 
                        JOIN venues ON bookings.venue_id = venues.id 
                        WHERE {$where_clause}
                        ORDER BY bookings.created_at DESC");

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all venues for filter dropdown
$venue_stmt = $conn->prepare("SELECT DISTINCT v.id, v.name FROM venues v 
                              JOIN bookings b ON v.id = b.venue_id 
                              WHERE b.user_id = :user_id 
                              ORDER BY v.name");
$venue_stmt->bindParam(':user_id', $user_id);
$venue_stmt->execute();
$venues = $venue_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - VTU Venue Booking</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --vtu-blue: #1e40af;
            --vtu-blue-light: #3b82f6;
            --vtu-green: #059669;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            line-height: 1.6;
        }

        .header {
            background: white;
            box-shadow: var(--shadow-md);
            padding: 20px 0;
            margin-bottom: 30px;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 28px;
            font-weight: bold;
            color: var(--vtu-blue);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--vtu-blue);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: var(--vtu-blue-light);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .bookings-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }

        .booking-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            padding: 24px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -5px rgb(0 0 0 / 0.1);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .booking-id {
            font-size: 14px;
            color: var(--gray-600);
            font-weight: 500;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .event-name {
            font-size: 20px;
            font-weight: bold;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .venue-name {
            font-size: 16px;
            color: var(--vtu-blue);
            font-weight: 600;
            margin-bottom: 16px;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-600);
        }

        .detail-item i {
            color: var(--vtu-blue);
            width: 16px;
        }

        .booking-actions {
            display: flex;
            gap: 12px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--vtu-blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--vtu-blue-light);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
        }

        .empty-state i {
            font-size: 48px;
            color: var(--gray-600);
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 20px;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--gray-600);
            margin-bottom: 24px;
        }

        .success-message {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .success-message i {
            font-size: 20px;
            color: #059669;
        }

        .new-booking {
            border: 2px solid var(--vtu-green);
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }

        .filters-section {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            padding: 24px;
            margin-bottom: 24px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 14px;
        }

        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--vtu-blue);
        }

        .filter-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
        }

        .filter-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .bulk-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .results-info {
            background: var(--gray-50);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .results-count {
            color: var(--gray-700);
            font-weight: 500;
        }

        .select-all-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox {
            width: 18px;
            height: 18px;
            accent-color: var(--vtu-blue);
        }

        .booking-checkbox {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 18px;
            height: 18px;
            accent-color: var(--vtu-blue);
        }

        .booking-card {
            position: relative;
        }

        .selected-booking {
            border: 2px solid var(--vtu-blue);
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }

            .booking-details {
                grid-template-columns: 1fr;
            }

            .booking-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 class="page-title">My Bookings</h1>
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success'] ?>
                <?php if (isset($_GET['new_booking'])): ?>
                    <a href="generate_booking_pdf.php?booking_id=<?= $_GET['new_booking'] ?>" 
                       class="btn btn-primary" style="margin-left: 16px;" target="_blank">
                        <i class="fas fa-download"></i>
                        Download Confirmation PDF
                    </a>
                <?php endif; ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="filters-section">
            <h3 style="margin: 0 0 20px 0; color: var(--gray-900); font-size: 18px;">
                <i class="fas fa-filter"></i> Filter & Search Bookings
            </h3>
            
            <form method="GET" id="filterForm">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="venue">Venue</label>
                        <select name="venue" id="venue">
                            <option value="all">All Venues</option>
                            <?php foreach ($venues as $venue): ?>
                                <option value="<?= $venue['id'] ?>" <?= $venue_filter == $venue['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($venue['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date_from">From Date</label>
                        <input type="date" name="date_from" id="date_from" value="<?= htmlspecialchars($date_from) ?>">
                    </div>

                    <div class="filter-group">
                        <label for="date_to">To Date</label>
                        <input type="date" name="date_to" id="date_to" value="<?= htmlspecialchars($date_to) ?>">
                    </div>

                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" placeholder="Event name or venue..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="filter-actions">
                    <div class="filter-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Apply Filters
                        </button>
                        <a href="my_bookings.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Clear All
                        </a>
                    </div>

                    <div class="bulk-actions">
                        <button type="button" id="downloadSelected" class="btn btn-primary" disabled>
                            <i class="fas fa-download"></i>
                            Download Selected PDFs
                        </button>
                        <button type="button" id="downloadAll" class="btn btn-secondary">
                            <i class="fas fa-file-pdf"></i>
                            Download All PDFs
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Info -->
        <?php if (!empty($bookings)): ?>
            <div class="results-info">
                <div class="results-count">
                    <i class="fas fa-calendar-check"></i>
                    Showing <?= count($bookings) ?> booking<?= count($bookings) !== 1 ? 's' : '' ?>
                    <?php if ($status_filter !== 'all' || !empty($search) || !empty($date_from) || !empty($date_to) || $venue_filter !== 'all'): ?>
                        (filtered)
                    <?php endif; ?>
                </div>
                <div class="select-all-container">
                    <input type="checkbox" id="selectAll" class="checkbox">
                    <label for="selectAll">Select All</label>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Bookings Found</h3>
                <p>You haven't made any venue bookings yet.</p>
                <a href="venues.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Book a Venue
                </a>
            </div>
        <?php else: ?>
            <div class="bookings-grid">
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card <?= (isset($_GET['new_booking']) && $_GET['new_booking'] == $booking['id']) ? 'new-booking' : '' ?>" data-booking-id="<?= $booking['id'] ?>">
                        <input type="checkbox" class="booking-checkbox" value="<?= $booking['id'] ?>">
                        
                        <div class="booking-header">
                            <div class="booking-id">
                                Booking #<?= str_pad($booking['id'], 6, '0', STR_PAD_LEFT) ?>
                            </div>
                            <div class="status-badge status-<?= strtolower($booking['status']) ?>">
                                <?= ucfirst($booking['status']) ?>
                            </div>
                        </div>

                        <div class="event-name"><?= htmlspecialchars($booking['event_name']) ?></div>
                        <div class="venue-name">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($booking['venue_name']) ?>
                        </div>

                        <div class="booking-details">
                            <div class="detail-item">
                                <i class="fas fa-calendar"></i>
                                <span><?= date('d M Y', strtotime($booking['event_date'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span><?= date('h:i A', strtotime($booking['start_time'])) ?> - <?= date('h:i A', strtotime($booking['end_time'])) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-building"></i>
                                <span><?= htmlspecialchars($booking['venue_location']) ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar-plus"></i>
                                <span>Booked on <?= date('d M Y', strtotime($booking['created_at'])) ?></span>
                            </div>
                        </div>

                        <div class="booking-actions">
                            <a href="generate_booking_pdf.php?booking_id=<?= $booking['id'] ?>" 
                               class="btn btn-primary" target="_blank">
                                <i class="fas fa-download"></i>
                                Download PDF
                            </a>
                            <a href="venue_details.php?id=<?= $booking['venue_id'] ?? '' ?>" 
                               class="btn btn-secondary">
                                <i class="fas fa-eye"></i>
                                View Venue
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Checkbox functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        const bookingCheckboxes = document.querySelectorAll('.booking-checkbox');
        const downloadSelectedBtn = document.getElementById('downloadSelected');
        const downloadAllBtn = document.getElementById('downloadAll');

        // Select all functionality
        selectAllCheckbox?.addEventListener('change', function() {
            bookingCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                updateBookingCardStyle(checkbox);
            });
            updateDownloadButton();
        });

        // Individual checkbox functionality
        bookingCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBookingCardStyle(this);
                updateSelectAllState();
                updateDownloadButton();
            });
        });

        function updateBookingCardStyle(checkbox) {
            const bookingCard = checkbox.closest('.booking-card');
            if (checkbox.checked) {
                bookingCard.classList.add('selected-booking');
            } else {
                bookingCard.classList.remove('selected-booking');
            }
        }

        function updateSelectAllState() {
            const checkedBoxes = document.querySelectorAll('.booking-checkbox:checked');
            const totalBoxes = bookingCheckboxes.length;
            
            if (checkedBoxes.length === 0) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            } else if (checkedBoxes.length === totalBoxes) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else {
                selectAllCheckbox.indeterminate = true;
                selectAllCheckbox.checked = false;
            }
        }

        function updateDownloadButton() {
            const checkedBoxes = document.querySelectorAll('.booking-checkbox:checked');
            downloadSelectedBtn.disabled = checkedBoxes.length === 0;
            
            if (checkedBoxes.length > 0) {
                downloadSelectedBtn.innerHTML = `
                    <i class="fas fa-download"></i>
                    Download Selected (${checkedBoxes.length}) PDFs
                `;
            } else {
                downloadSelectedBtn.innerHTML = `
                    <i class="fas fa-download"></i>
                    Download Selected PDFs
                `;
            }
        }

        // Download selected PDFs
        downloadSelectedBtn?.addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.booking-checkbox:checked');
            const bookingIds = Array.from(checkedBoxes).map(cb => cb.value);
            
            if (bookingIds.length === 0) {
                alert('Please select at least one booking to download.');
                return;
            }

            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDFs...';
            this.disabled = true;

            // Download each PDF
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
                    if (downloadCount === bookingIds.length) {
                        // Reset button after all downloads
                        setTimeout(() => {
                            this.innerHTML = '<i class="fas fa-download"></i> Download Selected PDFs';
                            this.disabled = false;
                            updateDownloadButton();
                        }, 1000);
                    }
                }, index * 500); // Stagger downloads by 500ms
            });
        });

        // Download all PDFs
        downloadAllBtn?.addEventListener('click', function() {
            const allBookingIds = Array.from(bookingCheckboxes).map(cb => cb.value);
            
            if (allBookingIds.length === 0) {
                alert('No bookings available to download.');
                return;
            }

            const confirmDownload = confirm(`This will download ${allBookingIds.length} PDF files. Continue?`);
            if (!confirmDownload) return;

            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating All PDFs...';
            this.disabled = true;

            // Download each PDF
            let downloadCount = 0;
            allBookingIds.forEach((bookingId, index) => {
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = `generate_booking_pdf.php?booking_id=${bookingId}`;
                    link.target = '_blank';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    downloadCount++;
                    if (downloadCount === allBookingIds.length) {
                        // Reset button after all downloads
                        setTimeout(() => {
                            this.innerHTML = '<i class="fas fa-file-pdf"></i> Download All PDFs';
                            this.disabled = false;
                        }, 1000);
                    }
                }, index * 500); // Stagger downloads by 500ms
            });
        });

        // Auto-submit form on filter change (optional)
        document.querySelectorAll('#filterForm select').forEach(select => {
            select.addEventListener('change', function() {
                // Uncomment the line below for auto-submit on filter change
                // document.getElementById('filterForm').submit();
            });
        });

        // Search input with debounce
        let searchTimeout;
        document.getElementById('search')?.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Uncomment the line below for auto-submit on search
                // document.getElementById('filterForm').submit();
            }, 500);
        });

        // Initialize button states
        updateDownloadButton();
    </script>
</body>
</html>
