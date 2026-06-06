# WEB PROGRAMIRANJE - Projekat

## Rezervacija stolova u restoranu

## Opis projekta

Cilj projekta je da se kreira web aplikacija/sajt za rezervaciju stolova u jednom restoranu.

Aplikacija razlikuje četiri nivoa pristupa:

- Gost
- Registrovan/prijavljen korisnik
- Radnik restorana
- Administrator

---

# Gost

- može da pogleda ponuđene informacije na sajtu
- može da izvrši registraciju na sajtu

---

# Registrovan/prijavljen korisnik

- može da rezerviše stol(ove) u restoranu za željeni termin
- može da promeni svoje profilne podatke (lozinka, ime, prezime, broj telefona)
- može da izmeni, pregleda i obriše svoje rezervacije
- može da odjavi rezervaciju (najmanje do 4 sata pre termina rezervacije)
- može da zahteva promenu lozinke (zaboravljena lozinka)

---

# Radnik restorana

- može da evidentira dolazak gosta u rezervisanom terminu (proverava kôd rezervacije)
- može opciono da unese komentar za svaku rezervaciju

---

# Administrator

- može da unosi, menja i briše informacije o stolovima
- može da pregleda sve rezervacije
- može da unosi, menja i briše podatke o radnicima restorana
- može da na osnovu odabranog datuma pregleda rezervacije za taj datum
- sve rezervacije ostaju u bazi podataka

---

## Registracija i bezbednost

Registracija korisnika mora biti urađena na bezbedan način uz obavezno slanje aktivacionog linka putem e-maila.

Aktivacioni link se koristi i kod zahteva za promenu lozinke.

Nije dozvoljena registracija više korisničkih naloga sa istom e-mail adresom.

- E-mail adresa mora biti jedinstvena.
- E-mail adresa predstavlja korisničko ime.

---

## Rezervacija stolova

Prijavljeni korisnik vrši odabir:

- stola ili više stolova
- datuma rezervacije
- termina rezervacije
- vremenskog perioda rezervacije

Primer trajanja rezervacije:

- 1 sat
- 2 sata
- itd.

Za svaki sto treba da postoji informacija o:

- broju mesta
- opisu lokacije unutar restorana
- da li se nalazi u pušačkom ili nepušačkom delu

Poželjno je da postoji mapa restorana sa rasporedom stolova.

### Pravila rezervacije

- Minimalno trajanje rezervacije: 1 sat
- Maksimalno trajanje rezervacije: 6 sati

Pre rezervacije obavezno proveravati:

- da li je termin zauzet
- da li se vremenski period preklapa sa postojećim rezervacijama

Korisnik može da otkaže rezervaciju najkasnije 4 sata pre početka termina.

Nakon uspešne rezervacije korisniku se šalje e-mail sa kôdom rezervacije.

Prilikom dolaska u restoran korisnik prikazuje dobijeni kôd rezervacije.

---

## Administratorski deo

Administratorski deo mora biti zaštićen korišćenjem PHP sesija.

Sve korisničke lozinke moraju biti hešovane korišćenjem bcrypt algoritma.

---

## Baza podataka

Potrebno je kreirati bazu podataka pod nazivom:

```sql
restaurant
```

Unutar baze kreirati tabele koje podržavaju sve funkcionalnosti projekta.

---

# Zahtevi i smernice

- Studenti mogu dodati dodatne funkcionalnosti koje unapređuju projekat.
- Obavezna je upotreba spoljašnjeg JavaScript fajla.
- Programski kod (promenljive, funkcije, objekti...) pisati na engleskom jeziku.
- Koristiti komentare u kodu.
- Poštovati pravila iz dokumenta `coding_style_guide_sr.pdf`.
- Obavezna je upotreba spoljašnjeg CSS fajla.

---

## Rad sa bazom

Pristupne parametre za povezivanje sa MySQL serverom definisati u:

```php
db_config.php
```

Za rad sa MySQL bazom koristiti:

- PHP PDO ekstenziju

Deo PHP koda mora biti objektno orijentisan.

---

## Responsive dizajn

Projekat mora biti:

- multiplatformski
- prilagođen desktop uređajima
- prilagođen mobilnim uređajima

---

## Obavezne tehnologije

Obavezno koristiti:

- HTML
- CSS
- JavaScript
- AJAX ili Fetch API
- JSON
- Bootstrap
- PHP
- MySQL

Za slanje e-mailova koristiti:

- PHPMailer

GitHub repozitorijum:

https://github.com/PHPMailer/PHPMailer

---

## AJAX / Fetch API

Deo aplikacije koji koristi AJAX ili Fetch API mora da manipuliše podacima iz baze.

Preporučena upotreba:

- validacija podataka
- dobavljanje podataka iz baze
- registracija korisnika
- provere tokom registracije

---

## Validacija

Na svim stranicama gde se vrši validacija podataka potrebno je implementirati:

- klijentsku validaciju
- serversku validaciju

---

## Postavljanje projekta

Projekat je potrebno postaviti na školski web server.

Pristupni podaci biće dostavljeni putem e-maila.

---

## Trello

Obavezno koristiti Trello.

Nastavnicima dodeliti pristup:

- prowebing@gmail.com
- szakall.edina@gmail.com

Potrebne liste:

1. Additional Documents (Info)
2. Brainstorming
3. To do
4. Doing
5. Testing
6. Done
7. To be Archived

Opisi zadataka mogu biti na srpskom jeziku.

---

## Git

Obavezno koristiti Git i hosting servis:

- GitHub
- GitLab
- BitBucket

Nastavnicima dodeliti pristup:

- chole@vts.su.ac.rs
- szakall.edina@vts.su.ac.rs

Programski kod redovno postavljati na repozitorijum tokom razvoja.

---

## Predaja projekta

Potrebno je predati:

- programski kod
- SQL fajlove
- projektnu dokumentaciju

Dokumentacija se izrađuje prema zadatom šablonu.

Sve mora biti postavljeno na Git repozitorijum.

---

## Sigurnost

Poseban fokus staviti na:

- bezbednost sistema
- proveru korisničkih unosa

---

## Bootstrap šabloni

Dozvoljeno je korišćenje besplatnih Bootstrap 5 šablona uz obavezne izmene.

Nije dozvoljeno korišćenje neizmenjenog šablona.

U dokumentaciji navesti korišćeni šablon.

Na web server se mogu postavljati samo besplatni materijali.

Dozvoljene fotografije:

- sopstvene fotografije
- fotografije sa FreeImages
- fotografije sa Unsplash
- fotografije generisane veštačkom inteligencijom

---

# Prezentacija projekata

## I prezentacija

Prikazati:

- bazu podataka
- dizajn projekta
- dodatne tehnologije, biblioteke ili API-je
- dodatne funkcionalnosti (opciono)

Trajanje:

- maksimalno 10 minuta

---

## II prezentacija

Prikazati:

- trenutno stanje projekta
- implementirane funkcionalnosti

Poželjno je da bude završeno najmanje 40% projekta.

Trajanje:

- maksimalno 10 minuta

---

# Završna predaja

Završeni projekat predaje se u ispitnom roku uz prethodnu prijavu ispita.

Projekat se može predati do kraja školske 2025/2026 godine.

---

**Subotica, 22.03.2026.**
