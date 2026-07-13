<?php

declare(strict_types=1);

date_default_timezone_set('Europe/Belgrade');

ob_start();

try {
    require_once '../../autoload.php';

    header('Content-Type: application/json; charset=UTF-8');

    $user = Auth::getUser();
    if (!$user) {
        throw new Exception('Niste autorizovani.');
    }

    if (!$user->isStaff()) {
        throw new Exception('Samo osoblje može prijaviti goste.');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $reservationId = (int)($data['reservation_id'] ?? 0);
    $reservationCode = trim((string)($data['reservation_code'] ?? ''));

    if (!$reservationId) {
        throw new Exception('Neispravan zahtev (nedostaje ID rezervacije).');
    }

    if ($reservationCode === '') {
        throw new Exception('Morate uneti kod rezervacije.');
    }

    $reservation = db()->reservations->findFirst([
        'where' => [
            'id' => $reservationId,
            'status' => 'ACTIVE'
        ]
    ]);

    if (!$reservation) {
        throw new Exception('Rezervacija nije pronađena.');
    }

    if (!empty($reservation['arrived'])) {
        throw new Exception('Gost je već prijavljen.');
    }

    if (!hash_equals(
        mb_strtoupper((string)$reservation['reservation_code']),
        mb_strtoupper($reservationCode)
    )) {
        throw new Exception('Kod rezervacije nije ispravan.');
    }

    db()->reservations->update([
        'where' => ['id' => $reservationId],
        'data' => [
            'arrived' => 1,
            'arrived_at' => date('Y-m-d H:i:s'),
            'arrival_staff_id' => $user->id,
            'comment' => $data['comment'] ?? null
        ]
    ]);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Gost uspešno prijavljen!'
    ]);
} catch (Exception $e) {
    if (ob_get_length()) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

exit;
