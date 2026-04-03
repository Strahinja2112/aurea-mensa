<?php

declare(strict_types=1);

require_once '../../init.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Molimo unesite ispravnu email adresu.";
    } else {
        $user = db()->users->findFirst(['where' => ['email' => $email]]);
        
        if (!$user) {
            $success = "Ako nalog postoji, instrukcije su poslate.";
        } elseif ($user['email_verified']) {
            $error = "Ovaj nalog je već verifikovan.";
        } else {
            $existingToken = db()->verificationTokens->findFirst([
                    'where' => [
                            'user_id' => $user['id'],
                            'expires_at' => ['gt' => date('Y-m-d H:i:s')]
                    ]
            ]);
            
            if ($existingToken) {
                $error = "Već imate aktivan kod na mejlu.";
            } else {
                db()->verificationTokens->delete(['where' => ['user_id' => $user['id']]]);
                
                $token = bin2hex(random_bytes(32));
                db()->verificationTokens->create([
                        'data' => [
                                'user_id' => $user['id'],
                                'token' => $token,
                                'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
                        ]
                ]);
                
                if (Mailer::sendActivationEmail($email, $token)) {
                    $success = "Novi aktivacioni link je poslat na vašu adresu.";
                } else {
                    $error = "Greška prilikom slanja. Pokušajte kasnije.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sr">
<?php
Helpers::renderHead("Novi kod", ['auth.css']); ?>
<body>
<?php
require_once Helpers::getFullPath('components', 'header.php'); ?>

<main>
    <div class="container d-flex justify-content-center">
        <div class="glass-card">
            <h2 id="title" class="text-center mb-4">Aktivacija</h2>
            
            <?php
            if ($error): ?>
                <div class="alert alert-danger bg-danger bg-opacity-20 border-0 text-white small mb-4">
                    <i class="fas fa-clock me-2"></i> <?= $error ?>
                </div>
            <?php
            endif; ?>
            
            <?php
            if ($success): ?>
                <div class="alert alert-success bg-success bg-opacity-20 border-0 text-white small mb-4">
                    <i class="fas fa-paper-plane me-2"></i> <?= $success ?>
                </div>
            <?php
            endif; ?>
            
            <?php
            if (!$success): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email" class="form-label">Email adresa</label>
                        <input type="email" id="email" name="email" class="form-control custom-input"
                               placeholder="primer@email.com" required>
                    </div>
                    <div class="register-btn-wrapper">
                        <div class="button">
                            <button type="submit">POŠALJI LINK</button>
                        </div>
                    </div>
                </form>
            <?php
            endif; ?>

            <div class="text-center mt-4 login-link-text">
                <a href="../login/">Nazad na prijavu</a>
            </div>
        </div>
    </div>
</main>
</body>
</html>