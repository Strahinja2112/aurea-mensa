<?php

declare(strict_types=1);
require_once '../../autoload.php';

Auth::requireAuth();
$user = Auth::getUser();

$error = null;
$success = null;

$oldFirstName = $_POST['first_name'] ?? $user->firstName;
$oldLastName = $_POST['last_name'] ?? $user->lastName;
$oldPhone = $_POST['phone'] ?? ($user->phone ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        db()->beginTransaction();
        
        $updateData = [
                'first_name' => $oldFirstName,
                'last_name' => $oldLastName,
                'phone' => $oldPhone
        ];
        
        if (!empty($oldPhone) && !Helpers::isValidPhone($oldPhone)) {
            throw new Exception("Format broja telefona nije ispravan.");
        }
        
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        
        if (!empty($oldPassword) || !empty($newPassword)) {
            if (empty($oldPassword) || empty($newPassword)) {
                throw new Exception("Popunite i trenutnu i novu lozinku za promenu.");
            }
            
            if (!password_verify($oldPassword, $user->passwordHash)) {
                throw new Exception("Trenutna lozinka nije ispravna.");
            }
            
            if (!Helpers::isStrongPassword($newPassword)) {
                throw new Exception("Nova lozinka mora imati bar 8 karaktera, veliko slovo i broj.");
            }
            
            $updateData['password_hash'] = password_hash($newPassword, PASSWORD_BCRYPT);
        }
        
        db()->users->update([
                'where' => ['id' => $user->id],
                'data' => $updateData
        ]);
        
        db()->commit();
        $success = "Izmene su uspešno sačuvane.";
        $user = Auth::getUser();
    } catch (Exception $e) {
        db()->rollBack();
        $error = $e->getMessage();
    }
}

$myReservations = db()->reservations->findMany([
        'where' => [
                'AND' => [
                        ['user_id' => $user->id],
                        ['status' => 'ACTIVE'],
                ]
        ],
        'orderBy' => ['reservation_date' => 'DESC', 'start_time' => 'DESC']
]);

$relativePath = Helpers::getRelativePath("pages/auth/")
?>

<!DOCTYPE html>
<html lang="sr">
<?php
Helpers::renderHead("Moj Profil", ['auth.css', 'profile.css']); ?>
<body>

<?php
require_once Helpers::getRelativePath('src/components/header.php'); ?>

