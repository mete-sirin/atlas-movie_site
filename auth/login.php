<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['u'] ?? '';          
    $p = $_POST['p'] ?? '';          

    $st = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([$u]);
    $row = $st->fetch(PDO::FETCH_ASSOC);   

    if ($row && password_verify($p, $row['password_hash'])) {
        $_SESSION['uid']  = $row['id'];
        $_SESSION['user'] = $row['username'];
        
        if ($row['is_admin']) {
          $_SESSION['is_admin'] = $row['is_admin'];
          header('Location: ../admin/admin.php');
         }
        else {
        header('Location: ../index.php');
        }
        exit;
    }
    $err = 'Invalid username or password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Atlas Movie Database</title>
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

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 40px 20px;
        }

        .login-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            margin: 0 0 10px 0;
        }

        .login-form {
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

        .login-button {
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

        .login-button:hover {
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

        .register-link {
      text-align: center;
            margin-top: 20px;
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: var(--hover-color);
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }
    }
  </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Sign in to your account</p>
            </div>

            <form method="post" class="login-form">
                <?php if (!empty($err)): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($err) ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="u" 
                        class="form-control" 
                        placeholder="Enter your username" 
                        required 
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="p" 
                        class="form-control" 
                        placeholder="Enter your password" 
                        required 
                        autocomplete="current-password"
                    >
                </div>
    
                <button type="submit" class="login-button">Sign In</button>
</form>

            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register now</a></p>
            </div>
        </div>
    </div>
</body>
</html>
