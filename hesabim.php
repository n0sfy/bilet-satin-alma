<?php
session_start();
require_once 'includes/config.php';

$basari_mesaji = $_SESSION['basari_mesaji'] ?? '';
$hata_mesaji = $_SESSION['hata_mesaji'] ?? '';
unset($_SESSION['basari_mesaji'], $_SESSION['hata_mesaji']);

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$user_balance = $user['balance'];
$biletler = [];

try {
    $sql_biletler = "SELECT 
                        T.id AS ticket_id,
                        T.created_at AS purchase_date,
                        T.total_price,
                        T.status,
                        B.seat_number,
                        TR.departure_city,
                        TR.destination_city,
                        TR.departure_time,
                        TR.arrival_time,
                        TR.price AS trip_price
                    FROM Tickets AS T
                    INNER JOIN Booked_Seats AS B ON T.id = B.ticket_id
                    INNER JOIN Trips AS TR ON T.trip_id = TR.id
                    WHERE T.user_id = ? 
                    ORDER BY TR.departure_time DESC";

    $stmt_biletler = $pdo->prepare($sql_biletler);
    $stmt_biletler->execute([$user_id]);
    $biletler = $stmt_biletler->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $hata_mesaji = "Biletler listelenirken veritabanı hatası: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hesabım - Biletlerim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h1 class="mb-4 text-center text-primary fw-bold">Hesabım ve Biletlerim</h1>

<div class="row justify-content-center g-4">

    <!-- Kişisel Bilgilerim -->
    <div class="col-md-5">
        <div class="card shadow-lg border-0" style="background: linear-gradient(135deg, #198754, #20c997); color: #fff;">
            <div class="card-header border-0 bg-transparent text-center">
                <h4 class="fw-bold mb-0"><i class="bi bi-person-circle me-2"></i>Kişisel Bilgilerim</h4>
            </div>
            <div class="card-body bg-white text-dark rounded-bottom">
                <p class="mb-2"><strong>Ad Soyad:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                <p class="mb-2"><strong>E-posta:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p class="mb-2 fw-bold text-success">
                    <strong>Mevcut Bakiye:</strong> <?= htmlspecialchars($user_balance) ?> TL
                </p>
                <a href="index.php" class="btn btn-outline-success btn-sm mt-3">
                    <i class="bi bi-house-door-fill me-1"></i> Ana Sayfaya Dön
                </a>
            </div>
        </div>
    </div>
    

    

   <div class="container my-5">

    <!-- Mesajlar -->
    <?php if (!empty($basari_mesaji)): ?>
        <div class="alert alert-success d-flex align-items-center shadow-sm">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($basari_mesaji) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($hata_mesaji)): ?>
        <div class="alert alert-danger d-flex align-items-center shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($hata_mesaji) ?>
        </div>
    <?php endif; ?>

    <!-- Başlık -->
    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary">
            <i class="bi bi-ticket-perforated-fill me-2"></i> Satın Aldığım Biletler
        </h2>
    </div>

    <!-- Boş durum -->
    <?php if (empty($biletler)): ?>
        <div class="alert alert-info text-center shadow-sm py-4">
            <i class="bi bi-emoji-smile me-2"></i> Henüz satın alınmış biletiniz bulunmamaktadır.
            <br>
            <a href="bilet_al.php" class="btn btn-outline-primary btn-sm mt-3">
                <i class="bi bi-search me-1"></i> Sefer Ara
            </a>
        </div>
    <?php else: ?>

        <!-- Bilet Tablosu -->
        <div class="card shadow-lg border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>Sefer</th>
                                <th>Kalkış Zamanı</th>
                                <th>Koltuk No</th>
                                <th>Fiyat</th>
                                <th>Durum</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            <?php foreach ($biletler as $bilet):
                                $kalkis_saati = strtotime($bilet['departure_time']);
                                $son_saat = strtotime('-1 hour', $kalkis_saati);
                                $simdiki_zaman = time();
                                $iptal_edilebilir = ($simdiki_zaman < $son_saat && $bilet['status'] === 'active');
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($bilet['departure_city']) ?> → <?= htmlspecialchars($bilet['destination_city']) ?></td>
                                <td><?= date('d.m.Y H:i', $kalkis_saati) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($bilet['seat_number']) ?></span></td>
                                <td><strong><?= htmlspecialchars($bilet['total_price']) ?> TL</strong></td>
                                <td>
                                    <span class="badge bg-<?= $bilet['status'] === 'active' ? 'success' : 'danger' ?> px-3 py-2">
                                        <?= strtoupper($bilet['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($bilet['status'] === 'active'): ?>
                                        <a href="bilet_pdf.php?id=<?= $bilet['ticket_id'] ?>" class="btn btn-sm btn-outline-info me-2">
                                            <i class="bi bi-file-earmark-pdf-fill me-1"></i> PDF
                                        </a>
                                        <?php if ($iptal_edilebilir): ?>
                                            <a href="?action=cancel&id=<?= $bilet['ticket_id'] ?>" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-circle me-1"></i> İptal Et
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                                <i class="bi bi-hourglass-split me-1"></i> Süre Doldu
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
