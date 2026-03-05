<?php
require_once 'config.php';
requireLogin();

$db = getDBConnection();
$user_id = getCurrentUserId();
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    redirect('dashboard.php', 'No appointment specified');
}

// Get appointment details
$stmt = $db->prepare("SELECT * FROM appointments WHERE id = ?");
$stmt->execute([$appointment_id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    redirect('dashboard.php', 'Appointment not found');
}

// Get user info
$stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Check authorization
$is_host = ($appointment['host_id'] == $user_id);
$is_guest = ($appointment['guest_email'] == $user['email']);

if (!$is_host && !$is_guest) {
    redirect('dashboard.php', 'Not authorized to cancel this appointment');
}

// Process cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = $_POST['reason'] ?? '';
    
    // Update appointment status
    $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$appointment_id]);
    
    redirect('dashboard.php', 'Appointment cancelled successfully');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Appointment | SchedulePro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .cancel-container {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 40px;
        }
        
        .warning-icon {
            width: 60px;
            height: 60px;
            background-color: #F59E0B;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 20px;
        }
        
        h1 {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .subtitle {
            text-align: center;
            color: #6B7280;
            margin-bottom: 30px;
        }
        
        .appointment-details {
            background-color: #F9FAFB;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: #6B7280;
        }
        
        .detail-value {
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            resize: vertical;
            min-height: 100px;
        }
        
        textarea:focus {
            outline: none;
            border-color: #5B6BE6;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            flex: 1;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        
        .btn-danger {
            background-color: #EF4444;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #DC2626;
        }
        
        .btn-secondary {
            background-color: #F9FAFB;
            color: #6B7280;
            border: 2px solid #E5E7EB;
        }
        
        .btn-secondary:hover {
            background-color: #E5E7EB;
        }
        
        @media (max-width: 480px) {
            .cancel-container {
                padding: 30px 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <div class="warning-icon">⚠️</div>
        <h1>Cancel Appointment</h1>
        <p class="subtitle">Are you sure you want to cancel this meeting?</p>
        
        <div class="appointment-details">
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value"><?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time:</span>
                <span class="detail-value"><?php echo date('g:i A', strtotime($appointment['start_time'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Duration:</span>
                <span class="detail-value"><?php echo $appointment['duration']; ?> minutes</span>
            </div>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="reason">Reason for Cancellation (Optional)</label>
                <textarea id="reason" name="reason" placeholder="Please let us know why you're cancelling this meeting"></textarea>
            </div>
            
            <div class="form-actions">
                <a href="dashboard.php" class="btn btn-secondary">Go Back</a>
                <button type="submit" class="btn btn-danger">Cancel Appointment</button>
            </div>
        </form>
    </div>
</body>
</html>
