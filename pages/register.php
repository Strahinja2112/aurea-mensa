<?php
declare(strict_types=1);
require_once __DIR__ . '/../server/Auth.php'; // Prilagodi putanju ako je potrebno

// Ako je korisnik već ulogovan, prebaci ga na početnu
if (Auth::isSignedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registracija | Restoran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .register-container {
            max-width: 500px;
            margin-top: 50px;
        }
    </style>
</head>
<body>

<div class="container register-container">
    <div class="card shadow border-0">
        <div class="card-body p-4">
            <h2 class="text-center mb-4">Napravi nalog</h2>

            <div id="alertBox" class="alert d-none" role="alert"></div>

            <form id="registerForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">Ime</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Prezime</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail adresa</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Telefon</label>
                    <input type="tel" class="form-control" id="phone" name="phone">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Lozinka</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Potvrdi lozinku</label>
                    <input type="password" class="form-control" id="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 mt-3" id="submitBtn">
                    Registruj se
                </button>
            </form>

            <div class="text-center mt-3">
                <p>Već imate nalog? <a href="login.php">Prijavite se</a></p>
            </div>
        </div>
    </div>
</div>

<script>
    // Selektori elemenata
    const registerForm = document.getElementById('registerForm');
    const alertBox = document.getElementById('alertBox');
    const submitBtn = document.getElementById('submitBtn');

    // Named funkcija za prikaz poruka (kako si tražio)
    function showAlert(message, type = 'danger') {
        alertBox.textContent = message;
        alertBox.className = `alert alert-${type}`;
        alertBox.classList.remove('d-none');
    }

    // Named funkcija za slanje podataka
    async function handleRegister(event) {
        event.preventDefault();

        const formData = new FormData(registerForm);
        const data = Object.fromEntries(formData.entries());

        // Osnovna validacija lozinki na klijentu
        if (data.password !== document.getElementById('confirm_password').value) {
            showAlert('Lozinke se ne podudaraju!');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Slanje...';

        try {
            const response = await fetch('../server/actions/register.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok) {
                showAlert('Uspešna registracija! Proverite e-mail za aktivaciju.', 'success');
                registerForm.reset();
            } else {
                showAlert(result.error || 'Došlo je do greške.');
            }
        } catch (error) {
            showAlert(error.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Registruj se';
        }
    }

    // Event listener sa callback funkcijom (dozvoljen arrow po tvom uputstvu)
    registerForm.addEventListener('submit', (e) => handleRegister(e));
</script>

</body>
</html>