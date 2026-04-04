<?php

declare(strict_types=1);

require_once '../../init.php';

// Zaštita pristupa putem sesija [cite: 31]
Auth::requireAuth();
$user = Auth::getUser();

$error = null;
$success = null;

// Sticky form inicijalizacija
$old_first_name = $user['first_name'] ?? '';
$old_last_name = $user['last_name'] ?? '';
$old_phone = $user['phone'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $old_first_name = $_POST['first_name'] ?? '';
    $old_last_name = $_POST['last_name'] ?? '';
    $old_phone = $_POST['phone'] ?? '';

    try {
        // Validacija na serverskoj strani [cite: 45]
        if (!empty($old_phone) && !Helpers::isValidPhone($old_phone)) {
            throw new Exception("Format broja telefona nije ispravan.");
        }

        db()->users->update([
                'where' => ['id' => $user['id']],
                'data' => [
                        'first_name' => $old_first_name,
                        'last_name' => $old_last_name,
                        'phone' => $old_phone
                ]
        ]);

        $success = "Profil uspešno ažuriran.";
        $user = Auth::getUser(); // Osvežavanje podataka u sesiji
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Dobavljanje rezervacija korisnika
$myReservations = db()->reservations->findMany([
        'where' => ['user_id' => $user['id']],
        'orderBy' => ['reservation_date' => 'DESC', 'start_time' => 'DESC']
]);
?>

<!DOCTYPE html>
<html lang="sr">
<?php Helpers::renderHead("Moj Profil", ['auth.css']); ?>
<body>

<?php require_once Helpers::getFullPath('components', 'header.php'); ?>

<main class="container py-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="glass-card">
                <h3 class="mb-4">Profilni podaci</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small"><?= $error ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success bg-success bg-opacity-20 border-0 text-white small"><?= $success ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label small">Email (Korisničko ime)</label>
                        <input type="text" class="form-control custom-input opacity-50"
                               value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Ime</label>
                        <input type="text" name="first_name" class="form-control custom-input"
                               value="<?= htmlspecialchars($old_first_name) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Prezime</label>
                        <input type="text" name="last_name" class="form-control custom-input"
                               value="<?= htmlspecialchars($old_last_name) ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small">Telefon</label>
                        <input type="text" name="phone" class="form-control custom-input"
                               value="<?= htmlspecialchars($old_phone) ?>">
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-light w-100 fw-bold">AŽURIRAJ</button>
                </form>

                <div class="mt-4 text-center">
                    [cite_start]<a href="../forgot-password/" class="text-white-50 small">Promena lozinke?</a> [cite:
                    11]
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-card">
                <h3 class="mb-4">Moje rezervacije</h3>

                <div class="table-responsive">
                    <table class="table table-dark table-hover border-secondary">
                        <thead>
                        <tr class="small text-uppercase opacity-50">
                            [cite_start]
                            <th>Kôd</th>
                            [cite: 29]
                            [cite_start]
                            <th>Datum i vreme</th>
                            [cite: 23]
                            <th>Status</th>
                            <th class="text-end">Akcija</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($myReservations as $res):
                            $resTimestamp = strtotime($res['reservation_date'] . ' ' . $res['start_time']);
                            $canCancel = ($res['status'] === 'ACTIVE' && ($resTimestamp - time()) > 14400);
                            ?>
                            <tr>
                                <td class="fw-bold text-info"><?= $res['reservation_code'] ?></td>
                                <td><?= date('d.m.Y', strtotime($res['reservation_date'])) ?>
                                    u <?= substr($res['start_time'], 0, 5) ?></td>
                                <td>
                                        <span class="badge bg-<?= $res['status'] === 'ACTIVE' ? 'primary' : ($res['status'] === 'CANCELLED' ? 'danger' : 'success') ?> opacity-75">
                                            <?= $res['status'] ?>
                                        </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($canCancel): ?>
                                        [cite_start]
                                        <button onclick="cancelReservation(<?= $res['id'] ?>)"
                                                class="btn btn-sm btn-outline-danger">Otkaži
                                        </button> [cite: 10]
                                    <?php else: ?>
                                        <span class="text-muted small">Zaključano</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($myReservations)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 opacity-50">Nemate napravljenih rezervacija.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    /**
     * [cite_start]Upotreba Fetch API-ja za otkazivanje rezervacije [cite: 41, 43]
     */
    async function cancelReservation(id) {
        if (!confirm('Da li ste sigurni da želite da otkažete rezervaciju?')) return;

        try {
            const response = await fetch('cancel.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.message || 'Greška pri otkazivanju.');
            }
        } catch (e) {
            console.error(e);
        }
    }
</script>
</body>
</html>