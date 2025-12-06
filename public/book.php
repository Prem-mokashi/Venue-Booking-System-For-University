<?php
$venue_id = $_GET['venue_id'] ?? 0;
?>

<form method="post" action="../api/bookings.php">
    <input type="hidden" name="venue_id" value="<?php echo $venue_id; ?>">
    <label>Booking Date:</label>
    <input type="date" name="booking_date" required>
    <input type="hidden" name="user_id" value="1">
    <button type="submit">Book</button>
</form>
