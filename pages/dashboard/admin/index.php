<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../autoload.php';

$user = Auth::getUser();

if (!$user) {
    header('Location: ' . Helpers::getRelativePath('pages/auth/login'));
    exit;
}

if (!$user->isAdmin()) {
    Helpers::redirectToError(ErrorType::FORBIDDEN);
    exit;
}

?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead("Admin Dashboard", ['dashboard.css']) ?>

<body>
    <?php include Helpers::getRelativePath('src/components/header.php') ?>

    <main>
        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div class="admin-header text-center">
                    <h1 class="admin-title">Admin Panel</h1>
                    <p class="admin-subtitle">Dobrodošli, <?= htmlspecialchars($user->getFullName()) ?></p>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-6 col-lg-3">
                    <a href="tables/" class="text-decoration-none d-block h-100">
                        <div class="glass-card text-center p-4 border-0 admin-card h-100">
                            <i class="fa-solid fa-chair fa-3x d-block mb-3 admin-card-icon"></i>
                            <span class="fw-semibold d-block text-white fs-5">Stolovi</span>
                            <span class="small opacity-50 d-block text-white">Dodaj, izmeni, obriši</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="reservations/" class="text-decoration-none d-block h-100">
                        <div class="glass-card text-center p-4 border-0 admin-card h-100">
                            <i class="fa-solid fa-calendar-check fa-3x d-block mb-3 admin-card-icon"></i>
                            <span class="fw-semibold d-block text-white fs-5">Rezervacije</span>
                            <span class="small opacity-50 d-block text-white">Pregled po datumu</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="staff/" class="text-decoration-none d-block h-100">
                        <div class="glass-card text-center p-4 border-0 admin-card h-100">
                            <i class="fa-solid fa-users-gear fa-3x d-block mb-3 admin-card-icon"></i>
                            <span class="fw-semibold d-block text-white fs-5">Radnici</span>
                            <span class="small opacity-50 d-block text-white">Upravljanje nalozima</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-lg-3">
                    <a href="seed/" class="text-decoration-none d-block h-100">
                        <div class="glass-card text-center p-4 border-0 admin-card h-100">
                            <i class="fa-solid fa-database fa-3x d-block mb-3 admin-card-icon"></i>
                            <span class="fw-semibold d-block text-white fs-5">Seed</span>
                            <span class="small opacity-50 d-block text-white">Test podaci</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>

</body>

</html>