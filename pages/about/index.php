<?php

require_once '../../autoload.php';
?>

<!doctype html>
<html lang="en">
<?php
Helpers::renderHead("O nama", ["about.css"]); ?>
<body>

<?php
require_once Helpers::getFullPath('src/components/header.php'); ?>

<main>
    <section class="about-masthead">
        <div class="container">
            <div class="row align-items-end g-5">
                <div class="col-lg-7">
                    <span class="about-tag">Aurea Mensa</span>
                    <h1 class="about-heading">
                        Rezervacije<br>
                        <span class="about-heading-accent">bez granica.</span>
                    </h1>
                    <p class="about-lead">
                        Povezujemo goste sa najboljim restoranima u Subotici — brzo, jednostavno, pouzdano.
                    </p>
                </div>
                <div class="col-lg-5 text-lg-end">
                    <div class="about-hero-stat">
                        <span class="about-hero-number">10k+</span>
                        <span class="about-hero-label">uspelih rezervacija</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-mission">
        <div class="container">
            <div class="about-mission-inner">
                <span class="about-quote-mark">&ldquo;</span>
                <p class="about-mission-text">
                    Naša misija je da svako ko želi da izađe na ručak ili večeru to može da uradi
                    bez napora — bez čekanja na telefonu, bez neizvesnosti, bez gubljenja vremena.
                </p>
                <div class="about-mission-author">
                    <div class="about-mission-avatar">AM</div>
                    <div>
                        <div class="about-mission-name">Aurea Mensa Tim</div>
                        <div class="about-mission-role">Osnivači</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-steps">
        <div class="container">
            <div class="text-center mb-5">
                <span class="about-tag">Kako funkcioniše</span>
                <h2 class="about-section-title">Tri koraka do savršene večeri</h2>
            </div>
            <div class="about-steps-grid">
                <div class="about-step">
                    <span class="about-step-num">01</span>
                    <div class="about-step-icon">
                        <i class="fa-regular fa-building"></i>
                    </div>
                    <h3 class="about-step-title">Izaberite restoran</h3>
                    <p class="about-step-desc">Pregledajte ponudu restorana i pronađite onaj koji vam odgovara.</p>
                </div>
                <div class="about-step-connector"></div>
                <div class="about-step">
                    <span class="about-step-num">02</span>
                    <div class="about-step-icon">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                    <h3 class="about-step-title">Odaberite termin</h3>
                    <p class="about-step-desc">Vidite slobodne stolove u realnom vremenu i izaberite savršeno vreme.</p>
                </div>
                <div class="about-step-connector"></div>
                <div class="about-step">
                    <span class="about-step-num">03</span>
                    <div class="about-step-icon">
                        <i class="fa-regular fa-circle-check"></i>
                    </div>
                    <h3 class="about-step-title">Potvrdite rezervaciju</h3>
                    <p class="about-step-desc">Trenutna potvrda bez čekanja. Spremni ste za izlazak!</p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-features">
        <div class="container">
            <div class="about-feature-row">
                <div class="about-feature-content">
                    <span class="about-tag">Prednosti</span>
                    <h2 class="about-section-title">Rezervišite 24/7</h2>
                    <p>
                        Bez obzira na doba dana ili noći, naš sistem je uvek dostupan.
                        Zaboravite na radno vreme i pozive — sve što vam treba je internet.
                    </p>
                </div>
                <div class="about-feature-visual">
                    <i class="fa-regular fa-clock about-feature-icon"></i>
                </div>
            </div>
            <div class="about-feature-row about-feature-row-reverse">
                <div class="about-feature-content">
                    <span class="about-tag">Pouzdanost</span>
                    <h2 class="about-section-title">Trenutna potvrda</h2>
                    <p>
                        Više ne morate da čekate povratni poziv. Sistem potvrđuje vašu rezervaciju
                        istog momenta — onlajn, bez dvostrukog bookiranja.
                    </p>
                </div>
                <div class="about-feature-visual">
                    <i class="fa-regular fa-bell about-feature-icon"></i>
                </div>
            </div>
            <div class="about-feature-row">
                <div class="about-feature-content">
                    <span class="about-tag">Fleksibilnost</span>
                    <h2 class="about-section-title">Otkazivanje u par klikova</h2>
                    <p>
                        Planovi se menjaju? Bez brige. Otkažite ili izmenite rezervaciju
                        direktno sa vašeg profila — brzo i bez pitanja.
                    </p>
                </div>
                <div class="about-feature-visual">
                    <i class="fa-regular fa-pen-to-square about-feature-icon"></i>
                </div>
            </div>
        </div>
    </section>

    <section class="about-numbers">
        <div class="container">
            <div class="about-numbers-grid">
                <div class="about-number-item">
                    <span class="about-number-value">100+</span>
                    <span class="about-number-label">dnevnih rezervacija</span>
                </div>
                <div class="about-number-divider"></div>
                <div class="about-number-item">
                    <span class="about-number-value">10+</span>
                    <span class="about-number-label">restorana</span>
                </div>
                <div class="about-number-divider"></div>
                <div class="about-number-item">
                    <span class="about-number-value">98%</span>
                    <span class="about-number-label">zadovoljstva</span>
                </div>
                <div class="about-number-divider"></div>
                <div class="about-number-item">
                    <span class="about-number-value">5★</span>
                    <span class="about-number-label">podrška</span>
                </div>
            </div>
        </div>
    </section>

    <section class="about-cta">
        <div class="container">
            <div class="about-cta-inner">
                <h2 class="about-cta-title">Spremni za večeru?</h2>
                <p class="about-cta-text">
                    Rezervišite sto i prepustite se uživanju. Mi smo tu za sve ostalo.
                </p>
                <a href="<?= Helpers::getRelativePath('pages/reserve-table') ?>" class="btn btn-cta btn-lg px-5">
                    REZERVIŠI STO
                </a>
            </div>
        </div>
    </section>
</main>

<?php
require_once Helpers::getFullPath('src/components/footer.php'); ?>

</body>
</html>