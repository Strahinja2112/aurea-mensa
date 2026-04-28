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

$where = [
    'reservation_date' => $today,
];

if ($statusFilter === 'pending') {
    $where['status'] = 'ACTIVE';
} elseif ($statusFilter === 'checked_in') {
    $where['status'] = 'ACTIVE';
} elseif ($statusFilter === 'all') {
    // no status filter
}

$reservations = db()->reservations->findMany([
    'where' => $where,
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
<?php Helpers::renderHead($pageTitle, ['auth.css', 'profile.css']) ?>

<body>
<?php include Helpers::getRelativePath('src/components/header.php') ?>

<main>
    <div class="container-fluid container-lg row justify-content-center g-4 w-100">
        <div class="col-12 col-lg-4">
            <div class="glass-card h-100">
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
        
        <div class="col-12 col-lg-8">
            <div class="glass-card h-100">
                <h2 class="text-center mb-4">Today's Reservations</h2>
                
                <div class="mb-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <input type="text" id="searchInput" class="form-control custom-input"
                                   placeholder="Search by code, name, email, or phone..."
                                   value="<?= htmlspecialchars($searchQuery) ?>">
                        </div>
                        <div class="col-auto">
                            <select id="statusFilter" class="form-control custom-input" onchange="applyFilters()">
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Only</option>
                                <option value="checked_in" <?= $statusFilter === 'checked_in' ? 'selected' : '' ?>>Checked In Only</option>
                                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-light btn-lg" onclick="applyFilters()">
                                <i class="fa-solid fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($todayReservations)): ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-calendar-xmark fa-3x mb-3 opacity-10"></i>
                        <h3 class="text-white opacity-25 fw-light">No reservations found.</h3>
                    </div>
                <?php else: ?>
                    <div class="reservation-container">
                        <?php foreach ($todayReservations as $item): ?>
                            <?php
                            $res = $item['reservation'];
                            $tableNames = $item['tables'];
                            $arrived = $item['arrived'];
                            $log = $item['log'];
                            $resUser = $item['user'];
                            
                            $time = substr($res['start_time'], 0, 5);
                            $endTime = substr($res['end_time'], 0, 5);
                            ?>
                            
                            <div class="res-card mb-4 <?= $arrived ? 'checked-in' : '' ?>">
                                <div class="row align-items-start g-0">
                                    <div class="col-auto date-column text-center">
                                        <span class="d-block day"><?= $time ?></span>
                                        <span class="d-block month text-uppercase">- <?= $endTime ?></span>
                                    </div>
                                    
                                    <div class="col ps-4">
                                        <div class="d-flex align-items-baseline mb-2">
                                            <span class="h2 mb-0 fw-light text-white"><?= htmlspecialchars($res['reservation_code']) ?></span>
                                        </div>
                                        
                                        <div class="res-meta opacity-50 mb-1">
                                            <i class="fa-solid fa-user me-2"></i>
                                            <?= htmlspecialchars(($resUser['first_name'] ?? '') . ' ' . ($resUser['last_name'] ?? '')) ?>
                                            <span class="opacity-50">(<?= htmlspecialchars($resUser['email'] ?? '') ?>)</span>
                                        </div>
                                        
                                        <div class="res-meta opacity-50 mb-1">
                                            <i class="fa-solid fa-phone me-2"></i>
                                            <?= htmlspecialchars($resUser['phone'] ?? 'No phone') ?>
                                        </div>
                                        
                                        <div class="res-meta opacity-50 mb-2">
                                            <i class="fa-solid fa-chair me-2"></i>
                                            Tables: <?= htmlspecialchars(implode(', ', $tableNames)) ?>
                                        </div>
                                        
                                        <?php if (!empty($log['comment'])): ?>
                                            <div class="res-meta opacity-50 mt-2 p-2 bg-white bg-opacity-5 rounded">
                                                <i class="fa-solid fa-note-sticky me-2"></i>
                                                <?= htmlspecialchars($log['comment']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-auto">
                                        <?php if ($arrived): ?>
                                            <span class="badge bg-success fs-6">Checked In</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark fs-6">Pending</span>
                                        <?php endif; ?>
                                    </div>
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

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>