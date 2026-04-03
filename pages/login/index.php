<?php

declare(strict_types=1);

require '../../init.php';

$error = null;
$relativePath = Helpers::getRelativePath();

if (Auth::isSignedIn()) {
    header("Location: ../../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (Auth::signIn($email, $password)) {
        header("Location: ../../index.php");
        exit;
    } else {
        $error = "Neispravni podaci ili nalog nije aktiviran.";
    }
}
?>

<!DOCTYPE html>
<html lang="sr">
<?php
Helpers::renderHead("Prijava", [], ['auth.css']); ?>
<body>

<?php
require_once Helpers::getFullPath('components', 'header.php'); ?>

<main>
    <div class="container d-flex justify-content-center">
        <div class="glass-card">
            <h2 id="title">Dobrodošli nazad</h2>
            
            <?php
            if ($error): ?>
                <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?= $error ?>
                </div>
            <?php
            endif; ?>

            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email adresa</label>
                    <input type="email" name="email" class="form-control custom-input" placeholder="marko@primer.com"
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label">Lozinka</label>
                    <input type="password" name="password" class="form-control custom-input" placeholder="Vaša lozinka"
                           required>
                </div>

                <div class="text-end mb-4 login-link-text">
                    <a href="../forgot-password" style="font-size: 14px;">Zaboravili ste lozinku?</a>
                </div>

                <div class="register-btn-wrapper">
                    <div class="button">
                        <button type="submit" id="submitBtn">
                            <span id="btnText">PRIJAVI SE</span>
                            <span id="btnSpinner" class="d-none">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                PROVERA...
                            </span>
                        </button>
                    </div>
                </div>
            </form>

            <div class="text-center mt-4 login-link-text">
                <p>Nemate nalog? <a href="../register">Registrujte se ovde</a></p>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.getElementById("loginForm");
        const btn = document.getElementById("submitBtn");
        const btnText = document.getElementById("btnText");
        const btnSpinner = document.getElementById("btnSpinner");

        form.addEventListener("submit", () => {
            btn.disabled = true;
            btnText.classList.add("d-none");
            btnSpinner.classList.remove("d-none");
            btn.style.opacity = "0.7";
            btn.style.cursor = "not-allowed";
        });
    });
</script>
</body>
</html>