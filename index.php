<?php
require_once 'includes/config.php';
session_start();
$kullanici = $_SESSION['user'] ?? null;
if (empty($kullanici)) {
    $hosgeldin = "Hoş Geldiniz.";
} else {
    
    $hosgeldin = "Hoş Geldin " . $kullanici['full_name'];
}   


$kalkis_stmt = $pdo->query("SELECT DISTINCT departure_city FROM Trips ORDER BY departure_city");
$kalkis_yerleri = $kalkis_stmt->fetchAll(PDO::FETCH_ASSOC);

$varis_stmt = $pdo->query("SELECT DISTINCT destination_city FROM Trips");
$varis_yerleri = $varis_stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['ara']) && !empty($_GET['destination_city']) && !empty($_GET['departure_city'])) {
    $destination_city = $_GET['destination_city'];
    $departure_city = $_GET['departure_city'];
    $departure_time =  $_GET['departure_time'];

    $getTrips = $pdo->prepare("SELECT * FROM Trips WHERE destination_city = ? AND departure_city = ? AND DATE(departure_time) = ?");
    $getTrips->execute([$destination_city, $departure_city, $departure_time]);
    $sefer_listesi = $getTrips->fetchAll(PDO::FETCH_ASSOC);

    if (empty($sefer_listesi)) {
        $uyari = "Seçtiğiniz kriterlere uygun sefer bulunamadı.";
    }
} else {
    $uyari = "Lütfen sefer bilgilerini giriniz.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Sayfa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Biletinko</a>
        <div class="d-flex gap-2">
            <span class="navbar-text text-secondary me-3"><?php echo $hosgeldin; ?></span>
            <?php if (empty($_SESSION['user'])): ?>
                <a href="login.php" class="btn btn-outline-light btn-sm me-2">Giriş Yap</a>
                <a href="register.php" class="btn btn-info btn-sm">Kayıt Ol</a>
            <?php else: ?>
                <a href="hesabim.php" class="btn btn-success">Hesabım</a>
                <a href="logout.php" class="btn btn-danger">Çıkış Yap</a>   
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <?php if (isset($uyari)) : ?>
        <div class="alert alert-info text-center"><?php echo $uyari; ?></div>
    <?php endif; ?>

    <div class="card shadow p-4">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <label for="depar" class="form-label">Kalkış Yeri</label>
                <select name="departure_city" id="depar" class="form-select">
                    <option value="">Seçiniz</option>
                    <?php foreach ($kalkis_yerleri as $kalkis): ?>
                        <option value="<?php echo htmlspecialchars($kalkis['departure_city']); ?>">
                            <?php echo htmlspecialchars($kalkis['departure_city']); ?>
                        </option>
                    <?php endforeach;?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="dest" class="form-label">Varış Yeri</label>
                <select name="destination_city" id="dest" class="form-select">
                    <option value="">Seçiniz</option>
                    <?php foreach ($varis_yerleri as $varis): ?>
                        <option value="<?php echo htmlspecialchars($varis['destination_city']); ?>">
                            <?php echo htmlspecialchars($varis['destination_city']); ?>
                        </option>
                    <?php endforeach;?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="time" class="form-label">Tarih</label>
                <input type="date" name="departure_time" id="time" class="form-control" required>
            </div>

            <div class="col-md-1 d-flex align-items-end">
                <input type="submit" value="Ara" name="ara" class="btn btn-primary w-100">
            </div>
        </form>
    </div>

    <?php if(!empty($sefer_listesi)): ?>
        <div class="card shadow mt-5">
            <div class="card-body">
                <h5 class="card-title mb-3">Uygun Seferler</h5>
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Kalkış Yeri</th>
                            <th>Varış Yeri</th>
                            <th>Kalkış Tarihi</th>
                            <th>Varış Tarihi</th>
                            <th>Firma</th>
                            <th>Fiyat</th>
                            <th> </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($sefer_listesi as $sefer): ?>
                        <tr>
                            <td><?php echo $sefer['departure_city']; ?></td>
                            <td><?php echo $sefer['destination_city']; ?></td>
                            <td><?php echo $sefer['departure_time']; ?></td>
                            <td><?php echo $sefer['arrival_time']; ?></td>
                            <td><?php echo $sefer['company'] ?? 'Belirtilmemiş'; ?></td>
                            <td><?php echo $sefer['price']; ?> ₺</td>
                            <td><a href="bilet_al.php?id=<?= $sefer['id'] ?>" class="btn btn-sm btn-primary w-100">SATIN AL</a></td>
                        </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
