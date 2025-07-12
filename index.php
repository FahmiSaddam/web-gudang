<?php
session_start();
require 'config/database.php';

// Jika pengguna sudah login, arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';
$login_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Password dari form

    if (empty($username) || empty($password)) {
        $error_message = "Username dan password tidak boleh kosong.";
    } else {
        try {
            // Menggunakan prepared statement untuk keamanan dari SQL Injection
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && $password === $user['password']) {
                // Login berhasil, simpan data ke session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                
                // Set flag untuk menunjukkan login berhasil (untuk animasi)
                $login_success = true;
            } else {
                $error_message = "Username atau password salah.";
            }
        } catch (PDOException $e) {
            $error_message = "Terjadi kesalahan pada database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Gudang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style src >
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow: hidden;
            font-family: 'Nunito', sans-serif;
            position: relative;
        }
        
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            animation: float 15s infinite;
            opacity: 0;
        }
        
        @keyframes float {
            0% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 0.5; }
            100% { transform: translateY(-100vh) translateX(100px); opacity: 0; }
        }
        
        .login-card {
            max-width: 450px;
            width: 100%;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            transform: translateY(20px);
            opacity: 0;
            animation: fadeIn 0.8s ease-out forwards;
        }
        
        @keyframes fadeIn {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .login-logo i {
            font-size: 4rem;
            color: #4e73df;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(0.95); }
            50% { transform: scale(1.05); }
            100% { transform: scale(0.95); }
        }
        
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            position: relative;
            padding-bottom: 15px;
        }
        
        .login-title:after {
            content: "";
            position: absolute;
            width: 60px;
            height: 3px;
            background: #4e73df;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            animation: expandWidth 1s ease-out forwards;
        }
        
        @keyframes expandWidth {
            from { width: 0; }
            to { width: 60px; }
        }
        
        .form-label {
            color: #555;
            font-weight: 600;
        }
        
        .form-control {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.25);
            border-color: #4e73df;
            transform: translateY(-2px);
        }
        
        .input-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #4e73df;
            z-index: 10;
        }
        
        .form-control {
            padding-left: 45px;
        }
        
        .btn-primary {
            background: #4e73df;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: 0.5s;
        }
        
        .btn-primary:hover:before {
            left: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.6);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .alert-danger {
            border-radius: 10px;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-10px); }
            80% { transform: translateX(10px); }
            100% { transform: translateX(0); }
        }
        
        /* Success Animation Styles */
        .success-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.5s ease;
        }
        
        .success-animation.show {
            opacity: 1;
            pointer-events: all;
        }
        
        .package {
            width: 200px;
            height: 200px;
            position: relative;
            perspective: 1000px;
        }
        
        .package-box {
            width: 100%;
            height: 100%;
            position: absolute;
            transform-style: preserve-3d;
        }
        
        .package-lid {
            width: 200px;
            height: 200px;
            background-color: #4e73df;
            position: absolute;
            top: 0;
            transform-origin: top;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
        }
        
        .package-bottom {
            width: 200px;
            height: 200px;
            background-color: #5a5c69;
            position: absolute;
            top: 0;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
        }
        
        .package-content {
            width: 180px;
            height: 180px;
            position: absolute;
            top: 10px;
            left: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fc;
            border-radius: 5px;
            transform: translateZ(10px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .content-icon {
            font-size: 4rem;
            color: #4e73df;
        }
        
        @keyframes openBox {
            0% { transform: rotateX(0deg); }
            70% { transform: rotateX(-120deg); }
            100% { transform: rotateX(-100deg); }
        }
        
        @keyframes floatContent {
            0% { transform: translateZ(10px); }
            100% { transform: translateZ(50px); }
        }
        
        .welcome-text {
            position: absolute;
            bottom: 20%;
            width: 100%;
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            color: white;
            opacity: 0;
            transform: translateY(20px);
            transition: all 1s ease;
        }
        
        .welcome-text.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <!-- Success Animation Container -->
    <div id="successAnimation" class="success-animation">
        <div class="package">
            <div class="package-box">
                <div class="package-bottom"></div>
                <div class="package-content">
                    <i class="fas fa-user-check content-icon"></i>
                </div>
                <div class="package-lid"></div>
            </div>
        </div>
        <div id="welcomeText" class="welcome-text">Selamat datang!</div>
    </div>
    
    <div class="card login-card shadow-sm">
        <div class="card-body p-5">
            <div class="login-logo">
                <i class="fas fa-box"></i>
            </div>
            <h3 class="login-title">Login Sistem Gudang</h3>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <form action="index.php" method="post">
                <div class="input-group mb-4">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required>
                </div>
                <div class="input-group mb-4">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Create floating particles for background
        document.addEventListener('DOMContentLoaded', function() {
            const particles = document.getElementById('particles');
            const particleCount = 25;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random size
                const size = Math.floor(Math.random() * 30) + 5;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                // Random position
                const left = Math.floor(Math.random() * 100);
                const top = Math.floor(Math.random() * 100);
                particle.style.left = left + '%';
                particle.style.top = top + '%';
                
                // Random animation delay and duration
                const delay = Math.random() * 10;
                const duration = Math.random() * 10 + 10;
                particle.style.animationDelay = delay + 's';
                particle.style.animationDuration = duration + 's';
                
                particles.appendChild(particle);
            }
            
            // Add focus animation for inputs
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                    this.parentElement.style.transition = 'transform 0.3s ease';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
            
            // Handle login success animation
            <?php if ($login_success): ?>
            setTimeout(function() {
                const successAnim = document.getElementById('successAnimation');
                const packageLid = document.querySelector('.package-lid');
                const packageContent = document.querySelector('.package-content');
                const welcomeText = document.getElementById('welcomeText');
                
                successAnim.classList.add('show');
                
                setTimeout(function() {
                    // Animate box opening
                    packageLid.style.animation = 'openBox 1.5s forwards';
                    
                    setTimeout(function() {
                        // Show content
                        packageContent.style.opacity = '1';
                        packageContent.style.animation = 'floatContent 1s forwards';
                        
                        setTimeout(function() {
                            // Show welcome text
                            welcomeText.classList.add('show');
                            
                            // Redirect after animation completes
                            setTimeout(function() {
                                window.location.href = 'dashboard.php';
                            }, 1500);
                        }, 500);
                    }, 700);
                }, 500);
            }, 300);
            <?php endif; ?>
        });
    </script>
</body>
</html>