<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../autoload.php';

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
    'orderBy' => ['start_time' => 'ASC'],
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

    $arrived = !empty($res['arrived']);
    $comment = $res['comment'] ?? null;

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
        'user' => $userData,
    ];
}

$message = null;
$error = null;

$pageTitle = 'Staff Dashboard';
?>
<!doctype html>
<html lang="sr">
<?php Helpers::renderHead($pageTitle, ['auth.css', 'dashboard.css', 'table.css']) ?>

<body>
    <?php include Helpers::getRelativePath('src/components/header.php') ?>

    <main>
        <div class="glass-card dashboard-container">
            <div class="page-header text-center mb-4">
                <h2 class="mb-0 pb-2">Današnje Rezervacije</h2>
                <p class="page-subtitle"><?= date('l, d. M') ?></p>
            </div>

            <div class="filter-buttons mb-4">
                <button type="button" class="btn btn-filter <?= $statusFilter === 'all' ? 'active' : '' ?>"
                    onclick="setStatusFilter('all')">
                    <i class="fa-solid fa-list"></i> Sve
                </button>
                <button type="button" class="btn btn-filter <?= $statusFilter === 'pending' ? 'active' : '' ?>"
                    onclick="setStatusFilter('pending')">
                    <i class="fa-solid fa-clock"></i> Na Čekanju
                </button>
                <button type="button" class="btn btn-filter <?= $statusFilter === 'checked_in' ? 'active' : '' ?>"
                    onclick="setStatusFilter('checked_in')">
                    <i class="fa-solid fa-check"></i> Prijavljeni
                </button>
            </div>

            <div class="search-bar mb-4">
                <input type="text" id="searchInput" class="form-control custom-input"
                    placeholder="Pretraži po kodu, imenu, emailu ili telefonu..."
                    value="<?= htmlspecialchars($searchQuery) ?>">
            </div>

            <?php if (empty($todayReservations)): ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-calendar-xmark fa-3x mb-3 opacity-10"></i>
                    <h3 class="text-white opacity-25 fw-light">Nema rezervacija.</h3>
                </div>
            <?php else: ?>
                <div class="reservations-table rt-table-actions">
                    <div class="rt-head">
                        <div class="rt-row">
                            <div class="rt-cell rt-cell-code">Kod</div>
                            <div class="rt-cell rt-cell-time">Vreme</div>
                            <div class="rt-cell rt-cell-guest">Gost</div>
                            <div class="rt-cell rt-cell-email">Kontakt</div>
                            <div class="rt-cell rt-cell-tables">Stolovi</div>
                            <div class="rt-cell rt-cell-status">Status</div>
                            <div class="rt-cell rt-cell-arrival">Akcije</div>
                        </div>
                    </div>
                    <div class="rt-body">
                        <?php foreach ($todayReservations as $item):
                            $res = $item['reservation'];
                            $resUser = $item['user'];
                            $arrived = $item['arrived'];
                            $tables = $item['tables'];
                            $hasComment = !empty($res['comment']);
                            $userName = trim(($resUser['first_name'] ?? '') . ' ' . ($resUser['last_name'] ?? ''));
                            if (!$userName) {
                                $userName = $resUser['email'] ?? 'Nepoznat';
                            }
                        ?>
                            <div class="rt-row">
                                <div class="rt-cell rt-cell-code" data-label="Kod">
                                    <code><?= htmlspecialchars($res['reservation_code']) ?></code>
                                </div>
                                <div class="rt-cell rt-cell-time" data-label="Vreme">
                                    <?= substr($res['start_time'], 0, 5) ?> – <?= substr($res['end_time'], 0, 5) ?>
                                </div>
                                <div class="rt-cell rt-cell-guest" data-label="Gost">
                                    <?= htmlspecialchars($userName) ?>
                                </div>
                                <div class="rt-cell rt-cell-email" data-label="Kontakt">
                                    <div><?= htmlspecialchars($resUser['email'] ?? '') ?></div>
                                </div>
                                <div class="rt-cell rt-cell-tables" data-label="Stolovi">
                                    <?= !empty($tables) ? htmlspecialchars(implode(', ', $tables)) : '—' ?>
                                </div>
                                <div class="rt-cell rt-cell-status" data-label="Status">
                                    <span class="status-badge <?= $arrived ? 'status-completed' : 'status-active' ?>">
                                        <?= $arrived ? 'Prijavljen' : 'Na čekanju' ?>
                                    </span>
                                </div>
                                <div class="rt-cell rt-cell-arrival" data-label="Akcije" style="text-align: right;">
                                    <?php if (!$arrived): ?>
                                        <button type="button" class="btn btn-success btn-sm"
                                            onclick="quickCheckIn(<?= $res['id'] ?>, '<?= htmlspecialchars($res['reservation_code']) ?>')">
                                            <i class="fa-solid fa-check me-1"></i>Prijavi
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($arrived && !$hasComment): ?>
                                        <button type="button" class="btn btn-outline-light btn-sm"
                                            onclick="openCommentModal(<?= $res['id'] ?>, '<?= htmlspecialchars($res['reservation_code']) ?>')">
                                            <i class="fa-solid fa-plus me-1"></i>Komentar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
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
        async function quickCheckIn(reservationId, reservationCode) {
            try {
                const response = await fetch('<?= Helpers::getRelativePath("src/actions/checkin.php") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        reservation_id: reservationId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.message);
                }
            } catch (e) {
                alert('Došlo je do greške. Pokušajte ponovo.');
            }
        }

        function setStatusFilter(status) {
            const search = document.getElementById('searchInput').value;
            let url = '?status=' + status;
            if (search) url += '&search=' + encodeURIComponent(search);
            window.location.href = url;
        }

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const status = new URLSearchParams(window.location.search).get('status') || 'all';
                const search = document.getElementById('searchInput').value;
                let url = '?status=' + status;
                if (search) url += '&search=' + encodeURIComponent(search);
                window.location.href = url;
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

</body>

</html>