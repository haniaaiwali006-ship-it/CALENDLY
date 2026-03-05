<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    
    // Validate input
    $errors = [];
    
    if (empty($username)) $errors[] = 'Username is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($password)) $errors[] = 'Password is required';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    
    if (empty($errors)) {
        try {
            $db = getDBConnection();
            
            // Check if username or email exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Username or email already exists';
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $password_hash, $full_name]);
                
                // Get user ID
                $user_id = $db->lastInsertId();
                
                // Set default availability (Monday-Friday, 9 AM - 5 PM)
                $defaultAvailability = [
                    [1, '09:00:00', '17:00:00'], // Monday
                    [2, '09:00:00', '17:00:00'], // Tuesday
                    [3, '09:00:00', '17:00:00'], // Wednesday
                    [4, '09:00:00', '17:00:00'], // Thursday
                    [5, '09:00:00', '17:00:00'], // Friday
                ];
                
                $stmt = $db->prepare("INSERT INTO user_availability (user_id, day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, ?, ?, ?)");
                foreach ($defaultAvailability as $availability) {
                    $stmt->execute([$user_id, $availability[0], $availability[1], $availability[2], 30]);
                }
                
                // Set session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                
                redirect('dashboard.php', 'Account created successfully!');
            }
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | SchedulePro</title>
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
        
        .auth-container {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 480px;
            padding: 40px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #5B6BE6;
            text-decoration: none;
            margin-bottom: 10px;
            display: block;
        }
        
        .auth-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 10px;
        }
        
        .auth-header p {
            color: #6B7280;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #5B6BE6;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background-color: #5B6BE6;
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
            background-color: #4A55C7;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #E5E7EB;
        }
        
        .auth-footer a {
            color: #5B6BE6;
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        
        .alert ul {
            margin-left: 20px;
        }
        
        @media (max-width: 480px) {
            .auth-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <a href="index.php" class="logo">SchedulePro</a>
            <h1>Create your account</h1>
            <p>Start scheduling meetings in minutes</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" placeholder="John Doe" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required placeholder="johndoe" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required placeholder="john@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required placeholder="At least 6 characters">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
            </div>
            
            <button type="submit" class="btn">Create Account</button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>
</body>
</html>
