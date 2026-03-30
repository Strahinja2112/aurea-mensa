# Rezervacija stolova u restoranu

Ovaj projekat predstavlja web aplikaciju za upravljanje rezervacijama stolova u ugostiteljskom objektu. Sistem omogucava korisnicima pregled informacija i zakazivanje termina, dok zaposlenima i administratorima pruza alate za evidenciju i upravljanje resursima restorana.

## Tehnologije i tehnike

* Frontend: HTML5, CSS3, JavaScript, Bootstrap 5
* Backend: PHP (Objektno-orijentisano programiranje, PDO ekstenzija)
* Baza podataka: MySQL
* Komunikacija: AJAX / Fetch API (JSON)
* Slanje e-mailova: PHPMailer
* Sigurnost: Bcrypt hesiranje lozinki, sesije, validacija na klijentskoj i serverskoj strani

## Funkcionalnosti

Aplikacija razlikuje cetiri nivoa pristupa:

1. Gost: Pregled informacija i registracija na sistem uz aktivacioni link.
2. Registrovani korisnik: Rezervacija stolova (1-6 sati), pregled, izmena i otkazivanje sopstvenih rezervacija (najmanje 4 sata pre termina), promena profilnih podataka.
3. Radnik restorana: Evidencija dolaska gostiju putem koda rezervacije i unosenje komentara.
4. Administrator: Upravljanje podacima o stolovima i radnicima, pregled svih rezervacija po datumima.

## Specifikacije sistema

* Autentifikacija: Jedinstvena e-mail adresa sluzi kao korisnicko ime. Obavezna aktivacija naloga putem e-maila.
* Rezervacije: Provera preklapanja termina u realnom vremenu. Svaka uspesna rezervacija generise jedinstveni kod koji se salje korisniku na e-mail.
* Stolovi: Svaki sto sadrzi informaciju o broju mesta, lokaciji i statusu (pusacki/nepusacki deo).
* Responzivnost: Dizajn je prilagodjen racunarima i mobilnim uredjajima (Multiplatform/Responsive).

## Instalacija i podesavanje

1. Klonirati repozitorijum na lokalni server ili skolski web server.
2. Kreirati MySQL bazu podataka pod nazivom "restaurant".
3. Importovati prilozenu .sql strukturu baze podataka.
4. Konfigurisati parametre za pristup bazi u fajlu "db_config.php".
5. Podesiti PHPMailer podatke za uspesno slanje aktivacionih linkova i kodova.

## Autori

Projekat je radjen u okviru predmeta Web programiranje, generacija 2025/2026.