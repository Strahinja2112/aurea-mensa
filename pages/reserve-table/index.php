<?php

declare(strict_types=1);

require_once '../../init.php';

Auth::requireAuth();

$tables = db()->restaurantTables->findMany([
        'orderBy' => ['id' => 'ASC']
]);

$today = date('Y-m-d');
?>

<!doctype html>
<html lang="sr">
<?php Helpers::renderHead("Rezervacija", ['auth.css']); ?>
<body>

<?php require_once Helpers::getFullPath('components', 'header.php'); ?>

<main>
    <div class="container d-flex justify-content-center">
        <div class="glass-card" style="max-width: 850px;">
            <h2 class="text-center mb-4">Rezervacija stola</h2>

            <form id="reservationForm">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="form-label">Datum rezervacije</label>
                        <input
                                type="date"
                                name="reservation_date"
                                class="form-control custom-input"
                                min="<?= $today ?>"
                                required
                        >
                    </div>

                    <div class="col-md-6 form-group">
                        <label class="form-label">Vreme početka</label>
                        <input
                                type="time"
                                name="start_time"
                                class="form-control custom-input"
                                required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Trajanje rezervacije</label>
                    <select name="duration_hours" class="form-control custom-input" required>
                        <option value="">Izaberite trajanje</option>
                        <option value="1">1 sat</option>
                        <option value="2">2 sata</option>
                        <option value="3">3 sata</option>
                        <option value="4">4 sata</option>
                        <option value="5">5 sati</option>
                        <option value="6">6 sati</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Izaberite sto</label>

                    <div class="row g-3">
                        <?php foreach ($tables as $table): ?>
                            <div class="col-md-6">
                                <label class="table-card d-block p-3 rounded-3">
                                    <input
                                            type="checkbox"
                                            name="table_ids[]"
                                            value="<?= htmlspecialchars((string)$table['id']) ?>"
                                    >

                                    <span class="ms-2 text-white fw-bold">
                                        <?= htmlspecialchars($table['name']) ?>
                                    </span>

                                    <div class="small opacity-75 mt-2">
                                        Broj mesta: <?= htmlspecialchars((string)$table['seats']) ?>
                                    </div>

                                    <div class="small opacity-50">
                                        <?= htmlspecialchars($table['location_description']) ?>
                                    </div>

                                    <div class="small mt-1">
                                        <?= $table['is_smoking'] ? 'Pušački deo' : 'Nepušački deo' ?>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="reservationMessage" class="mt-4"></div>

                <div class="register-btn-wrapper">
                    <div class="button">
                        <button type="submit" id="submitBtn">
                            <span id="btnText">REZERVIŠI</span>
                            <span id="btnSpinner" class="d-none">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                SLANJE...
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once Helpers::getFullPath("components", "footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.getElementById("reservationForm");
        const messageBox = document.getElementById("reservationMessage");
        const submitBtn = document.getElementById("submitBtn");
        const btnText = document.getElementById("btnText");
        const btnSpinner = document.getElementById("btnSpinner");

        const tableCards = document.querySelectorAll(".table-card");

        tableCards.forEach(card => {
            const checkbox = card.querySelector("input[type='checkbox']");

            checkbox.addEventListener("change", () => {
                if (checkbox.checked) {
                    card.classList.add("selected");
                } else {
                    card.classList.remove("selected");
                }
            });
        });

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            messageBox.innerHTML = "";

            const formData = new FormData(form);

            const selectedTables = formData.getAll("table_ids[]").map(id => Number(id));

            if (selectedTables.length === 0) {
                messageBox.innerHTML = `
                    <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3">
                        Morate izabrati bar jedan sto.
                    </div>
                `;
                return;
            }

            const payload = {
                table_ids: selectedTables,
                reservation_date: formData.get("reservation_date"),
                start_time: formData.get("start_time"),
                duration_hours: Number(formData.get("duration_hours"))
            };

            submitBtn.disabled = true;
            btnText.classList.add("d-none");
            btnSpinner.classList.remove("d-none");

            try {
                const response = await fetch("<?= Helpers::getRelativePath('src/actions/create_reservation.php') ?>", {
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

                    tableCards.forEach(card => {
                        card.classList.remove("selected");
                    });
                } else {
                    messageBox.innerHTML = `
                        <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            ${result.message}
                        </div>
                    `;
                }
            } catch (error) {
                messageBox.innerHTML = `
                    <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3">
                        Greška pri komunikaciji sa serverom.
                    </div>
                `;
            } finally {
                submitBtn.disabled = false;
                btnText.classList.remove("d-none");
                btnSpinner.classList.add("d-none");
            }
        });
    });
</script>

<style>
    .table-card {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.08);
        cursor: pointer;
        transition: 0.3s;
        height: 100%;
    }

    .table-card:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
    }

    .table-card.selected {
        border-color: #ffa07f;
        background: rgba(255, 160, 127, 0.15);
    }

    .table-card input {
        transform: scale(1.2);
        accent-color: #ffa07f;
    }

    #reservationMessage strong {
        letter-spacing: 2px;
    }

    select.custom-input {
        background-color: rgba(255, 255, 255, 0.06);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    select.custom-input option {
        background-color: #1e1e1e;
        color: #fff;
    }

</style>

</body>
</html>