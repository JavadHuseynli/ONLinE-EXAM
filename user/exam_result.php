<?php
// user/exam_result.php
session_start();
require_once "../config.php";

if(!isset($_SESSION['logged_in']) || $_SESSION['role'] != 1) {
    header("Location: ../login.php");
    exit();
}

$score = $_GET['score'] ?? 0;
$correct = $_GET['correct'] ?? 0;
$incorrect = $_GET['incorrect'] ?? 0;

// Calculate percentage if we have result data
$answered = $correct + $incorrect;
$percentage = $answered > 0 ? round(($correct / $answered) * 100) : 0;

// Determine color based on score
$colorClass = "";
if ($percentage >= 80) {
    $colorClass = "success"; // Green
} elseif ($percentage >= 60) {
    $colorClass = "info"; // Blue
} elseif ($percentage >= 40) {
    $colorClass = "warning"; // Yellow/Orange
} else {
    $colorClass = "danger"; // Red
}
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>İmtahan Nəticəsi - Rabite Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
         :root {
            --primary: #2c62ef;
            --success: #2ecc71;
            --warning: #f1c40f;
            --danger: #e74c3c;
            --info: #3498db;
            --dark: #2c3e50;
            --light: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .result-card {
            width: 100%;
            max-width: 550px;
            border: none;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
        }
        
        .card-header {
            background: var(--primary);
            color: white;
            text-align: center;
            padding: 25px 15px;
            position: relative;
        }
        
        .card-header h3 {
            font-weight: 700;
            margin-bottom: 0;
            font-size: 1.8rem;
        }
        
        .card-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 0;
            right: 0;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            transform: scaleX(1.3);
        }
        
        .score-container {
            position: relative;
            z-index: 10;
            margin-top: -20px;
            padding-top: 30px;
        }
        
        .score-circle {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            color: white;
            background: linear-gradient(135deg, var(--<?php echo $colorClass; ?>) 0%, var(--<?php echo $colorClass; ?>-light, var(--<?php echo $colorClass; ?>)) 100%);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .score-circle::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 70%);
        }
        
        .score-emoji {
            font-size: 48px;
            margin-bottom: 5px;
        }
        
        .score-percentage {
            font-size: 46px;
            font-weight: 700;
            line-height: 1;
        }
        
        .score-text {
            font-size: 18px;
            font-weight: 500;
        }
        
        .result-message {
            text-align: center;
            color: var(--dark);
            font-weight: 600;
            margin: 25px 0;
            padding: 0 20px;
        }
        
        .results-container {
            padding: 0 25px;
        }
        
        .result-stat {
            background: white;
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        
        .result-stat:hover {
            transform: translateY(-3px);
        }
        
        .result-stat i {
            margin-right: 12px;
            font-size: 20px;
        }
        
        .result-label {
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .result-value {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--dark);
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            margin: 30px 0 25px;
        }
        
        .btn-custom {
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }
        
        .text-success { color: var(--success) !important; }
        .text-danger { color: var(--danger) !important; }
        .text-warning { color: var(--warning) !important; }

        /* Animate the elements */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate3d(0, 30px, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }
        
        .animate-fadeInUp {
            animation: fadeInUp 0.8s ease forwards;
        }
        
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.4s; }
        .delay-3 { animation-delay: 0.6s; }
        .delay-4 { animation-delay: 0.8s; }
    </style>
</head>
<body>
<div class="container">
        <div class="card result-card">
            <div class="card-header">
                <h3>İmtahan Nəticəniz</h3>
            </div>
            
            <div class="card-body">
                <div class="score-container">
                    <div class="score-circle animate-fadeInUp">
                        <div class="score-emoji"><?php echo $emoji; ?></div>
                        <div class="score-percentage"><?php echo $percentage; ?>%</div>
                        <div class="score-text">Düzgün</div>
                    </div>
                    
                    <div class="result-message animate-fadeInUp delay-1"><?php echo $message; ?></div>
                    
                    <div class="results-container">
                        <div class="result-stat animate-fadeInUp delay-2">
                            <div class="result-label">
                                <i class="fas fa-check-circle text-success"></i>
                                Düzgün cavablar
                            </div>
                            <div class="result-value text-success"><?php echo $correct; ?></div>
                        </div>
                        
                        <div class="result-stat animate-fadeInUp delay-3">
                            <div class="result-label">
                                <i class="fas fa-times-circle text-danger"></i>
                                Səhv cavablar
                            </div>
                            <div class="result-value text-danger"><?php echo $incorrect; ?></div>
                        </div>
                        
                        <div class="result-stat animate-fadeInUp delay-4">
                            <div class="result-label">
                                <i class="fas fa-star text-warning"></i>
                                Ümumi xal
                            </div>
                            <div class="result-value"><?php echo $score; ?></div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="dashboard.php" class="btn btn-custom btn-primary">
                            <i class="fas fa-home me-2"></i> Ana Səhifəyə Qayıt
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>