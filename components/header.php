<?php
$user = Auth::getUser();
$navLinks = [
        'menu' => 'Menu',
        'reservations' => 'Reservations',
        'about' => 'About',
        'contact' => 'Contact'
];

$relativePath = Helpers::getRelativePath('pages/auth/');
?>

<nav id="mainNavbar" class="navbar fixed-top navbar-expand-lg navbar-dark ">
    <div class="container-fluid container-xl d-flex justify-content-between align-items-center">
        <div class="logo">
            <a href="<?= Helpers::getRelativePath() . 'index.php' ?>">
                <div><?php echo Helpers::WEBSITE_NAME ?></div>
                <div>restoran</div>
            </a>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu"
                aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon no-border"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <?php foreach ($navLinks as $file => $name): ?>
                    <li class="nav-item">
                        <a class="nav-link fs-5 ls-1 px-lg-3 mt-3 mt-lg-0 py-lg-0"
                           href="<?= $relativePath . $file ?>">
                            <?= $name ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex gap-2">
                <?php if ($user): ?>
                    <a href="<?= $relativePath . 'profile' ?>" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-user me-2"></i>
                        Profil
                    </a>
                    <a href="<?= $relativePath . 'logout' ?>" class="btn btn-danger btn-lg">
                        <i class="fa-solid fa-right-from-bracket me-2"></i>
                        Logout
                    </a>
                <?php else: ?>
                    <a href="<?= $relativePath . 'login' ?>" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>
                        Login
                    </a>
                    <a href="<?= $relativePath . 'register' ?>" class="btn btn-light btn-lg">
                        <i class="fa-solid fa-user-plus me-2"></i>
                        Registracija
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener("DOMContentLoaded", () => {

        const navbar = document.getElementById("mainNavbar");

        window.addEventListener("scroll", () => {
            if (window.scrollY > 50) {
                navbar.classList.add("scrolled");
            } else {
                navbar.classList.remove("scrolled");
            }

        });

    });
</script>