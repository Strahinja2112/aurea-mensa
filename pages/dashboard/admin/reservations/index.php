<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../autoload.php';

$user = Auth::getUser();

if (!$user || !$user->isAdmin()) {
    http_response_code(403);
    header('Location: ' . Helpers::getRelativePath('pages/auth/login'));
    exit;
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$reservationWhere = ['reservation_date' => $selectedDate];

if ($search !== '') {
    $matchingUsers = db()->users->findMany([
        'where' => [
            'OR' => [
                ['email' => ['contains' => $search]],
                ['first_name' => ['contains' => $search]],
                ['last_name' => ['contains' => $search]],
                ['phone' => ['contains' => $search]],
            ]
        ]
    ]);
    $userIds = array_map(fn($u) => $u['id'], $matchingUsers);
    if (!empty($userIds)) {
        $reservationWhere = [
            'AND' => [
                ['reservation_date' => $selectedDate],
                ['user_id' => ['in' => $userIds]],
            ]
        ];
    } else {
        $reservationWhere = [
            'AND' => [
                ['reservation_date' => $selectedDate],
                ['user_id' => 0],
            ]
        ];
    }
}

$total = db()->reservations->count(['where' => $reservationWhere]);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);

$reservations = db()->reservations->findMany([
    'where' => $reservationWhere,
    'orderBy' => ['start_time' => 'ASC'],
    'take' => $perPage,
    'skip' => ($page - 1) * $perPage,
]);

$reservationUsers = [];
$reservationTables = [];

foreach ($reservations as $res) {
    $u = db()->users->findFirst(['where' => ['id' => $res['user_id']]]);
    $reservationUsers[$res['id']] = $u;

    $rts = db()->reservationTables->findMany([
        'where' => ['reservation_id' => $res['id']]
    ]);
    $tableNames = [];
    foreach ($rts as $rt) {
        $t = db()->restaurantTables->findFirst(['where' => ['id' => $rt['table_id']]]);
        if ($t) $tableNames[] = $t['name'];
    }
    $reservationTables[$res['id']] = $tableNames;
}

$relativePath = Helpers::getRelativePath("/pages/dashboard/admin/");

?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead("Rezervacije", ['dashboard.css', 'table.css']) ?>

<body>
    <?php include Helpers::getRelativePath('src/components/header.php') ?>

    <main>
        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="admin-title">Rezervacije</h1>
                    <p class="admin-subtitle">Pregled rezervacija po datumu</p>
                </div>
                <a href="<?= $relativePath . '/dashboard/admin' ?>" class="btn btn-outline-light btn-lg">
                    <i class="fa-solid fa-arrow-left me-2"></i> Nazad
                </a>
            </div>

            <div class="glass-card p-4 border-0" style="max-width: 100%;">
                <form method="GET" class="mb-4 pb-4" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label small opacity-50 mb-1">Izaberite datum</label>
                            <input type="date" name="date" class="form-control custom-input" value="<?= htmlspecialchars($selectedDate) ?>" required style="padding-top: 6px; padding-bottom: 6px;">
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label small opacity-50 mb-1">Pretraga (email, ime, telefon)</label>
                            <input type="text" name="search" class="form-control custom-input" value="<?= htmlspecialchars($search) ?>" placeholder="Pretraži..." style="padding-top: 6px; padding-bottom: 6px;">
                        </div>
                        <div class="col-12 col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-cta flex-fill" style="padding-top: 6px; padding-bottom: 6px;">Prikaži</button>
                            <?php if ($search !== ''): ?>
                                <a href="?date=<?= urlencode($selectedDate) ?>" class="btn btn-outline-light rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px;" title="Poništi pretragu">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <span class="opacity-50">
                        Rezervacije za <?= (DateTime::createFromFormat('Y-m-d', $selectedDate))->format('d. m. Y.') ?>
                    </span>
                    <span class="opacity-50">
                        Ukupno: <?= $total ?> rezervacija
                        <?php if ($totalPages > 1): ?>
                            (strana <?= $page ?> od <?= $totalPages ?>)
                        <?php endif; ?>
                    </span>
                </div>

                <?php

                $statusGroups = [
                        'ACTIVE' => [],
                        'COMPLETED' => [],
                        'CANCELLED' => []
                ];

                foreach ($reservations as $reservation) {
                    $statusGroups[$reservation['status']][] = $reservation;
                }

                ?>

                <?php if (empty($reservations)): ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-calendar-xmark fa-3x mb-3 opacity-10"></i>
                        <p class="text-white opacity-25 fw-light mb-0">Nema rezervacija za ovaj datum.</p>
                    </div>
                <?php else: ?>
                <div class="reservation-board">

                    <?php
                    $labels = [
                            'ACTIVE' => 'Aktivne',
                            'COMPLETED' => 'Realizovane',
                            'CANCELLED' => 'Otkazane'
                    ];
                    ?>

                    <?php foreach ($statusGroups as $status => $items): ?>

                        <div class="reservation-column"
                                data-status="<?= $status ?>">

                            <div class="reservation-column-title">
                                <?= $labels[$status] ?>
                            </div>

                            <?php foreach ($items as $res):

                                $u = $reservationUsers[$res['id']] ?? null;
                                $tables = $reservationTables[$res['id']] ?? [];

                                $statusLabel = match ($res['status']) {
                                    'ACTIVE' => 'Aktivna',
                                    'COMPLETED' => 'Realizovana',
                                    'CANCELLED' => 'Otkazana',
                                    default => $res['status'],
                                };
                                ?>

                                <div class="reservation-card"
                                        draggable="true"
                                        data-reservation-id="<?= $res['id'] ?>"
                                        data-status="<?= $res['status'] ?>">

                                    <div class="reservation-card-header">

                    <span class="reservation-code">
                        <?= htmlspecialchars($res['reservation_code']) ?>
                    </span>

                                        <span class="status-badge status-<?= strtolower($res['status']) ?>">
                        <?= $statusLabel ?>
                    </span>

                                    </div>

                                    <div class="reservation-card-body">

                                        <strong>Gost</strong><br>
                                        <?= $u
                                                ? htmlspecialchars($u['first_name'] . ' ' . $u['last_name'])
                                                : 'Nepoznat'; ?>

                                        <br><br>

                                        <strong>Vreme</strong><br>
                                        <?= substr($res['start_time'],0,5) ?>
                                        -
                                        <?= substr($res['end_time'],0,5) ?>

                                        <br><br>

                                        <strong>Stolovi</strong><br>
                                        <?= !empty($tables)
                                                ? htmlspecialchars(implode(', ', $tables))
                                                : '—'; ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endforeach; ?>

                </div>


                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-wrap">
                            <?php if ($page > 1): ?>
                                <a href="?date=<?= urlencode($selectedDate) ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" class="page-btn">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?date=<?= urlencode($selectedDate) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"
                                    class="page-btn <?= $i === $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?date=<?= urlencode($selectedDate) ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" class="page-btn">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>

</body>

</html>