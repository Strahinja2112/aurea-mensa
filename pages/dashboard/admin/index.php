<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../autoload.php';

$user = Auth::getUser();

if (!$user || !$user->isAdmin()) {
    http_response_code(403);
    header('Location: ' . Helpers::getRelativePath('pages/auth/login'));
    exit;
}

$today = date('Y-m-d');

$stats = [
    'tables' => db()->restaurantTables->count(),
    'today_active' => db()->reservations->count([
        'where' => [
            'reservation_date' => $today,
            'status' => 'ACTIVE',
        ]
    ]),
    'today_completed' => db()->reservations->count([
        'where' => [
            'reservation_date' => $today,
            'status' => 'COMPLETED',
        ]
    ]),
    'staff' => db()->users->count([
        'where' => ['role_id' => 2]
    ]),
    'users' => db()->users->count([
        'where' => ['role_id' => 1]
    ]),
];

$recentReservations = db()->reservations->findMany([
    'orderBy' => ['created_at' => 'DESC'],
    'take' => 5,
]);

$pageTitle = 'Admin Dashboard';
?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead($pageTitle, ['dashboard.css']) ?>

<body>
<?php include Helpers::getRelativePath('src/components/header.php') ?>

<main>
    <div class="dashboard-container">
        <div class="page-header">
            <h2 class="mb-1">Admin Dashboard</h2>
            <p class="page-subtitle">Dobrodošli, <?= htmlspecialchars($user->getFullName()) ?></p>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-6 col-lg-3">
                <a href="<?= Helpers::getRelativePath('pages/dashboard/admin/tables.php') ?>" class="text-decoration-none d-block h-100">
                    <div class="glass-card text-center p-4 h-100 stat-card">
                        <i class="fa-solid fa-chair fa-2x mb-3 stat-icon"></i>
                        <div class="display-4 text-white fw-light"><?= $stats['tables'] ?></div>
                        <div class="small text-white opacity-50 text-uppercase ls-1">Stolova</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="<?= Helpers::getRelativePath('pages/dashboard/admin/reservations.php') ?>" class="text-decoration-none d-block h-100">
                    <div class="glass-card text-center p-4 h-100 stat-card">
                        <i class="fa-solid fa-clock fa-2x mb-3 stat-icon"></i>
                        <div class="display-4 text-white fw-light"><?= $stats['today_active'] ?></div>
                        <div class="small text-white opacity-50 text-uppercase ls-1">Aktivnih</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="<?= Helpers::getRelativePath('pages/dashboard/admin/reservations.php') ?>" class="text-decoration-none d-block h-100">
                    <div class="glass-card text-center p-4 h-100 stat-card">
                        <i class="fa-solid fa-check fa-2x mb-3 stat-icon"></i>
                        <div class="display-4 text-white fw-light"><?= $stats['today_completed'] ?></div>
                        <div class="small text-white opacity-50 text-uppercase ls-1">Realizovanih</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="<?= Helpers::getRelativePath('pages/dashboard/admin/staff.php') ?>" class="text-decoration-none d-block h-100">
                    <div class="glass-card text-center p-4 h-100 stat-card">
                        <i class="fa-solid fa-user-tie fa-2x mb-3 stat-icon"></i>
                        <div class="display-4 text-white fw-light"><?= $stats['staff'] ?></div>
                        <div class="small text-white opacity-50 text-uppercase ls-1">Radnika</div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-12 col-md-6 col-lg-3">
                <a href="<?= Helpers::getRelativePath('pages/dashboard/admin/tables.php') ?>" class="btn btn-admin-nav w-100 p-4">
                    <i class="fa-solid fa-chair fa-xl d-block mb-2"></i>
                    <span class="fw-semibold d-block">Stolovi</span>
                    <span class="small opacity-50 d-block">Dodaj, izmeni, obriši</span>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="<?= Helpers::getRelativePath('pages/dashboard/admin/reservations.php') ?>" class="btn btn-admin-nav w-100 p-4">
                    <i class="fa-solid fa-calendar-check fa-xl d-block mb-2"></i>
                    <span class="fw-semibold d-block">Rezervacije</span>
                    <span class="small opacity-50 d-block">Pregled po datumu</span>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="<?= Helpers::getRelativePath('pages/dashboard/admin/staff.php') ?>" class="btn btn-admin-nav w-100 p-4">
                    <i class="fa-solid fa-users-gear fa-xl d-block mb-2"></i>
                    <span class="fw-semibold d-block">Radnici</span>
                    <span class="small opacity-50 d-block">Upravljanje nalozima</span>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="<?= Helpers::getRelativePath('pages/dashboard/admin/seed/') ?>" class="btn btn-admin-nav w-100 p-4">
                    <i class="fa-solid fa-database fa-xl d-block mb-2"></i>
                    <span class="fw-semibold d-block">Seed</span>
                    <span class="small opacity-50 d-block">Test podaci</span>
                </a>
            </div>
        </div>

        <div class="glass-card p-4 w-100" style="max-width: 100%;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-white mb-0">Poslednje rezervacije</h5>
                <a href="<?= Helpers::getRelativePath('pages/dashboard/admin/reservations.php') ?>" class="small" style="color: #4A6A6A;">Pregled svih</a>
            </div>

            <?php if (empty($recentReservations)): ?>
                <div class="text-center py-4">
                    <i class="fa-solid fa-calendar-xmark fa-2x mb-2 opacity-10"></i>
                    <p class="text-white opacity-25 fw-light mb-0">Nema rezervacija.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-borderless align-middle mb-0" style="background: transparent;">
                        <thead>
                            <tr class="opacity-50 small text-uppercase" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <th class="py-2 ps-0">Kod</th>
                                <th class="py-2">Datum</th>
                                <th class="py-2">Vreme</th>
                                <th class="py-2">Status</th>
                                <th class="py-2 text-end pe-0">Dolazak</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentReservations as $res): ?>
                                <?php
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
                                $arrivedLabel = $res['arrived'] ? 'Prijavljen' : '—';
                                $arrivedClass = $res['arrived'] ? 'text-success' : 'opacity-25';
                                ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td class="py-3 ps-0"><code class="text-white"><?= htmlspecialchars($res['reservation_code']) ?></code></td>
                                    <td class="py-3 text-white"><?= htmlspecialchars($res['reservation_date']) ?></td>
                                    <td class="py-3 text-white"><?= substr($res['start_time'], 0, 5) ?> - <?= substr($res['end_time'], 0, 5) ?></td>
                                    <td class="py-3 <?= $statusClass ?> fw-medium"><?= $statusLabel ?></td>
                                    <td class="py-3 text-end pe-0 <?= $arrivedClass ?>"><?= $arrivedLabel ?></td>
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
    .stat-card {
        transition: all 0.3s ease;
        cursor: pointer;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .stat-card:hover {
        background: rgba(74, 106, 106, 0.15) !important;
        border-color: rgba(74, 106, 106, 0.4) !important;
        transform: translateY(-3px);
    }

    .stat-icon {
        color: #4A6A6A;
        transition: color 0.3s ease;
    }

    .stat-card:hover .stat-icon {
        color: #5A7A7A;
    }

    .btn-admin-nav {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        color: #E6E6E6;
        transition: all 0.3s ease;
        text-align: center;
    }

    .btn-admin-nav:hover {
        background: rgba(74, 106, 106, 0.15);
        border-color: rgba(74, 106, 106, 0.4);
        color: #E6E6E6;
        transform: translateY(-3px);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>