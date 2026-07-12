<?php

declare(strict_types=1);

require_once '../../../autoload.php';

Auth::redirectIfSignedIn();

$error = null;
$success = null;
$token = $_GET['token'] ?? null;

if (!$token) {
    $error = "Nevažeći zahtev. Token nedostaje.";
} else {
    $tokens = db()->verificationTokens->findMany();
    $foundToken = null;
    foreach ($tokens as $tokenRecord) {
        if ($tokenRecord['token'] == $token) {
            $foundToken = $tokenRecord;
        }
    }

    if (!$foundToken) {
        $error = "Link je nevažeći ili je istekao (važi 10 minuta).";
    } else {
        $isBefore = new DateTime($foundToken['expires_at']) < new DateTime();
        if ($isBefore) {
            $error = "Link je istekao!";
            return;
        } else {
            db()->beginTransaction();
            try {
                db()->users->update([
                    'where' => ['id' => $foundToken['user_id']],
                    'data' => ['email_verified' => 1]
                ]);

                // db()->verificationTokens->delete([
                //     'where' => ['id' => $verificationRecord['id']]
                // ]);

                db()->commit();
                $success = "Nalog je uspešno aktiviran! Sada se možete prijaviti.";
            } catch (Exception $e) {
                db()->rollBack();
                $error = "Greška prilikom aktivacije. Pokušajte ponovo kasnije.";
            }
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
                            <button type="submit" id="submitBtn">
                                <a href="../login/" id="btnText">Prijavi se</a>
                            </button>
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