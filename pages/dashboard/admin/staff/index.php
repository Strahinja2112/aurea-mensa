<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../autoload.php';

$user = Auth::getUser();

if (!$user || !$user->isAdmin()) {
    http_response_code(403);
    header('Location: ' . Helpers::getRelativePath('pages/auth/login'));
    exit;
}

$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
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
        } elseif ($action === 'edit') {
            $updateData = [
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'phone' => $_POST['phone'] ?: null,
            ];

            if (!empty($_POST['password'])) {
                if (!Helpers::isStrongPassword($_POST['password'])) {
                    throw new Exception('Lozinka mora imati bar 8 karaktera, veliko slovo i broj.');
                }
                $updateData['password_hash'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
            }

            db()->users->update([
                'where' => ['id' => (int)$_POST['id']],
                'data' => $updateData,
            ]);
            $message = 'Radnik je uspešno izmenjen.';
            $messageType = 'success';
        } elseif ($action === 'delete') {
            $target = db()->users->findFirst(['where' => ['id' => (int)$_POST['id']]]);
            if (!$target) {
                throw new Exception('Korisnik ne postoji.');
            }
            if ((int)$target['id'] === $user->id) {
                throw new Exception('Ne možete obrisati sami sebe.');
            }

            db()->users->delete(['where' => ['id' => $target['id']]]);
            $message = 'Radnik je obrisan.';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

$staffMembers = db()->users->findMany([
    'where' => ['role_id' => 2],
    'orderBy' => ['id' => 'ASC'],
]);

$relativePath = Helpers::getRelativePath('pages/dashboard/admin/');
?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead("Radnici", ['dashboard.css']) ?>

<body>
    <?php include Helpers::getRelativePath('src/components/header.php') ?>

    <main>
        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="admin-title">Radnici</h1>
                    <p class="admin-subtitle">Upravljanje nalozima osoblja restorana</p>
                </div>
                <a href="<?= $relativePath . '/dashboard/admin' ?>" class="btn btn-outline-light btn-lg">
                    <i class="fa-solid fa-arrow-left me-2"></i> Nazad
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> bg-<?= $messageType ?> bg-opacity-20 border-0 text-white small rounded-3 mb-4">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="glass-card p-4 mb-5 border-0" style="max-width: 600px;">
                <h5 class="text-white mb-3 font-serif">Dodaj novog radnika</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
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
                            <button type="submit" class="btn btn-light w-100 py-2 fw-bold">Dodaj radnika</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="glass-card p-4 border-0" style="max-width: 100%;">
                <h5 class="text-white mb-3 font-serif">Osoblje restorana</h5>

                <?php if (empty($staffMembers)): ?>
                    <div class="text-center py-4">
                        <i class="fa-solid fa-users-slash fa-2x mb-2 opacity-10"></i>
                        <p class="text-white opacity-25 fw-light mb-0">Nema radnika.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-borderless align-middle mb-0" style="background: transparent;">
                            <thead>
                                <tr class="opacity-50 small text-uppercase" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <th class="py-2 ps-0">Ime i prezime</th>
                                    <th class="py-2">Email</th>
                                    <th class="py-2">Telefon</th>
                                    <th class="py-2 text-end pe-0">Akcije</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staffMembers as $s): ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td class="py-3 ps-0 text-white fw-medium">
                                            <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                                        </td>
                                        <td class="py-3 text-white opacity-75"><?= htmlspecialchars($s['email']) ?></td>
                                        <td class="py-3 text-white opacity-75"><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                                        <td class="py-3 text-end pe-0">
                                            <button class="btn btn-sm btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= $s['id'] ?>">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <?php if ((int)$s['id'] !== $user->id): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Obrisati radnika?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php foreach ($staffMembers as $s): ?>
        <div class="modal fade" id="editStaffModal<?= $s['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content glass-card p-4 border-0">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="text-white font-serif">Izmeni radnika</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-0">
                        <form method="POST">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small opacity-50">Ime</label>
                                    <input type="text" name="first_name" class="form-control custom-input" value="<?= htmlspecialchars($s['first_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small opacity-50">Prezime</label>
                                    <input type="text" name="last_name" class="form-control custom-input" value="<?= htmlspecialchars($s['last_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small opacity-50">Telefon</label>
                                    <input type="text" name="phone" class="form-control custom-input" value="<?= htmlspecialchars($s['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small opacity-50">Nova lozinka (ostavi prazno ako ne menjaš)</label>
                                    <input type="password" name="password" class="form-control custom-input" minlength="8">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-light w-100 py-2 fw-bold">Sačuvaj izmene</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>
</body>

</html>