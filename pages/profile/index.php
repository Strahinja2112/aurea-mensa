<?php

declare(strict_types=1);
require_once '../../autoload.php';

Auth::requireAuth();
$user = Auth::getUser();

$error = null;
$success = null;

$oldFirstName = $_POST['first_name'] ?? $user->firstName;
$oldLastName = $_POST['last_name'] ?? $user->lastName;
$oldPhone = $_POST['phone'] ?? ($user->phone ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        db()->beginTransaction();

        $updateData = [
            'first_name' => $oldFirstName,
            'last_name' => $oldLastName,
            'phone' => $oldPhone
        ];

        if (!empty($oldPhone) && !Helpers::isValidPhone($oldPhone)) {
            throw new Exception("Format broja telefona nije ispravan.");
        }

        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (!empty($oldPassword) || !empty($newPassword)) {
            if (empty($oldPassword) || empty($newPassword)) {
                throw new Exception("Popunite i trenutnu i novu lozinku za promenu.");
            }

            if (!password_verify($oldPassword, $user->passwordHash)) {
                throw new Exception("Trenutna lozinka nije ispravna.");
            }

            if (!Helpers::isStrongPassword($newPassword)) {
                throw new Exception("Nova lozinka mora imati bar 8 karaktera, veliko slovo i broj.");
            }

            $updateData['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        db()->users->update([
            'where' => ['id' => $user->id],
            'data' => $updateData
        ]);

        db()->commit();
        $success = "Izmene su uspešno sačuvane.";
        $user = Auth::getUser();
    } catch (Exception $e) {
        db()->rollBack();
        $error = $e->getMessage();
    }
}

