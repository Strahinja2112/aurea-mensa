<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../autoload.php';

$user = Auth::getUser();

if (!$user || !$user->isAdmin()) {
    http_response_code(403);
    header('Location: ' . Helpers::getRelativePath('pages/auth/login'));
    exit;
}

$allowedTableShapes = ['round', 'square', 'rectangle', 'oval', 'corner'];
$tables = db()->restaurantTables->findMany(['orderBy' => ['id' => 'ASC']]);
$tableLayoutData = array_map(static function (array $table, int $index) use ($allowedTableShapes): array {
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
        'layout_rotation' => isset($table['layout_rotation']) ? (int)$table['layout_rotation'] : 0,
        'table_shape' => in_array($table['table_shape'] ?? 'round', $allowedTableShapes, true)
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
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" id="addLayoutTableButton" class="btn btn-light btn-lg">
                        <i class="fa-solid fa-plus me-2"></i> Dodaj sto
                    </button>
                    <a href="<?= $relativePath . '/dashboard/admin' ?>" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-arrow-left me-2"></i> Nazad
                    </a>
                </div>
            </div>

            <div
                class="table-layout-shell"
                data-table-layout
                data-mode="admin"
                data-tables-source="adminTableLayoutData"
                data-save-url="<?= Helpers::getRelativePath('src/actions/save_tables_layout.php') ?>">
                <section class="glass-card p-4 border-0 table-layout-map-wrap">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                        <h5 class="text-white mb-0 ">Mapa restorana</h5>
                        <div class="layout-legend">
                            <span><i></i> Slobodan sto</span>
                            <span><i class="selected-dot"></i> Izabran sto</span>
                        </div>
                    </div>
                    <div class="table-layout-map" aria-label="Mapa stolova za uređivanje"></div>
                </section>

                <aside class="table-layout-panel">
                    <div class="glass-card p-4 border-0">
                        <div id="layoutNoSelectionHint" class="layout-panel-empty">
                            <h5>Izaberite sto</h5>
                            <p>Kliknite sto na mapi da izmenite naziv, broj mesta, oblik i zonu.</p>
                        </div>

                        <div id="layoutInspector" class="layout-inspector d-none">
                            <fieldset id="layoutEditFields">
                                <div class="layout-control-group">
                                    <label class="layout-field-label" for="layoutTableName">Naziv stola</label>
                                    <input type="text" id="layoutTableName" class="form-control custom-input">

                                    <label class="layout-field-label mt-3" for="layoutTableLocation">Lokacija</label>
                                    <input
                                        type="text"
                                        id="layoutTableLocation"
                                        class="form-control custom-input"
                                        placeholder="npr. Pored prozora">
                                </div>

                                <div class="layout-control-group">
                                    <div class="layout-group-title">
                                        <i class="fa-solid fa-chair"></i>
                                        <span>Kapacitet</span>
                                    </div>

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

                                <div class="layout-control-group">
                                    <div class="layout-group-title">
                                        <i class="fa-solid fa-shapes"></i>
                                        <span>Izgled i zona</span>
                                    </div>

                                    <label class="layout-field-label">Oblik stola</label>
                                    <div class="shape-toggle mb-3">
                                        <button type="button" class="btn btn-outline-light" data-layout-shape="round">
                                            Okrugli
                                        </button>
                                        <button type="button" class="btn btn-outline-light" data-layout-shape="square">
                                            Kvadratni
                                        </button>
                                        <button type="button" class="btn btn-outline-light" data-layout-shape="rectangle">
                                            Pravougaoni
                                        </button>
                                        <button type="button" class="btn btn-outline-light" data-layout-shape="oval">
                                            Ovalni
                                        </button>
                                        <button type="button" class="btn btn-outline-light" data-layout-shape="corner">
                                            Ugaoni
                                        </button>
                                    </div>

                                    <label class="layout-field-label">Rotacija</label>
                                    <div class="rotation-stepper mb-3">
                                        <button type="button" id="layoutRotateLeft" class="btn btn-outline-light" title="Rotiraj ulevo">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </button>
                                        <output id="layoutRotationValue">0°</output>
                                        <button type="button" id="layoutRotateRight" class="btn btn-outline-light" title="Rotiraj udesno">
                                            <i class="fa-solid fa-rotate-right"></i>
                                        </button>
                                    </div>

                                    <label class="layout-switch" for="layoutTableSmoking">
                                        <input type="checkbox" id="layoutTableSmoking">
                                        <span></span>
                                        <strong>Pušački deo</strong>
                                    </label>
                                </div>
                            </fieldset>

                            <div id="tableLayoutStatus" class="layout-status mb-3"></div>

                            <div class="layout-action-row">
                                <button type="button" id="saveLayoutButton" class="btn btn-light py-2 fw-bold">
                                    <i class="fa-solid fa-floppy-disk me-2"></i> Sačuvaj
                                </button>

                                <button type="button" id="removeLayoutTableButton" class="btn btn-outline-danger py-2">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <script type="application/json" id="adminTableLayoutData">
        <?= $tableLayoutJson ?>
    </script>
    <script src="<?= Helpers::getRelativePath('src/scripts/table-layout.js') ?>"></script>

    <?php include Helpers::getRelativePath('src/components/footer.php') ?>
</body>

</html>