<main>
    <div class="container-fluid container-lg row justify-content-center g-4 w-100">
        <div class="col-12 col-lg-5">
            <div class="glass-card h-100" style="max-width: 1000px !important;">
                <h2 class="text-center mb-4">Izmena Profila</h2>
                
                <?php
                if ($error): ?>
                    <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mb-4 text-center">
                        <?= $error ?>
                    </div>
                <?php
                endif; ?>
                
                <?php
                if ($success): ?>
                    <div class="alert alert-success bg-success bg-opacity-20 border-0 text-white small rounded-3 mb-4 text-center">
                        <?= $success ?>
                    </div>
                <?php
                endif; ?>

                <form method="POST" action="">
                    <div class="form-group mb-3">
                        <label class="form-label small opacity-50">Email</label>
                        <input type="text" class="form-control custom-input opacity-50"
                               value="<?= htmlspecialchars($user->email) ?>" readonly>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small opacity-50">Ime</label>
                            <input type="text" name="first_name" class="form-control custom-input"
                                   value="<?= htmlspecialchars($oldFirstName) ?>" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small opacity-50">Prezime</label>
                            <input type="text" name="last_name" class="form-control custom-input"
                                   value="<?= htmlspecialchars($oldLastName) ?>" required>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label small opacity-50">Telefon</label>
                        <input type="text" required name="phone" class="form-control custom-input"
                               value="<?= htmlspecialchars($oldPhone) ?>">
                    </div>

                    <div class="password-section mt-4 pt-4 border-top border-white border-opacity-10">
                        <p class="small opacity-50 mb-3 text-uppercase" style="letter-spacing: 1px;">Promena lozinke</p>

                        <div class="form-group mb-3">
                            <label class="form-label small opacity-50">Trenutna lozinka</label>
                            <input type="password" name="old_password" class="form-control custom-input"
                                   placeholder="********">
                        </div>

                        <div class="form-group mb-4">
                            <label class="form-label small opacity-50">Nova lozinka</label>
                            <input type="password" name="new_password" class="form-control custom-input"
                                   placeholder="Min. 8 karaktera"
                                   pattern="(?=.*\d)(?=.*[A-Z]).{8,}"
                                   title="Lozinka mora imati bar 8 karaktera, jedno veliko slovo i jedan broj">
                        </div>
                    </div>

                    <div class="d-flex gap-3 w-100">
                        <a href="<?= $relativePath . '/auth/logout' ?>" class="btn btn-danger btn-lg flex-fill">
                            <i class="fa-solid fa-right-from-bracket me-2"></i>
                            Odjavi se
                        </a>
                        <button type="submit" name="update_profile" class="btn btn-light btn-lg flex-fill">
                            <i class="fa-solid fa-save me-2"></i>
                            Sačuvaj izmene
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="glass-card h-100 w-100" style="max-width: 1000px !important;">
                <h2 class="text-center mb-4">Rezervacije</h2>

                <div class="reservation-container">
                    <?php
                    foreach ($myReservations as $res):
                        $resTimestamp = strtotime($res['reservation_date'] . ' ' . $res['start_time']);
                        $now = time();
                        $deadlineTimestamp = $resTimestamp - 14400;
                        $secondsLeftToCancel = $deadlineTimestamp - $now;
                        
                        $canCancel = ($res['status'] === 'ACTIVE' && $secondsLeftToCancel > 0);
                        
                        $timerText = "";
                        if ($canCancel) {
                            if ($secondsLeftToCancel >= 86400) {
                                $days = floor($secondsLeftToCancel / 86400);
                                $timerText = $days . ($days == 1 ? " dan" : " dana");
                            } elseif ($secondsLeftToCancel >= 3600) {
                                $hours = floor($secondsLeftToCancel / 3600);
                                $mins = floor(($secondsLeftToCancel % 3600) / 60);
                                $timerText = "{$hours}h {$mins}min";
                            } else {
                                $mins = ceil($secondsLeftToCancel / 60);
                                $timerText = "{$mins} min";
                            }
                        }
                        
                        $time = substr($res['start_time'], 0, 5);
                        $day = date('d', $resTimestamp);
                        $month = date('M', $resTimestamp);
                        $status = strtolower($res['status']);
                        ?>

                        <div class="res-card mb-3 <?= $status ?>">
                            <div class="row align-items-center g-0">
                                <div class="col-auto date-column text-center">
                                    <span class="d-block day"><?= $day ?></span>
                                    <span class="d-block month text-uppercase"><?= $month ?></span>
                                </div>

                                <div class="col ps-4">
                                    <div class="d-flex align-items-baseline mb-1">
                                        <span class="h2 mb-0 fw-light text-white"><?= $time ?></span>
                                        <span class="ms-1 opacity-50 small">h</span>
                                    </div>
                                    <div class="res-meta small opacity-40 text-uppercase">
                                        <span class="text-white opacity-75"><?= $res['reservation_code'] ?></span>
                                    </div>
                                </div>

                                <div class="col-auto pe-3">
                                    <div class="action-wrapper text-end">
                                        <?php
                                        if ($canCancel): ?>
                                            <span class="d-block timer-text opacity-75 mb-2"
                                                  style="font-size: 0.7rem;">
                                                Možete otkazati još <?= $timerText ?>
                                            </span>
                                            <button onclick="cancelReservation(<?= $res['id'] ?>)"
                                                    class="btn-res-action btn-active fs-6">
                                                <i class="fa-solid fa-xmark me-2"></i> Otkaži
                                            </button>
                                        <?php
                                        else: ?>
                                            <span class="d-block timer-text opacity-25 mb-2" style="font-size: 0.7rem;">
                                                <?= $res['status'] === 'ACTIVE'
                                                        ? 'Isteklo vreme za otkaz'
                                                        : 'Akcija onemogućena'
                                                ?>
                                            </span>
                                            <button class="btn-res-action btn-disabled fs-6" disabled>
                                                <i class="fa-solid fa-lock me-2"></i>
                                            </button>
                                        <?php
                                        endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    endforeach; ?>
                    
                    <?php
                    if (empty($myReservations)): ?>
                        <div class="text-center py-5">
                            <i class="fa-solid fa-calendar-xmark fa-3x mb-3 opacity-10"></i>
                            <h3 class="text-white opacity-25 fw-light">Nema podataka o rezervacijama.</h3>
                        </div>
                    <?php
                    endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
require_once Helpers::getFullPath("src/components/footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    async function cancelReservation(id) {
        if (!confirm('Otkazati rezervaciju?')) {
            return;
        }

        try {
            const response = await fetch(`<?= Helpers::getRelativePath() ?>server/actions/cancel_reservation.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            });

            const result = await response.json();

            if (result.success) {
                location.reload();
            } else {
                alert(result.message);
            }
        } catch (e) {
            console.error(e);
        }
    }
</script>
</body>
</html>