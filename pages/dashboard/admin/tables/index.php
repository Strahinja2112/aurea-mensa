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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            db()->restaurantTables->create([
                'data' => [
                    'name' => trim((string)$_POST['name']),
                    'seats' => max(1, min(20, (int)$_POST['seats'])),
                    'location_description' => trim((string)($_POST['location'] ?? '')) ?: null,
                    'is_smoking' => isset($_POST['is_smoking']) ? 1 : 0,
                    'layout_x' => 50,
                    'layout_y' => 50,
                    'table_shape' => in_array($_POST['table_shape'] ?? 'round', ['round', 'square'], true)
                        ? $_POST['table_shape']
                        : 'round',
                ]
            ]);
            $message = 'Sto je uspešno dodat. Prevucite ga na željeno mesto i sačuvajte raspored.';
            $messageType = 'success';
        } elseif ($action === 'delete') {
            db()->restaurantTables->delete([
                'where' => ['id' => (int)$_POST['id']]
            ]);
            $message = 'Sto je obrisan.';
            $messageType = 'success';
        }
    } catch (Exception $exception) {
        $message = $exception->getMessage();
        $messageType = 'danger';
    }
}

$tables = db()->restaurantTables->findMany(['orderBy' => ['id' => 'ASC']]);
$tableLayoutData = array_map(static function (array $table, int $index): array {
    $defaultX = [18, 42, 66, 82][$index % 4];
    $defaultY = min(88, 24 + (floor($index / 4) * 24));

    return [
        'id' => (int)$table['id'],
        'name' => (string)$table['name'],
        'seats' => (int)$table['seats'],
        'location_description' => $table['location_description'] ?? '',
        'is_smoking' => (int)($table['is_smoking'] ?? 0),
        'layout_x' => isset($table['layout_x']) ? (float)$table['layout_x'] : $defaultX,
        'layout_y' => isset($table['layout_y']) ? (float)$table['layout_y'] : $defaultY,
        'table_shape' => in_array($table['table_shape'] ?? 'round', ['round', 'square'], true)
            ? $table['table_shape']
            : 'round',
    ];
}, $tables, array_keys($tables));
$tableLayoutJson = json_encode($tableLayoutData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$relativePath = Helpers::getRelativePath("/pages/dashboard/admin/");

?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead("Stolovi", ['dashboard.css', 'table-layout.css']) ?>

<body>
    <?php include Helpers::getRelativePath('src/components/header.php') ?>

    <main>
        <div class="dashboard-container">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="admin-title">Stolovi</h1>
                    <p class="admin-subtitle">Vizuelno upravljanje rasporedom restorana</p>
                </div>
                <a href="<?= $relativePath . '/dashboard/admin' ?>" class="btn btn-outline-light btn-lg">
                    <i class="fa-solid fa-arrow-left me-2"></i> Nazad
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> bg-<?= $messageType ?> bg-opacity-20 border-0 text-white small rounded-3 mb-4">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div
                class="table-layout-shell"
                data-table-layout
                data-mode="admin"
                data-tables-source="adminTableLayoutData"
                data-save-url="<?= Helpers::getRelativePath('src/actions/save_tables_layout.php') ?>"
            >
                <section class="glass-card p-4 border-0 table-layout-map-wrap">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                        <h5 class="text-white mb-0 font-serif">Mapa restorana</h5>
                        <div class="layout-legend">
                            <span><i></i> Slobodan sto</span>
                            <span><i class="selected-dot"></i> Izabran sto</span>
                        </div>
                    </div>
                    <div class="table-layout-map" aria-label="Mapa stolova za uređivanje"></div>
                </section>

                <aside class="table-layout-panel">
                    <div class="glass-card p-4 border-0 mb-4">
                        <h5 class="text-white mb-3 font-serif">Dodaj novi sto</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label class="form-label small opacity-50">Naziv stola</label>
                                <input type="text" name="name" class="form-control custom-input" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small opacity-50">Broj mesta</label>
                                <input type="number" name="seats" class="form-control custom-input" min="1" max="20" value="4" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small opacity-50">Lokacija</label>
                                <input type="text" name="location" class="form-control custom-input">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small opacity-50">Oblik</label>
                                <select name="table_shape" class="form-control custom-input">
                                    <option value="round">Okrugli</option>
                                    <option value="square">Kvadratni</option>
                                </select>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="is_smoking" id="is_smoking" class="form-check-input">
                                <label for="is_smoking" class="form-check-label text-white small">Pušački deo</label>
                            </div>
                            <button type="submit" class="btn btn-light w-100 py-2 fw-bold">
                                <i class="fa-solid fa-plus me-2"></i> Dodaj sto
                            </button>
                        </form>
                    </div>

                    <div class="glass-card p-4 border-0">
                        <h5 class="text-white mb-3 font-serif">Izabrani sto</h5>
                        <?php if (empty($tables)): ?>
                            <p class="text-white opacity-50 small mb-0">Dodajte prvi sto da biste uredili detalje.</p>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label small opacity-50">Naziv stola</label>
                                <input type="text" id="layoutTableName" class="form-control custom-input">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small opacity-50">Broj mesta</label>
                                <div class="seat-stepper">
                                    <button type="button" id="layoutDecreaseSeats" class="btn btn-outline-light" title="Smanji broj mesta">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <output id="layoutSeatCount">1</output>
                                    <button type="button" id="layoutIncreaseSeats" class="btn btn-outline-light" title="Povećaj broj mesta">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small opacity-50">Lokacija</label>
                                <input type="text" id="layoutTableLocation" class="form-control custom-input">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small opacity-50">Oblik</label>
                                <div class="shape-toggle">
                                    <button type="button" id="layoutShapeRound" class="btn btn-outline-light">
                                        <i class="fa-regular fa-circle me-2"></i> Okrugli
                                    </button>
                                    <button type="button" id="layoutShapeSquare" class="btn btn-outline-light">
                                        <i class="fa-regular fa-square me-2"></i> Kvadratni
                                    </button>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" id="layoutTableSmoking" class="form-check-input">
                                <label for="layoutTableSmoking" class="form-check-label text-white small">Pušački deo</label>
                            </div>

                            <div id="tableLayoutStatus" class="layout-status mb-3"></div>

                            <button type="button" id="saveLayoutButton" class="btn btn-light w-100 py-2 fw-bold mb-3">
                                <i class="fa-solid fa-floppy-disk me-2"></i> Sačuvaj raspored
                            </button>

                            <form method="POST" onsubmit="return confirm('Obrisati izabrani sto?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" id="deleteTableId" value="">
                                <button type="submit" class="btn btn-outline-danger w-100 py-2">
                                    <i class="fa-solid fa-trash me-2"></i> Obriši izabrani sto
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <script type="application/json" id="adminTableLayoutData"><?= $tableLayoutJson ?></script>
    <script src="<?= Helpers::getRelativePath('src/scripts/table-layout.js') ?>"></script>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>
</body>

</html>
