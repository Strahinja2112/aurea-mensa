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

if (isset($_GET['added'])) {
    $message = 'Radnik je uspešno dodat.';
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'edit') {
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

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$staffWhere = ['role_id' => 2];

if ($search !== '') {
    $staffWhere = [
        'AND' => [
            ['role_id' => 2],
            [
                'OR' => [
                    ['email' => ['contains' => $search]],
                    ['first_name' => ['contains' => $search]],
                    ['last_name' => ['contains' => $search]],
                    ['phone' => ['contains' => $search]],
                ]
            ]
        ]
    ];
}

$total = db()->users->count(['where' => $staffWhere]);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);

$staffMembers = db()->users->findMany([
    'where' => $staffWhere,
    'orderBy' => ['id' => 'ASC'],
    'take' => $perPage,
    'skip' => ($page - 1) * $perPage,
]);

$relativePath = Helpers::getRelativePath('pages/dashboard/admin/');
?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead("Radnici", ['dashboard.css', 'table.css']) ?>

<body>
    <?php include Helpers::getRelativePath('src/components/header.php') ?>

    <main>
        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="admin-title">Radnici</h1>
                    <p class="admin-subtitle">Upravljanje nalozima osoblja restorana</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= $relativePath . '/dashboard/admin' ?>" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-arrow-left me-2"></i> Nazad
                    </a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-' . $messageType ?> bg-<?= $messageType ?> bg-opacity-20 border-0 text-white small rounded-3 mb-4" style="<?= $messageType === 'success' ? 'background: rgba(74,106,106,0.2) !important;' : '' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="glass-card p-4 border-0" style="max-width: 100%;">
                <form method="GET" class="mb-4 pb-4" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                    <div class="d-flex align-items-end gap-2">
                        <div style="flex: 1; min-width: 0;">
                            <label class="form-label small opacity-50 mb-1">Pretraga (ime, email, telefon)</label>
                            <input type="text" name="search" class="form-control custom-input" value="<?= htmlspecialchars($search) ?>" placeholder="Pretraži radnike..." style="padding-top: 6px; padding-bottom: 6px;">
                        </div>
                        <button type="submit" class="btn btn-cta flex-shrink-0" style="padding-top: 6px; padding-bottom: 6px;">Pretraži</button>
                        <?php if ($search !== ''): ?>
                            <a href="?" class="btn btn-outline-light rounded-circle p-0 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; min-width: 38px;" title="Poništi pretragu">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <span class="opacity-50">
                        Ukupno: <?= $total ?> radnika
                        <?php if ($totalPages > 1): ?>
                            (strana <?= $page ?> od <?= $totalPages ?>)
                        <?php endif; ?>
                    </span>
                </div>

                <?php if (empty($staffMembers)): ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-users-slash fa-3x mb-3 opacity-10"></i>
                        <p class="text-white opacity-25 fw-light mb-0">Nema radnika.</p>
                    </div>
                <?php else: ?>
                    <div class="reservations-table rt-table-staff">
                        <div class="rt-head">
                            <div class="rt-row">
                                <div class="rt-cell">Ime i prezime</div>
                                <div class="rt-cell">Email</div>
                                <div class="rt-cell">Telefon</div>
                                <div class="rt-cell">Akcije</div>
                            </div>
                        </div>
                        <div class="rt-body">
                            <?php foreach ($staffMembers as $s): ?>
                                <div class="rt-row">
                                    <div class="rt-cell" data-label="Ime i prezime">
                                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                                    </div>
                                    <div class="rt-cell" data-label="Email">
                                        <?= htmlspecialchars($s['email']) ?>
                                    </div>
                                    <div class="rt-cell" data-label="Telefon">
                                        <?= htmlspecialchars($s['phone'] ?? '—') ?>
                                    </div>
                                    <div class="rt-cell" data-label="Akcije">
                                        <button class="btn btn-sm btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= $s['id'] ?>" title="Izmeni">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <?php if ((int)$s['id'] !== $user->id): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Obrisati radnika?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Obriši">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-wrap">
                            <?php if ($page > 1): ?>
                                <a href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" class="page-btn">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"
                                    class="page-btn <?= $i === $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" class="page-btn">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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