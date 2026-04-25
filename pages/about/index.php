<?php

require_once '../../init.php';
?>

<!doctype html>
<html lang="en">
<?php
Helpers::renderHead("O nama", ["about.css"]); ?>
<body>
<?php include Helpers::getFullPath("components", "header.php"); ?>

<main>
    <section id="about-hero" class="d-flex align-items-center justify-content-center text-center px-3">
        <div class="container">
            <p class="page-subtitle text-uppercase ls-1 text-white opacity-50 mb-3">Upoznajte nas</p>
            <h1 class="display-2 text-white font-serif mb-4">O nama</h1>
            <p class="lead text-white opacity-75 mx-auto" style="max-width: 650px;">
                Već više od decenije, Aurea Mensa predstavlja sinonim za vrhunsku gastronomsku kulturu u srcu Subotice.
            </p>
        </div>
    </section>

    <section id="story" class="container py-5 my-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <p class="text-uppercase ls-1 text-white opacity-50 mb-3">Naša priča</p>
                <h2 class="h1 text-white font-serif mb-4">Tradicija koja se poštuje, kvalitet koji se očekuje</h2>
                <p class="lead opacity-75">
                    Osnovani 2012. godine sa skromnom vizijom - da donesemo jedinstveno kulinarsko iskustvo u srce
                    Subotice. Danas, posle više od decenije posvećenosti, Aurea Mensa se čvrsto usidrio kao jedan od
                    najprestižnijih restorana u regionu.
                </p>
                <p class="mt-4 opacity-75">
                    Naš tim čine strastveni kuvari, sommelieri i profesionalci koji dele istu misiju: da svakim obrokom
                    stvaraju nezaboravno iskustvo. Odabir samo najsvežijih lokalnih sastojaka, kombinovan sa tehnikama
                    koje
                    nasleđujemo iz generacije u generaciju, čini našu kuhinju prepoznatljivom.
                </p>
                <p class="mt-4 opacity-75">
                    Bilo da dolazite na romantičnu večeru, poslovni ručak ili svečanu priliku, naš cilj je isti - da vas
                    dočekamo sa toplinom i posvetimo svaki trenutak vašeg boravka u našem restoranu.
                </p>
            </div>
            <div class="col-lg-6">
                <div class="about-image-wrapper">
                    <img src="<?= Helpers::getRelativePath('public/images/anotheronethankyou.jpg') ?>"
                         alt="Enterijer restorana Aurea Mensa" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <section id="values" class="container py-5 my-5">
        <div class="text-center mb-5">
            <p class="text-uppercase ls-1 text-white opacity-50 mb-3">Naše vrednosti</p>
            <h2 class="h1 text-white font-serif">Šta nas definiše</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100">
                    <i class="fa-solid fa-leaf fa-2x mb-4 text-accent"></i>
                    <h3 class="h3 text-white font-serif mb-3">Svežina Sastojaka</h3>
                    <p class="small opacity-75 mb-0">
                        Sarađujemo isključivo sa lokalnim proizvođačima. Svaki sastojak prolazi strogu selekciju pre
                        nego
                        što dospe do naše kuhinje. Sezonska jela su sastavni deo naše ponude.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100">
                    <i class="fa-solid fa-users fa-2x mb-4 text-accent"></i>
                    <h3 class="h3 text-white font-serif mb-3">Posvećenost Gostima</h3>
                    <p class="small opacity-75 mb-0">
                        Svaki gost je za nas poseban. Naš tim je obučen da prepozna i ispuni i najzahtevnije želje.
                        Pažnja prema detalju je ono što nas razlikuje.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100">
                    <i class="fa-solid fa-fire fa-2x mb-4 text-accent"></i>
                    <h3 class="h3 text-white font-serif mb-3">Majstorstvo Kuvara</h3>
                    <p class="small opacity-75 mb-0">
                        Naš glavni kuvar, sa preko 20 godina iskustva u elitnim kuhinjama širom Evrope, donosi znanje i
                        strast koje se ogledaju u svakom jelu na meniju.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="stats" class="stats-section py-5 my-5">
        <div class="container">
            <div class="row g-4 text-center">
                <div class="col-6 col-md-3">
                    <div class="stat-number font-serif text-white">10+</div>
                    <div class="stat-label text-uppercase ls-1 opacity-50">Godina iskustva</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-number font-serif text-white">50+</div>
                    <div class="stat-label text-uppercase ls-1 opacity-50">Jela na meniju</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-number font-serif text-white">15k+</div>
                    <div class="stat-label text-uppercase ls-1 opacity-50">Zadovoljnih gostiju</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-number font-serif text-white">4.8</div>
                    <div class="stat-label text-uppercase ls-1 opacity-50">Prosečna ocena</div>
                </div>
            </div>
        </div>
    </section>

    <section id="cta" class="cta-section d-flex align-items-center justify-content-center text-center py-5 my-5">
        <div class="container">
            <p class="text-uppercase ls-1 text-white opacity-50 mb-3">Pridružite nam se</p>
            <h2 class="h1 text-white font-serif mb-4">Doživite Aurea Mensa</h2>
            <p class="lead text-white opacity-75 mx-auto mb-4" style="max-width: 550px;">
                Pozivamo vas da rezervišete sto i lično doživite kulinarsko putovanje koje smo pripremili za vas.
            </p>
            <a href="<?= Helpers::getRelativePath('pages/reserve-table') ?>" class="btn btn-cta px-5 py-3 fw-bold">
                REZERVIŠI STO
            </a>
        </div>
    </section>
</main>

<?php include Helpers::getFullPath("components", "footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>