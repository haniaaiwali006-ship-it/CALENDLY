<?php
require_once 'config.php';

$message = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SchedulePro - Professional Scheduling Platform</title>
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
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--background);
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
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(91, 107, 230, 0.3);
        }
        
        /* Hero Section */
        .hero {
            padding: 100px 0;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0 0 40px 40px;
            margin-bottom: 80px;
        }
        
        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 20px;
            max-width: 600px;
            margin: 0 auto 40px;
            opacity: 0.9;
        }
        
        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Features */
        .features {
            padding: 80px 0;
        }
        
        .section-title {
            text-align: center;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 60px;
            color: var(--text-primary);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }
        
        .feature-card {
            background: var(--surface);
            padding: 40px 30px;
            border-radius: 16px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--primary);
        }
        
        .feature-card h3 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: var(--text-secondary);
        }
        
        /* Footer */
        footer {
            background-color: var(--surface);
            padding: 60px 0 30px;
            border-top: 1px solid var(--border);
            margin-top: 80px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid var(--border);
            color: var(--text-secondary);
        }
        
        /* Alert */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
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
            
            .hero h1 {
                font-size: 36px;
            }
            
            .hero p {
                font-size: 18px;
            }
            
            .section-title {
                font-size: 28px;
            }
        }
        
        @media (max-width: 480px) {
            .hero h1 {
                font-size: 28px;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
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
                    <?php if (isLoggedIn()): ?>
                        <a href="dashboard.php">Dashboard</a>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Get Started</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Flash Message -->
    <?php if ($message): ?>
        <div class="container">
            <div class="alert alert-success"><?php echo $message; ?></div>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Smart Scheduling for Busy Professionals</h1>
            <p>Effortlessly schedule meetings without the back-and-forth emails. Share your availability and let clients book time with you.</p>
            <div class="hero-buttons">
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="btn btn-primary" style="background: white; color: var(--primary);">Go to Dashboard</a>
                <?php else: ?>
                    <a href="signup.php" class="btn btn-primary" style="background: white; color: var(--primary);">Start Free</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Simple & Professional Scheduling</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📅</div>
                    <h3>Easy Scheduling</h3>
                    <p>Set your availability and share your booking link with clients and colleagues.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>No Back-and-Forth</h3>
                    <p>Eliminate scheduling conflicts with automated time slot management.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔔</div>
                    <h3>Automatic Reminders</h3>
                    <p>Reduce no-shows with automatic appointment reminders.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div>
                    <div class="footer-logo">SchedulePro</div>
                    <p>Professional scheduling made simple and efficient.</p>
                </div>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> SchedulePro. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
