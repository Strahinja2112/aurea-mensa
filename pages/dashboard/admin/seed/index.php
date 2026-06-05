<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../autoload.php';

$user = Auth::getUser();

if (!$user || !$user->isAdmin()) {
    http_response_code(403);
    header('Location: ' . Helpers::getRelativePath('pages/auth/login'));
    exit;
}

$success = $_GET['success'] ?? null;
$error = $_GET['error'] ?? null;

$seeds = [
    'roles' => 'Uloge (USER, STAFF, ADMIN)',
    'tables' => 'Restoranski Stolovi',
    'users' => 'Korisnici (Test korisnici)',
    'reservations' => 'Rezervacije (Test rezervacije)',
];

$pageTitle = 'Seed Database';
?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead($pageTitle) ?>

<body>
    <?php include Helpers::getRelativePath('src/components/header.php') ?>

    <main class="pt-5">
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="text-white font-serif">Seed Database</h1>
                <a href="<?= Helpers::getRelativePath('pages/dashboard/admin') ?>" class="btn btn-outline-light">
                    <i class="fa-solid fa-arrow-left me-2"></i> Nazad
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success bg-success bg-opacity-20 border-0 text-white small rounded-3 mb-4">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <?php foreach ($seeds as $key => $label): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="glass-card h-100 p-4">
                            <h5 class="text-white mb-3"><?= htmlspecialchars($label) ?></h5>
                            <p class="text-white opacity-50 small mb-4">
                                Dodaj test podatke u bazu podataka.
                            </p>
                            <form method="POST" action="<?= Helpers::getRelativePath('src/actions/seed.php') ?>">
                                <input type="hidden" name="seed" value="<?= htmlspecialchars($key) ?>">
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="fa-solid fa-seedling me-2"></i> Seed
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="glass-card h-100 p-4">
                        <h5 class="text-white mb-3">Reset All</h5>
                        <p class="text-white opacity-50 small mb-4">
                            Obriši sve podatke i reseeduj sve tablice.
                        </p>
                        <form method="POST" action="<?= Helpers::getRelativePath('src/actions/seed.php') ?>">
                            <input type="hidden" name="seed" value="all">
                            <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Da li ste sigurni? Svi podaci će biti obrisani.');">
                                <i class="fa-solid fa-trash me-2"></i> Reset All
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>

</body>

</html>