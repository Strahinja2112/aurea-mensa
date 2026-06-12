<?php

declare(strict_types=1);

require_once __DIR__ . '/../../autoload.php';

$user = Auth::getUser();
$error = null;
$success = null;

$statusLabels = [
    'PENDING' => 'Na čekanju',
    'REVIEWED' => 'Pregledano',
    'ACCEPTED' => 'Prihvaćeno',
    'REJECTED' => 'Odbijeno',
];

$oldPosition = trim($_POST['position'] ?? '');
$oldAvailability = trim($_POST['availability'] ?? '');
$oldExperience = trim($_POST['experience'] ?? '');
$oldMotivation = trim($_POST['motivation'] ?? '');

if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pendingApplication = db()->jobApplications->findFirst([
            'where' => [
                'AND' => [
                    ['user_id' => $user->id],
                    ['status' => 'PENDING'],
                ],
            ],
        ]);

        if ($pendingApplication) {
            throw new Exception('Već imate prijavu koja čeka pregled.');
        }

        if ($oldPosition === '' || $oldAvailability === '' || $oldExperience === '' || $oldMotivation === '') {
            throw new Exception('Popunite sva obavezna polja.');
        }

        db()->jobApplications->create([
            'data' => [
                'user_id' => $user->id,
                'position' => $oldPosition,
                'availability' => $oldAvailability,
                'experience' => $oldExperience,
                'motivation' => $oldMotivation,
                'status' => 'PENDING',
            ],
        ]);

        $success = 'Prijava je uspešno poslata.';
        $oldPosition = '';
        $oldAvailability = '';
        $oldExperience = '';
        $oldMotivation = '';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$currentPendingApplication = null;

if ($user) {
    $currentPendingApplication = db()->jobApplications->findFirst([
        'where' => [
            'AND' => [
                ['user_id' => $user->id],
                ['status' => 'PENDING'],
            ],
        ],
        'orderBy' => ['created_at' => 'DESC'],
    ]);
}

$relativePath = Helpers::getRelativePath('pages');
?>
<!doctype html>
<html lang="sr">
<?php Helpers::renderHead("Posao", ['auth.css', 'dashboard.css', 'table.css']) ?>

<body>
    <?php include Helpers::getRelativePath('src/components/header.php') ?>

    <main>
        <div class="container-fluid container-lg row justify-content-center g-4 w-100">
            <div class="col-12 col-lg-5">
                <div class="glass-card h-100" style="max-width: 1000px !important;">
                    <p class="small opacity-50 text-uppercase mb-2" style="letter-spacing: 1px;">Karijera</p>
                    <h1 class="admin-title mb-3" style="font-size: 42px;">Rad u Aurea Mensa</h1>
                    <p class="admin-subtitle mb-4">
                        Tražimo odgovorne ljude za rad u sali, kuhinji i podršci restorana. Pošaljite prijavu sa dostupnošću,
                        prethodnim iskustvom i kratkim obrazloženjem zašto želite da radite sa nama.
                    </p>
                    <div class="d-flex flex-column gap-3 text-white-50">
                        <div><i class="fa-solid fa-utensils me-2"></i> Pozicije u sali i kuhinji</div>
                        <div><i class="fa-solid fa-clock me-2"></i> Smene po dogovoru</div>
                        <div><i class="fa-solid fa-user-check me-2"></i> Pregled prijava u admin panelu</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="glass-card h-100 w-100" style="max-width: 1000px !important;">
                    <h2 class="text-center mb-4">Prijava za posao</h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mb-4 text-center">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success bg-success bg-opacity-20 border-0 text-white small rounded-3 mb-4 text-center">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$user): ?>
                        <div class="text-center py-4">
                            <i class="fa-solid fa-right-to-bracket fa-3x mb-3 opacity-25"></i>
                            <p class="text-white opacity-75 mb-4">
                                Prijavite se ili napravite nalog da biste poslali prijavu za posao.
                            </p>
                            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                                <a href="<?= $relativePath . '/auth/login' ?>" class="btn btn-light btn-lg">
                                    <i class="fa-solid fa-right-to-bracket me-2"></i> Login
                                </a>
                                <a href="<?= $relativePath . '/auth/register' ?>" class="btn btn-outline-light btn-lg">
                                    <i class="fa-solid fa-user-plus me-2"></i> Registracija
                                </a>
                            </div>
                        </div>
                    <?php elseif ($currentPendingApplication): ?>
                        <div class="text-center py-4">
                            <i class="fa-solid fa-hourglass-half fa-3x mb-3 opacity-25"></i>
                            <h3 class="text-white fw-light mb-3">Prijava je poslata</h3>
                            <p class="text-white opacity-50 mb-4">
                                Vaša prijava za poziciju
                                <strong><?= htmlspecialchars($currentPendingApplication['position']) ?></strong>
                                je trenutno: <?= htmlspecialchars($statusLabels[$currentPendingApplication['status']] ?? $currentPendingApplication['status']) ?>.
                            </p>
                            <span class="status-badge status-pending">
                                <?= htmlspecialchars($statusLabels[$currentPendingApplication['status']] ?? $currentPendingApplication['status']) ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="form-group mb-3">
                                <label class="form-label small opacity-50">Pozicija</label>
                                <input type="text" name="position" class="form-control custom-input"
                                    value="<?= htmlspecialchars($oldPosition) ?>" placeholder="Konobar, kuvar, pomoćni radnik..." required>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label small opacity-50">Dostupnost</label>
                                <textarea name="availability" class="form-control custom-input" rows="3" required><?= htmlspecialchars($oldAvailability) ?></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label small opacity-50">Iskustvo</label>
                                <textarea name="experience" class="form-control custom-input" rows="4" required><?= htmlspecialchars($oldExperience) ?></textarea>
                            </div>

                            <div class="form-group mb-4">
                                <label class="form-label small opacity-50">Motivacija</label>
                                <textarea name="motivation" class="form-control custom-input" rows="4" required><?= htmlspecialchars($oldMotivation) ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-light btn-lg w-100">
                                <i class="fa-solid fa-paper-plane me-2"></i> Pošalji prijavu
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>
</body>

</html>
