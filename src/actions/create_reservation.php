<?php

declare(strict_types=1);

date_default_timezone_set('Europe/Belgrade');

ob_start();

require_once '../../autoload.php';

try {
    require_once Helpers::getRelativePath('src/classes/Mailer.php');

    header('Content-Type: application/json; charset=UTF-8');

    $user = Auth::getUser();

    if (!$user) {
        throw new Exception('Morate biti prijavljeni da biste rezervisali sto.');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Neispravan JSON zahtev.');
    }

    Auth::verifyCsrfToken($data['csrf_token'] ?? null);


    $tableIds = $data['table_ids'] ?? [];
    $reservationDate = $data['reservation_date'] ?? '';
    $startTime = $data['start_time'] ?? '';
    $durationHours = (int)($data['duration_hours'] ?? 0);

    if (!is_array($tableIds) || empty($tableIds)) {
        throw new Exception('Morate izabrati bar jedan sto.');
    }

    $tableIds = array_map('intval', $tableIds);
    $tableIds = array_values(array_unique($tableIds));

    if ($reservationDate === '' || $startTime === '') {
        throw new Exception('Datum i vreme su obavezni.');
    }

    if ($durationHours < 1 || $durationHours > 6) {
        throw new Exception('Rezervacija može trajati minimum 1 sat, a maksimum 6 sati.');
    }

    $startDateTime = DateTime::createFromFormat(
        'Y-m-d H:i',
        $reservationDate . ' ' . $startTime
    );

    if (!$startDateTime) {
        throw new Exception('Format datuma ili vremena nije ispravan.');
    }

    $now = new DateTime();

    if ($startDateTime <= $now) {
        throw new Exception('Ne možete napraviti rezervaciju u prošlosti.');
    }

    $endDateTime = clone $startDateTime;
    $endDateTime->modify("+{$durationHours} hours");

    $workingHoursText = (int)$startDateTime->format('N') >= 6
        ? Helpers::WEBSITE_WORKING_WEEKEND
        : Helpers::WEBSITE_WORKING_DAYS;
    [$workingStartText, $workingEndText] = array_map('trim', explode('-', $workingHoursText));
    $toMinutes = static function (string $time): int {
        [$hours, $minutes] = array_map('intval', explode(':', $time));
        return ($hours * 60) + $minutes;
    };
    $workingStartMinutes = $toMinutes($workingStartText);
    $workingEndMinutes = $toMinutes($workingEndText);

    if ($workingEndMinutes <= $workingStartMinutes) {
        $workingEndMinutes += 1440;
    }

    $reservationStartMinutes = ((int)$startDateTime->format('H') * 60) + (int)$startDateTime->format('i');
    $reservationEndMinutes = $reservationStartMinutes + ($durationHours * 60);

    if ($reservationStartMinutes < $workingStartMinutes || $reservationEndMinutes > $workingEndMinutes) {
        throw new Exception('Rezervacija mora biti u okviru radnog vremena restorana.');
    }

    $startTimeFormatted = $startDateTime->format('H:i:s');
    $endTimeFormatted = $endDateTime->format('H:i:s');
    $reservationDateFormatted = $startDateTime->format('Y-m-d');

    $existingTables = db()->restaurantTables->findMany([
        'where' => [
            'id' => ['in' => $tableIds]
        ],
        'select' => ['id']
    ]);

    if (count($existingTables) !== count($tableIds)) {
        throw new Exception('Jedan ili više izabranih stolova ne postoji.');
    }

    $conflictingReservations = db()->reservationTables->findMany([
        'where' => [
            'table_id' => ['in' => $tableIds]
        ]
    ]);

    foreach ($conflictingReservations as $rt) {
        $reservation = db()->reservations->findFirst([
            'where' => [
                'id' => $rt['reservation_id'],
                'reservation_date' => $reservationDateFormatted,
                'status' => 'ACTIVE'
            ]
        ]);

        if (!$reservation) {
            continue;
        }

        $existingStart = strtotime($reservation['start_time']);
        $existingEnd = strtotime($reservation['end_time']);

        $newStart = strtotime($startTimeFormatted);
        $newEnd = strtotime($endTimeFormatted);

        $overlap = ($existingStart < $newEnd) && ($existingEnd > $newStart);

        if ($overlap) {
            throw new Exception('Jedan od izabranih stolova je već rezervisan u tom terminu.');
        }
    }

    db()->beginTransaction();

    try {
        $maxAttempts = 5;
        do {
            $reservationCode = Helpers::generateReservationCode();
            $existing = db()->reservations->findFirst([
                'where' => ['reservation_code' => $reservationCode],
                'columns' => ['id']
            ]);
            if (!$existing) break;
        } while (--$maxAttempts > 0);

        if ($existing) {
            throw new Exception('Could not generate unique reservation code. Please try again.');
        }

        $reservation = db()->reservations->create([
            'data' => [
                'user_id' => $user->id,
                'reservation_code' => $reservationCode,
                'reservation_date' => $reservationDateFormatted,
                'start_time' => $startTimeFormatted,
                'end_time' => $endTimeFormatted,
                'status' => 'ACTIVE'
            ]
        ]);

        foreach ($tableIds as $tableId) {
            db()->reservationTables->create([
                'data' => [
                    'reservation_id' => $reservation['id'],
                    'table_id' => $tableId
                ]
            ]);
        }

        db()->commit();

        Mailer::sendReservationConfirmationEmail(
            $user->email,
            $reservationCode,
            $reservationDateFormatted,
            $startTimeFormatted,
            $endTimeFormatted,
            count($tableIds)
        );
    } catch (Exception $e) {
        db()->rollBack();
        throw $e;
    }

    ob_end_clean();

    echo json_encode([
        'success' => true,
        'message' => 'Rezervacija uspešno kreirana.',
        'reservation_code' => $reservationCode,
        'data' => [
            'table_ids' => $tableIds,
            'reservation_date' => $reservationDateFormatted,
            'start_time' => $startTimeFormatted,
            'end_time' => $endTimeFormatted,
            'duration_hours' => $durationHours
        ]
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
