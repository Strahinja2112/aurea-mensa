<?php

declare(strict_types=1);

require_once '../../init.php';

Auth::signOut();

header("Location: ../../index.php");
exit;