<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="./styles/globals.css">
    <title><?php echo "Restoran" ?></title>
</head>
<body>
<?php

require __DIR__ . "/server/db.php";

$users = db()->users->findMany(['where' => ['id' => ['gt' => 2]]]);

?>

<pre>
    <?php var_dump($users) ?>
</pre>
</body>
</html>