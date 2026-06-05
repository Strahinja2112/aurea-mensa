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
                    <label class="form-label small opacity-50">Izaberite datum</label>
                    <div class="d-flex gap-2">
                        <input type="date" name="date" class="form-control custom-input" value="<?= htmlspecialchars($selectedDate) ?>" required style="padding-top: 6px; padding-bottom: 6px;">
                        <button type="submit" class="btn btn-cta">Prikaži</button>
                    </div>
                </form>

                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="text-white mb-0">
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
                    <div class="reservations-table">
                        <div class="rt-head">
                            <div class="rt-row">
                                <div class="rt-cell rt-cell-code">Kod</div>
                                <div class="rt-cell rt-cell-time">Vreme</div>
                                <div class="rt-cell rt-cell-guest">Gost</div>
                                <div class="rt-cell rt-cell-email">Email</div>
                                <div class="rt-cell rt-cell-tables">Stolovi</div>
                                <div class="rt-cell rt-cell-status">Status</div>
                                <div class="rt-cell rt-cell-arrival">Dolazak</div>
                            </div>
                        </div>
                        <div class="rt-body">
                            <?php foreach ($reservations as $res):
                                $u = $reservationUsers[$res['id']] ?? null;
                                $tables = $reservationTables[$res['id']] ?? [];

                                $statusLabel = match ($res['status']) {
                                    'ACTIVE' => 'Aktivna',
                                    'COMPLETED' => 'Realizovana',
                                    'CANCELLED' => 'Otkazana',
                                    default => $res['status'],
                                };
                            ?>
                                <div class="rt-row">
                                    <div class="rt-cell rt-cell-code" data-label="Kod">
                                        <code><?= htmlspecialchars($res['reservation_code']) ?></code>
                                    </div>
                                    <div class="rt-cell rt-cell-time" data-label="Vreme">
                                        <?= substr($res['start_time'], 0, 5) ?> – <?= substr($res['end_time'], 0, 5) ?>
                                    </div>
                                    <div class="rt-cell rt-cell-guest" data-label="Gost">
                                        <?php if ($u): ?>
                                            <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                        <?php else: ?>
                                            <span class="opacity-25">Nepoznat</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="rt-cell rt-cell-email" data-label="Email">
                                        <?= $u ? htmlspecialchars($u['email']) : '—' ?>
                                    </div>
                                    <div class="rt-cell rt-cell-tables" data-label="Stolovi">
                                        <?= !empty($tables) ? htmlspecialchars(implode(', ', $tables)) : '—' ?>
                                    </div>
                                    <div class="rt-cell rt-cell-status" data-label="Status">
                                        <span class="status-badge status-<?= strtolower($res['status']) ?>">
                                            <?= $statusLabel ?>
                                        </span>
                                    </div>
                                    <div class="rt-cell rt-cell-arrival" data-label="Dolazak">
                                        <?php if ($res['arrived']): ?>
                                            <i class="fa-solid fa-check-circle" style="color: #2ecc71;"></i>
                                        <?php else: ?>
                                            <span class="opacity-25">—</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>

</body>

</html>