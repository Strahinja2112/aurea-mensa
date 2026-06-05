<?php

declare(strict_types=1);

try {
    require_once '../../autoload.php';

    $user = Auth::getUser();

    if (!$user || !$user->isAdmin()) {
        http_response_code(403);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit;
    }

    $seedType = $_POST['seed'] ?? '';

    $redirectUrl = Helpers::getRelativePath('pages/dashboard/admin/seed');

    switch ($seedType) {
        case 'roles':
            seedRoles($redirectUrl);
            break;
        case 'tables':
            seedTables($redirectUrl);
            break;
        case 'users':
            seedUsers($redirectUrl);
            break;
        case 'reservations':
            seedReservations($redirectUrl);
            break;
        case 'all':
            seedAll($redirectUrl);
            break;
        default:
            header('Location: ' . $redirectUrl . '?error=Nepoznat tip seedovanja');
            exit;
    }
} catch (Exception $e) {
    header('Location: ' . $redirectUrl . '?error=' . urlencode($e->getMessage()));
    exit;
}

function seedRoles(string $redirectUrl): void
{
    $existingRoles = db()->roles->findMany();
    if (!empty($existingRoles)) {
        header('Location: ' . $redirectUrl . '?error=Uloge već postoje');
        exit;
    }

    db()->roles->create(['data' => ['name' => 'USER']]);
    db()->roles->create(['data' => ['name' => 'STAFF']]);
    db()->roles->create(['data' => ['name' => 'ADMIN']]);

    header('Location: ' . $redirectUrl . '?success=Uloge su uspešno dodate');
    exit;
}

function seedTables(string $redirectUrl): void
{
    $existingTables = db()->restaurantTables->findMany();
    if (!empty($existingTables)) {
        header('Location: ' . $redirectUrl . '?error=Stolovi već postoje');
        exit;
    }

    $tables = [
        ['name' => 'Sto 1', 'seats' => 2, 'location_description' => 'Prozor', 'is_smoking' => 0],
        ['name' => 'Sto 2', 'seats' => 2, 'location_description' => 'Prozor', 'is_smoking' => 0],
        ['name' => 'Sto 3', 'seats' => 4, 'location_description' => 'Centar', 'is_smoking' => 0],
        ['name' => 'Sto 4', 'seats' => 4, 'location_description' => 'Centar', 'is_smoking' => 0],
        ['name' => 'Sto 5', 'seats' => 6, 'location_description' => 'Velika sala', 'is_smoking' => 0],
        ['name' => 'Sto 6', 'seats' => 6, 'location_description' => 'Velika sala', 'is_smoking' => 0],
        ['name' => 'Sto 7', 'seats' => 8, 'location_description' => 'VIP sala', 'is_smoking' => 0],
        ['name' => 'Sto 8', 'seats' => 4, 'location_description' => 'Terasa', 'is_smoking' => 1],
        ['name' => 'Sto 9', 'seats' => 4, 'location_description' => 'Terasa', 'is_smoking' => 1],
        ['name' => 'Sto 10', 'seats' => 2, 'location_description' => 'Bar', 'is_smoking' => 0],
    ];

    foreach ($tables as $table) {
        db()->restaurantTables->create(['data' => $table]);
    }

    header('Location: ' . $redirectUrl . '?success=Stolovi su uspešno dodati');
    exit;
}

