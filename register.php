<?php
require 'includes/config.php';
session_start();
$basarili="";

if($_SERVER['REQUEST_METHOD']==='POST'){
    $full_name = $_POST['adsoyad'];
    $email = $_POST['eposta'];
    $password =  password_hash($_POST['sifre'],PASSWORD_DEFAULT);

    $check_stmt = $pdo->prepare("SELECT email FROM User WHERE email = ?");
    $check_stmt->execute([$email]);
    if($check_stmt->fetch()){
        echo "Eposta zaten kayıtlı.";
    }else {
        $id = generateUUID();
        $stmt = $pdo->prepare("INSERT INTO User (id,full_name,email,password) VALUES (?,?,?,?)");
        $stmt->execute([$id, $full_name, $email, $password]);
        $_SESSION['basarili']="Kaydoldunuz. Giriş Yapabilirsiniz.";
        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Biletinko</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

<div class="card shadow-lg p-4" style="width: 450px;">
    <h3 class="text-center mb-4">Kayıt Ol</h3>

    <form action="#" method="POST">
        <div class="mb-3">
            <label for="Adsoyad" class="form-label">Ad Soyad</label>
            <input type="text" id="Adsoyad" name="adsoyad" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="Eposta" class="form-label">Eposta</label>
            <input type="email" id="Eposta" name="eposta" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="Sifre" class="form-label">Şifre</label>
            <input type="password" id="Sifre" name="sifre" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
    </form>

    <div class="text-center mt-3">
        <span>Zaten hesabın var mı?</span>
        <a href="login.php" class="text-decoration-none">Giriş Yap</a>
    </div>
</div>

</body>
</html>
