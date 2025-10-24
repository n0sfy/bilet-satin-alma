<?php 
require_once '../includes/config.php';
session_start();

$basari_mesaji = $_SESSION['basari_mesaji'] ?? '';
$hata_mesaji = $_SESSION['hata_mesaji'] ?? '';

unset($_SESSION['basari_mesaji']);
unset($_SESSION['hata_mesaji']);

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$user = $_SESSION['user'];

if ($_SESSION['user']['role'] !== 'company') {
    header('HTTP/1.1 401 Unauthorized', true, 401);
    exit('401 Unauthorized');
}

$sefer_listele = [];
$company_id = $user['company_id'];

if (isset($_GET['sil'])) {
    $trip_id = $_GET['sil'];
    try {
        $sqlsil = $pdo->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
        $sqlsil->execute([$trip_id, $company_id]);

        $_SESSION['basari_mesaji'] = "Sefer başarıyla silindi.";
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['hata_mesaji'] = "Sefer silinemedi. " . $e->getMessage();
        header("Location: index.php");
        exit;
    }
}

try {
    $sql = $pdo->prepare("SELECT * FROM Trips WHERE company_id = ? ORDER BY departure_time DESC");
    $sql->execute([$company_id]);
    $sefer_listele = $sql->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['hata_mesaji'] = "Seferler listelenemedi. " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure_city = $_POST['departure_city'];
    $destination_city = $_POST['destination_city'];
    $departure_date = $_POST['departure_date'];
    $destination_date = $_POST['destination_date'];
    $depart_time = $_POST['depart_time'];
    $destination_time = $_POST['destination_time'];
    $capacity = $_POST['capacity'];
    $price = $_POST['price'];

    $company_id = $user['company_id'];
    $departure_time = $departure_date . ' ' . $depart_time . ':00';
    $arrival_time = $destination_date . ' ' . $destination_time . ':00';
    $trip_id = generateUUID();

    if (empty($departure_city) || empty($destination_city) || empty($departure_date) || empty($depart_time) || empty($destination_date) || empty($destination_time) || empty($capacity) || empty($price)) {
        $_SESSION['hata_mesaji'] = "Lütfen tüm alanları doldurunuz.";
        header("Location: index.php");
        exit;
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO Trips (id, company_id, destination_city, arrival_time, departure_time, departure_city, price, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$trip_id, $company_id, $destination_city, $arrival_time, $departure_time, $departure_city, $price, $capacity]);
            $_SESSION['basari_mesaji'] = "Sefer başarıyla eklendi.";
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['hata_mesaji'] = "Bir hata oluştu. " . $e->getMessage();
            header("Location: index.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">Firma Paneli</a>
    <div class="d-flex">
        <a href="../logout.php" class="btn btn-warning btn-sm">Çıkış Yap</a>
    </div>
  </div>
</nav>

<div class="container">
    <?php if (!empty($basari_mesaji)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($basari_mesaji) ?></div>
    <?php endif; ?>

    <?php if (!empty($hata_mesaji)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($hata_mesaji) ?></div>
    <?php endif; ?>

    <!-- SEFER EKLEME FORMU -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-primary text-white fw-bold">
            Yeni Sefer Ekle
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Kalkış Yeri</label>
                    <input type="text" name="departure_city" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Varış Yeri</label>
                    <input type="text" name="destination_city" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kalkış Tarihi</label>
                    <input type="date" name="departure_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kalkış Saati</label>
                    <input type="time" name="depart_time" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Varış Tarihi</label>
                    <input type="date" name="destination_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Varış Saati</label>
                    <input type="time" name="destination_time" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Koltuk Sayısı</label>
                    <input type="number" name="capacity" class="form-control" min="1" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fiyat (TL)</label>
                    <input type="number" name="price" class="form-control" min="0" required>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-success">Seferi Ekle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SEFERLER TABLOSU -->
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white fw-bold">
            Seferleriniz
        </div>
        <div class="card-body">
            <?php if (empty($sefer_listele)): ?>
                <div class="alert alert-info text-center mb-0">Kayıtlı sefer bulunamadı.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Kalkış</th>
                                <th>Varış</th>
                                <th>Kalkış Zamanı</th>
                                <th>Varış Zamanı</th>
                                <th>Koltuk Sayısı</th>
                                <th>Fiyat (TL)</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sefer_listele as $sefer): ?>
                            <tr>
                                <td><?= htmlspecialchars($sefer['departure_city']) ?></td>
                                <td><?= htmlspecialchars($sefer['destination_city']) ?></td>
                                <td><?= htmlspecialchars($sefer['departure_time']) ?></td>
                                <td><?= htmlspecialchars($sefer['arrival_time']) ?></td>
                                <td><?= htmlspecialchars($sefer['capacity']) ?></td>
                                <td><?= htmlspecialchars($sefer['price']) ?> TL</td>
                                <td>
                                    <a href="edit_trip.php?trip_id=<?= $sefer['id'] ?>" class="btn btn-sm btn-outline-primary me-1">Düzenle</a>
                                    <a href="?sil=<?= $sefer['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu seferi silmek istediğine emin misin?')">Sil</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
