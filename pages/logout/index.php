<?php
declare(strict_types=1);

require '../../init.php';

// Auth::signOut();

Mailer::sendActivationEmail("pavicevicstrahinja99@gmail.com", "asd");

header("Location: ../../index.php");
exit;