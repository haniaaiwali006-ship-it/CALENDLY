<?php
require_once 'config.php';
requireLogin();

$db = getDBConnection();
$user_id = getCurrentUserId();

// Get user info
$stmt = $db->prepare("SELECT username, email, full_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    redirect('login.php', 'User not found. Please login again.');
}

// Get appointment statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM appointments 
    WHERE host_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

if (!$stats) {
    $stats = [
        'total_appointments' => 0,
        'scheduled' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];
}

// Get upcoming appointments
$stmt = $db->prepare("
    SELECT a.*, u.full_name as host_name 
    FROM appointments a
    JOIN users u ON a.host_id = u.id
    WHERE a.status = 'scheduled' AND a.appointment_date >= CURDATE()
    AND (a.host_id = ? OR a.guest_email = ?)
    ORDER BY a.appointment_date, a.start_time
    LIMIT 5
");
$stmt->execute([$user_id, $user['email']]);
$appointments = $stmt->fetchAll();

$message = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SchedulePro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5B6BE6;
            --primary-light: #6D7BED;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        header {
            background-color: var(--background);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
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
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Dashboard Layout */
        .dashboard {
            padding: 40px 0;
        }
        
        .dashboard-header {
            margin-bottom: 40px;
        }
        
        .dashboard-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .dashboard-header p {
            color: var(--text-secondary);
            font-size: 18px;
        }
        
        .dashboard-content {
            display: flex;
            flex-direction: column;
            gap: 40px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background-color: var(--background);
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background-color: var(--background);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
        }
        
        .action-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .action-card p {
            color: var(--text-secondary);
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        /* Appointment Cards */
        .appointment-card {
            background-color: var(--background);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 15px;
            border-left: 4px solid var(--primary);
        }
        
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .appointment-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .appointment-date {
            font-size: 14px;
            color: var(--text-secondary);
            background-color: var(--surface);
            padding: 4px 12px;
            border-radius: 20px;
        }
        
        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            font-size: 14px;
            font-weight: 500;
        }
        
        .appointment-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
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
            background-color: var(--primary-light);
        }
        
        .btn-danger {
            background-color: #EF4444;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #DC2626;
        }
        
        /* Alert */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #D1FAE5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 20px;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 15px;
            }
            
            .appointment-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
                    <a href="dashboard.php" class="active">Dashboard</a>
                    <a href="availability.php">Availability</a>
                    <a href="booking.php">Book a Meeting</a>
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr(htmlspecialchars($user['full_name'] ?? $user['username']), 0, 1)); ?>
                        </div>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Dashboard Content -->
    <div class="dashboard">
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="dashboard-header">
                <h1>Welcome back, <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>!</h1>
                <p>Manage your schedule and appointments</p>
            </div>
            
            <div class="dashboard-content">
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Appointments</h3>
                        <div class="stat-value"><?php echo $stats['total_appointments']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Scheduled</h3>
                        <div class="stat-value"><?php echo $stats['scheduled']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Completed</h3>
                        <div class="stat-value"><?php echo $stats['completed']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Cancelled</h3>
                        <div class="stat-value"><?php echo $stats['cancelled']; ?></div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <div class="action-card">
                        <h3>Set Availability</h3>
                        <p>Define when you're available for meetings</p>
                        <a href="availability.php" class="btn btn-primary">Set Schedule</a>
                    </div>
                    <div class="action-card">
                        <h3>Share Booking Link</h3>
                        <p>Share your booking page with others</p>
                        <a href="booking.php?host=<?php echo urlencode($user['username']); ?>" class="btn btn-primary">Get Link</a>
                    </div>
                    <div class="action-card">
                        <h3>Book Appointment</h3>
                        <p>Schedule a meeting with someone</p>
                        <a href="booking.php" class="btn btn-primary">Book Now</a>
                    </div>
                </div>
                
                <!-- Upcoming Appointments -->
                <div>
                    <h2 style="margin-bottom: 20px; font-size: 24px; font-weight: 600;">Upcoming Appointments</h2>
                    
                    <?php if (empty($appointments)): ?>
                        <div style="text-align: center; padding: 40px; background-color: var(--background); border-radius: 12px;">
                            <p style="color: var(--text-secondary); margin-bottom: 20px;">No upcoming appointments scheduled.</p>
                            <a href="availability.php" class="btn btn-primary">Set Up Availability</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="appointment-header">
                                    <div class="appointment-title">
                                        <?php 
                                        if ($appointment['host_id'] == $user_id) {
                                            echo 'Meeting with ' . htmlspecialchars($appointment['guest_name']);
                                        } else {
                                            echo 'Meeting with ' . htmlspecialchars($appointment['host_name']);
                                        }
                                        ?>
                                    </div>
                                    <div class="appointment-date">
                                        <?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?>
                                        at <?php echo date('g:i A', strtotime($appointment['start_time'])); ?>
                                    </div>
                                </div>
                                
                                <div class="appointment-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Duration</span>
                                        <span class="detail-value"><?php echo $appointment['duration']; ?> minutes</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Type</span>
                                        <span class="detail-value">
                                            <?php echo ($appointment['host_id'] == $user_id) ? 'Hosting' : 'Attending'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="appointment-actions">
                                    <a href="cancel-appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-danger">Cancel</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
