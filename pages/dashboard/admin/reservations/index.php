<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../autoload.php';

$user = Auth::getUser();

if (!$user || !$user->isAdmin()) {
    http_response_code(403);
    header('Location: ' . Helpers::getRelativePath('pages/auth/login'));
    exit;
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');

$reservations = db()->reservations->findMany([
    'where' => ['reservation_date' => $selectedDate],
    'orderBy' => ['start_time' => 'ASC'],
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
?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead("Rezervacije", ['dashboard.css']) ?>

<body>
<?php include Helpers::getRelativePath('src/components/header.php') ?>

<main>
    <div class="dashboard-container">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="admin-title">Rezervacije</h1>
                <p class="admin-subtitle">Pregled rezervacija po datumu</p>
            </div>
            <a href="index.php" class="btn btn-outline-light btn-lg">
                <i class="fa-solid fa-arrow-left me-2"></i> Nazad
            </a>
        </div>

        <div class="glass-card p-4 mb-4 border-0" style="max-width: 400px;">
            <form method="GET">
                <label class="form-label small opacity-50">Izaberite datum</label>
                <div class="d-flex gap-2">
                    <input type="date" name="date" class="form-control custom-input" value="<?= htmlspecialchars($selectedDate) ?>" required>
                    <button type="submit" class="btn btn-light px-4 fw-bold">Prikaži</button>
                </div>
            </form>
        </div>

        <div class="glass-card p-4 border-0" style="max-width: 100%;">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="text-white mb-0 font-serif">
                    Rezervacije za <?= (DateTime::createFromFormat('Y-m-d', $selectedDate))->format('d. m. Y.') ?>
                </h5>
                <span class="small opacity-50">
                    Ukupno: <?= count($reservations) ?> rezervacija
                </span>
            </div>

            <?php if (empty($reservations)): ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-calendar-xmark fa-3x mb-3 opacity-10"></i>
                    <p class="text-white opacity-25 fw-light mb-0">Nema rezervacija za ovaj datum.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-borderless align-middle mb-0" style="background: transparent;">
                        <thead>
                            <tr class="opacity-50 small text-uppercase" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <th class="py-2 ps-0">Kod</th>
                                <th class="py-2">Vreme</th>
                                <th class="py-2">Gost</th>
                                <th class="py-2">Email</th>
                                <th class="py-2">Stolovi</th>
                                <th class="py-2">Status</th>
                                <th class="py-2 text-end pe-0">Dolazak</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $res): ?>
                                <?php
                                $u = $reservationUsers[$res['id']] ?? null;
                                $tables = $reservationTables[$res['id']] ?? [];

                                $statusLabel = match ($res['status']) {
                                    'ACTIVE' => 'Aktivna',
                                    'COMPLETED' => 'Realizovana',
                                    'CANCELLED' => 'Otkazana',
                                    default => $res['status'],
                                };
                                $statusClass = match ($res['status']) {
                                    'ACTIVE' => 'text-success',
                                    'COMPLETED' => 'text-info',
                                    'CANCELLED' => 'text-danger opacity-50',
                                    default => 'text-white',
                                };
                                $arrivedIcon = $res['arrived']
                                    ? '<i class="fa-solid fa-check-circle text-success"></i>'
                                    : '<span class="opacity-25">—</span>';
                                ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td class="py-3 ps-0"><code class="text-white"><?= htmlspecialchars($res['reservation_code']) ?></code></td>
                                    <td class="py-3 text-white"><?= substr($res['start_time'], 0, 5) ?> – <?= substr($res['end_time'], 0, 5) ?></td>
                                    <td class="py-3 text-white">
                                        <?php if ($u): ?>
                                            <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                        <?php else: ?>
                                            <span class="opacity-25">Nepoznat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 text-white opacity-75 small">
                                        <?= $u ? htmlspecialchars($u['email']) : '—' ?>
                                    </td>
                                    <td class="py-3 text-white small">
                                        <?= !empty($tables) ? htmlspecialchars(implode(', ', $tables)) : '—' ?>
                                    </td>
                                    <td class="py-3 <?= $statusClass ?> fw-medium"><?= $statusLabel ?></td>
                                    <td class="py-3 text-end pe-0"><?= $arrivedIcon ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include Helpers::getRelativePath('src/components/footer.php') ?>

<style>
    .admin-title {
        font-size: 42px;
        line-height: 1;
        font-family: serif;
        font-weight: 300;
        color: #f8f9fa;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }
    .admin-subtitle {
        color: rgba(255, 255, 255, 0.5);
        font-size: 1rem;
        margin: 0;
    }
    main {
        padding-top: 150px !important;
        padding-bottom: 80px !important;
        padding-inline: 0;
    }
    .dashboard-container {
        padding: 0 20px;
    }
    .glass-card {
        max-width: none;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
