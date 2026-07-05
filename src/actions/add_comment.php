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
        throw new Exception('Samo osoblje može dodavati komentare.');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    Auth::verifyCsrfToken($data['csrf_token'] ?? null);

    $reservationId = (int)($data['reservation_id'] ?? 0);
    $comment = trim($data['comment'] ?? '');

    if (!$reservationId) {
        throw new Exception('Neispravan zahtev (nedostaje ID rezervacije).');
    }

    if (empty($comment)) {
        throw new Exception('Komentar ne može biti prazan.');
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

    db()->reservations->update([
        'where' => ['id' => $reservationId],
        'data' => ['comment' => $comment]
    ]);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Komentar uspešno dodat!'
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
