<?php
$user = Auth::getUser();
?>

<nav id="mainNavbar" class="navbar fixed-top navbar-expand-lg navbar-light bg-transparent border-bottom">
    <div class="container-fluid container-lg d-flex justify-content-between align-items-center">
        <div class="logo my-12">
            <a href="#">
                <div><?php echo Helpers::WEBSITE_NAME ?></div>
                <div>restaurant</div>
            </a>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon no-border"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link text-center fs-5 px-3" href="/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 px-3" href="/menu">Menu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 px-3" href="/reservations">Reservations</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 px-3" href="/about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fs-5 px-3" href="/contact">Contact</a>
                </li>
            </ul>
            <div class="d-flex gap-2">
                <?php if ($user): ?>
                    <a href="/profile" class="btn btn-outline-dark btn-lg">
                        <i class="fa-solid fa-user me-2"></i>
                        Profile
                    </a>
                    <a href="/logout" class="btn btn-dark btn-lg">
                        <i class="fa-solid fa-right-from-bracket me-2"></i>
                        Logout
                    </a>
                <?php else: ?>
                    <a href="/login" class="btn btn-outline-dark btn-lg">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>
                        Login
                    </a>
                    <a href="/register" class="btn btn-dark btn-lg">
                        <i class="fa-solid fa-user-plus me-2"></i>
                        Register
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