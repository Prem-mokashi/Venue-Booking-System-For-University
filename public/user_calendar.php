<?php
session_start();
include '../api/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT b.*, v.name as venue_name FROM bookings b JOIN venues v ON b.venue_id = v.id WHERE b.user_id = ?");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bookings Calendar</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js'></script>
    <style>
        body { margin: 40px; font-family: Arial; }
        #calendar { max-width: 900px; margin: auto; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">My Booking Calendar</h2>
    <div id='calendar'></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                initialDate: new Date(), // Set to current date
                events: [
                    <?php foreach ($bookings as $b): ?>
                    {
                        title: '<?= htmlspecialchars($b['venue_name']) ?>',
                        start: '<?= htmlspecialchars($b['event_date']) ?>',
                        description: '<?= htmlspecialchars($b['event_name']) ?>'
                    },
                    <?php endforeach; ?>
                ]
            });
            calendar.render();
        });
    </script>
</body>
</html>
