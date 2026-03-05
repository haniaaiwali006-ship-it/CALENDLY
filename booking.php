<?php
require_once 'config.php';

$db = getDBConnection();

// Get host from URL or default to logged in user
$host_username = $_GET['host'] ?? null;
$host_id = null;
$host = null;

if ($host_username) {
    $stmt = $db->prepare("SELECT id, username, full_name, email FROM users WHERE username = ?");
    $stmt->execute([$host_username]);
    $host = $stmt->fetch();
    
    if ($host) {
        $host_id = $host['id'];
    }
}

// If no host specified and user is logged in, show booking form for their own page
if (!$host_id && isLoggedIn()) {
    $user_id = getCurrentUserId();
    $stmt = $db->prepare("SELECT id, username, full_name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Redirect to their own booking page
    if ($user) {
        header('Location: booking.php?host=' . urlencode($user['username']));
        exit;
    }
}

// If still no host, show error
if (!$host) {
    die("Host not found. Please check the booking link.");
}

// Get host availability
$stmt = $db->prepare("SELECT * FROM user_availability WHERE user_id = ? AND is_active = 1 ORDER BY day_of_week");
$stmt->execute([$host_id]);
$availability = $stmt->fetchAll();

// Get booked appointments for next 30 days
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+30 days'));
$stmt = $db->prepare("SELECT appointment_date, start_time FROM appointments WHERE host_id = ? AND status = 'scheduled' AND appointment_date BETWEEN ? AND ?");
$stmt->execute([$host_id, $start_date, $end_date]);
$booked_slots = $stmt->fetchAll();

// Convert booked slots to array for easy checking
$booked_times = [];
foreach ($booked_slots as $slot) {
    $booked_times[$slot['appointment_date']][] = $slot['start_time'];
}

// Process booking form
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_name = sanitize($_POST['guest_name']);
    $guest_email = sanitize($_POST['guest_email']);
    $appointment_date = $_POST['appointment_date'];
    $start_time = $_POST['start_time'];
    $duration = intval($_POST['duration']);
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Validate required fields
    if (empty($guest_name) || empty($guest_email) || empty($appointment_date) || empty($start_time)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Calculate end time
        $end_time = date('H:i:s', strtotime($start_time) + ($duration * 60));
        
        // Validate availability
        $day_of_week = date('w', strtotime($appointment_date));
        $is_available = false;
        
        foreach ($availability as $slot) {
            if ($slot['day_of_week'] == $day_of_week) {
                $slot_start = strtotime($slot['start_time']);
                $slot_end = strtotime($slot['end_time']);
                $selected_start = strtotime($start_time);
                
                if ($selected_start >= $slot_start && ($selected_start + ($duration * 60)) <= $slot_end) {
                    $is_available = true;
                    break;
                }
            }
        }
        
        if (!$is_available) {
            $error = "Selected time slot is not available.";
        } else {
            // Check if slot is already booked
            $is_booked = false;
            if (isset($booked_times[$appointment_date])) {
                foreach ($booked_times[$appointment_date] as $booked_time) {
                    if ($booked_time == $start_time) {
                        $is_booked = true;
                        break;
                    }
                }
            }
            
            if ($is_booked) {
                $error = "This time slot is already booked.";
            } else {
                try {
                    // Create appointment
                    $stmt = $db->prepare("INSERT INTO appointments (host_id, guest_name, guest_email, appointment_date, start_time, end_time, duration, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$host_id, $guest_name, $guest_email, $appointment_date, $start_time, $end_time, $duration, $notes]);
                    
                    $appointment_id = $db->lastInsertId();
                    
                    // Show success message
                    $success = 'Appointment booked successfully!';
                    
                    // Clear form
                    $_POST = [];
                    
                } catch (PDOException $e) {
                    $error = "Booking failed. Please try again.";
                }
            }
        }
    }
}

// Generate available time slots for the next 30 days
$available_slots = [];
$current_date = new DateTime();
$end_date = new DateTime('+30 days');

while ($current_date <= $end_date) {
    $date_str = $current_date->format('Y-m-d');
    $day_of_week = $current_date->format('w');
    
    foreach ($availability as $slot) {
        if ($slot['day_of_week'] == $day_of_week) {
            $start = new DateTime($date_str . ' ' . $slot['start_time']);
            $end = new DateTime($date_str . ' ' . $slot['end_time']);
            $duration = $slot['slot_duration'];
            
            while ($start < $end) {
                $slot_start = $start->format('H:i:s');
                
                // Check if slot is already booked
                $is_booked = false;
                if (isset($booked_times[$date_str])) {
                    foreach ($booked_times[$date_str] as $booked_time) {
                        if ($booked_time == $slot_start) {
                            $is_booked = true;
                            break;
                        }
                    }
                }
                
                if (!$is_booked) {
                    $available_slots[$date_str][] = [
                        'time' => $slot_start,
                        'duration' => $duration
                    ];
                }
                
                $start->modify("+$duration minutes");
            }
        }
    }
    
    $current_date->modify('+1 day');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Meeting | SchedulePro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5B6BE6;
            --text-primary: #1F2937;
            --text-secondary: #6B7280;
            --background: #FFFFFF;
            --surface: #F9FAFB;
            --border: #E5E7EB;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--surface);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .booking-container {
            background-color: var(--background);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 900px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        
        /* Left Panel - Host Info */
        .host-panel {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
        }
        
        .host-avatar {
            width: 80px;
            height: 80px;
            background-color: white;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .host-info h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .host-info p {
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .host-details {
            margin-top: auto;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        /* Right Panel - Booking Form */
        .booking-panel {
            padding: 40px;
        }
        
        .booking-header {
            margin-bottom: 30px;
        }
        
        .booking-header h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .booking-header p {
            color: var(--text-secondary);
        }
        
        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #D1FAE5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        
        .alert-error {
            background-color: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        label.required:after {
            content: " *";
            color: #EF4444;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.3s;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .calendar {
            margin-bottom: 30px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .calendar-header h3 {
            font-size: 18px;
            font-weight: 600;
        }
        
        .calendar-nav {
            display: flex;
            gap: 10px;
        }
        
        .calendar-nav button {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background-color: var(--background);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .calendar-nav button:hover {
            background-color: var(--surface);
            border-color: var(--primary);
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .calendar-day-header {
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            padding: 8px;
        }
        
        .calendar-dates {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }
        
        .calendar-date {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .calendar-date:hover {
            background-color: var(--surface);
        }
        
        .calendar-date.selected {
            background-color: var(--primary);
            color: white;
        }
        
        .calendar-date.disabled {
            color: var(--border);
            cursor: not-allowed;
        }
        
        .calendar-date.has-slots {
            background-color: rgba(91, 107, 230, 0.1);
        }
        
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 30px;
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
        }
        
        .time-slot {
            padding: 12px;
            text-align: center;
            border: 2px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .time-slot:hover {
            border-color: var(--primary);
        }
        
        .time-slot.selected {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            font-family: 'Inter', sans-serif;
        }
        
        .btn:hover {
            background-color: var(--primary);
        }
        
        .btn:disabled {
            background-color: var(--border);
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .booking-container {
                grid-template-columns: 1fr;
            }
            
            .host-panel {
                padding: 30px 20px;
            }
            
            .booking-panel {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <!-- Left Panel - Host Info -->
        <div class="host-panel">
            <div class="host-avatar">
                <?php echo strtoupper(substr($host['full_name'] ?: $host['username'], 0, 1)); ?>
            </div>
            <div class="host-info">
                <h1><?php echo htmlspecialchars($host['full_name'] ?: $host['username']); ?></h1>
                <p>Schedule a meeting at a time that works for you.</p>
            </div>
            <div class="host-details">
                <div class="detail-item">
                    <span>⏰</span>
                    <span>Available for next 30 days</span>
                </div>
                <div class="detail-item">
                    <span>📅</span>
                    <span>Professional scheduling</span>
                </div>
            </div>
        </div>
        
        <!-- Right Panel - Booking Form -->
        <div class="booking-panel">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="booking-header">
                <h2>Book a Meeting</h2>
                <p>Select a date and time for your appointment</p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="selected_date" name="appointment_date" required>
                <input type="hidden" id="selected_time" name="start_time" required>
                <input type="hidden" id="selected_duration" name="duration" required>
                
                <!-- Calendar -->
                <div class="calendar">
                    <div class="calendar-header">
                        <h3 id="current-month"><?php echo date('F Y'); ?></h3>
                        <div class="calendar-nav">
                            <button type="button" id="prev-month">←</button>
                            <button type="button" id="next-month">→</button>
                        </div>
                    </div>
                    
                    <div class="calendar-days">
                        <div class="calendar-day-header">Sun</div>
                        <div class="calendar-day-header">Mon</div>
                        <div class="calendar-day-header">Tue</div>
                        <div class="calendar-day-header">Wed</div>
                        <div class="calendar-day-header">Thu</div>
                        <div class="calendar-day-header">Fri</div>
                        <div class="calendar-day-header">Sat</div>
                    </div>
                    
                    <div class="calendar-dates" id="calendar-dates">
                        <!-- Calendar dates will be populated by JavaScript -->
                    </div>
                </div>
                
                <!-- Time Slots -->
                <div id="time-slots-container" style="display: none;">
                    <h3 style="margin-bottom: 15px; font-size: 16px; font-weight: 600;">Available Time Slots</h3>
                    <div class="time-slots" id="time-slots">
                        <!-- Time slots will be populated by JavaScript -->
                    </div>
                </div>
                
                <!-- Guest Information -->
                <div id="guest-form" style="display: none;">
                    <div class="form-group">
                        <label for="guest_name" class="required">Your Name</label>
                        <input type="text" id="guest_name" name="guest_name" required placeholder="Enter your full name" value="<?php echo isset($_POST['guest_name']) ? htmlspecialchars($_POST['guest_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="guest_email" class="required">Your Email</label>
                        <input type="email" id="guest_email" name="guest_email" required placeholder="Enter your email address" value="<?php echo isset($_POST['guest_email']) ? htmlspecialchars($_POST['guest_email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Additional Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Any additional information you'd like to share"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn" id="book-button">Book Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Calendar data from PHP
        const availableSlots = <?php echo json_encode($available_slots); ?>;
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();
        let selectedDate = null;
        let selectedTime = null;
        let selectedDuration = null;
        
        // Render calendar
        function renderCalendar() {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('current-month').textContent = `${monthNames[currentMonth]} ${currentYear}`;
            
            const firstDay = new Date(currentYear, currentMonth, 1);
            const lastDay = new Date(currentYear, currentMonth + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDay = firstDay.getDay();
            
            const calendarDates = document.getElementById('calendar-dates');
            calendarDates.innerHTML = '';
            
            // Add empty cells for days before the first day of the month
            for (let i = 0; i < startingDay; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'calendar-date disabled';
                calendarDates.appendChild(emptyCell);
            }
            
            // Add cells for each day of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dateCell = document.createElement('div');
                dateCell.className = 'calendar-date';
                dateCell.textContent = day;
                dateCell.dataset.date = dateStr;
                
                // Check if date has available slots
                if (availableSlots[dateStr] && availableSlots[dateStr].length > 0) {
                    dateCell.classList.add('has-slots');
                    dateCell.addEventListener('click', () => selectDate(dateStr));
                } else {
                    dateCell.classList.add('disabled');
                }
                
                calendarDates.appendChild(dateCell);
            }
        }
        
        // Select a date
        function selectDate(dateStr) {
            selectedDate = dateStr;
            selectedTime = null;
            selectedDuration = null;
            
            // Update UI
            document.querySelectorAll('.calendar-date').forEach(cell => {
                cell.classList.remove('selected');
            });
            document.querySelector(`.calendar-date[data-date="${dateStr}"]`).classList.add('selected');
            
            // Show time slots
            showTimeSlots(dateStr);
            
            // Hide guest form until time is selected
            document.getElementById('guest-form').style.display = 'none';
        }
        
        // Show time slots for selected date
        function showTimeSlots(dateStr) {
            const timeSlotsContainer = document.getElementById('time-slots-container');
            const timeSlots = document.getElementById('time-slots');
            
            timeSlots.innerHTML = '';
            
            if (availableSlots[dateStr]) {
                availableSlots[dateStr].forEach(slot => {
                    const timeSlot = document.createElement('div');
                    timeSlot.className = 'time-slot';
                    timeSlot.textContent = formatTime(slot.time);
                    timeSlot.dataset.time = slot.time;
                    timeSlot.dataset.duration = slot.duration;
                    
                    timeSlot.addEventListener('click', () => selectTime(slot.time, slot.duration));
                    timeSlots.appendChild(timeSlot);
                });
                
                timeSlotsContainer.style.display = 'block';
            }
        }
        
        // Select a time slot
        function selectTime(time, duration) {
            selectedTime = time;
            selectedDuration = duration;
            
            // Update UI
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            document.querySelector(`.time-slot[data-time="${time}"]`).classList.add('selected');
            
            // Set hidden form values
            document.getElementById('selected_date').value = selectedDate;
            document.getElementById('selected_time').value = selectedTime;
            document.getElementById('selected_duration').value = selectedDuration;
            
            // Show guest form
            document.getElementById('guest-form').style.display = 'block';
            
            // Scroll to form
            document.getElementById('guest-form').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Format time for display
        function formatTime(timeStr) {
            const [hours, minutes] = timeStr.split(':');
            const date = new Date();
            date.setHours(hours);
            date.setMinutes(minutes);
            return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        }
        
        // Event listeners for calendar navigation
        document.getElementById('prev-month').addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar();
        });
        
        document.getElementById('next-month').addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar();
        });
        
        // Initial render
        renderCalendar();
    </script>
</body>
</html>
