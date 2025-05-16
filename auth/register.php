<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $form_user_name = trim($_POST['name']);
    $form_password = trim($_POST['password']);
    $form_email = trim($_POST['email']);
    $form_date = date('Y-m-d H:i:s');
    $h_number = password_hash($form_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$form_user_name, $form_email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        $error = "Username or email already taken.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$form_user_name, $h_number, $form_email, $form_date]);

        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Atlas Movie Database</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #3498db;
            --hover-color: #2980b9;
            --background-color: #f8f9fa;
            --text-color: #2c3e50;
            --error-color: #e74c3c;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--background-color);
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-color);
        }

        .register-container {
            width: 100%;
            max-width: 400px;
            padding: 40px 20px;
        }

        .register-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin: 0 0 10px 0;
        }

        .register-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            padding: 12px 16px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .register-button {
            background: var(--primary-color);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .register-button:hover {
            background: var(--hover-color);
            transform: translateY(-2px);
        }

        .error-message {
            background: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            padding: 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 20px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: var(--hover-color);
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .register-card {
                padding: 30px 20px;
            }
        }
    </style>
    </head>
    <body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>Create Account</h1>
                <p>Join Atlas Movie Database today</p>
            </div>

            <form method="post" class="register-form">
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error) ?>
                        </div>    
                <?php endif; ?>

                        <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="name" 
                        class="form-control" 
                        placeholder="Choose a username" 
                        required 
                        autocomplete="username"
                    >
                        </div>    

                        <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="Enter your email" 
                        required 
                        autocomplete="email"
                    >
                        </div>

                        <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Create a password" 
                        required 
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit" name="submit" class="register-button">Create Account</button>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </div>    
    </div>
</body>
</html>    
