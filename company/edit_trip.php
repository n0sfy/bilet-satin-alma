<?php
require '../includes/config.php';
session_start();

$trip_id = $_GET['trip_id'];
$user = $_SESSION['user'];

$stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ?");
$stmt->execute([$trip_id]);
$edit_trip = $stmt->fetch();

$edit_departure_city = $edit_trip['departure_city'];
$edit_destination_city = $edit_trip['destination_city'];
$edit_departure_date = date('Y-m-d', strtotime($edit_trip['departure_time']));
$edit_destination_date = date('Y-m-d', strtotime($edit_trip['arrival_time']));
$edit_depart_time = date('H:i', strtotime($edit_trip['departure_time']));
$edit_destination_time = date('H:i', strtotime($edit_trip['arrival_time']));
$edit_capacity = $edit_trip['capacity'];
$edit_price = $edit_trip['price'];
$edit_company_id = $edit_trip['company_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guncellenecek_id = $_POST['trip_id'];
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
            $stmt = $pdo->prepare("UPDATE Trips SET destination_city=?,arrival_time=?,departure_time=?,departure_city=?,price=?,capacity=? WHERE id=? AND company_id=?");
            $stmt->execute([$destination_city, $arrival_time, $departure_time, $departure_city, $price, $capacity, $guncellenecek_id, $company_id]);
            $_SESSION['basari_mesaji'] = "Sefer başarıyla düzenlendi.";
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['hata_mesaji'] = "Bir hata oluştu." . $e->getMessage();
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
    <title>Sefer Düzenle - Firma Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">Firma Paneli</a>
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

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white fw-bold">
            Sefer Düzenle
        </div>
        <div class="card-body">
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="trip_id" value="<?= htmlspecialchars($_GET['trip_id']) ?>">

                <div class="col-md-6">
                    <label class="form-label">Kalkış Yeri</label>
                    <input type="text" name="departure_city" value="<?= htmlspecialchars($edit_departure_city) ?>" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Varış Yeri</label>
                    <input type="text" name="destination_city" value="<?= htmlspecialchars($edit_destination_city) ?>" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kalkış Tarihi</label>
                    <input type="date" name="departure_date" value="<?= htmlspecialchars($edit_departure_date) ?>" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kalkış Saati</label>
                    <input type="time" name="depart_time" value="<?= htmlspecialchars($edit_depart_time) ?>" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Varış Tarihi</label>
                    <input type="date" name="destination_date" value="<?= htmlspecialchars($edit_destination_date) ?>" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Varış Saati</label>
                    <input type="time" name="destination_time" value="<?= htmlspecialchars($edit_destination_time) ?>" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Koltuk Sayısı</label>
                    <input type="number" name="capacity" value="<?= htmlspecialchars($edit_capacity) ?>" class="form-control" min="1" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fiyat (TL)</label>
                    <input type="number" name="price" value="<?= htmlspecialchars($edit_price) ?>" class="form-control" min="0" required>
                </div>

                <div class="col-12 text-end">
                    <a href="index.php" class="btn btn-secondary">Geri Dön</a>
                    <button type="submit" class="btn btn-success">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
