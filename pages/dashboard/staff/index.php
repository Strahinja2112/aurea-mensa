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
                <h2 class="mb-0 pb-2 pt-5">Današnje Rezervacije</h2>
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

            <form method="GET" class="mb-4 pb-4" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                <div class="d-flex align-items-end gap-2">
                    <div style="flex: 1; min-width: 0;">
                        <label class="form-label small opacity-50 mb-1">Pretraga (ime, email, telefon)</label>
                        <input type="text" name="search" id="searchInput" class="form-control custom-input"
                            placeholder="Pretraži po kodu, imenu, emailu ili telefonu..."
                            value="<?= htmlspecialchars($searchQuery) ?>"
                            style="padding-top: 6px; padding-bottom: 6px;">
                    </div>
                    <button type="submit" class="btn btn-cta flex-shrink-0" style="padding-top: 6px; padding-bottom: 6px;">Pretraži</button>
                    <?php if ($searchQuery !== ''): ?>
                        <a href="?status=<?= urlencode($statusFilter) ?>" class="btn btn-outline-light rounded-circle p-0 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; min-width: 38px;" title="Poništi pretragu">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

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
                                        <button type="button" class="btn btn-cta btn-sm"
                                            onclick="quickCheckIn(<?= $res['id'] ?>, '<?= htmlspecialchars($res['reservation_code']) ?>')">
                                            <i class="fa-solid fa-check me-1"></i>Prijavi
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($arrived && !$hasComment): ?>
                                        <button type="button" class="btn btn-outline-light btn-sm"
                                            onclick="openCommentModal(<?= $res['id'] ?>, '<?= htmlspecialchars($res['reservation_code']) ?>', '<?= htmlspecialchars($userName) ?>', '<?= htmlspecialchars($resUser['email'] ?? '') ?>', '<?= htmlspecialchars($resUser['phone'] ?? '') ?>', '<?= htmlspecialchars(implode(', ', $tables)) ?>', '<?= substr($res['start_time'], 0, 5) ?> – <?= substr($res['end_time'], 0, 5) ?>', '')">
                                            <i class="fa-solid fa-plus me-1"></i>Komentar
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($arrived && $hasComment): ?>
                                        <button type="button" class="btn btn-outline-light btn-sm"
                                            onclick="openCommentModal(<?= $res['id'] ?>, '<?= htmlspecialchars($res['reservation_code']) ?>', '<?= htmlspecialchars($userName) ?>', '<?= htmlspecialchars($resUser['email'] ?? '') ?>', '<?= htmlspecialchars($resUser['phone'] ?? '') ?>', '<?= htmlspecialchars(implode(', ', $tables)) ?>', '<?= substr($res['start_time'], 0, 5) ?> – <?= substr($res['end_time'], 0, 5) ?>', '<?= htmlspecialchars($res['comment']) ?>')">
                                            <i class="fa-regular fa-note-sticky me-1"></i>Komentar
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
            <div class="modal-content glass-card p-0" style="max-width: 520px;">
                <div class="modal-header border-0 pb-0 d-flex align-items-center justify-content-between px-4 pt-4">
                    <h5 class="modal-title text-white m-0 fs-5">
                        <i class="fa-regular fa-note-sticky me-2 opacity-50"></i>Komentar
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Zatvori"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <input type="hidden" id="modalReservationId" value="">

                    <div class="p-3 rounded-3 mb-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 40px; height: 40px; background: rgba(74,106,106,0.3);">
                                <i class="fa-solid fa-user text-white" style="opacity: 0.75;"></i>
                            </div>
                            <div style="min-width: 0;">
                                <div class="text-white fw-medium" id="modalGuestName" style="font-size: 0.95rem;"></div>
                                <code class="fs-5" id="modalReservationCode" style="color: rgba(74,106,106)"></code>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-3 mt-3 pt-2" style="border-top: 1px solid rgba(255,255,255,0.04);">
                            <div class="d-flex align-items-center gap-2" style="font-size: 0.8rem;">
                                <i class="fa-regular fa-envelope" style="width: 14px; opacity: 0.3;"></i>
                                <span style="opacity: 0.6;" id="modalGuestEmail"></span>
                            </div>
                            <div class="d-flex align-items-center gap-2" style="font-size: 0.8rem;">
                                <i class="fa-regular fa-clock" style="width: 14px; opacity: 0.3;"></i>
                                <span style="opacity: 0.6;" id="modalReservationTime"></span>
                            </div>
                            <div class="d-flex align-items-center gap-2" style="font-size: 0.8rem;">
                                <i class="fa-solid fa-phone" style="width: 14px; opacity: 0.3;"></i>
                                <span style="opacity: 0.6;" id="modalGuestPhone"></span>
                            </div>
                            <div class="d-flex align-items-center gap-2" style="font-size: 0.8rem;">
                                <i class="fa-solid fa-chair" style="width: 14px; opacity: 0.3;"></i>
                                <span style="opacity: 0.6;" id="modalGuestTables"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2" id="commentInputGroup">
                        <label for="modalComment" class="form-label small mb-2" style="opacity: 0.6;">Unesite komentar</label>
                        <textarea class="form-control custom-input" id="modalComment" rows="3"
                            placeholder="Zabeleška o gostu..." required
                            style="resize: vertical; min-height: 80px;"></textarea>
                    </div>
                    <div class="mb-2 d-none" id="commentDisplayGroup">
                        <label class="form-label small mb-2" style="opacity: 0.6;">Komentar</label>
                        <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); white-space: pre-wrap; font-size: 0.9rem; color: rgba(255,255,255,0.8);" id="modalCommentDisplay"></div>
                    </div>
                    <div id="commentError" class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mb-2 text-center d-none"></div>
                    <div id="commentSuccess" class="alert border-0 text-white small rounded-3 mb-2 text-center d-none" style="background: rgba(74,106,106,0.2);"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-light btn-sm px-3" data-bs-dismiss="modal">Zatvori</button>
                    <button class="btn btn-cta btn-sm px-4" id="saveCommentBtn">Sačuvaj</button>
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

        document.getElementById('modalComment').addEventListener('input', function() {
            document.getElementById('saveCommentBtn').disabled = !this.value.trim();
        });

        function openCommentModal(reservationId, reservationCode, guestName, guestEmail, guestPhone, guestTables, reservationTime, existingComment) {
            document.getElementById('modalReservationId').value = reservationId;
            document.getElementById('modalReservationCode').textContent = reservationCode;
            document.getElementById('modalGuestName').textContent = guestName || 'Nepoznat gost';
            document.getElementById('modalGuestEmail').textContent = guestEmail || '—';
            document.getElementById('modalGuestPhone').textContent = guestPhone || '—';
            document.getElementById('modalGuestTables').textContent = guestTables || '—';
            document.getElementById('modalReservationTime').textContent = reservationTime || '—';

            const inputGroup = document.getElementById('commentInputGroup');
            const displayGroup = document.getElementById('commentDisplayGroup');
            const commentDisplay = document.getElementById('modalCommentDisplay');
            const textarea = document.getElementById('modalComment');
            const saveBtn = document.getElementById('saveCommentBtn');

            if (existingComment) {
                inputGroup.classList.add('d-none');
                displayGroup.classList.remove('d-none');
                commentDisplay.textContent = existingComment;
                saveBtn.classList.add('d-none');
            } else {
                inputGroup.classList.remove('d-none');
                displayGroup.classList.add('d-none');
                textarea.value = '';
                saveBtn.classList.remove('d-none');
                saveBtn.disabled = true;
            }

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