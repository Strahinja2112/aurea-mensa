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
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;

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

$totalStaffReservations = count($todayReservations);
$totalStaffPages = max(1, (int)ceil($totalStaffReservations / $perPage));
$page = min($page, $totalStaffPages);
$paginatedTodayReservations = array_slice($todayReservations, ($page - 1) * $perPage, $perPage);

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
        <div class="glass-card dashboard-container py-4">
            <div class="page-header text-center mb-4">
                <h2 class="mb-0 pb-2">Današnje Rezervacije</h2>
                <p class=" page-subtitle"><?= date('l, d. M') ?></p>
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
                        <label class="form-label small opacity-50 mb-1">Pretraži po imenu, emailu ili telefonu...</label>
                        <input type="text" name="search" id="searchInput" class="form-control custom-input"
                            placeholder="Pretraga..."
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

            <?php if (empty($paginatedTodayReservations)): ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-calendar-xmark fa-3x mb-3 opacity-10"></i>
                    <h3 class="text-white opacity-25 fw-light">Nema rezervacija.</h3>
                </div>
            <?php else: ?>
                <div class="reservations-table rt-table-actions">
                    <div class="rt-head">
                        <div class="rt-row">
                            <div class="rt-cell rt-cell-time">Vreme</div>
                            <div class="rt-cell rt-cell-guest">Gost</div>
                            <div class="rt-cell rt-cell-email">Kontakt</div>
                            <div class="rt-cell rt-cell-tables">Stolovi</div>
                            <div class="rt-cell rt-cell-status">Status</div>
                            <div class="rt-cell rt-cell-arrival">Akcije</div>
                        </div>
                    </div>
                    <div class="rt-body">
                        <?php foreach ($paginatedTodayReservations as $item):
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
                                            onclick="openCheckInModal(<?= (int)$res['id'] ?>, <?= htmlspecialchars(json_encode($userName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode(substr($res['start_time'], 0, 5) . ' – ' . substr($res['end_time'], 0, 5), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES) ?>)">
                                            <i class="fa-solid fa-check me-2"></i>Prijavi
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($arrived && !$hasComment): ?>
                                        <button type="button" class="btn btn-outline-light btn-sm"
                                            onclick="openCommentModal(<?= $res['id'] ?>, '<?= htmlspecialchars($res['reservation_code']) ?>', '<?= htmlspecialchars($userName) ?>', '<?= htmlspecialchars($resUser['email'] ?? '') ?>', '<?= htmlspecialchars($resUser['phone'] ?? '') ?>', '<?= htmlspecialchars(implode(', ', $tables)) ?>', '<?= substr($res['start_time'], 0, 5) ?> – <?= substr($res['end_time'], 0, 5) ?>', '')">
                                            <i class="fa-solid fa-plus me-2"></i>Komentar
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($arrived && $hasComment): ?>
                                        <button type="button" class="btn btn-outline-light btn-sm"
                                            onclick="openCommentModal(<?= $res['id'] ?>, '<?= htmlspecialchars($res['reservation_code']) ?>', '<?= htmlspecialchars($userName) ?>', '<?= htmlspecialchars($resUser['email'] ?? '') ?>', '<?= htmlspecialchars($resUser['phone'] ?? '') ?>', '<?= htmlspecialchars(implode(', ', $tables)) ?>', '<?= substr($res['start_time'], 0, 5) ?> – <?= substr($res['end_time'], 0, 5) ?>', '<?= htmlspecialchars($res['comment']) ?>')">
                                            <i class="fa-regular fa-note-sticky me-2"></i>Komentar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($totalStaffPages > 1): ?>
                    <div class="pagination-wrap">
                        <?php if ($page > 1): ?>
                            <a href="?status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($searchQuery) ?>&page=<?= $page - 1 ?>" class="page-btn">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalStaffPages; $i++): ?>
                            <a href="?status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($searchQuery) ?>&page=<?= $i ?>"
                                class="page-btn <?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($page < $totalStaffPages): ?>
                            <a href="?status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($searchQuery) ?>&page=<?= $page + 1 ?>" class="page-btn">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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

    <div class="modal fade" id="checkInModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card p-0" style="max-width: 520px;">
                <div class="modal-header border-0 pb-0 d-flex align-items-center justify-content-between px-4 pt-4">
                    <h5 class="modal-title text-white m-0 fs-5">
                        <i class="fa-solid fa-check me-2 opacity-50"></i>Prijava gosta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Zatvori"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <input type="hidden" id="checkInReservationId" value="">

                    <div class="p-3 rounded-3 mb-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 40px; height: 40px; background: rgba(74,106,106,0.3);">
                                <i class="fa-solid fa-user-check text-white" style="opacity: 0.75;"></i>
                            </div>
                            <div style="min-width: 0;">
                                <div class="text-white fw-medium" id="checkInGuestName" style="font-size: 0.95rem;"></div>
                                <div class="small" id="checkInReservationTime" style="opacity: 0.6;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small mb-2" style="opacity: 0.6;">Unesite kod rezervacije</label>
                        <input type="hidden" id="checkInReservationCode" value="">
                        <div class="otp-code-inputs" aria-label="Kod rezervacije">
                            <?php for ($i = 0; $i < 8; $i++): ?>
                                <input type="text" class="form-control custom-input otp-code-digit"
                                    inputmode="text" maxlength="1" autocomplete="off"
                                    data-check-in-code-part
                                    aria-label="Karakter koda <?= $i + 1 ?>">
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div id="checkInError" class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mb-2 text-center d-none"></div>
                    <div id="checkInSuccess" class="alert border-0 text-white small rounded-3 mb-2 text-center d-none" style="background: rgba(74,106,106,0.2);"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-light btn-sm px-3" data-bs-dismiss="modal">Zatvori</button>
                    <button class="btn btn-cta btn-sm px-4" id="confirmCheckInBtn" disabled>Prijavi</button>
                </div>
            </div>
        </div>
    </div>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>

    <script>
        function openCheckInModal(reservationId, guestName, reservationTime) {
            document.getElementById('checkInReservationId').value = reservationId;
            document.getElementById('checkInGuestName').textContent = guestName || 'Nepoznat gost';
            document.getElementById('checkInReservationTime').textContent = reservationTime || '—';

            resetCheckInCode();
            document.getElementById('confirmCheckInBtn').disabled = true;
            document.getElementById('checkInError').classList.add('d-none');
            document.getElementById('checkInSuccess').classList.add('d-none');

            const modalEl = document.getElementById('checkInModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            modalEl.addEventListener('shown.bs.modal', () => getCheckInCodeInputs()[0]?.focus(), {
                once: true
            });
        }

        async function confirmCheckIn() {
            const reservationId = document.getElementById('checkInReservationId').value;
            const reservationCode = document.getElementById('checkInReservationCode').value.trim();
            const errorEl = document.getElementById('checkInError');
            const successEl = document.getElementById('checkInSuccess');
            const btn = document.getElementById('confirmCheckInBtn');

            errorEl.classList.add('d-none');
            successEl.classList.add('d-none');

            if (!reservationCode) {
                errorEl.textContent = 'Morate uneti kod rezervacije.';
                errorEl.classList.remove('d-none');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Provera...';

            try {
                const response = await fetch('<?= Helpers::getRelativePath("src/actions/checkin.php") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        reservation_id: parseInt(reservationId),
                        reservation_code: reservationCode
                    })
                });

                const result = await response.json();

                if (result.success) {
                    successEl.textContent = result.message;
                    successEl.classList.remove('d-none');
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('checkInModal')).hide();
                        window.location.reload();
                    }, 700);
                } else {
                    errorEl.textContent = result.message;
                    errorEl.classList.remove('d-none');
                }
            } catch (e) {
                errorEl.textContent = 'Došlo je do greške. Pokušajte ponovo.';
                errorEl.classList.remove('d-none');
            } finally {
                btn.disabled = document.getElementById('checkInReservationCode').value.trim().length !== 8;
                btn.textContent = 'Prijavi';
            }
        }

        document.getElementById('confirmCheckInBtn').addEventListener('click', confirmCheckIn);

        function getCheckInCodeInputs() {
            return Array.from(document.querySelectorAll('[data-check-in-code-part]'));
        }

        function normalizeReservationCode(value) {
            return value.toUpperCase().replace(/[^A-F0-9]/g, '').slice(0, 8);
        }

        function syncCheckInCode() {
            const code = getCheckInCodeInputs().map((input) => input.value).join('');
            document.getElementById('checkInReservationCode').value = code;
            document.getElementById('confirmCheckInBtn').disabled = code.length !== 8;
        }

        function resetCheckInCode() {
            getCheckInCodeInputs().forEach((input) => {
                input.value = '';
            });
            syncCheckInCode();
        }

        function fillCheckInCode(value, startIndex = 0) {
            const chars = normalizeReservationCode(value).split('');
            const inputs = getCheckInCodeInputs();

            inputs.slice(startIndex).forEach((input) => {
                input.value = '';
            });

            chars.forEach((char, offset) => {
                const input = inputs[startIndex + offset];
                if (input) {
                    input.value = char;
                }
            });

            syncCheckInCode();
            const nextInput = inputs[Math.min(startIndex + chars.length, inputs.length - 1)];
            nextInput?.focus();
        }

        getCheckInCodeInputs().forEach((input, index, inputs) => {
            input.addEventListener('input', function() {
                fillCheckInCode(this.value, index);
            });

            input.addEventListener('paste', function(event) {
                event.preventDefault();
                fillCheckInCode(event.clipboardData.getData('text'), index);
            });

            input.addEventListener('keydown', function(event) {
                if (event.key === 'Backspace' && !this.value && index > 0) {
                    inputs[index - 1].focus();
                    inputs[index - 1].value = '';
                    syncCheckInCode();
                    return;
                }

                if (event.key === 'ArrowLeft' && index > 0) {
                    event.preventDefault();
                    inputs[index - 1].focus();
                    return;
                }

                if (event.key === 'ArrowRight' && index < inputs.length - 1) {
                    event.preventDefault();
                    inputs[index + 1].focus();
                    return;
                }

                if (event.key === 'Enter' && document.getElementById('checkInReservationCode').value.length === 8) {
                    event.preventDefault();
                    confirmCheckIn();
                }
            });
        });

        function setStatusFilter(status) {
            const search = document.getElementById('searchInput').value;
            let url = '?status=' + status;
            if (search) url += '&search=' + encodeURIComponent(search);
            url += '&page=1';
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