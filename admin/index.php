<?php
require_once '../includes/config.php';
session_start();

$basari_mesaji = $_SESSION['basari_mesaji'] ?? '';
$hata_mesaji = $_SESSION['hata_mesaji'] ?? '';

unset($_SESSION['basari_mesaji'], $_SESSION['hata_mesaji']);

// Sadece admin erişimi
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('HTTP/1.1 401 Unauthorized', true, 401);
    exit('401 Unauthorized');
}

$user = $_SESSION['user'];

// Firma Ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $company_id = generateUUID();
    $logo_path = null;

    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo_file'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png'];

        if (!in_array($file_extension, $allowed_extensions)) {
            $_SESSION['hata_mesaji'] = "Sadece JPG, JPEG veya PNG formatında logo yükleyebilirsiniz.";
            header("Location: index.php");
            exit;
        }

        $target_dir = "../logo_folder/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

        $logo_adi = uniqid('logo_') . '.' . $file_extension;
        $target_file = $target_dir . $logo_adi;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $logo_path = 'logo_folder/' . $logo_adi;
        } else {
            $_SESSION['hata_mesaji'] = "Logo yüklenirken bir hata oluştu.";
            header("Location: index.php");
            exit;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO Bus_Company (id, logo_path, name) VALUES (?, ?, ?)");
        $stmt->execute([$company_id, $logo_path, $name]);
        $_SESSION['basari_mesaji'] = "Firma başarıyla eklendi.";
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['hata_mesaji'] = "Bir hata oluştu: " . $e->getMessage();
        header("Location: index.php");
        exit;
    }
}

// Firma sil
if (isset($_GET['sil'])) {
    try {
        $company_id = $_GET['sil'];
        $stmt = $pdo->prepare("DELETE FROM Bus_Company WHERE id = ?");
        $stmt->execute([$company_id]);
        $_SESSION['basari_mesaji'] = "Firma başarıyla silindi.";
    } catch (PDOException $e) {
        $_SESSION['hata_mesaji'] = "Firma silinemedi: " . $e->getMessage();
    }
    header("Location: index.php");
    exit;
}

// Firma admini ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['admin_ekle'] ?? '') === 'company_admin_ekle') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'company';
    $company_id = $_POST['company_id'];

    $check_stmt = $pdo->prepare("SELECT email FROM User WHERE email = ?");
    $check_stmt->execute([$email]);

    if ($check_stmt->fetch()) {
        $_SESSION['hata_mesaji'] = "Bu e-posta zaten kayıtlı.";
    } else {
        $id = generateUUID();
        $stmt = $pdo->prepare("INSERT INTO User (id, full_name, email, role, password, company_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $full_name, $email, $role, $password, $company_id]);
        $_SESSION['basari_mesaji'] = "Firma admini başarıyla oluşturuldu.";
    }

    header("Location: index.php");
    exit;
}

// Firma admini sil
if (isset($_GET['sil_admin'])) {
    try {
        $id = $_GET['sil_admin'];
        $stmt = $pdo->prepare("DELETE FROM User WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['basari_mesaji'] = "Firma admini başarıyla silindi.";
    } catch (PDOException $e) {
        $_SESSION['hata_mesaji'] = "Firma admini silinemedi: " . $e->getMessage();
    }
    header("Location: index.php");
    exit;
}

// Verileri listele
$firma_listele = $pdo->query("SELECT * FROM Bus_Company ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);
$sqlliste = $pdo->prepare("SELECT U.full_name, U.email, U.id AS user_id, C.name AS company_name
                           FROM User AS U 
                           INNER JOIN Bus_Company AS C ON U.company_id = C.id 
                           WHERE U.role = ? 
                           ORDER BY U.full_name ASC");
$sqlliste->execute(['company']);
$admin_listele = $sqlliste->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-primary fw-bold">Admin Paneli</h1>
        <a href="../logout.php" class="btn btn-warning">Çıkış Yap</a>
    </div>

    <?php if ($basari_mesaji): ?>
        <div class="alert alert-success"><?= htmlspecialchars($basari_mesaji) ?></div>
    <?php endif; ?>
    <?php if ($hata_mesaji): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($hata_mesaji) ?></div>
    <?php endif; ?>

    <!-- Firma Ekleme -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Yeni Otobüs Firması Ekle</h4>
        </div>
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Firma Adı</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Firma Logosu</label>
                    <input type="file" name="logo_file" accept=".jpg,.jpeg,.png" class="form-control" required>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" name="submit" class="btn btn-success">Ekle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Firma Listesi -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Kayıtlı Otobüs Firmaları</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Firma Adı</th>
                        <th>Logo</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($firma_listele as $firma): ?>
                        <tr>
                            <td><?= htmlspecialchars($firma['name']) ?></td>
                            <td>
                                <?php if (!empty($firma['logo_path'])): ?>
                                    <img src="../<?= htmlspecialchars($firma['logo_path']) ?>" alt="Logo" class="img-thumbnail" style="max-height:60px;">
                                <?php else: ?>
                                    <span class="text-muted">Yok</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?sil=<?= $firma['id'] ?>" class="btn btn-sm btn-danger">Sil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Firma Admini Ekle -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0">Firma Admini Oluştur</h4>
        </div>
        <div class="card-body">
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="admin_ekle" value="company_admin_ekle">
                <div class="col-md-6">
                    <label class="form-label">Ad Soyad</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-posta</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Geçici Şifre</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Firma Seç</label>
                    <select name="company_id" class="form-select" required>
                        <option value="">Firma Seçiniz</option>
                        <?php foreach ($firma_listele as $firma): ?>
                            <option value="<?= htmlspecialchars($firma['id']) ?>"><?= htmlspecialchars($firma['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-info">Admin Ekle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin Listesi -->
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            <h4 class="mb-0">Kayıtlı Firma Adminleri</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Firma</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admin_listele as $admin): ?>
                        <tr>
                            <td><?= htmlspecialchars($admin['full_name']) ?></td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td><?= htmlspecialchars($admin['company_name']) ?></td>
                            <td>
                                <a href="?sil_admin=<?= $admin['user_id'] ?>" class="btn btn-sm btn-danger">Sil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
