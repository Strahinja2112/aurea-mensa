<?php

declare(strict_types=1);

date_default_timezone_set('Europe/Belgrade');

ob_start();

try {
    require_once '../../autoload.php';

    header('Content-Type: application/json; charset=UTF-8');

    $user = Auth::getUser();

    if (!$user) {
        throw new Exception('Morate biti prijavljeni da biste proverili termine.');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!is_array($data)) {
        throw new Exception('Neispravan JSON zahtev.');
    }

    $tableIds = $data['table_ids'] ?? [];
    $reservationDate = (string)($data['reservation_date'] ?? '');

    if (!is_array($tableIds) || empty($tableIds)) {
        throw new Exception('Morate izabrati bar jedan sto.');
    }

    if ($reservationDate === '') {
        throw new Exception('Datum je obavezan.');
    }

    $date = DateTime::createFromFormat('Y-m-d', $reservationDate);

    if (!$date || $date->format('Y-m-d') !== $reservationDate) {
        throw new Exception('Format datuma nije ispravan.');
    }

    $tableIds = array_values(array_unique(array_map('intval', $tableIds)));

    $existingTables = db()->restaurantTables->findMany([
        'where' => [
            'id' => ['in' => $tableIds]
        ],
        'select' => ['id']
    ]);

    if (count($existingTables) !== count($tableIds)) {
        throw new Exception('Jedan ili više izabranih stolova ne postoji.');
    }

    $reservationTables = db()->reservationTables->findMany([
        'where' => [
            'table_id' => ['in' => $tableIds]
        ]
    ]);

    $tablesByReservation = [];

    foreach ($reservationTables as $reservationTable) {
        $reservationId = (int)$reservationTable['reservation_id'];
        $tableId = (int)$reservationTable['table_id'];
        $tablesByReservation[$reservationId][] = $tableId;
    }

    $reservationIds = array_values(array_unique(array_map(
        static fn(array $reservationTable): int => (int)$reservationTable['reservation_id'],
        $reservationTables
    )));

    $busyIntervals = [];
    $busyByTable = [];

    foreach ($tableIds as $tableId) {
        $busyByTable[(string)$tableId] = [];
    }

    if (!empty($reservationIds)) {
        $reservations = db()->reservations->findMany([
            'where' => [
                'id' => ['in' => $reservationIds],
                'reservation_date' => $reservationDate,
                'status' => 'ACTIVE'
            ],
            'select' => ['id', 'start_time', 'end_time'],
            'orderBy' => ['start_time' => 'ASC']
        ]);

        foreach ($reservations as $reservation) {
            $interval = [
                'start' => substr((string)$reservation['start_time'], 0, 5),
                'end' => substr((string)$reservation['end_time'], 0, 5),
            ];

            $busyIntervals[] = $interval;

            foreach ($tablesByReservation[(int)$reservation['id']] ?? [] as $tableId) {
                if (isset($busyByTable[(string)$tableId])) {
                    $busyByTable[(string)$tableId][] = $interval;
                }
            }
        }
    }

    ob_end_clean();

    echo json_encode([
        'success' => true,
        'busy_intervals' => $busyIntervals,
        'busy_by_table' => $busyByTable
    ]);
} catch (Exception $exception) {
    if (ob_get_length()) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
        'busy_intervals' => [],
        'busy_by_table' => []
    ]);
}

exit;
