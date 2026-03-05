<?php
require_once 'config.php';
requireLogin();

$db = getDBConnection();
$user_id = getCurrentUserId();

// Get current availability
$stmt = $db->prepare("SELECT * FROM user_availability WHERE user_id = ? ORDER BY day_of_week");
$stmt->execute([$user_id]);
$availability = $stmt->fetchAll();

// Days of week
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Delete existing availability
        $stmt = $db->prepare("DELETE FROM user_availability WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Insert new availability
        $stmt = $db->prepare("INSERT INTO user_availability (user_id, day_of_week, start_time, end_time, slot_duration, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        
        $hasAvailability = false;
        foreach ($_POST['availability'] as $day => $data) {
            if (isset($data['active']) && $data['active'] == '1') {
                $start_time = $data['start_time'] . ':00';
                $end_time = $data['end_time'] . ':00';
                $slot_duration = intval($data['slot_duration']);
                
                $stmt->execute([$user_id, $day, $start_time, $end_time, $slot_duration, 1]);
                $hasAvailability = true;
            }
        }
        
        if (!$hasAvailability) {
            // Set default availability if none selected
            $defaultAvailability = [
                [1, '09:00:00', '17:00:00'], // Monday
                [2, '09:00:00', '17:00:00'], // Tuesday
                [3, '09:00:00', '17:00:00'], // Wednesday
                [4, '09:00:00', '17:00:00'], // Thursday
                [5, '09:00:00', '17:00:00'], // Friday
            ];
            
            foreach ($defaultAvailability as $availability) {
                $stmt->execute([$user_id, $availability[0], $availability[1], $availability[2], 30, 1]);
            }
        }
        
        redirect('dashboard.php', 'Availability settings saved successfully!');
    } catch (PDOException $e) {
        $error = 'Failed to save availability settings.';
    }
}

// Format availability for display
$availability_map = [];
foreach ($availability as $slot) {
    if ($slot['is_active']) {
        $availability_map[$slot['day_of_week']] = [
            'start_time' => substr($slot['start_time'], 0, 5),
            'end_time' => substr($slot['end_time'], 0, 5),
            'slot_duration' => $slot['slot_duration']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Availability | SchedulePro</title>
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
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            background-color: var(--background);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        .availability-container {
            background-color: var(--background);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }
        
        .page-header {
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: var(--text-secondary);
        }
        
        .availability-form {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .day-card {
            background-color: var(--surface);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid var(--border);
        }
        
        .day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .day-name {
            font-size: 18px;
            font-weight: 600;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: var(--primary);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }
        
        .time-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        label {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
        }
        
        select {
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
        }
        
        select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 20px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--border);
        }
        
        .btn {
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(91, 107, 230, 0.3);
        }
        
        .btn-secondary {
            background-color: transparent;
            color: var(--text-secondary);
            border: 2px solid var(--border);
        }
        
        .btn-secondary:hover {
            background-color: var(--surface);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        
        @media (max-width: 768px) {
            .availability-container {
                padding: 30px 20px;
            }
            
            .time-inputs {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">SchedulePro</a>
                <div class="nav-links">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="availability.php" class="active">Availability</a>
                    <a href="booking.php">Book a Meeting</a>
                    <a href="logout.php">Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="availability-container">
            <div class="page-header">
                <h1>Set Your Availability</h1>
                <p>Define when you're available for meetings. These times will be shown on your booking page.</p>
            </div>
            
            <form method="POST" action="" class="availability-form">
                <?php for ($i = 0; $i < 7; $i++): ?>
                    <?php 
                    $day_availability = $availability_map[$i] ?? null;
                    ?>
                    
                    <div class="day-card">
                        <div class="day-header">
                            <div class="day-name">
                                <span><?php echo $days[$i]; ?></span>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="availability[<?php echo $i; ?>][active]" value="1" <?php echo $day_availability ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="time-inputs">
                            <div class="form-group">
                                <label for="start_time_<?php echo $i; ?>">Start Time</label>
                                <select id="start_time_<?php echo $i; ?>" name="availability[<?php echo $i; ?>][start_time]">
                                    <?php for ($hour = 0; $hour < 24; $hour++): ?>
                                        <?php for ($minute = 0; $minute < 60; $minute += 30): ?>
                                            <?php 
                                            $time = sprintf('%02d:%02d', $hour, $minute);
                                            $display = date('g:i A', strtotime($time));
                                            $selected = ($day_availability && $day_availability['start_time'] == $time) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo $time; ?>" <?php echo $selected; ?>>
                                                <?php echo $display; ?>
                                            </option>
                                        <?php endfor; ?>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_time_<?php echo $i; ?>">End Time</label>
                                <select id="end_time_<?php echo $i; ?>" name="availability[<?php echo $i; ?>][end_time]">
                                    <?php for ($hour = 0; $hour < 24; $hour++): ?>
                                        <?php for ($minute = 0; $minute < 60; $minute += 30): ?>
                                            <?php 
                                            $time = sprintf('%02d:%02d', $hour, $minute);
                                            $display = date('g:i A', strtotime($time));
                                            $selected = ($day_availability && $day_availability['end_time'] == $time) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo $time; ?>" <?php echo $selected; ?>>
                                                <?php echo $display; ?>
                                            </option>
                                        <?php endfor; ?>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="duration_<?php echo $i; ?>">Meeting Duration</label>
                                <select id="duration_<?php echo $i; ?>" name="availability[<?php echo $i; ?>][slot_duration]">
                                    <option value="15" <?php echo ($day_availability && $day_availability['slot_duration'] == 15) ? 'selected' : ''; ?>>15 minutes</option>
                                    <option value="30" <?php echo ($day_availability && $day_availability['slot_duration'] == 30) ? 'selected' : ''; ?>>30 minutes</option>
                                    <option value="45" <?php echo ($day_availability && $day_availability['slot_duration'] == 45) ? 'selected' : ''; ?>>45 minutes</option>
                                    <option value="60" <?php echo ($day_availability && $day_availability['slot_duration'] == 60) ? 'selected' : ''; ?>>60 minutes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
                
                <div class="form-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Availability</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
