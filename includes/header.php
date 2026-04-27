<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <title>Beauty Salon Booking</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark custom-nav">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">BeautyBook</a>

        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="index.php">Нүүр</a>

            <?php if (isset($_SESSION["user_id"])) { ?>
                <a class="nav-link" href="book.php">Цаг захиалах</a>
                <a class="nav-link" href="my_appointments.php">Миний цагууд</a>

                <?php if ($_SESSION["role"] === "admin") { ?>
                    <a class="nav-link" href="admin.php">Админ</a>
                <?php } ?>

                <a class="nav-link" href="logout.php">Гарах</a>
            <?php } else { ?>
                <a class="nav-link" href="login.php">Нэвтрэх</a>
                <a class="nav-link" href="register.php">Бүртгүүлэх</a>
            <?php } ?>
        </div>
    </div>
</nav>

<div class="container mt-4"></div>