<?php

declare(strict_types=1);

require_once '../../../autoload.php';

Auth::redirectIfSignedIn();

$error = null;
$success = null;
$token = $_GET['token'] ?? null;

var_dump($token);

if (!$token) {
    $error = "Nevažeći zahtev. Token nedostaje.";
} else {
    $verificationRecord = db()->verificationTokens->findFirst([
            'where' => [
                    'token' => $token,
                    'expires_at' => ['gt' => date('Y-m-d H:i:s')]
            ]
    ]);

    if (!$verificationRecord) {
        $error = "Link je nevažeći ili je istekao (važi 10 minuta).";
    } else {
        db()->beginTransaction();
        try {
            db()->users->update([
                    'where' => ['id' => $verificationRecord['user_id']],
                    'data' => ['email_verified' => 1]
            ]);

            db()->verificationTokens->delete([
                    'where' => ['id' => $verificationRecord['id']]
            ]);

            db()->commit();
            $success = "Nalog je uspešno aktiviran! Sada se možete prijaviti.";
        } catch (Exception $e) {
            db()->rollBack();
            $error = "Greška prilikom aktivacije. Pokušajte ponovo kasnije.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sr">
<?php
Helpers::renderHead("Aktivacija naloga", ['auth.css']); ?>
<body>

<?php
require_once Helpers::getFullPath('src/components/header.php'); ?>

<main>
    <div class="container d-flex justify-content-center">
        <div class="glass-card text-center">
            <h2 id="title" class="mb-4">Aktivacija Naloga</h2>

            <?php
            if ($error): ?>
                <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small rounded-3 mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?= $error ?>
                </div>
                <div class="login-link-text">
                    <p>Vratite se na <a href="../register">registraciju</a> kako biste dobili novi kod.</p>
                </div>
            <?php
            endif; ?>

            <?php
            if ($success): ?>
                <div class="alert alert-success bg-success bg-opacity-20 border-0 text-white small rounded-3 mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $success ?>
                </div>
                <div class="register-btn-wrapper">
                    <div class="button">
                        <a href="../login">PRIJAVI SE</a>
                    </div>
                </div>
            <?php
            endif; ?>
        </div>
    </div>
</main>

<?php
require_once Helpers::getFullPath('src/components/footer.php'); ?>


</body>
</html>