$filterDate = $_GET['date'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;

$where = [
    'AND' => [
        ['user_id' => $user->id],
    ]
];

if ($filterDate) {
    $where['AND'][] = ['reservation_date' => $filterDate];
}

$totalProfile = db()->reservations->count(['where' => $where]);
$totalProfilePages = max(1, (int)ceil($totalProfile / $perPage));
$page = min($page, $totalProfilePages);

$myReservations = db()->reservations->findMany([
    'where' => $where,
    'orderBy' => ['reservation_date' => 'DESC', 'start_time' => 'DESC'],
    'take' => $perPage,
    'skip' => ($page - 1) * $perPage,
]);

$reservationTables = [];
foreach ($myReservations as $res) {
    $rts = db()->reservationTables->findMany([
        'where' => ['reservation_id' => $res['id']]
    ]);
    $tableNames = [];
    foreach ($rts as $rt) {
        $t = db()->restaurantTables->findFirst(['where' => ['id' => $rt['table_id']]]);
        if ($t) $tableNames[] = $t['name'];
    }
    $reservationTables[$res['id']] = $tableNames;
}

$relativePath = Helpers::getRelativePath("pages/auth/")
?>

<!DOCTYPE html>
<html lang="sr">
<?php
Helpers::renderHead("Moj Profil", ['auth.css', 'table.css']); ?>

<body>

    <?php
    require_once Helpers::getRelativePath('src/components/header.php'); ?>

    <main>
        <div class="container-fluid container-lg row justify-content-center g-4 w-100">
            <div class="col-12 col-lg-5">
                <div class="glass-card h-100" style="max-width: 1000px !important;">
                    <h2 class="text-center mb-4">Izmena Profila</h2>

                    <?php
                    if ($error): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mb-4 text-center">
                            <?= $error ?>
                        </div>
                    <?php
                    endif; ?>

                    <?php
                    if ($success): ?>
                        <div class="alert alert-success bg-success bg-opacity-20 border-0 text-white small rounded-3 mb-4 text-center">
                            <?= $success ?>
                        </div>
                    <?php
                    endif; ?>

                    <form method="POST" action="">
                        <div class="form-group mb-3">
                            <label class="form-label small opacity-50">Email</label>
                            <input type="text" class="form-control custom-input opacity-50"
                                value="<?= htmlspecialchars($user->email) ?>" readonly>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label small opacity-50">Ime</label>
                                <input type="text" name="first_name" class="form-control custom-input"
                                    value="<?= htmlspecialchars($oldFirstName) ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label small opacity-50">Prezime</label>
                                <input type="text" name="last_name" class="form-control custom-input"
                                    value="<?= htmlspecialchars($oldLastName) ?>" required>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label small opacity-50">Telefon</label>
                            <input type="text" required name="phone" class="form-control custom-input"
                                value="<?= htmlspecialchars($oldPhone) ?>">
                        </div>

                        <div class="password-section mt-4 pt-4 border-top border-white border-opacity-10">
                            <p class="small opacity-50 mb-3 text-uppercase" style="letter-spacing: 1px;">Promena lozinke</p>

                            <div class="form-group mb-3">
                                <label class="form-label small opacity-50">Trenutna lozinka</label>
                                <input type="password" name="old_password" class="form-control custom-input"
                                    placeholder="********">
                            </div>

                            <div class="form-group mb-4">
                                <label class="form-label small opacity-50">Nova lozinka</label>
                                <input type="password" name="new_password" class="form-control custom-input"
                                    placeholder="Min. 8 karaktera"
                                    pattern="(?=.*\d)(?=.*[A-Z]).{8,}"
                                    title="Lozinka mora imati bar 8 karaktera, jedno veliko slovo i jedan broj">
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-3 w-100">
                            <a href="<?= $relativePath . '/auth/logout' ?>" class="btn btn-danger btn-lg flex-fill">
                                <i class="fa-solid fa-right-from-bracket me-2"></i>
                                Odjavi se
                            </a>
                            <button type="submit" name="update_profile" class="btn btn-light btn-lg flex-fill">
                                <i class="fa-solid fa-save me-2"></i>
                                Sačuvaj izmene
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="glass-card h-100 w-100" style="max-width: 1000px !important;">
                    <h2 class="text-center mb-4">Rezervacije</h2>

                    <form method="GET" class="mb-3 pb-3" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                        <label class="form-label small opacity-50 mb-2">Filter po datumu</label>
                        <div class="d-flex gap-2">
                            <input type="date" name="date" class="form-control custom-input"
                                value="<?= htmlspecialchars($filterDate) ?>"
                                style="min-width: 0; flex: 1; padding-top: 6px; padding-bottom: 6px;">
                            <button type="submit" class="btn btn-cta" style="white-space: nowrap; padding-top: 6px; padding-bottom: 6px;">Filtriraj</button>
                            <?php if ($filterDate): ?>
                                <a href="." class="btn btn-outline-light rounded-circle p-0 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px;" title="Poništi filter">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if (empty($myReservations)): ?>
                        <div class="text-center py-5">
                            <i class="fa-solid fa-calendar-xmark fa-3x mb-3 opacity-10"></i>
                            <h3 class="text-white opacity-25 fw-light">Nema podataka o rezervacijama.</h3>
                        </div>
                    <?php else: ?>
                        <div class="reservations-table rt-table-profile">
                            <div class="rt-head">
                                <div class="rt-row">
                                    <div class="rt-cell rt-cell-code">Kod</div>
                                    <div class="rt-cell rt-cell-date">Datum</div>
                                    <div class="rt-cell rt-cell-time">Vreme</div>
                                    <div class="rt-cell rt-cell-tables">Stolovi</div>
                                    <div class="rt-cell rt-cell-arrival">Akcije</div>
                                </div>
                            </div>
                            <div class="rt-body">
                                <?php foreach ($myReservations as $res):
                                    $tables = $reservationTables[$res['id']] ?? [];
                                    $resTimestamp = strtotime($res['reservation_date'] . ' ' . $res['start_time']);
                                    $now = time();
                                    $deadlineTimestamp = $resTimestamp - 14400;
                                    $secondsLeftToCancel = $deadlineTimestamp - $now;
                                    $canCancel = ($res['status'] === 'ACTIVE' && $secondsLeftToCancel > 0);
                                    $timerText = "";
                                    if ($canCancel) {
                                        if ($secondsLeftToCancel >= 86400) {
                                            $days = floor($secondsLeftToCancel / 86400);
                                            $timerText = $days . ($days == 1 ? " dan" : " dana");
                                        } elseif ($secondsLeftToCancel >= 3600) {
                                            $hours = floor($secondsLeftToCancel / 3600);
                                            $mins = floor(($secondsLeftToCancel % 3600) / 60);
                                            $timerText = "{$hours}h {$mins}min";
                                        } else {
                                            $mins = ceil($secondsLeftToCancel / 60);
                                            $timerText = "{$mins} min";
                                        }
                                    }
                                ?>
                                    <div class="rt-row">
                                        <div class="rt-cell rt-cell-code" data-label="Kod">
                                            <code><?= htmlspecialchars($res['reservation_code']) ?></code>
                                        </div>
                                        <div class="rt-cell rt-cell-date" data-label="Datum">
                                            <?php
                                            $d = new DateTime($res['reservation_date']);
                                            echo '<span style="font-size: 1.1rem; font-weight: 300; color: #f8f9fa;">' . $d->format('d') . '</span>';
                                            echo '<span class="opacity-50" style="font-size: 0.65rem; display: block; text-transform: uppercase; letter-spacing: 1px;">' . $d->format('M') . '</span>';
                                            ?>
                                        </div>
                                        <div class="rt-cell rt-cell-time" data-label="Vreme">
                                            <?= substr($res['start_time'], 0, 5) ?> – <?= substr($res['end_time'], 0, 5) ?>
                                        </div>
                                        <div class="rt-cell rt-cell-tables" data-label="Stolovi">
                                            <?= !empty($tables) ? htmlspecialchars(implode(', ', $tables)) : '—' ?>
                                        </div>
                                        <div class="rt-cell rt-cell-arrival" data-label="Akcije">
                                            <?php if ($canCancel): ?>
                                                <button onclick="cancelReservation(<?= $res['id'] ?>)"
                                                    class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-xmark me-1"></i>
                                                    <span>Otkaži</span>
                                                    <span style="font-size: 9px;">(<?= $timerText ?>)</span>
                                                </button>
                                            <?php else: ?>
                                                <span class="opacity-25" style="font-size: 0.65rem;">
                                                    <?= $res['status'] === 'ACTIVE' ? 'Isteklo vreme' : '—' ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php if ($totalProfilePages > 1): ?>
                            <div class="pagination-wrap">
                                <?php if ($page > 1): ?>
                                    <a href="?date=<?= urlencode($filterDate) ?>&page=<?= $page - 1 ?>" class="page-btn">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $totalProfilePages; $i++): ?>
                                    <a href="?date=<?= urlencode($filterDate) ?>&page=<?= $i ?>"
                                        class="page-btn <?= $i === $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                <?php if ($page < $totalProfilePages): ?>
                                    <a href="?date=<?= urlencode($filterDate) ?>&page=<?= $page + 1 ?>" class="page-btn">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php
    require_once Helpers::getFullPath("src/components/footer.php"); ?>

    <script>
        async function cancelReservation(id) {
            if (!confirm('Otkazati rezervaciju?')) {
                return;
            }

            try {
                const response = await fetch(`<?= Helpers::getRelativePath("src/actions/cancel_reservation.php") ?>`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id
                    })
                });

                const result = await response.json();

                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (e) {
                console.error(e);
            }
        }
    </script>
</body>

</html>