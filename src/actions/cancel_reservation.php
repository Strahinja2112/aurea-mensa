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

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $reservationId = $data['id'] ?? null;

    if (!$reservationId) {
        throw new Exception('Neispravan zahtev (nedostaje ID).');
    }

    $reservation = db()->reservations->findFirst([
        'where' => [
            'id' => (int)$reservationId,
            'user_id' => $user->id,
            'status' => 'ACTIVE'
        ]
    ]);

    if (!$reservation) {
        throw new Exception("Rezervacija nije pronađena ili je već otkazana.");
    }

    $resTimestamp = strtotime($reservation['reservation_date'] . ' ' . $reservation['start_time']);
    if (($resTimestamp - time()) < 14400) {
        throw new Exception("Otkazivanje je moguće najkasnije 4 sata pre termina.");
    }

    $updated = db()->reservations->update([
        'where' => ['id' => $reservation['id']],
        'data' => ['status' => 'CANCELLED']
    ]);

    if (!$updated) {
        throw new Exception("Greška prilikom ažuriranja baze podataka.");
    }

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Uspešno otkazano!']);
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
