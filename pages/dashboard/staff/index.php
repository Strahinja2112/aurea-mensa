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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['reservation_code'] ?? '';

    if (empty($code)) {
        $error = 'Please enter a reservation code.';
    } else {
        $reservation = db()->reservations->findFirst([
                'where' => [
                        'reservation_code' => $code,
                        'status' => 'ACTIVE',
                ],
        ]);

        if (!$reservation) {
            $error = 'Reservation not found or already processed.';
        } else {
            $existingLog = db()->reservationLogs->findFirst([
                    'where' => [
                            'reservation_id' => $reservation['id'],
                            'arrived' => 1,
                    ],
            ]);

            if ($existingLog) {
                $error = 'Guest has already checked in.';
            } else {
                db()->reservationLogs->create([
                        'data' => [
                                'reservation_id' => $reservation['id'],
                                'staff_id' => $user->id,
                                'arrived' => 1,
                                'comment' => $_POST['comment'] ?? null,
                        ],
                ]);
                $message = 'Guest checked in successfully!';
            }
        }
    }
}

$pageTitle = 'Staff Dashboard';
?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead($pageTitle, ['auth.css', 'profile.css', 'dashboard.css']) ?>

<body>
<?php include Helpers::getRelativePath('src/components/header.php') ?>

<main>
    <div class="dashboard-container">
        <div class="dashboard-left">
            <div class="glass-card">
                <h2 class="text-center mb-4">Check In</h2>

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

                <form method="post">
                    <div class="form-group mb-3">
                        <label class="form-label opacity-50">Reservation Code</label>
                        <input type="text" name="reservation_code" class="form-control custom-input"
                               placeholder="e.g., ABC123XYZ" required>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label opacity-50">Comment (optional)</label>
                        <textarea name="comment" class="form-control custom-input"
                                  rows="3" placeholder="Any notes..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-light btn-lg w-100">
                        <i class="fa-solid fa-check me-2"></i>Check In Guest
                    </button>
                </form>
            </div>
        </div>

        <div class="dashboard-right">
            <div class="glass-card" style="max-width: 1000px !important;">
                <h2 class="text-center mb-4">Today's Reservations</h2>

                <div class="filter-bar mb-4">
                    <input type="text" id="searchInput" class="form-control custom-input"
                           placeholder="Search by code, name, email, or phone..."
                           value="<?= htmlspecialchars($searchQuery) ?>">

                    <select id="statusFilter" class="form-control custom-input" onchange="applyFilters()">
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Only
                        </option>
                        <option value="checked_in" <?= $statusFilter === 'checked_in' ? 'selected' : '' ?>>Checked In
                            Only
                        </option>
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All</option>
                    </select>

                    <button type="button" class="btn btn-outline-light btn-lg" onclick="applyFilters()">
                        <i class="fa-solid fa-search"></i>
                    </button>
                </div>

                <?php if (empty($todayReservations)): ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-calendar-xmark fa-3x mb-3 opacity-10"></i>
                        <h3 class="text-white opacity-25 fw-light">No reservations found.</h3>
                    </div>
                <?php else: ?>
                    <div class="res-cards-list">
                        <?php foreach ($todayReservations as $item): ?>
                            <?php
                            $res = $item['reservation'];
                            $tableNames = $item['tables'];
                            $arrived = $item['arrived'];
                            $log = $item['log'];
                            $resUser = $item['user'];

                            $time = substr($res['start_time'], 0, 5);
                            $endTime = substr($res['end_time'], 0, 5);
                            $userName = trim(($resUser['first_name'] ?? '') . ' ' . ($resUser['last_name'] ?? ''));
                            if (!$userName) {
                                $userName = $resUser['email'] ?? 'Unknown';
                            }
                            ?>

                            <div class="res-card <?= $arrived ? 'checked-in' : '' ?>">
                                <div class="res-card-header">
                                    <div class="res-time">
                                        <span class="time-start"><?= htmlspecialchars($time) ?></span>
                                        <span class="time-separator">-</span>
                                        <span class="time-end"><?= htmlspecialchars($endTime) ?></span>
                                    </div>
                                    <div class="res-status <?= $arrived ? 'arrived' : 'pending' ?>">
                                        <?= $arrived ? 'Checked In' : 'Pending' ?>
                                    </div>
                                </div>

                                <div class="res-card-body">
                                    <div class="res-code"><?= htmlspecialchars($res['reservation_code']) ?></div>
                                    <div class="res-user">
                                        <i class="fa-solid fa-user"></i>
                                        <span><?= htmlspecialchars($userName) ?></span>
                                    </div>
                                    <div class="res-contact">
                                        <i class="fa-solid fa-envelope"></i>
                                        <span><?= htmlspecialchars($resUser['email'] ?? '') ?></span>
                                    </div>
                                    <div class="res-contact">
                                        <i class="fa-solid fa-phone"></i>
                                        <span><?= htmlspecialchars($resUser['phone'] ?? 'No phone') ?></span>
                                    </div>
                                    <div class="res-tables">
                                        <i class="fa-solid fa-chair"></i>
                                        <span><?= htmlspecialchars(implode(', ', $tableNames)) ?></span>
                                    </div>
                                    <?php if (!empty($log['comment'])): ?>
                                        <div class="res-comment">
                                            <i class="fa-solid fa-comment"></i>
                                            <span><?= htmlspecialchars($log['comment']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="res-card-footer">
                                    <span class="res-date"><?= date('d M Y', strtotime($res['reservation_date'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include Helpers::getRelativePath('src/components/footer.php') ?>

<script>
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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>