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
$activeTab = ($_GET['tab'] ?? 'staff') === 'applications' ? 'applications' : 'staff';

$applicationStatuses = ['PENDING', 'REVIEWED', 'ACCEPTED', 'REJECTED'];
$reviewableStatuses = ['REVIEWED', 'ACCEPTED', 'REJECTED'];
$statusLabels = [
    'PENDING' => 'Na čekanju',
    'REVIEWED' => 'Pregledano',
    'ACCEPTED' => 'Prihvaćeno',
    'REJECTED' => 'Odbijeno',
];

if (isset($_GET['added'])) {
    $message = 'Radnik je uspešno dodat.';
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'edit') {
            $activeTab = 'staff';
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
            $activeTab = 'staff';
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
        } elseif ($action === 'update_application_status') {
            $activeTab = 'applications';
            $applicationId = (int)($_POST['id'] ?? 0);
            $newStatus = $_POST['status'] ?? '';

            if (!in_array($newStatus, $reviewableStatuses, true)) {
                throw new Exception('Izaberite važeći status prijave.');
            }

            $applicationStatement = db()->pdo()->prepare("
                SELECT
                    ja.id,
                    ja.status,
                    applicant.email AS applicant_email,
                    applicant.first_name AS applicant_first_name
                FROM job_applications ja
                INNER JOIN users applicant ON applicant.id = ja.user_id
                WHERE ja.id = :id
                LIMIT 1
            ");
            $applicationStatement->execute(['id' => $applicationId]);
            $application = $applicationStatement->fetch();

            if (!$application) {
                throw new Exception('Prijava ne postoji.');
            }

            if (($application['status'] ?? '') === $newStatus) {
                $message = 'Izabrani status je već postavljen.';
                $messageType = 'warning';
            } else {
                $updated = db()->jobApplications->update([
                    'where' => ['id' => $applicationId],
                    'data' => [
                        'status' => $newStatus,
                        'reviewed_at' => date('Y-m-d H:i:s'),
                        'reviewed_by' => $user->id,
                    ],
                ]);

                if (!$updated) {
                    throw new Exception('Prijava ne postoji.');
                }

                $mailSent = Mailer::sendJobApplicationStatusEmail(
                    $application['applicant_email'],
                    $newStatus,
                    $application['applicant_first_name'] ?? ''
                );

                if ($mailSent) {
                    $message = 'Status prijave je uspešno izmenjen i kandidat je obavešten mejlom.';
                    $messageType = 'success';
                } else {
                    $message = 'Status prijave je izmenjen, ali slanje mejla nije uspelo.';
                    $messageType = 'warning';
                }
            }
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

$applicationStatus = $_GET['app_status'] ?? 'ALL';
if ($applicationStatus !== 'ALL' && !in_array($applicationStatus, $applicationStatuses, true)) {
    $applicationStatus = 'ALL';
}

$applicationPage = max(1, (int)($_GET['app_page'] ?? 1));
$applicationSearch = trim($_GET['app_search'] ?? '');
$applicationPerPage = 8;
$applicationWhereParts = [];
$applicationParams = [];

if ($applicationStatus !== 'ALL') {
    $applicationWhereParts[] = 'ja.status = :status';
    $applicationParams['status'] = $applicationStatus;
}

if ($applicationSearch !== '') {
    $applicationWhereParts[] = "(
        applicant.email LIKE :app_search_email
        OR applicant.first_name LIKE :app_search_first_name
        OR applicant.last_name LIKE :app_search_last_name
        OR applicant.phone LIKE :app_search_phone
        OR ja.availability LIKE :app_search_availability
        OR ja.experience LIKE :app_search_experience
        OR ja.motivation LIKE :app_search_motivation
    )";
    $applicationSearchParam = '%' . $applicationSearch . '%';
    $applicationParams['app_search_email'] = $applicationSearchParam;
    $applicationParams['app_search_first_name'] = $applicationSearchParam;
    $applicationParams['app_search_last_name'] = $applicationSearchParam;
    $applicationParams['app_search_phone'] = $applicationSearchParam;
    $applicationParams['app_search_availability'] = $applicationSearchParam;
    $applicationParams['app_search_experience'] = $applicationSearchParam;
    $applicationParams['app_search_motivation'] = $applicationSearchParam;
}

$applicationWhereSql = $applicationWhereParts === [] ? '' : ' WHERE ' . implode(' AND ', $applicationWhereParts);

$countStatement = db()->pdo()->prepare("
    SELECT COUNT(*) AS total
    FROM job_applications ja
    INNER JOIN users applicant ON applicant.id = ja.user_id
    {$applicationWhereSql}
");
$countStatement->execute($applicationParams);
$applicationTotal = (int)($countStatement->fetch()['total'] ?? 0);
$applicationTotalPages = max(1, (int)ceil($applicationTotal / $applicationPerPage));
$applicationPage = min($applicationPage, $applicationTotalPages);
$applicationOffset = ($applicationPage - 1) * $applicationPerPage;

$applicationSql = "
    SELECT
        ja.*,
        applicant.email AS applicant_email,
        applicant.first_name AS applicant_first_name,
        applicant.last_name AS applicant_last_name,
        applicant.phone AS applicant_phone,
        reviewer.first_name AS reviewer_first_name,
        reviewer.last_name AS reviewer_last_name
    FROM job_applications ja
    INNER JOIN users applicant ON applicant.id = ja.user_id
    LEFT JOIN users reviewer ON reviewer.id = ja.reviewed_by
    {$applicationWhereSql}
    ORDER BY ja.created_at DESC, ja.id DESC
    LIMIT {$applicationPerPage} OFFSET {$applicationOffset}
";
$applicationStatement = db()->pdo()->prepare($applicationSql);
$applicationStatement->execute($applicationParams);
$applications = $applicationStatement->fetchAll();

$adminPath = Helpers::getRelativePath('pages/dashboard/admin');
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
                    <p class="admin-subtitle">Upravljanje osobljem i prijavama za posao</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= $adminPath ?>" class="btn btn-outline-light btn-lg">
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
                <div class="filter-buttons mb-4 pb-4" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                    <a href="?tab=staff" class="btn btn-filter <?= $activeTab === 'staff' ? 'active' : '' ?>">
                        <i class="fa-solid fa-users"></i> Radnici
                    </a>
                    <a href="?tab=applications" class="btn btn-filter <?= $activeTab === 'applications' ? 'active' : '' ?>">
                        <i class="fa-solid fa-briefcase"></i> Prijave
                    </a>
                </div>

                <?php if ($activeTab === 'staff'): ?>
                    <form method="GET" class="mb-4 pb-4" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                        <input type="hidden" name="tab" value="staff">
                        <div class="d-flex align-items-end gap-2">
                            <div style="flex: 1; min-width: 0;">
                                <label class="form-label small opacity-50 mb-1">Pretraga (ime, email, telefon)</label>
                                <input type="text" name="search" class="form-control custom-input" value="<?= htmlspecialchars($search) ?>" placeholder="Pretraži radnike..." style="padding-top: 6px; padding-bottom: 6px;">
                            </div>
                            <button type="submit" class="btn btn-cta flex-shrink-0" style="padding-top: 6px; padding-bottom: 6px;">Pretraži</button>
                            <?php if ($search !== ''): ?>
                                <a href="?tab=staff" class="btn btn-outline-light rounded-circle p-0 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; min-width: 38px;" title="Poništi pretragu">
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
                                    <a href="?tab=staff&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" class="page-btn">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="?tab=staff&search=<?= urlencode($search) ?>&page=<?= $i ?>"
                                        class="page-btn <?= $i === $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?tab=staff&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" class="page-btn">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="filter-buttons mb-4 pb-4" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                        <a href="?tab=applications&app_status=ALL&app_search=<?= urlencode($applicationSearch) ?>" class="btn btn-filter <?= $applicationStatus === 'ALL' ? 'active' : '' ?>">
                            <i class="fa-solid fa-list"></i> Sve
                        </a>
                        <?php foreach ($applicationStatuses as $status): ?>
                            <a href="?tab=applications&app_status=<?= urlencode($status) ?>&app_search=<?= urlencode($applicationSearch) ?>" class="btn btn-filter <?= $applicationStatus === $status ? 'active' : '' ?>">
                                <i class="fa-solid <?= match ($status) {
                                    'PENDING' => 'fa-clock',
                                    'REVIEWED' => 'fa-eye',
                                    'ACCEPTED' => 'fa-check',
                                    'REJECTED' => 'fa-xmark',
                                    default => 'fa-circle',
                                } ?>"></i>
                                <?= htmlspecialchars($statusLabels[$status]) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <form method="GET" class="mb-4 pb-4" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                        <input type="hidden" name="tab" value="applications">
                        <input type="hidden" name="app_status" value="<?= htmlspecialchars($applicationStatus) ?>">
                        <div class="d-flex align-items-end gap-2">
                            <div style="flex: 1; min-width: 0;">
                                <label class="form-label small opacity-50 mb-1">Pretraga (ime, email, telefon, odgovori)</label>
                                <input type="text" name="app_search" class="form-control custom-input" value="<?= htmlspecialchars($applicationSearch) ?>" placeholder="Pretraži prijave..." style="padding-top: 6px; padding-bottom: 6px;">
                            </div>
                            <button type="submit" class="btn btn-cta flex-shrink-0" style="padding-top: 6px; padding-bottom: 6px;">Pretraži</button>
                            <?php if ($applicationSearch !== ''): ?>
                                <a href="?tab=applications&app_status=<?= urlencode($applicationStatus) ?>" class="btn btn-outline-light rounded-circle p-0 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; min-width: 38px;" title="Poništi pretragu">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <span class="opacity-50">
                            Ukupno: <?= $applicationTotal ?> prijava
                            <?php if ($applicationSearch !== ''): ?>
                                za pretragu "<?= htmlspecialchars($applicationSearch) ?>"
                            <?php endif; ?>
                            <?php if ($applicationTotalPages > 1): ?>
                                (strana <?= $applicationPage ?> od <?= $applicationTotalPages ?>)
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if (empty($applications)): ?>
                        <div class="text-center py-5">
                            <i class="fa-solid fa-folder-open fa-3x mb-3 opacity-10"></i>
                            <p class="text-white opacity-25 fw-light mb-0">Nema prijava za izabrani filter.</p>
                        </div>
                    <?php else: ?>
                        <div class="reservations-table rt-table-applications">
                            <div class="rt-head">
                                <div class="rt-row">
                                    <div class="rt-cell">Kandidat</div>
                                    <div class="rt-cell">Kontakt</div>
                                    <div class="rt-cell">Datum</div>
                                    <div class="rt-cell">Status</div>
                                    <div class="rt-cell">Akcija</div>
                                </div>
                            </div>
                            <div class="rt-body">
                                <?php foreach ($applications as $application):
                                    $applicantName = trim(($application['applicant_first_name'] ?? '') . ' ' . ($application['applicant_last_name'] ?? ''));
                                    $applicantName = $applicantName !== '' ? $applicantName : $application['applicant_email'];
                                    $reviewerName = trim(($application['reviewer_first_name'] ?? '') . ' ' . ($application['reviewer_last_name'] ?? ''));
                                ?>
                                    <div class="rt-row">
                                        <div class="rt-cell rt-cell-applicant-name" data-label="Kandidat">
                                            <strong><?= htmlspecialchars($applicantName) ?></strong>
                                        </div>
                                        <div class="rt-cell rt-cell-applicant-contact" data-label="Kontakt">
                                            <span><?= htmlspecialchars($application['applicant_email']) ?></span>
                                            <span><?= htmlspecialchars($application['applicant_phone'] ?? '—') ?></span>
                                        </div>
                                        <div class="rt-cell rt-cell-applicant-date" data-label="Datum">
                                            <?= date('d.m.Y. H:i', strtotime($application['created_at'])) ?>
                                        </div>
                                        <div class="rt-cell rt-cell-application-status" data-label="Status">
                                            <span class="status-badge status-<?= strtolower($application['status']) ?>">
                                                <?= htmlspecialchars($statusLabels[$application['status']] ?? $application['status']) ?>
                                            </span>
                                            <?php if ($application['reviewed_at']): ?>
                                                <small>Pregledao: <?= htmlspecialchars($reviewerName !== '' ? $reviewerName : 'Admin') ?></small>
                                                <small><?= date('d.m.Y. H:i', strtotime($application['reviewed_at'])) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="rt-cell rt-cell-application-action" data-label="Akcija">
                                            <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#applicationModal<?= $application['id'] ?>">
                                                <i class="fa-solid fa-eye me-1"></i> Detalji
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php if ($applicationTotalPages > 1): ?>
                            <div class="pagination-wrap">
                                <?php if ($applicationPage > 1): ?>
                                    <a href="?tab=applications&app_status=<?= urlencode($applicationStatus) ?>&app_search=<?= urlencode($applicationSearch) ?>&app_page=<?= $applicationPage - 1 ?>" class="page-btn">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $applicationTotalPages; $i++): ?>
                                    <a href="?tab=applications&app_status=<?= urlencode($applicationStatus) ?>&app_search=<?= urlencode($applicationSearch) ?>&app_page=<?= $i ?>"
                                        class="page-btn <?= $i === $applicationPage ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                <?php if ($applicationPage < $applicationTotalPages): ?>
                                    <a href="?tab=applications&app_status=<?= urlencode($applicationStatus) ?>&app_search=<?= urlencode($applicationSearch) ?>&app_page=<?= $applicationPage + 1 ?>" class="page-btn">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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

    <?php foreach ($applications as $application):
        $applicantName = trim(($application['applicant_first_name'] ?? '') . ' ' . ($application['applicant_last_name'] ?? ''));
        $applicantName = $applicantName !== '' ? $applicantName : $application['applicant_email'];
        $reviewerName = trim(($application['reviewer_first_name'] ?? '') . ' ' . ($application['reviewer_last_name'] ?? ''));
    ?>
        <div class="modal fade" id="applicationModal<?= $application['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content glass-card p-4 border-0">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="text-white font-serif mb-1">Prijava za posao</h5>
                            <p class="text-white opacity-50 small mb-0">
                                <?= htmlspecialchars($applicantName) ?> · <?= date('d.m.Y. H:i', strtotime($application['created_at'])) ?>
                            </p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-0">
                        <div class="application-detail-grid mb-4">
                            <div>
                                <span>Ime</span>
                                <strong><?= htmlspecialchars($applicantName) ?></strong>
                            </div>
                            <div>
                                <span>Email</span>
                                <strong><?= htmlspecialchars($application['applicant_email']) ?></strong>
                            </div>
                            <div>
                                <span>Telefon</span>
                                <strong><?= htmlspecialchars($application['applicant_phone'] ?? '—') ?></strong>
                            </div>
                            <div>
                                <span>Status</span>
                                <strong>
                                    <span class="status-badge status-<?= strtolower($application['status']) ?>">
                                        <?= htmlspecialchars($statusLabels[$application['status']] ?? $application['status']) ?>
                                    </span>
                                </strong>
                            </div>
                            <?php if ($application['reviewed_at']): ?>
                                <div>
                                    <span>Pregledao</span>
                                    <strong><?= htmlspecialchars($reviewerName !== '' ? $reviewerName : 'Admin') ?></strong>
                                </div>
                                <div>
                                    <span>Pregledano</span>
                                    <strong><?= date('d.m.Y. H:i', strtotime($application['reviewed_at'])) ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="application-answer mb-3">
                            <span>Dostupnost</span>
                            <p><?= nl2br(htmlspecialchars($application['availability'])) ?></p>
                        </div>
                        <div class="application-answer mb-3">
                            <span>Iskustvo</span>
                            <p><?= nl2br(htmlspecialchars($application['experience'])) ?></p>
                        </div>
                        <div class="application-answer mb-4">
                            <span>Motivacija</span>
                            <p><?= nl2br(htmlspecialchars($application['motivation'])) ?></p>
                        </div>

                        <form method="POST" class="application-modal-actions">
                            <input type="hidden" name="action" value="update_application_status">
                            <input type="hidden" name="id" value="<?= $application['id'] ?>">
                            <div>
                                <label class="form-label small opacity-50">Promeni status</label>
                                <select name="status" class="form-control custom-input" required>
                                    <?php if ($application['status'] === 'PENDING'): ?>
                                        <option value="" selected disabled>Izaberi status</option>
                                    <?php endif; ?>
                                    <?php foreach ($reviewableStatuses as $status): ?>
                                        <option value="<?= $status ?>" <?= $application['status'] === $status ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($statusLabels[$status]) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-light">
                                <i class="fa-solid fa-check me-2"></i> Sačuvaj status
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>
</body>

</html>
