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
    'staff' => db()->users->count(['where' => ['role_id' => 2]]),
    'users' => db()->users->count(['where' => ['role_id' => 1]]),
    'today_active' => db()->reservations->count([
        'where' => ['reservation_date' => $today, 'status' => 'ACTIVE']
    ]),
    'today_completed' => db()->reservations->count([
        'where' => ['reservation_date' => $today, 'status' => 'COMPLETED']
    ]),
    'today_cancelled' => db()->reservations->count([
        'where' => ['reservation_date' => $today, 'status' => 'CANCELLED']
    ]),
    'total_reservations' => db()->reservations->count(),
];
?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead("Admin Dashboard", ['dashboard.css']) ?>

<body>
<?php include Helpers::getRelativePath('src/components/header.php') ?>

<main>
    <div class="dashboard-container">
        <div class="admin-header text-center mb-5">
            <h1 class="admin-title">Admin Panel</h1>
            <p class="admin-subtitle">Dobrodošli, <?= htmlspecialchars($user->getFullName()) ?></p>
        </div>

        <div class="row g-3 mb-5">
            <div class="col-6 col-md-4 col-lg">
                <div class="glass-card text-center py-3 px-2 border-0 h-100">
                    <div class="h3 text-white fw-light mb-0"><?= $stats['tables'] ?></div>
                    <small class="opacity-50 text-uppercase ls-1">Stolova</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="glass-card text-center py-3 px-2 border-0 h-100">
                    <div class="h3 text-white fw-light mb-0"><?= $stats['staff'] ?></div>
                    <small class="opacity-50 text-uppercase ls-1">Radnika</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="glass-card text-center py-3 px-2 border-0 h-100">
                    <div class="h3 text-white fw-light mb-0"><?= $stats['users'] ?></div>
                    <small class="opacity-50 text-uppercase ls-1">Korisnika</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="glass-card text-center py-3 px-2 border-0 h-100">
                    <div class="h3 text-white fw-light mb-0 text-success"><?= $stats['today_active'] ?></div>
                    <small class="opacity-50 text-uppercase ls-1">Aktivnih</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="glass-card text-center py-3 px-2 border-0 h-100">
                    <div class="h3 text-white fw-light mb-0 text-info"><?= $stats['today_completed'] ?></div>
                    <small class="opacity-50 text-uppercase ls-1">Re&shy;alizov.</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="glass-card text-center py-3 px-2 border-0 h-100">
                    <div class="h3 text-white fw-light mb-0 text-danger opacity-75"><?= $stats['today_cancelled'] ?></div>
                    <small class="opacity-50 text-uppercase ls-1">Otkazano</small>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="glass-card text-center py-3 px-2 border-0 h-100">
                    <div class="h3 text-white fw-light mb-0"><?= $stats['total_reservations'] ?></div>
                    <small class="opacity-50 text-uppercase ls-1">Ukupno rez.</small>
                </div>
            </div>
        </div>

        <hr class="border-white border-opacity-10 mb-5">

        <div class="row g-4 mb-5">
            <div class="col-6 col-lg-3">
                <a href="tables.php" class="btn btn-admin-nav w-100 p-4 d-block">
                    <i class="fa-solid fa-chair fa-xl d-block mb-2"></i>
                    <span class="fw-semibold d-block">Stolovi</span>
                    <span class="small opacity-50 d-block">Dodaj, izmeni, obriši</span>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="reservations.php" class="btn btn-admin-nav w-100 p-4 d-block">
                    <i class="fa-solid fa-calendar-check fa-xl d-block mb-2"></i>
                    <span class="fw-semibold d-block">Rezervacije</span>
                    <span class="small opacity-50 d-block">Pregled po datumu</span>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="staff.php" class="btn btn-admin-nav w-100 p-4 d-block">
                    <i class="fa-solid fa-users-gear fa-xl d-block mb-2"></i>
                    <span class="fw-semibold d-block">Radnici</span>
                    <span class="small opacity-50 d-block">Upravljanje nalozima</span>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="seed/" class="btn btn-admin-nav w-100 p-4 d-block">
                    <i class="fa-solid fa-database fa-xl d-block mb-2"></i>
                    <span class="fw-semibold d-block">Seed</span>
                    <span class="small opacity-50 d-block">Test podaci</span>
                </a>
            </div>
        </div>
    </div>
</main>

<?php include Helpers::getRelativePath('src/components/footer.php') ?>

<style>
    .admin-header {
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .admin-title {
        font-size: 52px;
        line-height: 1;
        font-family: serif;
        font-weight: 300;
        color: #f8f9fa;
        letter-spacing: 1px;
        margin-bottom: 12px;
    }

    .admin-subtitle {
        color: rgba(255, 255, 255, 0.5);
        font-size: 1.2rem;
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

    .glass-card {
        max-width: none;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
