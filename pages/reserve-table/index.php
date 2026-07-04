<?php

declare(strict_types=1);

require_once '../../autoload.php';

Auth::requireAuth();

$user = Auth::getUser();

$isEditMode = false;
$reservation = null;
$reservedTableIds = [];

if (isset($_GET['id'])) {

    $reservationId = (int)$_GET['id'];

    $reservation = db()->reservations->findFirst([
            'where' => [
                    'id' => $reservationId,
                    'user_id' => $user->id,
                    'status' => 'ACTIVE'
            ]
    ]);

    if (!$reservation) {
        http_response_code(302);
        header('Location: ' . Helpers::getRelativePath('pages/profile'));
        exit;
    }

    $reservationTables = db()->reservationTables->findMany([
            'where' => [
                    'reservation_id' => $reservationId
            ]
    ]);

    foreach ($reservationTables as $table) {
        $reservedTableIds[] = (int)$table['table_id'];
    }

    $isEditMode = true;
}

$allowedTableShapes = ['round', 'square', 'rectangle', 'oval', 'corner'];
$tables = db()->restaurantTables->findMany([
        'orderBy' => ['id' => 'ASC']
]);

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
$workingHoursJson = json_encode([
        'weekdays' => Helpers::WEBSITE_WORKING_DAYS,
        'weekend' => Helpers::WEBSITE_WORKING_WEEKEND,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$today = date('Y-m-d');

$selectedDate = $isEditMode
        ? $reservation['reservation_date']
        : $today;

$selectedStartTime = $isEditMode
        ? substr($reservation['start_time'], 0, 5)
        : '';

$selectedDuration = 1;

if ($isEditMode) {

    $start = new DateTime($reservation['start_time']);
    $end = new DateTime($reservation['end_time']);

    $selectedDuration = $start->diff($end)->h;
}
?>

<!doctype html>
<html lang="sr">
<?php Helpers::renderHead("Rezervacija", ['auth.css', 'table-layout.css']); ?>

<body>

<?php require_once Helpers::getFullPath('src/components/header.php'); ?>

<main>
    <div class="container-fluid d-flex justify-content-center">
        <div class="glass-card reserve-layout-card">
            <!-- <div class="reserve-layout-header">
                <h2>Rezervacija termina</h2>
            </div> -->

            <form id="reservationForm">
                <input
                        type="hidden"
                        name="start_time"
                        id="reservationStartTime"
                        value="<?= htmlspecialchars($selectedStartTime, ENT_QUOTES, 'UTF-8') ?>">
                <input
                        type="hidden"
                        name="duration_hours"
                        id="reservationDurationHours"
                        value="<?= $selectedDuration ?>">
                <input
                        type="hidden"
                        name="number_of_people"
                        id="numberOfPeople"
                        value="<?= $reservation['number_of_people'] ?? 2 ?>">

                <input
                        type="hidden"
                        name="is_smoking"
                        id="isSmoking"
                        value="">
                <input
                        type="hidden"
                        name="people"
                        id="reservationPeople"
                        value="1">

                <section class="reservation-timeline-panel mb-1">
                    <div class="reserve-time-header">
                        <div class="reserve-date-row">
                            <input
                                    type="date"
                                    name="reservation_date"
                                    class="form-control custom-input"
                                    min="<?= $today ?>"
                                    value="<?= $selectedDate ?>"
                                    required>
                        </div>

                        <div class="reserve-time-copy">
                            <label class="form-label mb-1">Dostupni termini</label>
                            <p class="reservation-timeline-summary" id="reservationTimelineSummary">
                                <?= $isEditMode
                                        ? 'Izmenite termin ili stolove po želji.'
                                        : 'Izaberite datum i sto da biste videli termine.' ?>
                            </p>
                        </div>
                    </div>

                    <div id="reservationTimeline" class="reservation-timeline"></div>
                    <div id="reservationTimeframes" class="reservation-timeframes"></div>
                </section>

                <section class="reserve-map-section">
                    <div class="reserve-map-grid">
                        <div
                                data-table-layout
                                data-mode="reserve"
                                data-tables-source="reserveTableLayoutData"
                                data-form-id="reservationForm"
                                data-selected-inputs="selectedTableInputs"
                                data-availability-url="<?= Helpers::getRelativePath('src/actions/table_availability.php') ?>"
                                data-timeframes-url="<?= Helpers::getRelativePath('src/actions/table_timeframes.php') ?>"
                                data-working-hours-source="restaurantWorkingHours">
                            <div class="table-layout-map reserve-map" aria-label="Mapa stolova za rezervaciju"></div>
                        </div>

                        <aside class="reserve-table-info-panel" id="reserveTableInfoPanel">

                            <div id="reserveTableInfoEmpty" class="reserve-empty-state">

                                <div class="reserve-empty-icon">
                                    <i class="fa-solid fa-hand-pointer"></i>
                                </div>

                                <h5>Izaberite sto</h5>

                                <p>
                                    Kliknite na sto na mapi kako biste videli njegove informacije
                                    i rezervisali termin.
                                </p>

                            </div>

                            <div id="reserveTableInfo" class="d-none">

                                <div class="layout-control-group">

                                    <label class="layout-field-label">Naziv stola</label>

                                    <input
                                            id="reserveInfoName"
                                            class="form-control custom-input"
                                            disabled>

                                    <label class="layout-field-label mt-3">Lokacija</label>

                                    <input
                                            id="reserveInfoLocation"
                                            class="form-control custom-input"
                                            disabled>

                                </div>

                                <div class="layout-control-group">

                                    <div class="layout-group-title">
                                        <i class="fa-solid fa-chair"></i>
                                        <span>Kapacitet</span>
                                    </div>

                                    <input
                                            id="reserveInfoSeats"
                                            class="form-control custom-input"
                                            disabled>

                                </div>

                                <div class="layout-control-group">

                                    <div class="layout-group-title">
                                        <i class="fa-solid fa-shapes"></i>
                                        <span>Izgled</span>
                                    </div>

                                    <input
                                            id="reserveInfoShape"
                                            class="form-control custom-input mb-2"
                                            disabled>

                                    <input
                                            id="reserveInfoSmoking"
                                            class="form-control custom-input"
                                            disabled>

                                </div>

                                <div class="layout-control-group">

                                    <label class="layout-field-label">

                                        Status

                                    </label>

                                    <input
                                            id="reserveInfoStatus"
                                            class="form-control custom-input"
                                            disabled>

                                </div>

                                <div class="mt-4">

                                    <button
                                            type="submit"
                                            id="submitBtn"
                                            class="btn btn-light w-100">

                                        <?= $isEditMode ? 'Sačuvaj izmene' : 'Rezerviši' ?>

                                    </button>

                                </div>

                            </div>

                        </aside>
                    </div>
                    <div id="selectedTableInputs"></div>
                </section>
                <div id="reservationMessage" class="mt-3"></div>
            </form>
        </div>
    </div>
</main>

<?php require_once Helpers::getFullPath('src/components/footer.php'); ?>

<script type="application/json" id="reserveTableLayoutData">
        <?= $tableLayoutJson ?>
    </script>
<script type="application/json" id="restaurantWorkingHours">
        <?= $workingHoursJson ?>
    </script>
<script type="application/json" id="reservationEditData">
    <?= json_encode([
            'editMode' => $isEditMode,
            'reservationId' => $reservation['id'] ?? null,
            'selectedTables' => $reservedTableIds,
            'startTime' => $selectedStartTime,
            'duration' => $selectedDuration
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
</script>
<script src="<?= Helpers::getRelativePath('src/scripts/table-layout.js') ?>"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.getElementById("reservationForm");
        const messageBox = document.getElementById("reservationMessage");
        const submitBtn = document.getElementById("submitBtn");
        const btnText = document.getElementById("btnText");
        const btnSpinner = document.getElementById("btnSpinner");

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            messageBox.innerHTML = "";

            const formData = new FormData(form);
            const selectedTables = formData.getAll("table_ids[]").map(id => Number(id));

            if (selectedTables.length === 0) {
                messageBox.innerHTML = `
                    <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mt-3">
                        Morate izabrati bar jedan sto.
                    </div>
                `;
                return;
            }

            const payload = {
                reservation_id: <?= $reservation['id'] ?? 'null' ?>,
                edit_mode: <?= $isEditMode ? 'true' : 'false' ?>,
                table_ids: selectedTables,
                reservation_date: formData.get("reservation_date"),
                start_time: formData.get("start_time"),
                duration_hours: Number(formData.get("duration_hours"))
            };
            if (!payload.start_time || !payload.duration_hours) {
                messageBox.innerHTML = `
                    <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3">
                        Morate izabrati termin rezervacije.
                    </div>
                `;
                return;
            }

            submitBtn.disabled = true;
            btnText.classList.add("d-none");
            btnSpinner.classList.remove("d-none");

            try {
                const url = payload.edit_mode
                    ? "<?= Helpers::getRelativePath('src/actions/update_reservation.php') ?>"
                    : "<?= Helpers::getRelativePath('src/actions/create_reservation.php') ?>";

                const response = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    messageBox.innerHTML = `
                        <div class="alert alert-success bg-success bg-opacity-20 border-0 text-white small rounded-3 text-center">
                            <i class="fa-solid fa-check-circle me-2"></i>
                            ${result.message}<br>
                            Kod rezervacije:
                            <strong class="d-block mt-2 fs-5">${result.reservation_code}</strong>
                        </div>
                    `;

                    form.reset();

                    if (window.AureaTableLayouts?.reserve) {
                        window.AureaTableLayouts.reserve.clearSelection();
                        window.AureaTableLayouts.reserve.refreshAvailability();
                    }
                } else {
                    messageBox.innerHTML = `
                        <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            ${result.message}
                        </div>
                    `;
                }
            } catch (error) {
                console.log(error);
                messageBox.innerHTML = `
                    <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3">
                        Greška pri komunikaciji sa serverom.
                    </div>
                `;
            }

            submitBtn.disabled = false;
            btnText.classList.remove("d-none");
            btnSpinner.classList.add("d-none");
        });
    });
</script>

</body>

</html>