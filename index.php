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
            <p class="section-kicker">Rezervacije bez poziva i čekanja</p>
            <h1 id="hero-title"><?= Helpers::WEBSITE_NAME ?></h1>
            <p class="hero-lead">
                Izaberite sto, vreme i trajanje večere kada vama odgovara. Aurea Mensa vam odmah pomaže da pošaljete sigurnu rezervaciju i sačuvate sve detalje na jednom mestu.
            </p>
            <div class="hero-actions" aria-label="Glavne akcije">
                <a href="pages/reserve-table/" class="btn btn-cta btn-lg">
                    <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                    Rezerviši sto
                </a>
                <a href="pages/auth/register/" class="btn btn-ghost btn-lg">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                    Napravi nalog
                </a>
            </div>
        </section>

        <section id="about" class="landing-section" aria-labelledby="flow-title">
            <div class="container-fluid container-xl">
                <div class="section-heading">
                    <p class="section-kicker">Jednostavan tok</p>
                    <h2 id="flow-title">Rezervacija koja ne prekida vaš dan</h2>
                    <p>Ne morate da pamtite radno vreme recepcije, ponavljate podatke telefonom ili se pitate da li je sto zaista slobodan.</p>
                </div>
                <div class="flow-grid">
                    <article class="flow-card">
                        <span>01</span>
                        <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                        <h3>Napravite siguran nalog</h3>
                        <p>Aktivirajte nalog preko e-maila i svaki sledeći dolazak zakažite brže, bez ponovnog unošenja osnovnih podataka.</p>
                    </article>
                    <article class="flow-card">
                        <span>02</span>
                        <i class="fa-solid fa-chair" aria-hidden="true"></i>
                        <h3>Birajte ono što vam odgovara</h3>
                        <p>Odaberite datum, vreme, trajanje i sto koji odgovara društvu, povodu i delu restorana koji preferirate.</p>
                    </article>
                    <article class="flow-card">
                        <span>03</span>
                        <i class="fa-solid fa-ticket" aria-hidden="true"></i>
                        <h3>Dolazite sa potvrdom</h3>
                        <p>Dobijate kod rezervacije i jasne detalje termina, pa osoblje može brzo da pronađe vaš sto pri dolasku.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-section rules-section" aria-labelledby="rules-title">
            <div class="container-fluid container-xl rules-grid">
                <div>
                    <p class="section-kicker">Pouzdano zakazivanje</p>
                    <h2 id="rules-title">Rezervacija ima jasna pravila</h2>
                    <p>Pre potvrde se proverava zauzetost izabranih stolova, a vi dobijate pregled trajanja, termina i koda koji važi pri dolasku.</p>
                </div>
                <div class="rules-panel">
                    <div>
                        <strong>1-6h</strong>
                        <span>Fleksibilno trajanje za kratak ručak ili dužu večeru</span>
                    </div>
                    <div>
                        <strong>4h</strong>
                        <span>Rok za otkazivanje ako vam se planovi promene</span>
                    </div>
                    <div>
                        <strong>Kod</strong>
                        <span>Potvrda koju pokazujete osoblju pri dolasku</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section roles-section" aria-labelledby="roles-title">
            <div class="container-fluid container-xl">
                <div class="section-heading">
                    <p class="section-kicker">Zašto vredi koristiti</p>
                    <h2 id="roles-title">Manje neizvesnosti pre izlaska</h2>
                </div>
                <div class="roles-grid">
                    <article class="role-card">
                        <i class="fa-solid fa-clock" aria-hidden="true"></i>
                        <h3>Rezervišete kad god želite</h3>
                        <p>Forma je dostupna online, pa termin možete poslati i kada nemate vremena za poziv.</p>
                    </article>
                    <article class="role-card">
                        <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                        <h3>Vidite bitne detalje stola</h3>
                        <p>Broj mesta, lokacija i pušački ili nepušački deo pomažu da izaberete sto pre dolaska.</p>
                    </article>
                    <article class="role-card">
                        <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                        <h3>Planovi mogu da se promene</h3>
                        <p>Svoje rezervacije možete pregledati, izmeniti ili otkazati na vreme direktno iz profila.</p>
                    </article>
                    <article class="role-card">
                        <i class="fa-solid fa-bell-concierge" aria-hidden="true"></i>
                        <h3>Restoran vas očekuje spreman</h3>
                        <p>Kod rezervacije pomaže osoblju da brzo potvrdi dolazak i pronađe prave stolove.</p>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <?php
    include Helpers::getRelativePath("src/components/footer.php") ?>

</body>

</html>