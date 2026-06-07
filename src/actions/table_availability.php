<?php

declare(strict_types=1);

date_default_timezone_set('Europe/Belgrade');

ob_start();

try {
    require_once '../../autoload.php';

    header('Content-Type: application/json; charset=UTF-8');

    $user = Auth::getUser();

    if (!$user) {
        throw new Exception('Morate biti prijavljeni da biste proverili dostupnost stolova.');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!is_array($data)) {
        throw new Exception('Neispravan JSON zahtev.');
    }

    $reservationDate = (string)($data['reservation_date'] ?? '');
    $startTime = (string)($data['start_time'] ?? '');
    $durationHours = (int)($data['duration_hours'] ?? 0);

    if ($reservationDate === '' || $startTime === '') {
        throw new Exception('Datum i vreme su obavezni.');
    }

    if ($durationHours < 1 || $durationHours > 6) {
        throw new Exception('Rezervacija može trajati minimum 1 sat, a maksimum 6 sati.');
    }

    $startDateTime = DateTime::createFromFormat('Y-m-d H:i', $reservationDate . ' ' . $startTime);

    if (!$startDateTime) {
        throw new Exception('Format datuma ili vremena nije ispravan.');
    }

    $endDateTime = clone $startDateTime;
    $endDateTime->modify("+{$durationHours} hours");

    $reservationDateFormatted = $startDateTime->format('Y-m-d');
    $newStart = strtotime($startDateTime->format('H:i:s'));
    $newEnd = strtotime($endDateTime->format('H:i:s'));

    $reservations = db()->reservations->findMany([
        'where' => [
            'reservation_date' => $reservationDateFormatted,
            'status' => 'ACTIVE'
        ],
        'select' => ['id', 'start_time', 'end_time']
    ]);

    $reservationIds = [];

    foreach ($reservations as $reservation) {
        $existingStart = strtotime($reservation['start_time']);
        $existingEnd = strtotime($reservation['end_time']);

        if (($existingStart < $newEnd) && ($existingEnd > $newStart)) {
            $reservationIds[] = (int)$reservation['id'];
        }
    }

    $unavailableTableIds = [];

    if (!empty($reservationIds)) {
        $reservationTables = db()->reservationTables->findMany([
            'where' => [
                'reservation_id' => ['in' => $reservationIds]
            ]
        ]);

        foreach ($reservationTables as $reservationTable) {
            $unavailableTableIds[] = (int)$reservationTable['table_id'];
        }
    }

    $unavailableTableIds = array_values(array_unique($unavailableTableIds));

    ob_end_clean();

    echo json_encode([
        'success' => true,
        'unavailable_table_ids' => $unavailableTableIds
    ]);
} catch (Exception $exception) {
    if (ob_get_length()) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
        'unavailable_table_ids' => []
    ]);
}

exit;
