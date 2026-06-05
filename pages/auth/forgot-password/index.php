<?php
declare(strict_types=1);
require_once '../../../autoload.php';

$error = null;
$success = null;
$token = $_GET['token'] ?? null;

$isTokenValid = false;
if ($token) {
    $check = db()->verificationTokens->findFirst([
            'where' => ['token' => $token, 'expires_at' => ['gt' => date('Y-m-d H:i:s')]]
    ]);
    $isTokenValid = (bool)$check;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($token) {
            $password = $_POST['password'] ?? '';
            if (!Helpers::isStrongPassword($password)) {
                throw new Exception("Lozinka mora imati bar 8 karaktera, jedno veliko slovo i jedan broj.");
            }
            Auth::resetPassword($token, $password);
            $success = "Lozinka je uspešno promenjena. Sada se možete <a href='../login/' class='text-white text-decoration-underline'>prijaviti</a>.";
        } else {
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            Auth::requestPasswordReset($email);
            $success = "Ako nalog postoji, poslali smo instrukcije na vaš email.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="sr">
<?php Helpers::renderHead($token ? "Nova lozinka" : "Reset lozinke", ['auth.css']); ?>
<body>

<?php
require_once Helpers::getFullPath('src/components/header.php'); ?>

<main>
    <div class="container d-flex justify-content-center">
        <div class="glass-card">
            <h2 id="title" class="text-center mb-4"><?= $token ? "Nova lozinka" : "Reset lozinke" ?></h2>

            <?php if ($error): ?>
                <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success bg-success bg-opacity-20 border-0 text-white small rounded-3 mb-4 text-center">
                    <i class="fas fa-check-circle me-2"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <?php if ($token && !$isTokenValid): ?>
                    <div class="alert alert-warning bg-warning bg-opacity-20 border-0 text-white small text-center">
                        Link je nevažeći ili je istekao. <br>
                        <a href="./" class="text-white fw-bold">Pokušajte ponovo</a>
                    </div>
                <?php else: ?>
                    <form id="authForm" method="POST" action="">
                        <?php if ($token): ?>
                            <div class="form-group">
                                <label class="form-label">Nova lozinka</label>
                                <input type="password" name="password" class="form-control custom-input"
                                       placeholder="Min. 6 karaktera" required>
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label class="form-label">Email adresa</label>
                                <input type="email" name="email" class="form-control custom-input"
                                       placeholder="marko@primer.com" required>
                            </div>
                        <?php endif; ?>

                        <div class="register-btn-wrapper">
                            <div class="button">
                                <button type="submit" id="submitBtn">
                                    <span id="btnText"><?= $token ? "PROMENI LOZINKU" : "POŠALJI LINK" ?></span>
                                    <span id="btnSpinner" class="d-none">
                                        <span class="spinner-border spinner-border-sm me-2"></span> SLANJE...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <div class="text-center mt-5 login-link-text">
                <p>
                    <a href="../login/">Prijavite se</a>
                    <span class="mx-3 opacity-25">|</span>
                    <a href="../register/">Napravite nalog</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php
require_once Helpers::getFullPath('src/components/footer.php'); ?>


<script>
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.getElementById("authForm");
        const btn = document.getElementById("submitBtn");
        if (!form || !btn) return;

        form.addEventListener("submit", () => {
            btn.disabled = true;
            document.getElementById("btnText").classList.add("d-none");
            document.getElementById("btnSpinner").classList.remove("d-none");
            btn.closest('.button').style.opacity = "0.7";
        });
    });
</script>
</body>
</html>