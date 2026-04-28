<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../autoload.php';

$user = Auth::getUser();

if (!$user || !$user->isStaff()) {
    http_response_code(403);
    header('Location: ' . Helpers::getRelativePath('pages/auth/login'));
    exit;
}

$pageTitle = 'Staff Dashboard';
?>
<!doctype html>
<html lang="en">
<?php Helpers::renderHead($pageTitle) ?>

<body>
<?php include Helpers::getRelativePath('src/components/header.php') ?>

<main class="pt-5">
    <div class="container py-5">
        <h1 class="text-white font-serif mb-4">Staff Dashboard</h1>

        <!-- Placeholder content -->
        <div class="glass-card p-5 text-center">
            <i class="fa-solid fa-clipboard-check fa-3x text-warning mb-3"></i>
            <h2 class="text-white mb-2">Staff Panel</h2>
            <p class="text-white opacity-75">Dashboard coming soon...</p>
        </div>
    </div>
</main>

<?php include Helpers::getRelativePath('src/components/footer.php') ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>