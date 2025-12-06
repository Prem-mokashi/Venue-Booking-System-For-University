<?php
/* 1.  ALWAYS FIRST – loads $conn  */
require '../api/config.php';

/* 2.  Auth check  */
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Dashboard access logged (audit system removed)

/* ----------  SUCCESS/ERROR MESSAGES  ---------- */
$successMessage = $_GET['success'] ?? '';
$errorMessage = $_GET['error'] ?? '';

/* ----------  BOOKING LIST WITH FILTERS  ---------- */
$venueFilter  = $_GET['venue']  ?? '';
$dateFilter   = $_GET['date']   ?? '';
$statusFilter = $_GET['status'] ?? '';

$query = "SELECT b.*, v.name AS venue_name, b.full_name AS user_name, b.email, b.phone, b.department
          FROM bookings b
          JOIN venues v ON b.venue_id = v.id
          WHERE 1";
$params = [];

// Apply filters
if ($venueFilter)  { 
    $query .= " AND v.name = ?";  
    $params[] = $venueFilter; 
}
if ($dateFilter)   { 
    $query .= " AND b.event_date = ?"; 
    $params[] = $dateFilter; 
}
if ($statusFilter) { 
    $query .= " AND b.status = ?";     
    $params[] = $statusFilter; 
}

$query .= " ORDER BY b.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ----------  DROPDOWNS DATA  ---------- */
$venues = $conn->query("SELECT DISTINCT name FROM venues ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

/* ----------  DASHBOARD STATISTICS  ---------- */
$total       = $conn->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pendingCnt  = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
$approvedCnt = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='approved'")->fetchColumn();
$rejectedCnt = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='rejected'")->fetchColumn();
$cancelledCnt = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='cancelled'")->fetchColumn();
$openVenues  = $conn->query("SELECT COUNT(*) FROM venues WHERE is_available=1")->fetchColumn();
$totalVenues = $conn->query("SELECT COUNT(*) FROM venues")->fetchColumn();



/* ----------  QUICK STATS FOR TODAY  ---------- */
$todayBookings = $conn->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$todayApprovals = $conn->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE() AND status = 'approved'")->fetchColumn();

/* ----------  UPCOMING EVENTS (Next 7 Days)  ---------- */
$upcomingEvents = $conn->query("
    SELECT b.*, v.name AS venue_name, b.full_name AS user_name
    FROM bookings b
    JOIN venues v ON b.venue_id = v.id
    WHERE b.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND b.status = 'approved'
    ORDER BY b.event_date ASC, b.start_time ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard – VTU Booking System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root{
      --bg-light:#f8f9ff;
      --bg-mid:#f0f4f8;
      --card-bg:#ffffff;
      --text-dark:#2d3748;
      --text-light:#4a5568;
      --accent:#4299e1;
      --success:#48bb78;
      --warning:#ed8936;
      --danger:#f56565;
      --border:#e2e8f0;
      --shadow-sm:0 2px 10px rgba(0,0,0,.05);
      --shadow-md:0 4px 20px rgba(0,0,0,.08);
      --shadow-lg:0 10px 30px rgba(0,0,0,.1);
    }
    
    body{
      margin:0;
      font-family:'Inter','Segoe UI',sans-serif;
      background:linear-gradient(135deg,var(--bg-light) 0%,var(--bg-mid) 100%);
      color:var(--text-dark);
      min-height:100vh;
      line-height:1.6;
    }
    
    .container{max-width:1400px;margin:0 auto;padding:30px 20px;}
    
    .header{
      display:flex;
      justify-content:space-between;
      align-items:center;
      background:var(--card-bg);
      border-radius:16px;
      box-shadow:var(--shadow-sm);
      padding:25px 30px;
      margin-bottom:30px;
      position:relative;
    }
    
    .header h1{font-size:1.8rem;font-weight:700;margin:0;color:var(--text-dark);}
    .header .admin-info{font-size:.85rem;color:var(--text-light);margin-top:5px;}
    
    .btn-modern{
      padding:10px 22px;
      border:none;
      border-radius:12px;
      font-weight:600;
      font-size:.9rem;
      cursor:pointer;
      transition:all .3s ease;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      gap:8px;
    }
    
    .btn-primary-modern{background:var(--accent);color:#fff;}
    .btn-primary-modern:hover{background:#3182ce;transform:translateY(-2px);box-shadow:var(--shadow-md);color:#fff;}
    .btn-danger-modern{background:var(--danger);color:#fff;}
    .btn-danger-modern:hover{background:#e53e3e;transform:translateY(-2px);box-shadow:var(--shadow-md);color:#fff;}
    .btn-success-modern{background:var(--success);color:#fff;font-size:.8rem;padding:6px 14px;}
    .btn-success-modern:hover{background:#38a169;color:#fff;}
    .btn-warning-modern{background:var(--warning);color:#fff;}
    .btn-warning-modern:hover{background:#dd7324;transform:translateY(-2px);box-shadow:var(--shadow-md);color:#fff;}
    .btn-info-modern{background:#38b2ac;color:#fff;}
    .btn-info-modern:hover{background:#2c7a7b;transform:translateY(-2px);box-shadow:var(--shadow-md);color:#fff;}
    
    .stats-grid{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
      gap:20px;
      margin-bottom:30px;
    }
    
    .mini-card{
      background:var(--card-bg);
      border-radius:12px;
      padding:20px;
      text-align:center;
      box-shadow:var(--shadow-sm);
      transition:all .3s ease;
      position:relative;
      overflow:hidden;
    }
    
    .mini-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md);}
    .mini-card h5{color:var(--accent);font-size:1.6rem;margin:0 0 4px;font-weight:700;}
    .mini-card small{color:var(--text-light);font-size:.85rem;font-weight:500;}
    .mini-card .card-icon{position:absolute;top:15px;right:15px;font-size:1.5rem;opacity:.2;}
    
    .mini-card.pending h5{color:var(--warning);}
    .mini-card.approved h5{color:var(--success);}
    .mini-card.rejected h5{color:var(--danger);}
    .mini-card.cancelled h5{color:#6b7280;}
    
    .alert-modern{
      border:none;
      border-radius:12px;
      padding:15px 20px;
      margin-bottom:20px;
      box-shadow:var(--shadow-sm);
    }
    
    .filter-section{
      background:var(--card-bg);
      border-radius:12px;
      padding:25px;
      box-shadow:var(--shadow-sm);
      margin-bottom:30px;
    }
    
    .filter-title{
      font-size:1.05rem;
      font-weight:600;
      margin-bottom:20px;
      color:var(--text-dark);
      display:flex;
      align-items:center;
      gap:8px;
    }
    
    .filter-form{
      display:flex;
      align-items:end;
      gap:15px;
      flex-wrap:wrap;
    }
    
    .filter-group{
      flex:1;
      min-width:200px;
      display:flex;
      flex-direction:column;
    }
    
    .filter-group label{
      font-size:0.9rem;
      font-weight:600;
      color:var(--text-dark);
      margin-bottom:8px;
    }
    
    .filter-button-group{
      display:flex;
      gap:10px;
      align-items:end;
    }
    
    .analytics-filter-form{
      display:flex;
      align-items:end;
      gap:15px;
      flex-wrap:wrap;
    }
    
    .analytics-filter-group{
      flex:1;
      min-width:180px;
      display:flex;
      flex-direction:column;
    }
    
    .analytics-filter-group label{
      font-size:0.9rem;
      font-weight:600;
      color:var(--text-dark);
      margin-bottom:8px;
    }
    
    .analytics-filter-button{
      display:flex;
      align-items:end;
    }
    
    .analytics-charts-grid{
      display:grid;
      grid-template-columns:repeat(2, 1fr);
      gap:20px;
      align-items:start;
    }
    
    .analytics-charts-grid .chart-container{
      height:300px;
      display:flex;
      flex-direction:column;
    }
    
    .analytics-charts-grid .chart-title{
      margin-bottom:15px;
      text-align:center;
    }
    
    .analytics-charts-grid canvas{
      flex:1;
      max-height:250px;
    }
    
    .form-control-modern,.form-select-modern{
      padding:10px 14px;
      border:2px solid var(--border);
      border-radius:10px;
      font-size:.9rem;
      background:var(--bg-light);
      transition:all .3s ease;
    }
    
    .form-control-modern:focus,.form-select-modern:focus{
      border-color:var(--accent);
      background:#fff;
      outline:none;
      box-shadow:0 0 0 3px rgba(66,153,225,.1);
    }
    
    .table-container{
      background:var(--card-bg);
      border-radius:12px;
      box-shadow:var(--shadow-sm);
      overflow:hidden;
      margin-bottom:30px;
    }
    
    .table-header{
      background:var(--bg-light);
      padding:20px 25px;
      border-bottom:1px solid var(--border);
      display:flex;
      justify-content:space-between;
      align-items:center;
      flex-wrap:wrap;
      gap:15px;
    }
    
    .table-title{font-size:1.15rem;font-weight:600;margin:0;color:var(--text-dark);}
    
    .modern-table{width:100%;border-collapse:collapse;font-size:.9rem;}
    .modern-table th{
      background:var(--bg-light);
      padding:14px 20px;
      text-align:left;
      font-weight:600;
      color:var(--text-dark);
      border-bottom:2px solid var(--border);
    }
    .modern-table td{padding:14px 20px;border-bottom:1px solid var(--border);vertical-align:middle;}
    .modern-table tbody tr{transition:background-color .2s ease;}
    .modern-table tbody tr:hover{background:#fafbfc;}
    
    .status-badge{
      padding:5px 12px;
      border-radius:20px;
      font-size:.7rem;
      font-weight:600;
      text-transform:uppercase;
      display:inline-block;
    }
    .status-pending{background:rgba(237,137,54,.15);color:#ed8936;}
    .status-approved{background:rgba(72,187,120,.15);color:#48bb78;}
    .status-rejected{background:rgba(245,101,101,.15);color:#f56565;}
    .status-cancelled{background:rgba(107,114,128,.15);color:#6b7280;}
    
    .contact-info{
      display:flex;
      flex-direction:column;
      gap:3px;
      font-size:.8rem;
      color:var(--text-light);
    }
    .contact-info i{width:14px;}
    
    .dropdown-menu-modern{
      border:none;
      box-shadow:var(--shadow-lg);
      border-radius:12px;
      padding:8px;
      background:var(--card-bg);
    }
    
    .dropdown-item-modern{
      padding:12px 16px;
      border-radius:8px;
      color:var(--text-dark);
      text-decoration:none;
      transition:all .3s ease;
      display:flex;
      align-items:center;
      gap:10px;
    }
    .dropdown-item-modern:hover{background:var(--bg-light);color:var(--text-dark);}
    
    .dropdown-toggle-dots{
      background:var(--bg-light);
      border:2px solid var(--border);
      border-radius:50%;
      width:40px;
      height:40px;
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      transition:all .3s ease;
    }
    .dropdown-toggle-dots:hover{background:var(--accent);color:white;border-color:var(--accent);}
    
    .stats-container{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:30px;}
    .chart-container{background:var(--card-bg);border-radius:12px;padding:20px;box-shadow:var(--shadow-sm);}
    .chart-title{font-size:1.1rem;font-weight:600;margin-bottom:15px;color:var(--text-dark);}
    

    
    .upcoming-events{
      background:var(--card-bg);
      border-radius:12px;
      padding:20px;
      box-shadow:var(--shadow-sm);
    }
    
    .event-item{
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:10px 0;
      border-bottom:1px solid var(--border);
    }
    .event-item:last-child{border-bottom:none;}
    .event-info h6{margin:0;font-size:.9rem;font-weight:600;}
    .event-info small{color:var(--text-light);}
    .event-date{text-align:right;font-size:.8rem;color:var(--text-light);}
    
    .loading-spinner{
      width:40px;
      height:40px;
      border:4px solid var(--border);
      border-top:4px solid var(--accent);
      border-radius:50%;
      animation:spin 1s linear infinite;
    }
    
    @keyframes spin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}
    
    @media(max-width:768px){
      .header{flex-direction:column;gap:15px;text-align:center;}
      .stats-grid{grid-template-columns:repeat(2,1fr);}
      .stats-container{grid-template-columns:1fr;}
      .table-header{flex-direction:column;align-items:stretch;}
      .container{padding:20px 15px;}
      .filter-form{flex-direction:column;align-items:stretch;}
      .filter-group{min-width:100%;}
      .filter-button-group{flex-direction:column;width:100%;}
      .filter-button-group .btn-modern{width:100%;margin-bottom:10px;}
      .analytics-filter-form{flex-direction:column;align-items:stretch;}
      .analytics-filter-group{min-width:100%;}
      .analytics-filter-button{width:100%;margin-top:15px;}
      .analytics-filter-button .btn-modern{width:100%;}
      .analytics-charts-grid{grid-template-columns:1fr;}
    }
    
    @media(max-width:480px){
      .stats-grid{grid-template-columns:1fr;}
      .modern-table{font-size:.8rem;}
      .modern-table th,.modern-table td{padding:10px 12px;}
    }
  </style>
</head>

<body>
  <!-- Loading Spinner -->
  <div id="spinner" style="position:fixed;inset:0;background:rgba(255,255,255,.9);z-index:9999;display:none;justify-content:center;align-items:center;">
    <div class="loading-spinner"></div>
  </div>

  <div class="container">
    <!-- Header Section -->
    <div class="header">
      <div>
        <h1><i class="fas fa-tachometer-alt" style="color:var(--accent);margin-right:10px;"></i>Admin Dashboard</h1>
        <div class="admin-info">
          Welcome back, <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?></strong> | 
          Last login: <?= date('M j, Y \a\t g:i A') ?>
        </div>
      </div>
      <div class="d-flex align-items-center gap-3">
        <div class="dropdown">
          <button class="dropdown-toggle-dots" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-ellipsis-v"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end">
            <li><a class="dropdown-item-modern" href="admin_venue_manager.php">
              <i class="fas fa-building"></i> Manage Venues</a></li>
            <li><a class="dropdown-item-modern" href="#" onclick="openStatsModal()">
              <i class="fas fa-chart-bar"></i> Statistics</a></li>
            <li><a class="dropdown-item-modern" href="#" onclick="openAuditModal()">
              <i class="fas fa-history"></i> Audit Logs</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item-modern" href="#" onclick="openExportModal()">
              <i class="fas fa-download"></i> Export Data</a></li>
          </ul>
        </div>
        <a href="admin_logout.php" class="btn-modern btn-danger-modern" onclick="showSpinner()">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($successMessage): ?>
      <div class="alert alert-success alert-modern">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
      </div>
    <?php endif; ?>
    
    <?php if ($errorMessage): ?>
      <div class="alert alert-danger alert-modern">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($errorMessage) ?>
      </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="mini-card">
        <i class="fas fa-calendar-alt card-icon"></i>
        <h5><?= $total ?></h5>
        <small>Total Bookings</small>
      </div>
      <div class="mini-card pending">
        <i class="fas fa-clock card-icon"></i>
        <h5><?= $pendingCnt ?></h5>
        <small>Pending Approval</small>
      </div>
      <div class="mini-card approved">
        <i class="fas fa-check-circle card-icon"></i>
        <h5><?= $approvedCnt ?></h5>
        <small>Approved</small>
      </div>
      <div class="mini-card rejected">
        <i class="fas fa-times-circle card-icon"></i>
        <h5><?= $rejectedCnt ?></h5>
        <small>Rejected</small>
      </div>
      <div class="mini-card cancelled">
        <i class="fas fa-ban card-icon"></i>
        <h5><?= $cancelledCnt ?></h5>
        <small>Cancelled</small>
      </div>
      <div class="mini-card">
        <i class="fas fa-building card-icon"></i>
        <h5><?= $openVenues ?>/<?= $totalVenues ?></h5>
        <small>Available Venues</small>
      </div>
      <div class="mini-card">
        <i class="fas fa-calendar-day card-icon"></i>
        <h5><?= $todayBookings ?></h5>
        <small>Today's Bookings</small>
      </div>
    </div>

    <!-- Upcoming Events Section -->
    <div class="upcoming-events" style="max-width: 800px; margin: 30px auto;">
        <h5 style="margin-bottom:15px;"><i class="fas fa-calendar-week" style="color:var(--success);margin-right:6px;"></i>Upcoming Events</h5>
        <?php if(empty($upcomingEvents)): ?>
          <p style="color:var(--text-light);font-style:italic;">No upcoming approved events.</p>
        <?php else: ?>
          <?php foreach($upcomingEvents as $event): ?>
            <div class="event-item">
              <div class="event-info">
                <h6><?= htmlspecialchars($event['event_name']) ?></h6>
                <small>
                  <i class="fas fa-building"></i> <?= htmlspecialchars($event['venue_name']) ?> | 
                  <i class="fas fa-user"></i> <?= htmlspecialchars($event['user_name']) ?>
                </small>
              </div>
              <div class="event-date">
                <div style="font-weight:600;"><?= date('M j', strtotime($event['event_date'])) ?></div>
                <small><?= date('g:i A', strtotime($event['start_time'])) ?></small>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <div class="filter-title">
        <i class="fas fa-filter" style="color:var(--accent);"></i>
        Filter Bookings
      </div>
      <form method="GET" onsubmit="showSpinner()">
        <div class="filter-form">
          <div class="filter-group">
            <label>Venue</label>
            <select name="venue" class="form-select-modern">
              <option value="">All Venues</option>
              <?php foreach($venues as $v): ?>
                <option value="<?= htmlspecialchars($v) ?>" <?= $venueFilter===$v?'selected':'' ?>><?= htmlspecialchars($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group">
            <label>Date</label>
            <input type="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>" class="form-control-modern" placeholder="dd-mm-yyyy">
          </div>
          <div class="filter-group">
            <label>Status</label>
            <select name="status" class="form-select-modern">
              <option value="">All Status</option>
              <option value="pending"  <?= $statusFilter==='pending'?'selected':'' ?>>Pending</option>
              <option value="approved" <?= $statusFilter==='approved'?'selected':'' ?>>Approved</option>
              <option value="rejected" <?= $statusFilter==='rejected'?'selected':'' ?>>Rejected</option>
              <option value="cancelled" <?= $statusFilter==='cancelled'?'selected':'' ?>>Cancelled</option>
            </select>
          </div>
          <div class="filter-button-group">
            <button type="submit" class="btn-modern btn-primary-modern">
              <i class="fas fa-search"></i> Apply Filter
            </button>
            <?php if($venueFilter || $dateFilter || $statusFilter): ?>
              <a href="admin_dashboard.php" class="btn-modern btn-warning-modern">
                <i class="fas fa-times"></i> Clear
              </a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>

    <!-- Bookings Table -->
    <div class="table-container">
      <div class="table-header">
        <h3 class="table-title">
          <i class="fas fa-table" style="color:var(--accent);margin-right:6px;"></i>Booking Management
          <?php if($venueFilter || $dateFilter || $statusFilter): ?>
            <small style="color:var(--text-light);margin-left:8px;">(Filtered Results: <?= count($bookings) ?>)</small>
          <?php endif; ?>
        </h3>
        <div class="d-flex gap-2">
          <button class="btn-modern btn-info-modern" onclick="openExportModal()">
            <i class="fas fa-download"></i> Export PDF
          </button>
        </div>
      </div>
      
      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th><i class="fas fa-user"></i> User</th>
              <th><i class="fas fa-phone"></i> Contact</th>
              <th><i class="fas fa-building"></i> Venue</th>
              <th><i class="fas fa-calendar"></i> Event</th>
              <th><i class="fas fa-clock"></i> Date & Time</th>
              <th><i class="fas fa-info-circle"></i> Status</th>
              <th><i class="fas fa-cogs"></i> Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($bookings)): ?>
              <tr>
                <td colspan="7" style="text-align:center;padding:40px;color:var(--text-light);">
                  <i class="fas fa-inbox" style="font-size:2.5rem;opacity:.3;margin-bottom:10px;"></i><br>
                  <?php if($venueFilter || $dateFilter || $statusFilter): ?>
                    No bookings found matching your filter criteria.
                  <?php else: ?>
                    No bookings found in the system.
                  <?php endif; ?>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach($bookings as $b): ?>
                <tr>
                  <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($b['user_name']) ?></div>
                    <small style="color:var(--text-light);"><?= htmlspecialchars($b['department'] ?? 'N/A') ?></small>
                  </td>
                  <td class="contact-info">
                    <div><i class="fas fa-phone"></i> <?= htmlspecialchars($b['phone']) ?></div>
                    <div><i class="fas fa-envelope"></i> <?= htmlspecialchars($b['email']) ?></div>
                  </td>
                  <td><?= htmlspecialchars($b['venue_name']) ?></td>
                  <td><?= htmlspecialchars($b['event_name']) ?></td>
                  <td>
                    <?= date('M j, Y', strtotime($b['event_date'])) ?><br>
                    <small><?= date('g:i A', strtotime($b['start_time'])) ?> - <?= date('g:i A', strtotime($b['end_time'])) ?></small>
                  </td>
                  <td><span class="status-badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                  <td>
                    <?php if($b['status']==='pending'): ?>
                      <div style="display:flex;gap:6px;margin-bottom:6px;flex-wrap:wrap;">
                        <button class="btn-modern btn-success-modern" onclick="openStatusModal(<?= $b['id'] ?>,'approved')">
                          <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn-modern btn-danger-modern" style="font-size:.8rem;padding:6px 14px;" onclick="openStatusModal(<?= $b['id'] ?>,'rejected')">
                          <i class="fas fa-times"></i> Reject
                        </button>
                      </div>
                    <?php else: ?>
                      <small style="color:var(--text-light);font-style:italic;">
                        <i class="fas fa-lock"></i> No actions available
                      </small>
                    <?php endif; ?>
                    <br>
                    <a href="#" class="view-details" style="font-size:.8rem;color:var(--accent);text-decoration:none;" onclick="toggleDetails('details<?= $b['id'] ?>');return false;">
                      <i class="fas fa-eye"></i> View Details
                    </a>
                  </td>
                </tr>
                <tr id="details<?= $b['id'] ?>" style="display:none;background:#fafbfc;">
                  <td colspan="7" style="padding:0;">
                    <div style="padding:20px;border-left:4px solid var(--accent);background:#fff;margin:10px;border-radius:8px;box-shadow:var(--shadow-sm);">
                      <div class="row">
                        <div class="col-md-6">
                          <h6 style="margin-bottom:10px;font-weight:600;color:var(--accent);">Event Details</h6>
                          <p style="white-space:pre-wrap;margin-bottom:15px;line-height:1.5;"><?= nl2br(htmlspecialchars($b['event_details'] ?? 'No additional details provided.')) ?></p>
                          
                          <h6 style="margin-bottom:10px;font-weight:600;color:var(--accent);">User Information</h6>
                          <p style="margin-bottom:10px;"><strong>Department:</strong> <?= htmlspecialchars($b['department'] ?? 'Not specified') ?></p>
                          <p style="margin-bottom:15px;"><strong>Contact:</strong> <?= htmlspecialchars($b['phone']) ?> | <?= htmlspecialchars($b['email']) ?></p>
                        </div>
                        <div class="col-md-6">
                          <h6 style="margin-bottom:10px;font-weight:600;color:var(--accent);">Booking Timeline</h6>
                          <p style="margin-bottom:10px;"><strong>Created:</strong> <?= date('F j, Y \a\t g:i A', strtotime($b['created_at'])) ?></p>

                          
                          <?php if($b['admin_remarks']): ?>
                            <h6 style="margin-bottom:10px;font-weight:600;color:var(--accent);">Admin Remarks</h6>
                            <p style="margin-bottom:15px;padding:10px;background:var(--bg-light);border-radius:8px;font-style:italic;">
                              "<?= nl2br(htmlspecialchars($b['admin_remarks'])) ?>"
                            </p>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick Actions Summary (if there are pending bookings) -->
    <?php if($pendingCnt > 0): ?>
    <div class="alert alert-info alert-modern">
      <i class="fas fa-info-circle"></i> 
      You have <strong><?= $pendingCnt ?></strong> pending booking<?= $pendingCnt > 1 ? 's' : '' ?> that require your attention.
      <a href="?status=pending" class="btn btn-sm btn-outline-primary ms-2">View Pending</a>
    </div>
    <?php endif; ?>

  </div>

  <!-- ===== MODALS ===== -->

  <!-- PDF Export Modal -->
  <div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-download" style="color:var(--accent);margin-right:6px;"></i>Export Bookings to PDF
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="export_pdf_simple.php" target="_blank" onsubmit="logExportAction()">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Export Type</label>
              <select name="export_type" class="form-select-modern" onchange="toggleExportOptions(this.value)">
                <option value="all">All Bookings</option>
                <option value="date">By Specific Date</option>
                <option value="venue">By Venue</option>
                <option value="status">By Status</option>
                <option value="custom">Custom Filter</option>
              </select>
            </div>
            
            <div id="dateOption" style="display:none;">
              <label class="form-label">Select Date</label>
              <input type="date" name="export_date" class="form-control-modern">
            </div>
            
            <div id="venueOption" style="display:none;">
              <label class="form-label">Select Venue</label>
              <select name="export_venue" class="form-select-modern">
                <option value="">Choose Venue</option>
                <?php foreach($venues as $v): ?>
                  <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($v) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div id="statusOption" style="display:none;">
              <label class="form-label">Select Status</label>
              <select name="export_status" class="form-select-modern">
                <option value="">Choose Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            
            <div id="customOption" style="display:none;">
              <div class="mb-3">
                <label class="form-label">Date Range</label>
                <div class="row">
                  <div class="col-6">
                    <input type="date" name="export_from_date" class="form-control-modern" placeholder="From">
                  </div>
                  <div class="col-6">
                    <input type="date" name="export_to_date" class="form-control-modern" placeholder="To">
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Venue (Optional)</label>
                <select name="export_custom_venue" class="form-select-modern">
                  <option value="">All Venues</option>
                  <?php foreach($venues as $v): ?>
                    <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($v) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Status (Optional)</label>
                <select name="export_custom_status" class="form-select-modern">
                  <option value="">All Status</option>
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="rejected">Rejected</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn-modern btn-primary-modern">
              <i class="fas fa-download"></i> Generate PDF
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Audit Logs Modal -->
  <div class="modal fade" id="auditModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-history" style="color:var(--accent);margin-right:6px;"></i>Complete Audit Trail
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label">Filter by Admin</label>
              <select id="auditAdminFilter" class="form-select-modern" onchange="filterAuditLogs()">
                <option value="">All Admins</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">From Date</label>
              <input type="date" id="auditFromDate" class="form-control-modern" onchange="filterAuditLogs()">
            </div>
            <div class="col-md-4">
              <label class="form-label">To Date</label>
              <input type="date" id="auditToDate" class="form-control-modern" onchange="filterAuditLogs()">
            </div>
          </div>
          <div id="auditLogsContainer">
            <div class="text-center">
              <div class="loading-spinner" style="margin:20px auto;"></div>
              <p>Loading audit logs...</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Modal -->
  <div class="modal fade" id="statisticsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-chart-bar" style="color:var(--accent);margin-right:6px;"></i>Analytics Dashboard
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="analytics-filter-section" style="background:var(--bg-light);border-radius:12px;padding:20px;margin-bottom:25px;">
            <div class="analytics-filter-form">
              <div class="analytics-filter-group">
                <label class="form-label">Venue</label>
                <select id="statsVenueFilter" class="form-select-modern" onchange="refreshDashboard()">
                  <option value="">All Venues</option>
                  <?php foreach($venues as $v): ?>
                    <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($v) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="analytics-filter-group">
                <label class="form-label">From Month</label>
                <input type="month" id="statsFromMonth" class="form-control-modern" onchange="refreshDashboard()">
              </div>
              <div class="analytics-filter-group">
                <label class="form-label">To Month</label>
                <input type="month" id="statsToMonth" class="form-control-modern" onchange="refreshDashboard()">
              </div>
              <div class="analytics-filter-button">
                <button class="btn-modern btn-info-modern" onclick="refreshDashboard()">
                  <i class="fas fa-sync"></i> Refresh
                </button>
              </div>
            </div>
          </div>

          <div class="analytics-charts-grid">
            <div class="chart-container">
              <div class="chart-title">Venue Utilization</div>
              <canvas id="venueChart"></canvas>
            </div>
            <div class="chart-container">
              <div class="chart-title">Monthly Booking Trend</div>
              <canvas id="trendChart"></canvas>
            </div>
            <div class="chart-container">
              <div class="chart-title">Success Rate Trend</div>
              <canvas id="successChart"></canvas>
            </div>
            <div class="chart-container">
              <div class="chart-title">Peak Hours</div>
              <canvas id="peakChart"></canvas>
            </div>
            <div class="chart-container">
              <div class="chart-title">Top 10 Active Users</div>
              <canvas id="usersChart"></canvas>
            </div>
            <div class="chart-container">
              <div class="chart-title">Advance Booking Lead-Time</div>
              <canvas id="leadChart"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Status Update Modal -->
  <div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
      <form id="statusForm" method="post" action="update_booking_status.php" onsubmit="showSpinner()">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-edit" style="color:var(--accent);margin-right:6px;"></i>Confirm Action
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i> <span id="statusText"></span>
            </div>
            <div class="mb-3">
              <label class="form-label">Admin Remarks (Optional)</label>
              <textarea name="admin_remarks" placeholder="Add any remarks or reason for this decision..." class="form-control-modern w-100" rows="3"></textarea>
              <small class="form-text text-muted">These remarks will be visible to other admins and can be included in reports.</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fas fa-times"></i> Cancel
            </button>
            <button type="submit" class="btn-modern btn-primary-modern">
              <i class="fas fa-check"></i> Confirm Action
            </button>
            <input type="hidden" name="booking_id" id="bookingId">
            <input type="hidden" name="status" id="statusField">
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- ===== JAVASCRIPT ===== -->
  <script>
    let venueChart, trendChart, successChart, peakChart, usersChart, leadChart;

    // Utility Functions
    function showSpinner() {
      document.getElementById('spinner').style.display = 'flex';
    }

    function hideSpinner() {
      document.getElementById('spinner').style.display = 'none';
    }

    // Modal Functions
    function openStatusModal(id, status) {
      const modal = new bootstrap.Modal(document.getElementById('statusModal'));
      document.getElementById('bookingId').value = id;
      document.getElementById('statusField').value = status;
      
      const statusText = status === 'approved' ? 
        'Are you sure you want to APPROVE this booking?' :
        'Are you sure you want to REJECT this booking?';
      
      document.getElementById('statusText').innerText = statusText;
      modal.show();
    }

    function toggleDetails(id) {
      const row = document.getElementById(id);
      const isVisible = row.style.display !== 'none';
      row.style.display = isVisible ? 'none' : '';
      
      // Update the link text
      const link = document.querySelector(`[onclick="toggleDetails('${id}')"]`);
      if (link) {
        const icon = link.querySelector('i');
        const text = isVisible ? 'View Details' : 'Hide Details';
        const iconClass = isVisible ? 'fa-eye' : 'fa-eye-slash';
        
        icon.className = `fas ${iconClass}`;
        link.innerHTML = `<i class="fas ${iconClass}"></i> ${text}`;
      }
    }

    function openExportModal() {
      const modal = new bootstrap.Modal(document.getElementById('exportModal'));
      modal.show();
    }

    function toggleExportOptions(type) {
      // Hide all options first
      ['dateOption', 'venueOption', 'statusOption', 'customOption'].forEach(id => {
        document.getElementById(id).style.display = 'none';
      });
      
      // Show relevant option
      if (type !== 'all') {
        const targetId = type + 'Option';
        const element = document.getElementById(targetId);
        if (element) {
          element.style.display = 'block';
        }
      }
    }

    function logExportAction() {
      // This could send an AJAX request to log the export action
      console.log('PDF export initiated');
    }

    function openStatsModal() {
      const modal = new bootstrap.Modal(document.getElementById('statisticsModal'));
      modal.show();
      setTimeout(() => refreshDashboard(), 500); // Small delay to ensure modal is shown
    }

    function openAuditModal() {
      const modal = new bootstrap.Modal(document.getElementById('auditModal'));
      modal.show();
      loadAuditLogs();
    }

    // Audit Functions
    async function loadAuditLogs() {
      try {
        const response = await fetch('audit_api.php');
        const data = await response.json();
        
        // Populate admin filter
        const adminFilter = document.getElementById('auditAdminFilter');
        adminFilter.innerHTML = '<option value="">All Admins</option>';
        data.admins.forEach(admin => {
          adminFilter.innerHTML += `<option value="${admin.id}">${admin.name}</option>`;
        });
        
        displayAuditLogs(data.logs);
      } catch (error) {
        console.error('Error loading audit logs:', error);
        document.getElementById('auditLogsContainer').innerHTML = 
          '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error loading audit logs. Please try again.</div>';
      }
    }

    async function filterAuditLogs() {
      const admin = document.getElementById('auditAdminFilter').value;
      const fromDate = document.getElementById('auditFromDate').value;
      const toDate = document.getElementById('auditToDate').value;
      
      const params = new URLSearchParams();
      if (admin) params.append('admin_id', admin);
      if (fromDate) params.append('from_date', fromDate);
      if (toDate) params.append('to_date', toDate);
      
      try {
        document.getElementById('auditLogsContainer').innerHTML = 
          '<div class="text-center"><div class="loading-spinner" style="margin:20px auto;"></div><p>Filtering audit logs...</p></div>';
        
        const response = await fetch('audit_api.php?' + params.toString());
        const data = await response.json();
        displayAuditLogs(data.logs);
      } catch (error) {
        console.error('Error filtering audit logs:', error);
        document.getElementById('auditLogsContainer').innerHTML = 
          '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Error filtering audit logs. Please try again.</div>';
      }
    }

    function displayAuditLogs(logs) {
      const container = document.getElementById('auditLogsContainer');
      
      if (logs.length === 0) {
        container.innerHTML = '<div class="text-center text-muted"><i class="fas fa-inbox fa-3x mb-3" style="opacity:0.3;"></i><p>No audit logs found.</p></div>';
        return;
      }
      
      let html = '<div class="table-responsive"><table class="table table-hover"><thead class="table-light">';
      html += '<tr><th><i class="fas fa-clock"></i> Date/Time</th><th><i class="fas fa-user"></i> Admin</th><th><i class="fas fa-cog"></i> Action</th><th><i class="fas fa-hashtag"></i> Booking ID</th><th><i class="fas fa-info-circle"></i> Details</th></tr></thead><tbody>';
      
      logs.forEach(log => {
        const actionBadge = getActionBadge(log.action);
        const formattedDate = new Date(log.created_at).toLocaleString();
        
        html += `<tr>
          <td><small>${formattedDate}</small></td>
          <td>${log.admin_name || '<em>System</em>'}</td>
          <td>${actionBadge}</td>
          <td>${log.booking_id ? `<span class="badge bg-info">#${log.booking_id}</span>` : '<small class="text-muted">N/A</small>'}</td>
          <td><small>${log.details || 'No additional details'}</small></td>
        </tr>`;
      });
      
      html += '</tbody></table></div>';
      container.innerHTML = html;
    }

    function getActionBadge(action) {
      let badgeClass = 'bg-primary';
      if (action.includes('approved')) badgeClass = 'bg-success';
      else if (action.includes('rejected')) badgeClass = 'bg-danger';
      else if (action.includes('Login') || action.includes('Logout')) badgeClass = 'bg-info';
      else if (action.includes('Export')) badgeClass = 'bg-warning';
      
      return `<span class="badge ${badgeClass}">${action}</span>`;
    }

    // Statistics Functions
    async function refreshDashboard() {
      const venue = document.getElementById('statsVenueFilter').value;
      const from = document.getElementById('statsFromMonth').value;
      const to = document.getElementById('statsToMonth').value;

      const params = new URLSearchParams();
      if (venue) params.append('venue', venue);
      if (from) params.append('from', from);
      if (to) params.append('to', to);

      try {
        const res = await fetch('stats_api.php?' + params.toString());
        const data = await res.json();
        drawAllCharts(data);
      } catch (error) {
        console.error('Error loading statistics:', error);
      }
    }

    function drawAllCharts(d) {
      // Destroy existing charts
      [venueChart, trendChart, successChart, peakChart, usersChart, leadChart].forEach(chart => {
        if (chart) chart.destroy();
      });

      // 1. Venue Utilization
      const vCtx = document.getElementById('venueChart').getContext('2d');
      venueChart = new Chart(vCtx, {
        type: 'bar',
        data: {
          labels: d.venueStats?.map(v => v.name) || [],
          datasets: [
            {
              label: 'Bookings',
              data: d.venueStats?.map(v => v.booking_count) || [],
              backgroundColor: 'rgba(66,153,225,0.7)',
              yAxisID: 'y'
            },
            {
              label: 'Utilization %',
              data: d.venueStats?.map(v => v.utilization_rate) || [],
              type: 'line',
              borderColor: 'rgba(245,101,101,1)',
              yAxisID: 'y1'
            }
          ]
        },
        options: {
          responsive: true,
          scales: {
            y: { beginAtZero: true },
            y1: { beginAtZero: true, max: 100, grid: { drawOnChartArea: false } }
          }
        }
      });

      // 2. Monthly Trend
      const tCtx = document.getElementById('trendChart').getContext('2d');
      trendChart = new Chart(tCtx, {
        type: 'line',
        data: {
          labels: d.monthlyStats?.map(m => new Date(m.month + '-01').toLocaleDateString('en-US', { month: 'short', year: 'numeric' })) || [],
          datasets: [{
            label: 'Bookings',
            data: d.monthlyStats?.map(m => m.bookings) || [],
            borderColor: 'rgba(72,187,120,1)',
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          scales: { y: { beginAtZero: true } }
        }
      });

      // 3. Success Rate
      const sCtx = document.getElementById('successChart').getContext('2d');
      successChart = new Chart(sCtx, {
        type: 'line',
        data: {
          labels: d.successRate?.map(s => new Date(s.month + '-01').toLocaleDateString('en-US', { month: 'short', year: 'numeric' })) || [],
          datasets: [{
            label: 'Success Rate %',
            data: d.successRate?.map(s => s.success_rate) || [],
            borderColor: 'rgba(54,162,235,1)',
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          scales: { y: { beginAtZero: true, max: 100 } }
        }
      });

      // 4. Peak Hours
      const pCtx = document.getElementById('peakChart').getContext('2d');
      peakChart = new Chart(pCtx, {
        type: 'bar',
        data: {
          labels: d.timeStats?.map(t => t.hour + ':00') || [],
          datasets: [{
            label: 'Bookings',
            data: d.timeStats?.map(t => t.booking_count) || [],
            backgroundColor: 'rgba(153,102,255,0.7)'
          }]
        },
        options: {
          responsive: true,
          scales: { y: { beginAtZero: true } }
        }
      });

      // 5. Top Users
      const uCtx = document.getElementById('usersChart').getContext('2d');
      usersChart = new Chart(uCtx, {
        type: 'bar',
        data: {
          labels: d.topUsers?.map(u => u.name) || [],
          datasets: [{
            label: 'Bookings',
            data: d.topUsers?.map(u => u.total_bookings) || [],
            backgroundColor: 'rgba(255,159,64,0.7)'
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          scales: { x: { beginAtZero: true } }
        }
      });

      // 6. Advance Lead-Time
      const lCtx = document.getElementById('leadChart').getContext('2d');
      leadChart = new Chart(lCtx, {
        type: 'bar',
        data: {
          labels: d.advanceStats?.map(a => a.advance_period) || [],
          datasets: [{
            label: 'Bookings',
            data: d.advanceStats?.map(a => a.booking_count) || [],
            backgroundColor: 'rgba(255,99,132,0.7)'
          }]
        },
        options: {
          responsive: true,
          scales: { y: { beginAtZero: true } }
        }
      });
    }

    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
      const alerts = document.querySelectorAll('.alert-modern');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.style.opacity = '0';
          setTimeout(() => alert.remove(), 300);
        }, 5000);
      });
    });

    // Auto-refresh data every 5 minutes
    setInterval(() => {
      if (document.visibilityState === 'visible') {
        location.reload();
      }
    }, 300000); // 5 minutes
  </script>
</body>
</html>