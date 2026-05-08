<?php

$host = 'localhost';
$dbname = 'sn';
$user = 'sn';
$password = '7v1zTOMzp3aHZ3g';

try {
    $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    
    $users = $pdo->query("SELECT id, email, first_name, last_name, created_at FROM users")->fetchAll();
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DB Test</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f4f4f4;
        }
    </style>
</head>
<body>
<h1>Users</h1>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Email</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Created At</th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($users as $row): ?>
        <tr>
            <td><?= htmlspecialchars((string)$row['id']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['first_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['last_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
        </tr>
    <?php
    endforeach; ?>
    </tbody>
</table>
</body>
</html>
