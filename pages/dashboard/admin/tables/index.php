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
            db()->restaurantTables->create([
                'data' => [
                    'name' => $_POST['name'],
                    'seats' => (int)$_POST['seats'],
                    'location_description' => $_POST['location'] ?: null,
                    'is_smoking' => isset($_POST['is_smoking']) ? 1 : 0,
                ]
            ]);
            $message = 'Sto je uspešno dodat.';
            $messageType = 'success';
        } elseif ($action === 'edit') {
            db()->restaurantTables->update([
                'where' => ['id' => (int)$_POST['id']],
                'data' => [
                    'name' => $_POST['name'],
                    'seats' => (int)$_POST['seats'],
                    'location_description' => $_POST['location'] ?: null,
                    'is_smoking' => isset($_POST['is_smoking']) ? 1 : 0,
                ]
            ]);
            $message = 'Sto je uspešno izmenjen.';
            $messageType = 'success';
        } elseif ($action === 'delete') {
            db()->restaurantTables->delete([
                'where' => ['id' => (int)$_POST['id']]
            ]);
            $message = 'Sto je obrisan.';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

$tables = db()->restaurantTables->findMany(['orderBy' => ['id' => 'ASC']]);
?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead("Stolovi", ['dashboard.css']) ?>

<body>
    <?php include Helpers::getRelativePath('src/components/header.php') ?>

    <main>
        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="admin-title">Stolovi</h1>
                    <p class="admin-subtitle">Upravljanje stolovima u restoranu</p>
                </div>
                <a href="index.php" class="btn btn-outline-light btn-lg">
                    <i class="fa-solid fa-arrow-left me-2"></i> Nazad
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> bg-<?= $messageType ?> bg-opacity-20 border-0 text-white small rounded-3 mb-4">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="glass-card p-4 mb-5 border-0" style="max-width: 600px;">
                <h5 class="text-white mb-3 font-serif">Dodaj novi sto</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small opacity-50">Naziv stola</label>
                            <input type="text" name="name" class="form-control custom-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small opacity-50">Broj mesta</label>
                            <input type="number" name="seats" class="form-control custom-input" min="1" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small opacity-50">Lokacija</label>
                            <input type="text" name="location" class="form-control custom-input">
                        </div>
                        <div class="col-md-4 d-flex align-items-end pb-2">
                            <div class="form-check">
                                <input type="checkbox" name="is_smoking" id="is_smoking" class="form-check-input">
                                <label for="is_smoking" class="form-check-label text-white small">Pušački deo</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-light w-100 py-2 fw-bold">Dodaj sto</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="glass-card p-4 border-0" style="max-width: 100%;">
                <h5 class="text-white mb-3 font-serif">Postojeći stolovi</h5>

                <?php if (empty($tables)): ?>
                    <div class="text-center py-4">
                        <i class="fa-solid fa-chair fa-2x mb-2 opacity-10"></i>
                        <p class="text-white opacity-25 fw-light mb-0">Nema stolova.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-borderless align-middle mb-0" style="background: transparent;">
                            <thead>
                                <tr class="opacity-50 small text-uppercase" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <th class="py-2 ps-0">Naziv</th>
                                    <th class="py-2">Mesta</th>
                                    <th class="py-2">Lokacija</th>
                                    <th class="py-2">Deo</th>
                                    <th class="py-2 text-end pe-0">Akcije</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tables as $t): ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td class="py-3 ps-0 text-white fw-medium"><?= htmlspecialchars($t['name']) ?></td>
                                        <td class="py-3 text-white"><?= $t['seats'] ?></td>
                                        <td class="py-3 text-white opacity-75"><?= htmlspecialchars($t['location_description'] ?? '—') ?></td>
                                        <td class="py-3">
                                            <?php if ($t['is_smoking']): ?>
                                                <span class="text-warning opacity-75 small">Pušački</span>
                                            <?php else: ?>
                                                <span class="text-success opacity-75 small">Nepušački</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-end pe-0">
                                            <button class="btn btn-sm btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#editModal<?= $t['id'] ?>">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Obrisati sto?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
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

    <?php foreach ($tables as $t): ?>
        <div class="modal fade" id="editModal<?= $t['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content glass-card p-4 border-0">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="text-white font-serif">Izmeni sto</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-0">
                        <form method="POST">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small opacity-50">Naziv stola</label>
                                    <input type="text" name="name" class="form-control custom-input" value="<?= htmlspecialchars($t['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small opacity-50">Broj mesta</label>
                                    <input type="number" name="seats" class="form-control custom-input" min="1" value="<?= $t['seats'] ?>" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small opacity-50">Lokacija</label>
                                    <input type="text" name="location" class="form-control custom-input" value="<?= htmlspecialchars($t['location_description'] ?? '') ?>">
                                </div>
                                <div class="col-md-4 d-flex align-items-end pb-2">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_smoking" id="edit_smoking_<?= $t['id'] ?>" class="form-check-input" <?= $t['is_smoking'] ? 'checked' : '' ?>>
                                        <label for="edit_smoking_<?= $t['id'] ?>" class="form-check-label text-white small">Pušački</label>
                                    </div>
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

        .form-check-input {
            accent-color: #4A6A6A;
        }

        .modal-content.glass-card {
            max-width: 500px;
            margin: 0 auto;
        }
    </style>

</body>

</html>