<?php
require_once 'includes/config.php';
session_start();

$basari_mesaji = $_SESSION['basari_mesaji'] ?? '';
$hata_mesaji   = $_SESSION['hata_mesaji'] ?? '';
unset($_SESSION['basari_mesaji'], $_SESSION['hata_mesaji']);
$user= $_SESSION['user'] ?? null;


if (empty($user)) {
    header('Location: login.php');
    exit();
}

$trip_id        = $_GET['id'] ?? null;
$hata           = '';
$trip           = null;
$dolu_koltuklar = [];

if (!$trip_id) {
    $hata = "Sefer ID'si eksik. Lütfen ana sayfadan bir sefer seçiniz.";
} else {
    try {
        $stmt_trip = $pdo->prepare("SELECT * FROM Trips WHERE id = ?");
        $stmt_trip->execute([$trip_id]);
        $trip = $stmt_trip->fetch(PDO::FETCH_ASSOC);

        if (!$trip) {
            $hata = "Belirtilen ID ile sefer bulunamadı.";
        } else {
            $sql_dolu = "
                SELECT B.seat_number
                FROM Booked_Seats AS B
                INNER JOIN Tickets AS T ON B.ticket_id = T.id
                WHERE T.trip_id = ?
            ";
            $stmt_seats = $pdo->prepare($sql_dolu);
            $stmt_seats->execute([$trip_id]);
            $dolu_koltuklar = $stmt_seats->fetchAll(PDO::FETCH_COLUMN, 0);
        }
    } catch (PDOException $e) {
        $hata = "Veritabanı hatası: " . $e->getMessage();
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'satinal' &&
    $trip
) {
    $secilen_koltuk = $_POST['seat_number'] ?? null;
    $bilet_fiyati   = (float)($trip['price'] ?? 0);
    $user_bakiyesi  = (float)$user['balance'];
    $hata_mesaji    = '';

    if (!$secilen_koltuk) {
        $hata_mesaji = "Lütfen bir koltuk numarası seçiniz.";
    } elseif (in_array((int)$secilen_koltuk, $dolu_koltuklar)) {
        $hata_mesaji = "Seçtiğiniz koltuk kısa süre önce satılmıştır.";
    } elseif ($user_bakiyesi < $bilet_fiyati) {
        $hata_mesaji = "Hesabınızda yeterli bakiye bulunmamaktadır.";
    }

    if (empty($hata_mesaji)) {
        $yeni_bakiye      = $user_bakiyesi - $bilet_fiyati;
        $ticket_id        = generateUUID();
        $booked_id        = generateUUID();
        $satinalma_tarihi = date('Y-m-d H:i:s');

        try {
            $pdo->beginTransaction();
            
            $stmt_update = $pdo->prepare("UPDATE User SET balance = ? WHERE id = ?");
            $stmt_update->execute([$yeni_bakiye, $user['id']]);

            $stmt_ticket = $pdo->prepare("
                INSERT INTO Tickets (id, trip_id, user_id, status, total_price, created_at)
                VALUES (?, ?, ?, 'active', ?, ?)
            ");
            $stmt_ticket->execute([$ticket_id, $trip_id, $user['id'], $bilet_fiyati, $satinalma_tarihi]);

            $stmt_booked = $pdo->prepare("
                INSERT INTO Booked_Seats (id, ticket_id, seat_number, created_at)
                VALUES (?, ?, ?, ?)
            ");
            $stmt_booked->execute([$booked_id, $ticket_id, $secilen_koltuk, $satinalma_tarihi]);

            $pdo->commit();

            $_SESSION['basari_mesaji'] = "Biletiniz başarıyla satın alındı. Koltuk No: $secilen_koltuk";
            $_SESSION['user']['balance'] = $yeni_bakiye;
            header('Location: hesabim.php');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $hata_mesaji = "Satın alma sırasında veritabanı hatası: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bilet Satın Al</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <?php if ($hata): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($hata) ?></div>
    <?php elseif ($hata_mesaji): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($hata_mesaji) ?></div>
    <?php elseif ($trip): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <?= htmlspecialchars($trip['departure_city']) ?> →
                    <?= htmlspecialchars($trip['destination_city']) ?>
                </h5>
            </div>
            <div class="card-body row">
                <div class="col-md-4">
                    <p><strong>Kalkış Zamanı:</strong>
                        <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></p>
                    <p><strong>Mevcut Bakiyeniz:</strong>
                        <span class="text-success fw-bold"><?= htmlspecialchars($user['balance']) ?> TL</span>
                    </p>
                </div>
                <div class="col-md-4">
                    <p class="text-danger fw-bold"><strong>Bilet Fiyatı:</strong>
                        <?= htmlspecialchars($trip['price']) ?> TL</p>
                </div>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip_id) ?>">
            <div class="card p-4 shadow-sm">
                <h5 class="mb-3">1. Koltuk Seçimi</h5>
                <div class="row row-cols-6 g-2 justify-content-center mb-4">
                    <?php
                    for ($i = 1; $i <= $trip['capacity']; $i++):
                        $is_dolu      = in_array($i, $dolu_koltuklar);
                        $koltuk_class = $is_dolu ? 'btn-danger disabled' : 'btn-outline-success';
                    ?>
                        <div class="col text-center">
                            <label>
                                <input type="radio" name="seat_number" value="<?= $i ?>"
                                       class="btn-check" id="seat-<?= $i ?>"
                                       <?= $is_dolu ? 'disabled' : '' ?> required>
                                <span class="btn <?= $koltuk_class ?> w-100" style="padding:10px;"><?= $i ?></span>
                            </label>
                        </div>
                    <?php endfor; ?>
                </div>

                <hr class="my-4">
                <h5 class="mb-3">2. Kupon Kodu (İsteğe Bağlı)</h5>
                <div class="input-group mb-3">
                    <input type="text" name="kupon_kodu" class="form-control" placeholder="Kupon Kodunu Giriniz" disabled>
                    <button class="btn btn-outline-secondary" type="button" disabled>Uygula</button>
                </div>

                <div class="text-center mt-3">
                    <button type="submit" name="action" value="satinal"
                            class="btn btn-success btn-lg w-75">
                        Bileti Satın Al (<?= htmlspecialchars($trip['price']) ?> TL)
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
