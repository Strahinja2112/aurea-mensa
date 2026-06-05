<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../../autoload.php';

$user = Auth::getUser();

if (!$user || !$user->isAdmin()) {
    http_response_code(403);
    header('Location: ' . Helpers::getRelativePath('pages/auth/login'));
    exit;
}

$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $existing = db()->users->findFirst(['where' => ['email' => $_POST['email']]]);
        if ($existing) {
            throw new Exception('Korisnik sa ovom email adresom već postoji.');
        }

        $staffRole = db()->roles->findFirst(['where' => ['name' => 'STAFF']]);
        if (!$staffRole) {
            throw new Exception('Uloga STAFF ne postoji. Seedujte prvo roles.');
        }

        if (!Helpers::isStrongPassword($_POST['password'])) {
            throw new Exception('Lozinka mora imati bar 8 karaktera, veliko slovo i broj.');
        }

        db()->users->create([
            'data' => [
                'email' => $_POST['email'],
                'password_hash' => password_hash($_POST['password'], PASSWORD_BCRYPT),
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'phone' => $_POST['phone'] ?: null,
                'role_id' => $staffRole['id'],
                'email_verified' => 1,
            ]
        ]);
        $message = 'Radnik je uspešno dodat.';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

$relativePath = Helpers::getRelativePath('pages/dashboard/admin/');
?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead("Dodaj radnika", ['dashboard.css']) ?>

<body>
    <?php include Helpers::getRelativePath('src/components/header.php') ?>

    <main>
        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="admin-title">Dodaj novog radnika</h1>
                    <p class="admin-subtitle">Kreiraj nalog za osoblje restorana</p>
                </div>
                <a href="../" class="btn btn-outline-light btn-lg">
                    <i class="fa-solid fa-arrow-left me-2"></i> Nazad
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-' . $messageType ?> bg-<?= $messageType ?> bg-opacity-20 border-0 text-white small rounded-3 mb-4" style="<?= $messageType === 'success' ? 'background: rgba(74,106,106,0.2) !important;' : '' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="glass-card p-4 border-0" style="max-width: 600px;">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small opacity-50">Ime</label>
                            <input type="text" name="first_name" class="form-control custom-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small opacity-50">Prezime</label>
                            <input type="text" name="last_name" class="form-control custom-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small opacity-50">Email</label>
                            <input type="email" name="email" class="form-control custom-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small opacity-50">Telefon</label>
                            <input type="text" name="phone" class="form-control custom-input">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small opacity-50">Lozinka</label>
                            <input type="password" name="password" class="form-control custom-input" required minlength="8">
                        </div>
                        <div class="col-md-6 d-flex align-items-end pb-2">
                            <small class="opacity-50">Min. 8 karaktera, 1 veliko slovo, 1 broj</small>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-cta w-100 py-2">Dodaj radnika</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>
</body>

</html>
