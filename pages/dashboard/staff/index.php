<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../autoload.php';
require_once Helpers::getRelativePath('src/components/reservation-card.php');

$user = Auth::getUser();

if (!$user || !$user->isStaff()) {
    http_response_code(403);
    header('Location: ' . Helpers::getRelativePath('pages/auth/login'));
    exit;
}

$today = date('Y-m-d');
$statusFilter = $_GET['status'] ?? 'pending';
$searchQuery = $_GET['search'] ?? '';

$reservations = db()->reservations->findMany([
        'where' => [
                'reservation_date' => $today,
        ],
        'order' => ['start_time' => 'ASC'],
]);

$todayReservations = [];
foreach ($reservations as $res) {
    $tables = db()->reservationTables->findMany([
            'where' => ['reservation_id' => $res['id']],
    ]);
    $tableIds = array_column($tables, 'table_id');
    $tableNames = [];
    foreach ($tableIds as $id) {
        $table = db()->restaurantTables->findFirst(['where' => ['id' => $id]]);
        $tableNames[] = $table['name'] ?? 'Unknown';
    }

    $log = db()->reservationLogs->findFirst([
            'where' => [
                    'reservation_id' => $res['id'],
                    'arrived' => 1,
            ],
    ]);
    $arrived = !empty($log);

    $userData = db()->users->findFirst(['where' => ['id' => $res['user_id']]]);

    if ($statusFilter === 'pending' && $arrived) {
        continue;
    }
    if ($statusFilter === 'checked_in' && !$arrived) {
        continue;
    }

    if ($searchQuery) {
        $searchMatch = false;
        $searchLower = mb_strtolower($searchQuery);

        if (mb_strpos(mb_strtolower($res['reservation_code']), $searchLower) !== false) {
            $searchMatch = true;
        }
        if ($userData && mb_strpos(mb_strtolower($userData['email']), $searchLower) !== false) {
            $searchMatch = true;
        }
        if ($userData && mb_strpos(mb_strtolower($userData['first_name'] ?? ''), $searchLower) !== false) {
            $searchMatch = true;
        }
        if ($userData && mb_strpos(mb_strtolower($userData['last_name'] ?? ''), $searchLower) !== false) {
            $searchMatch = true;
        }
        if ($userData && mb_strpos(mb_strtolower($userData['phone'] ?? ''), $searchLower) !== false) {
            $searchMatch = true;
        }

        if (!$searchMatch) {
            continue;
        }
    }

    $todayReservations[] = [
            'reservation' => $res,
            'tables' => $tableNames,
            'arrived' => $arrived,
            'log' => $log,
            'user' => $userData,
    ];
}

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkin'])) {
    $code = $_POST['reservation_code'] ?? '';

    if (empty($code)) {
        $error = 'Molimo unesite kod rezervacije.';
    } else {
        $reservation = db()->reservations->findFirst([
                'where' => [
                        'reservation_code' => $code,
                        'status' => 'ACTIVE',
                ],
        ]);

        if (!$reservation) {
            $error = 'Rezervacija nije pronađena ili je već obrađena.';
        } else {
            $existingLog = db()->reservationLogs->findFirst([
                    'where' => [
                            'reservation_id' => $reservation['id'],
                            'arrived' => 1,
                    ],
            ]);

            if ($existingLog) {
                $error = 'Gost je već prijavljen.';
            } else {
                db()->reservationLogs->create([
                        'data' => [
                                'reservation_id' => $reservation['id'],
                                'staff_id' => $user->id,
                                'arrived' => 1,
                                'comment' => $_POST['comment'] ?? null,
                        ],
                ]);
                $message = 'Gost uspešno prijavljen!';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $reservationId = (int)($_POST['reservation_id'] ?? 0);
    $comment = $_POST['new_comment'] ?? '';

    if ($reservationId && !empty($comment)) {
        $reservation = db()->reservations->findFirst(['where' => ['id' => $reservationId]]);

        if ($reservation) {
            $existingComment = db()->reservationLogs->findFirst([
                'where' => [
                    'reservation_id' => $reservationId,
                    'comment' => ['not' => null],
                ],
            ]);

            if ($existingComment) {
                $error = 'Komentar za ovu rezervaciju već postoji.';
            } else {
                db()->reservationLogs->create([
                    'data' => [
                        'reservation_id' => $reservationId,
                        'staff_id' => $user->id,
                        'arrived' => 0,
                        'comment' => $comment,
                    ],
                ]);
                $message = 'Komentar uspešno dodat!';
            }
        }
    }
}

$pageTitle = 'Staff Dashboard';
?>
<!doctype html>
<html lang="sr">
<?php Helpers::renderHead($pageTitle, ['auth.css', 'profile.css', 'dashboard.css']) ?>

<body>
<?php include Helpers::getRelativePath('src/components/header.php') ?>

<main>
    <div class="dashboard-container">
        <div class="dashboard-left">
            <div class="glass-card">
                <h2 class="text-center mb-4">Prijava Gosta</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success bg-success bg-opacity-20 border-0 text-white small rounded-3 mb-4 text-center">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mb-4 text-center">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" id="checkinForm">
                    <input type="hidden" name="checkin" value="1">
                    <div class="form-group mb-3">
                        <label class="form-label opacity-50">Kod Rezervacije</label>
                        <input type="text" name="reservation_code" id="checkinCode" class="form-control custom-input"
                               placeholder="npr. ABC123XYZ" required>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label opacity-50">Komentar (opciono)</label>
                        <textarea name="comment" class="form-control custom-input"
                                  rows="3" placeholder="Beleške o gostu..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-light btn-lg w-100">
                        <i class="fa-solid fa-check me-2"></i>Prijavi Gosta
                    </button>
                </form>
            </div>
        </div>

        <div class="dashboard-right">
            <div class="glass-card" style="max-width: 1000px">
                <h2 class="text-center mb-4">Danasnje Rezervacije</h2>

                <div class="filter-bar mb-4">
                    <input type="text" id="searchInput" class="form-control custom-input"
                           placeholder="Pretraži po kodu, imenu, emailu ili telefonu..."
                           value="<?= htmlspecialchars($searchQuery) ?>">

                    <select id="statusFilter" class="form-control custom-input" onchange="applyFilters()">
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Na Cekanju</option>
                        <option value="checked_in" <?= $statusFilter === 'checked_in' ? 'selected' : '' ?>>Prijavljeni
                        </option>
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Sve</option>
                    </select>

                    <button type="button" class="btn btn-outline-light btn-lg" onclick="applyFilters()">
                        <i class="fa-solid fa-search"></i>
                    </button>
                </div>

                <?php if (empty($todayReservations)): ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-calendar-xmark fa-3x mb-3 opacity-10"></i>
                        <h3 class="text-white opacity-25 fw-light">Nema rezervacija.</h3>
                    </div>
                <?php else: ?>
                    <div class="res-cards-grid">
                        <?php foreach ($todayReservations as $item): ?>
                            <?php renderReservationCard($item); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="commentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card">
            <div class="modal-header border-bottom border-white border-opacity-10">
                <h5 class="modal-title text-white">Dodaj Komentar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalReservationId" value="">
                <p class="text-white mb-3">Rezervacija: <code class="text-warning" id="modalReservationCode"></code></p>
                <div class="mb-3">
                    <label for="modalComment" class="form-label text-white">Komentar</label>
                    <textarea class="form-control custom-input" id="modalComment" rows="3"
                              placeholder="Unesite komentar..." required></textarea>
                </div>
                <div id="commentError" class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mb-3 text-center d-none"></div>
                <div id="commentSuccess" class="alert alert-success bg-success bg-opacity-20 border-0 text-white small rounded-3 mb-3 text-center d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Otkaži</button>
                <button disabled class="btn btn-success" id="saveCommentBtn">Sačuvaj</button>
            </div>
        </div>
    </div>
</div>

<?php include Helpers::getRelativePath('src/components/footer.php') ?>

<script>
    function fillCode(code) {
        document.getElementById('checkinCode').value = code;
        document.getElementById('checkinCode').focus();
        document.getElementById('checkinCode').scrollIntoView({behavior: 'smooth', block: 'center'});
    }

    function applyFilters() {
        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;

        let url = '?';
        if (search) url += 'search=' + encodeURIComponent(search) + '&';
        url += 'status=' + status;

        window.location.href = url;
    }

    document.getElementById('searchInput').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });

    function openCommentModal(reservationId, reservationCode) {
        document.getElementById('modalReservationId').value = reservationId;
        document.getElementById('modalReservationCode').textContent = reservationCode;
        document.getElementById('modalComment').value = '';
        document.getElementById('commentError').classList.add('d-none');
        document.getElementById('commentSuccess').classList.add('d-none');
        const modal = new bootstrap.Modal(document.getElementById('commentModal'));
        modal.show();
    }

    document.getElementById('saveCommentBtn').addEventListener('click', async function() {
        const reservationId = document.getElementById('modalReservationId').value;
        const comment = document.getElementById('modalComment').value.trim();
        const errorEl = document.getElementById('commentError');
        const successEl = document.getElementById('commentSuccess');
        const btn = this;

        errorEl.classList.add('d-none');
        successEl.classList.add('d-none');

        if (!comment) {
            errorEl.textContent = 'Komentar ne može biti prazan.';
            errorEl.classList.remove('d-none');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Sačuvaj...';

        try {
            const response = await fetch('<?= Helpers::getRelativePath("src/actions/add_comment.php") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    reservation_id: parseInt(reservationId),
                    comment: comment
                })
            });

            const result = await response.json();

            if (result.success) {
                successEl.textContent = result.message;
                successEl.classList.remove('d-none');
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('commentModal')).hide();
                    window.location.reload();
                }, 1000);
            } else {
                errorEl.textContent = result.message;
                errorEl.classList.remove('d-none');
            }
        } catch (e) {
            errorEl.textContent = 'Došlo je do greške. Pokušajte ponovo.';
            errorEl.classList.remove('d-none');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Sačuvaj';
        }
    });
</script>

<?php require_once Helpers::getRelativePath('src/components/reservation-card.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>