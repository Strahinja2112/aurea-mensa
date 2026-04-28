<?php

$user = Auth::getUser();
$navLinks = [
        'about' => 'O nama',
        'reserve-table' => "Rezervacija",
];

$relativePath = Helpers::getRelativePath('pages');
?>

<nav id="mainNavbar" class="navbar fixed-top navbar-expand-lg navbar-dark">
    <div class="container-fluid container-xl d-flex justify-content-between align-items-center">
        <div class="logo">
            <a href="<?= Helpers::getRelativePath() . 'index.php' ?>">
                <div><?= Helpers::WEBSITE_NAME ?></div>
                <span>RESTORAN</span>
            </a>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu"
                aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon no-border"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <?php
                foreach ($navLinks as $file => $name): ?>
                    <li class="nav-item">
                        <a class="nav-link fs-5 px-lg-3 mt-3 mt-lg-0 py-lg-0"
                           href="<?= $relativePath . '/' . $file ?>">
                            <?= $name ?>
                        </a>
                    </li>
                <?php
                endforeach; ?>
            </ul>
            <div class="d-flex gap-2">
                <?php
                if ($user): ?>
                    <a href="<?= $relativePath . '/profile' ?>" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-user me-2"></i>
                        Profil
                    </a>
                    <?php
                    if ($user->isAdmin()): ?>
                        <a href="<?= $relativePath . '/dashboard' ?>"
                           class="btn btn-success btn-lg">
                            <i class="fa-solid fa-dashboard me-2"></i>
                            Dashboard
                        </a>
                    <?php
                    endif ?>
                <?php
                else: ?>
                    <a href="<?= $relativePath . '/auth/login' ?>" class="btn btn-outline-light btn-lg">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>
                        Login
                    </a>
                    <a href="<?= $relativePath . '/auth/register' ?>" class="btn btn-light btn-lg">
                        <i class="fa-solid fa-user-plus me-2"></i>
                        Registracija
                    </a>
                <?php
                endif; ?>
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