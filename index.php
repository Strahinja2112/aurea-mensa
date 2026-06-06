<?php

require_once 'autoload.php';

$phoneHref = preg_replace('/\s+/', '', Helpers::WEBSITE_PHONE);
?>

<!doctype html>
<html lang="en">
<?php
Helpers::renderHead("Home") ?>

<body>
    <?php
    include Helpers::getRelativePath("src/components/header.php") ?>

    <main class="landing-page">
        <section id="hero" class="landing-hero" aria-labelledby="hero-title">
            <div class="container-fluid container-xl">
                <div class="hero-grid">
                    <div class="hero-copy">
                        <p class="section-kicker">Subotica fine dining</p>
                        <h1 id="hero-title"><?= Helpers::WEBSITE_NAME ?></h1>
                        <p class="hero-lead">
                            Ekskluzivna kulinarska avantura za večere koje ne žure. Rezervišite sto u ambijentu koji spaja sezonsku kuhinju, pažljivu uslugu i miran ritam grada.
                        </p>
                        <div class="hero-actions" aria-label="Glavne akcije">
                            <a href="pages/reserve-table/" class="btn btn-cta btn-lg">
                                <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                                Rezerviši sto
                            </a>
                            <a href="pages/about/" class="btn btn-ghost btn-lg">
                                <i class="fa-solid fa-utensils" aria-hidden="true"></i>
                                Pogledaj priču
                            </a>
                        </div>
                        <dl class="hero-proof" aria-label="Kratke informacije">
                            <div>
                                <dt>4.9</dt>
                                <dd>Ocena gostiju</dd>
                            </div>
                            <div>
                                <dt>18</dt>
                                <dd>Sezonskih tanjira</dd>
                            </div>
                            <div>
                                <dt>15 min</dt>
                                <dd>Online rezervacija</dd>
                            </div>
                        </dl>
                    </div>
                    <aside class="hero-panel" aria-label="Informacije za rezervaciju">
                        <div class="hero-panel-card">
                            <span class="panel-label">Danas služimo</span>
                            <h2>Degustacioni meni i večernji a la carte</h2>
                            <p>Stolovi za dvoje, porodične večere i diskretne poslovne susrete.</p>
                            <div class="panel-meta">
                                <span><i class="fa-solid fa-clock" aria-hidden="true"></i><?= Helpers::WEBSITE_WORKING_DAYS ?></span>
                                <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?= Helpers::WEBSITE_ADDRESS ?></span>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <section id="about" class="landing-section experience-section" aria-labelledby="experience-title">
            <div class="container-fluid container-xl">
                <div class="section-heading">
                    <p class="section-kicker">Iskustvo</p>
                    <h2 id="experience-title">Veče oblikovano oko detalja</h2>
                    <p>Od prvog pozdrava do poslednjeg zalogaja, Aurea Mensa je zamišljena kao restoran za razgovor, slavlje i miran luksuz.</p>
                </div>
                <div class="experience-grid">
                    <article class="feature-card">
                        <i class="fa-solid fa-seedling" aria-hidden="true"></i>
                        <h3>Sezonski kvalitet</h3>
                        <p>Sveže namirnice, pažljivo izabrani dobavljači i jelovnik koji prati najbolje trenutke sezone.</p>
                    </article>
                    <article class="feature-card">
                        <i class="fa-solid fa-wine-glass" aria-hidden="true"></i>
                        <h3>Uparena pića</h3>
                        <p>Preporuke uz svako jelo, od lokalnih etiketa do elegantnih klasika za posebne prilike.</p>
                    </article>
                    <article class="feature-card">
                        <i class="fa-solid fa-concierge-bell" aria-hidden="true"></i>
                        <h3>Diskretna usluga</h3>
                        <p>Tim koji vodi računa o tempu večere, alergijama, željama i svakom važnom povodu.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section signature-section" aria-labelledby="signature-title">
            <div class="container-fluid container-xl">
                <div class="signature-grid">
                    <div class="signature-image">
                        <img src="public/images/register.jpg" alt="Elegantno postavljeni stolovi u restoranu Aurea Mensa">
                    </div>
                    <div class="signature-copy">
                        <p class="section-kicker">Preporuka kuće</p>
                        <h2 id="signature-title">Meni koji daje večeri ritam</h2>
                        <p>Birajte između degustacionog toka i a la carte tanjira. Naš tim će predložiti tempo služenja, uparivanje pića i raspored stola prema povodu.</p>
                        <div class="menu-list" aria-label="Izdvojena jela">
                            <div>
                                <span>01</span>
                                <strong>Jadranska riba, maslinovo ulje, bilje</strong>
                            </div>
                            <div>
                                <span>02</span>
                                <strong>Sporo pečena junetina, redukcija vina</strong>
                            </div>
                            <div>
                                <span>03</span>
                                <strong>Čokolada, malina, hrskavi lešnik</strong>
                            </div>
                        </div>
                        <a href="pages/reserve-table/" class="text-link">
                            Rezerviši večeru
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="reservation-band" aria-labelledby="reservation-title">
            <div class="container-fluid container-xl">
                <div>
                    <p class="section-kicker">Rezervacije</p>
                    <h2 id="reservation-title">Sto je udaljen nekoliko klikova</h2>
                </div>
                <p>Izaberite datum, vreme i broj gostiju. Otkazivanje je moguće do 4 sata pre termina.</p>
                <a href="pages/reserve-table/" class="btn btn-cta btn-lg">
                    <i class="fa-solid fa-calendar-plus" aria-hidden="true"></i>
                    Započni rezervaciju
                </a>
            </div>
        </section>

        <section class="landing-section testimonials-section" aria-labelledby="testimonials-title">
            <div class="container-fluid container-xl">
                <div class="section-heading compact">
                    <p class="section-kicker">Utisci</p>
                    <h2 id="testimonials-title">Gosti pamte atmosferu</h2>
                </div>
                <div class="testimonial-grid">
                    <figure class="testimonial-card">
                        <blockquote>Odličan balans privatnosti, hrane i usluge. Rezervacija je prošla bez čekanja.</blockquote>
                        <figcaption>Milica R. <span>porodična večera</span></figcaption>
                    </figure>
                    <figure class="testimonial-card">
                        <blockquote>Ambijent je tih, elegantan i idealan za poslovni ručak koji ne deluje formalno.</blockquote>
                        <figcaption>Ivan P. <span>poslovni susret</span></figcaption>
                    </figure>
                    <figure class="testimonial-card">
                        <blockquote>Osoblje je pogodilo tempo večere i preporučilo sjajno uparivanje uz glavno jelo.</blockquote>
                        <figcaption>Nina S. <span>degustacioni meni</span></figcaption>
                    </figure>
                </div>
            </div>
        </section>

        <section class="visit-section" aria-label="Kontakt i radno vreme">
            <div class="container-fluid container-xl visit-grid">
                <div>
                    <p class="section-kicker">Posetite nas</p>
                    <h2><?= Helpers::WEBSITE_ADDRESS ?></h2>
                </div>
                <div class="visit-details">
                    <p><i class="fa-solid fa-phone" aria-hidden="true"></i><a href="tel:<?= $phoneHref ?>"><?= Helpers::WEBSITE_PHONE ?></a></p>
                    <p><i class="fa-solid fa-envelope" aria-hidden="true"></i><a href="mailto:<?= Helpers::WEBSITE_EMAIL ?>"><?= Helpers::WEBSITE_EMAIL ?></a></p>
                    <p><i class="fa-solid fa-clock" aria-hidden="true"></i>Pon - Pet <?= Helpers::WEBSITE_WORKING_DAYS ?>, Sub - Ned <?= Helpers::WEBSITE_WORKING_WEEKEND ?></p>
                </div>
            </div>
        </section>
    </main>

    <?php
    include Helpers::getRelativePath("src/components/footer.php") ?>

</body>

</html>
