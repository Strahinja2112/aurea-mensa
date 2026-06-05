<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../autoload.php';

$user = Auth::getUser();

if (!$user || !$user->isAdmin()) {
    http_response_code(403);
    header('Location: ' . Helpers::getRelativePath('pages/auth/login'));
    exit;
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');

$reservations = db()->reservations->findMany([
    'where' => ['reservation_date' => $selectedDate],
    'orderBy' => ['start_time' => 'ASC'],
]);

$reservationUsers = [];
$reservationTables = [];

foreach ($reservations as $res) {
    $u = db()->users->findFirst(['where' => ['id' => $res['user_id']]]);
    $reservationUsers[$res['id']] = $u;

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

$relativePath = Helpers::getRelativePath("/pages/dashboard/admin/");

?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead("Rezervacije", ['dashboard.css']) ?>

<body>
    <?php include Helpers::getRelativePath('src/components/header.php') ?>

    <main>
        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="admin-title">Rezervacije</h1>
                    <p class="admin-subtitle">Pregled rezervacija po datumu</p>
                </div>
                <a href="<?= $relativePath . '/dashboard/admin' ?>" class="btn btn-outline-light btn-lg">
                    <i class="fa-solid fa-arrow-left me-2"></i> Nazad
                </a>
            </div>

            <div class="glass-card p-4 border-0" style="max-width: 100%;">
                <form method="GET" class="mb-4 pb-4" style="border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                    <label class="form-label small opacity-50">Izaberite datum</label>
                    <div class="d-flex gap-2">
                        <input type="date" name="date" class="form-control custom-input" value="<?= htmlspecialchars($selectedDate) ?>" required>
                        <button type="submit" class="btn btn-light px-4 fw-bold">Prikaži</button>
                    </div>
                </form>

                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="text-white mb-0" style="font-weight: 300; letter-spacing: 0.5px;">
                        Rezervacije za <?= (DateTime::createFromFormat('Y-m-d', $selectedDate))->format('d. m. Y.') ?>
                    </h5>
                    <span class="small opacity-50">
                        Ukupno: <?= count($reservations) ?> rezervacija
                    </span>
                </div>

                <?php if (empty($reservations)): ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-calendar-xmark fa-3x mb-3 opacity-10"></i>
                        <p class="text-white opacity-25 fw-light mb-0">Nema rezervacija za ovaj datum.</p>
                    </div>
                <?php else: ?>
                    <div class="reservations-table">
                        <div class="rt-head">
                            <div class="rt-row">
                                <div class="rt-cell rt-cell-code">Kod</div>
                                <div class="rt-cell rt-cell-time">Vreme</div>
                                <div class="rt-cell rt-cell-guest">Gost</div>
                                <div class="rt-cell rt-cell-email">Email</div>
                                <div class="rt-cell rt-cell-tables">Stolovi</div>
                                <div class="rt-cell rt-cell-status">Status</div>
                                <div class="rt-cell rt-cell-arrival">Dolazak</div>
                            </div>
                        </div>
                        <div class="rt-body">
                            <?php foreach ($reservations as $res):
                                $u = $reservationUsers[$res['id']] ?? null;
                                $tables = $reservationTables[$res['id']] ?? [];

                                $statusLabel = match ($res['status']) {
                                    'ACTIVE' => 'Aktivna',
                                    'COMPLETED' => 'Realizovana',
                                    'CANCELLED' => 'Otkazana',
                                    default => $res['status'],
                                };
                                ?>
                                <div class="rt-row">
                                    <div class="rt-cell rt-cell-code" data-label="Kod">
                                        <code><?= htmlspecialchars($res['reservation_code']) ?></code>
                                    </div>
                                    <div class="rt-cell rt-cell-time" data-label="Vreme">
                                        <?= substr($res['start_time'], 0, 5) ?> – <?= substr($res['end_time'], 0, 5) ?>
                                    </div>
                                    <div class="rt-cell rt-cell-guest" data-label="Gost">
                                        <?php if ($u): ?>
                                            <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                        <?php else: ?>
                                            <span class="opacity-25">Nepoznat</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="rt-cell rt-cell-email" data-label="Email">
                                        <?= $u ? htmlspecialchars($u['email']) : '—' ?>
                                    </div>
                                    <div class="rt-cell rt-cell-tables" data-label="Stolovi">
                                        <?= !empty($tables) ? htmlspecialchars(implode(', ', $tables)) : '—' ?>
                                    </div>
                                    <div class="rt-cell rt-cell-status" data-label="Status">
                                        <span class="status-badge status-<?= strtolower($res['status']) ?>">
                                            <?= $statusLabel ?>
                                        </span>
                                    </div>
                                    <div class="rt-cell rt-cell-arrival" data-label="Dolazak">
                                        <?php if ($res['arrived']): ?>
                                            <i class="fa-solid fa-check-circle" style="color: #2ecc71;"></i>
                                        <?php else: ?>
                                            <span class="opacity-25">—</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>

    <style>
        .reservations-table {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            overflow: hidden;
        }

        .rt-head {
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .rt-row {
            display: grid;
            grid-template-columns: 140px 100px 1fr 1fr 120px 100px 70px;
            align-items: center;
            padding: 14px 20px;
            gap: 12px;
        }

        .rt-body .rt-row {
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            transition: background 0.2s ease;
        }

        .rt-body .rt-row:last-child {
            border-bottom: none;
        }

        .rt-body .rt-row:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .rt-head .rt-cell {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.4);
        }

        .rt-cell {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rt-cell code {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
            background: rgba(255, 255, 255, 0.05);
            padding: 2px 8px;
            border-radius: 4px;
        }

        .rt-cell-time {
            font-weight: 300;
            color: #f8f9fa;
            letter-spacing: 0.3px;
        }

        .rt-cell-email {
            font-size: 0.8rem;
            opacity: 0.6;
        }

        .rt-cell-tables {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .status-badge {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(255, 193, 7, 0.12);
            color: #ffc107;
        }

        .status-completed {
            background: rgba(46, 204, 113, 0.12);
            color: #2ecc71;
        }

        .status-cancelled {
            background: rgba(255, 77, 77, 0.1);
            color: #ff4d4d;
        }

        .rt-cell-arrival {
            text-align: center;
        }

        @media (max-width: 900px) {
            .rt-row {
                grid-template-columns: 1fr 1fr;
                gap: 4px 12px;
                padding: 12px 16px;
            }

            .rt-head {
                display: none;
            }

            .rt-cell::before {
                content: attr(data-label);
                font-size: 0.6rem;
                font-weight: 600;
                letter-spacing: 1px;
                text-transform: uppercase;
                color: rgba(255, 255, 255, 0.3);
                display: block;
                margin-bottom: 2px;
            }

            .rt-cell-code,
            .rt-cell-time,
            .rt-cell-guest,
            .rt-cell-email,
            .rt-cell-tables,
            .rt-cell-status,
            .rt-cell-arrival {
                grid-column: auto;
            }

            .rt-cell-arrival {
                text-align: left;
            }
        }

        @media (max-width: 500px) {
            .rt-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }
        }
    </style>

</body>

</html>