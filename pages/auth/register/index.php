<?php

declare(strict_types=1);

require_once '../../../autoload.php';

$error = null;
$success = null;

Auth::redirectIfSignedIn();

$oldFirstName = $_POST['first_name'] ?? '';
$oldLastName = $_POST['last_name'] ?? '';
$oldEmail = $_POST['email'] ?? '';
$oldPhone = $_POST['phone'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $password = $_POST['password'] ?? '';

        if (!Helpers::isStrongPassword($password)) {
            throw new Exception("Lozinka mora imati bar 8 karaktera, jedno veliko slovo i jedan broj.");
        }

        if (!empty($oldPhone) && !Helpers::isValidPhone($oldPhone)) {
            throw new Exception("Format broja telefona nije ispravan (npr. 0641234567).");
        }

        $data = [
                'email' => $oldEmail,
                'password' => $password,
                'first_name' => $oldFirstName,
                'last_name' => $oldLastName,
                'phone' => $oldPhone,
        ];

        Auth::registerNewUser($data);

        $success = "Registracija uspešna! Proverite svoj email za aktivaciju naloga.";

        $oldFirstName = $oldLastName = $oldEmail = $oldPhone = '';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="sr">
<?php
Helpers::renderHead("Registracija", ['auth.css']); ?>
<body>

<?php
require_once Helpers::getFullPath('src/components/header.php'); ?>

<main>
    <div class="container d-flex justify-content-center">
        <div class="glass-card">
            <h2 class="text-center">
                <?php
                echo $success ? "Nalog uspešno kreiran" : "Kreirajte vaš nalog" ?>
            </h2>

            <?php
            if ($error): ?>
                <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?= $error ?>
                </div>
            <?php
            endif; ?>

            <?php
            if ($success): ?>
                <div class="alert alert-success bg-success bg-opacity-20 border-0 text-white small rounded-3 mb-4 text-center">
                    <i class="fas fa-check-circle me-2"></i> <?= $success ?>
                </div>
            <?php
            endif; ?>

            <?php
            if (!$success): ?>
                <form id="registerForm" method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="form-label">Ime</label>
                            <input type="text" name="first_name" class="form-control custom-input"
                                   placeholder="npr. Marko" value="<?= htmlspecialchars($oldFirstName) ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">Prezime</label>
                            <input type="text" name="last_name" class="form-control custom-input"
                                   placeholder="npr. Marković" value="<?= htmlspecialchars($oldLastName) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email adresa</label>
                        <input type="email" name="email" class="form-control custom-input"
                               placeholder="marko@primer.com" value="<?= htmlspecialchars($oldEmail) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Broj telefona</label>
                        <input type="text" name="phone" class="form-control custom-input"
                               placeholder="06x xxx xxxx" value="<?= htmlspecialchars($oldPhone) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Lozinka</label>
                        <input type="password" name="password" class="form-control custom-input"
                               placeholder="Unesite lozinku" required>
                    </div>

                    <div class="register-btn-wrapper">
                        <div class="button">
                            <button type="submit" id="submitBtn">
                                <span id="btnText">NAPRAVI NALOG</span>
                                <span id="btnSpinner" class="d-none">
                                    <span class="spinner-border spinner-border-sm" role="status"
                                          aria-hidden="true"></span>
                                    SLANJE...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            <?php
            endif; ?>

            <div class="text-center mt-5 login-link-text d-flex justify-content-between">
                <p class="small mb-2 text-white">
                    Već imate nalog? <a href="../login/">Prijavite se</a>
                </p>
                <p class="small opacity-75 text-white">
                    Nema mejla? <a href="../resend-activation/">Pošalji novi link</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php
require_once Helpers::getFullPath('src/components/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.getElementById("registerForm");
        const btn = document.getElementById("submitBtn");
        const btnText = document.getElementById("btnText");
        const btnSpinner = document.getElementById("btnSpinner");

        if (form) {
            form.addEventListener("submit", () => {
                btn.disabled = true;
                btnText.classList.add("d-none");
                btnSpinner.classList.remove("d-none");
                btn.style.opacity = "0.7";
                btn.style.cursor = "not-allowed";
            });
        }
    });
</script>
</body>
</html>