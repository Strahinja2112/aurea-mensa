# WEB PROGRAMIRANJE - Projekat: Rezervacija stolova u restoranu

## Opis projekta

Cilj projekta je da se kreira web aplikacija-sajt za rezervaciju stolova u jednom restoranu. Aplikacija razlikuje četiri
nivoa pristupa: gost, registrovan/prijavljen korisnik, radnik restorana i administrator.

### Nivoi pristupa i funkcionalnosti

#### Gost

* Može da pogleda ponuđene informacije na sajtu.
* Može da izvrši registraciju na sajtu.

#### Registrovan/prijavljen korisnik

* Može da rezerviše stol(ove) u restoranu za željeni termin.
* Može da promeni svoje profilne podatke (lozinka, ime, prezime, broj telefona).
* Može da izmeni, pregleda i obriše svoje rezervacije.
* Može da odjavi rezervaciju (najmanje do 4 sata pre termina rezervacije).
* Može da zahteva promenu lozinke (zaboravljena lozinka).

#### Radnik restorana

* Može da evidentira dolazak gosta u rezervisanom terminu (proverava kôd rezervacije).
* Može da unese opciono komentar za svaku rezervaciju.

#### Administrator

* Može da unosi, menja i briše informacije o stolovima.
* Može da pregleda sve rezervacije.
* Može da unosi, menja i briše podatke o radnicima restorana.
* Može da na osnovu odabira datuma pregleda rezervacije za taj datum.
* Sve rezervacije ostaju u bazi podataka.

---

## Pravila i proces rezervacije

* **Registracija:** Mora biti urađena na bezbedan način uz obavezno slanje aktivacionog linka putem e-maila. Isti
  princip važi i za zaboravljenu lozinku.
* **Korisnički nalog:** E-mail adresa mora biti jedinstvena, predstavlja korisničko ime i nije dozvoljena registracija
  više naloga sa istom adresom.
* **Odabir termina:** Korisnik bira sto/stolove, datum, termin i trajanje (npr. 1 sat, 2 sata...). Periodi su ograničeni
  na minimum 1 sat i maksimum 6 sati.
* **Provera:** Pre potvrde rezervacije obavezno je proveriti da li je termin zauzet i da li postoji preklapanje sa
  postojećim rezervacijama.
* **Otkazivanje:** Korisnik može otkazati rezervaciju najkasnije 4 sata pre početka.
* **Potvrda:** Nakon uspešne rezervacije, korisniku se šalje e-mail sa kôdom rezervacije koji pokazuje prilikom dolaska.
* **Bezbednost:** Sve korisničke lozinke hešovati **bcrypt** algoritmom. Administratorski deo zaštititi upotrebom
  sesija (PHP).

---

## Tehnički zahtevi i smernice

* **Baza podataka:** Kreirati bazu pod imenom `restaurant` sa tabelama koje zadovoljavaju sve funkcionalnosti.
* **Stolovi:** Svaki sto mora imati informaciju o broju mesta, opis lokacije unutar restorana i naznaku da li je u
  pušačkom/nepušačkom delu. Poželjno je postojanje mape restorana.
* **Tehnologije:** Obavezno koristiti HTML, CSS, JavaScript, AJAX ili Fetch API, JSON, Bootstrap, PHP i MySQL.
* **PHP i MySQL:** Za rad sa bazom koristiti **PDO ekstenziju**. Deo PHP koda mora biti objektno orijentisan.
* **Slanje mejlova:** Koristiti **PHPMailer** klasu.
* **Frontend:** Projekat mora biti multiplatformski (**Responsive**). Validacija podataka je obavezna i na klijentskoj i
  na serverskoj strani.
* **Kodiranje:** Upotreba spoljašnjih fajlova za JS i CSS je obavezna.
    * Parametre za bazu definisati u `db_config.php`.
    * Programski kod (promenljive, funkcije, objekti) pisati na **engleskom jeziku** uz komentare.
    * Pridržavati se `coding_style_guide_sr.pdf` uputstva.

---

## Timski rad i materijali

* **Trello:** Obavezna upotreba za upravljanje zadacima. Potrebne liste: *Additional Documents (Info), Brainstorming, To
  do, Doing, Testing, Done, To be Archived*. Pristup dodeliti nastavnicima.
* **Git:** Obavezna upotreba VCS sistema (GitHub, GitLab i sl.) uz redovno postavljanje koda. Pristup dodeliti
  nastavnicima.
* **Resursi:** * Mogu se koristiti **besplatni** Bootstrap 5 šabloni, ali se moraju modifikovati (navesti u
  dokumentaciji).
    * Koristiti samo besplatne ili AI generisane fotografije.
* **Server:** Projekat postaviti na školski web server (pristupni parametri stižu putem e-maila).

---

## Prezentacija i predaja

* **I prezentacija:** Prikaz baze, dizajna i dodatnih funkcionalnosti (max 10 min).
* **II prezentacija:** Prikaz trenutnog stanja, poželjno 40% završenosti (max 10 min).
* **Predaja:** Projekat se predaje u elektronskom obliku (kod, .sql, dokumentacija) na Git repozitorijum.
* **Rok:** Do kraja školske 2025/2026 godine.

**Subotica, 22.03.2026.**