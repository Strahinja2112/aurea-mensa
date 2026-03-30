<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';

$user = Auth::getUser();
?>
<!doctype html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Početna</title>
</head>
<body>

<h1>Ordo Mensarum</h1>

<?php if ($user !== null): ?>

    <p>Ulogovani ste kao:</p>

    <ul>
        <li>ID: <?php echo (int)$user['id']; ?></li>
        <li>Ime: <?php echo htmlspecialchars((string)$user['first_name']); ?></li>
        <li>Prezime: <?php echo htmlspecialchars((string)$user['last_name']); ?></li>
        <li>Email: <?php echo htmlspecialchars((string)$user['email']); ?></li>
    </ul>

    <form action="/WebProgramiranjeProjekat/logout.php" method="post">
        <button type="submit">Odjavi se</button>
    </form>

<?php else: ?>

    <p>Niste ulogovani.</p>

    <a href="/WebProgramiranjeProjekat/login.php">Prijavi se</a>

<?php endif; ?>

</body>
</html>