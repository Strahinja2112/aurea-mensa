<?php

declare(strict_types=1);

require_once '../../../autoload.php';

Auth::signOut();

header("Location: ../../../index.php");
exit;