function seedUsers(string $redirectUrl): void
{
    $roleUser = db()->roles->findFirst(['where' => ['name' => 'USER']]);
    $roleStaff = db()->roles->findFirst(['where' => ['name' => 'STAFF']]);
    $roleAdmin = db()->roles->findFirst(['where' => ['name' => 'ADMIN']]);

    if (!$roleUser || !$roleStaff || !$roleAdmin) {
        header('Location: ' . $redirectUrl . '?error=Uloge ne postoje. Seedujte prvo roles.');
        exit;
    }

    $users = [
        [
            'email' => 'user@test.com',
            'password' => 'Test1234',
            'first_name' => 'Test',
            'last_name' => 'Korisnik',
            'phone' => '+381601234567',
            'role_id' => $roleUser['id'],
        ],
        [
            'email' => 'staff@test.com',
            'password' => 'Staff1234',
            'first_name' => 'Staff',
            'last_name' => 'Član',
            'phone' => '+381601234568',
            'role_id' => $roleStaff['id'],
        ],
        [
            'email' => 'admin@test.com',
            'password' => 'Admin1234',
            'first_name' => 'Admin',
            'last_name' => 'Super',
            'phone' => '+381601234569',
            'role_id' => $roleAdmin['id'],
        ],
    ];

    foreach ($users as $u) {
        $existing = db()->users->findFirst(['where' => ['email' => $u['email']]]);
        if ($existing) continue;

        db()->users->create([
            'data' => [
                'email' => $u['email'],
                'password_hash' => password_hash($u['password'], PASSWORD_BCRYPT),
                'first_name' => $u['first_name'],
                'last_name' => $u['last_name'],
                'phone' => $u['phone'],
                'role_id' => $u['role_id'],
                'email_verified' => 1,
            ]
        ]);
    }

    header('Location: ' . $redirectUrl . '?success=Korisnici su uspešno dodati');
    exit;
}

function seedReservations(string $redirectUrl): void
{
    $users = db()->users->findMany(['where' => ['role_id' => 1]]);
    $tables = db()->restaurantTables->findMany();

    if (empty($users) || empty($tables)) {
        header('Location: ' . $redirectUrl . '?error=Korisnici ili stolovi ne postoje. Seedujte prvo roles, tables i users.');
        exit;
    }

    $today = date('Y-m-d');
    $reservationDates = [
        date('Y-m-d', strtotime('-2 days', strtotime($today))),
        date('Y-m-d', strtotime('-1 days', strtotime($today))),
        $today,
        date('Y-m-d', strtotime('+1 days', strtotime($today))),
        date('Y-m-d', strtotime('+2 days', strtotime($today))),
        date('Y-m-d', strtotime('+5 days', strtotime($today))),
    ];

    $times = ['18:00:00', '19:00:00', '20:00:00'];
    $statuses = ['ACTIVE', 'ACTIVE', 'ACTIVE', 'COMPLETED', 'CANCELLED'];

    $counter = 1;
    foreach ($reservationDates as $date) {
        foreach ($times as $time) {
            if ($counter > 10) break;

            $user = $users[array_rand($users)];
            $status = $statuses[array_rand($statuses)];
            $table = $tables[array_rand($tables)];
            $duration = rand(1, 3);

            $startDateTime = new DateTime($date . ' ' . $time);
            $endDateTime = clone $startDateTime;
            $endDateTime->modify("+{$duration} hours");

            $reservationCode = 'RES' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $reservation = db()->reservations->create([
                'data' => [
                    'user_id' => $user['id'],
                    'reservation_code' => $reservationCode,
                    'reservation_date' => $date,
                    'start_time' => $time,
                    'end_time' => $endDateTime->format('H:i:s'),
                    'status' => $status,
                    'arrived' => $status === 'COMPLETED' ? 1 : 0,
                    'arrived_at' => $status === 'COMPLETED' ? date('Y-m-d H:i:s') : null,
                ]
            ]);

            db()->reservationTables->create([
                'data' => [
                    'reservation_id' => $reservation['id'],
                    'table_id' => $table['id'],
                ]
            ]);

            $counter++;
        }
    }

    header('Location: ' . $redirectUrl . '?success=Rezervacije su uspešno dodate');
    exit;
}

function seedAll(string $redirectUrl): void
{
    db()->beginTransaction();

    try {
        db()->reservationTables->delete(['where' => []]);
        db()->reservations->delete(['where' => []]);
        db()->users->delete(['where' => []]);
        db()->restaurantTables->delete(['where' => []]);
        db()->roles->delete(['where' => []]);

        db()->commit();
    } catch (Exception $e) {
        db()->rollBack();
        header('Location: ' . $redirectUrl . '?error=' . urlencode($e->getMessage()));
        exit;
    }

    seedRoles($redirectUrl);
}
