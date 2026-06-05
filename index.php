<?php

require_once 'autoload.php';
?>

<!doctype html>
<html lang="en">
<?php
Helpers::renderHead("Home") ?>

<body>
<?php
include Helpers::getRelativePath("src/components/header.php") ?>

<main>
    <section id="hero" class="d-flex align-items-center justify-content-center text-center px-3">
        <div class="">
            <h1 id="hero-title" class="display-2 text-white font-serif mb-3"><?= Helpers::WEBSITE_NAME ?></h1>
            <p class="h4 fw-light text-white opacity-75 mb-5 ls-1"
               style="max-width: 800px; margin: 0 auto 3rem;">
                U svetu koji nikada ne spava, pronađite trenutak za sebe. Uživajte u savršenoj harmoniji ukusa, mirisa i
                ambijenta koji je stvoren da vas izoluje od svakodnevice.
            </p>
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="pages/reserve-table/" class="btn btn-cta px-5 py-3 fw-bold fs-4">
                    REZERVIŠI STO
                </a>
            </div>
        </div>
    </section>
    <section id="about" class="container pb-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 border-0">
                    <i class="fa-solid fa-utensils fa-2x mb-3"></i>
                    <h3 class="h1 text-white mb-3 font-serif">Vrhunski Kvalitet</h3>
                    <p class="small opacity-50">Samo najsvežiji sastojci pripremljeni od strane naših šefova
                        kuhinje.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 border-0">
                    <i class="fa-solid fa-clock fa-2x mb-3"></i>
                    <h3 class="h1 font-serif text-white mb-3">Brza Rezervacija</h3>
                    <p class="small opacity-50">Sistem koji prati vaše vreme. Otkazivanje moguće do 4h pre termina.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 border-0">
                    <i class="fa-solid fa-gem fa-2x mb-3"></i>
                    <h3 class="h1 font-serif text-white mb-3">Unikatan Ambijent</h3>
                    <p class="small opacity-50">Uživajte u modernom enterijeru dizajniranom za vaš potpuni komfor.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
include Helpers::getRelativePath("src/components/footer.php") ?>

</body>
</html>