<?php

declare(strict_types=1);

require_once '../../Helpers.php';
require_once Helpers::getFullPath('src', 'classes', 'Auth.php');

Auth::signOut();

header("Location: /index.php");

exit;