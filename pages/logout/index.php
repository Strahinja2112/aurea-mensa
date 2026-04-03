<?php

declare(strict_types=1);

require '../../init.php';

Auth::signOut();

header("Location: ../../index.php");
exit;