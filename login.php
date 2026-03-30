<?php

declare(strict_types=1);

require_once __DIR__ . '/server/db_config.php';
require_once __DIR__ . '/server/db.php';
require_once __DIR__ . '/server/Auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Morate uneti email i lozinku.';
    } else {
        $isSignedIn = Auth::signIn($email, $password);

        if ($isSignedIn) {
            header('Location: /');
            exit;
        }

        $error = 'Pogrešan email ili lozinka.';
    }
}

if (Auth::isSignedIn()) {
    header('Location: /');
    exit;
}
?>
<!doctype html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Prijava</title>
    <link rel="stylesheet" href="/styles/globals.css">
</head>
<body>
<main>
    <h1>Prijava</h1>

    <?php if ($error !== null): ?>
        <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <form action="" method="post">
        <div>
            <label for="email">Email adresa</label>
            <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    value="<?php echo htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            >
        </div>

        <div>
            <label for="password">Lozinka</label>
            <input
                    id="password"
                    name="password"
                    type="password"
                    required
            >
        </div>

        <button type="submit">Prijavi se</button>
    </form>
</main>
</body>
</html>