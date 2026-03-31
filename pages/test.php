<?php

include '../init.php';

$result = Mailer::send("pavicevicstrahinja99@gmail.com", "Subject", "Body");

var_dump($result);