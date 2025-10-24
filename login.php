<?php
require 'includes/config.php';

session_start();
$basarili_mesaj = $_SESSION['basarili'] ?? '';
$hata_mesaji = '';
unset($_SESSION['basarili']); 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email=$_POST['email'];
    $password = $_POST['password'];
    $stmt=$pdo->prepare("SELECT * FROM User WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {   
        unset($user['password']);
        $_SESSION['user']=$user;
        $role=strtolower($user['role']);
        $rurl='index.php';
        switch ($role) {
            case 'admin':
                $rurl='admin/index.php';
                break;
            case 'company':
                $rurl='company/index.php';
                break;
            default:
                $rurl='index.php';
                break;
        }
        header('Location: '. $rurl);
        exit;
    } else {
        $hata_mesaji = "E-posta veya şifreniz hatalı. Lütfen tekrar deneyin.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

<div class="card shadow-lg p-4" style="width: 400px;">
    <h3 class="text-center mb-4">Giriş Yap</h3>

    <?php if (!empty($basarili_mesaj)): ?>
        <div class="alert alert-success text-center">
            <?= htmlspecialchars($basarili_mesaj) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($hata_mesaji)): ?>
        <div class="alert alert-danger text-center">
            <?= htmlspecialchars($hata_mesaji) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="Email" class="form-label">Email</label>
            <input type="email" name="email" id="Email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="Password" class="form-label">Şifre</label>
            <input type="password" name="password" id="Password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
    </form>

    <div class="text-center mt-3">
        <a href="register.php" class="text-decoration-none">Hesabın yok mu? Kayıt ol</a>
    </div>
</div>

</body>
</html>
