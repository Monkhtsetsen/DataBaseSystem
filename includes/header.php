<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeautyBook Salon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg custom-nav">
    <div class="container">
        <a class="navbar-brand" href="index.php">✦ BeautyBook</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"
                style="border-color:rgba(232,213,163,0.3);">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Нүүр</a>

                <?php if (isset($_SESSION["user_id"])) { ?>
                    <a class="nav-link" href="book.php">Цаг захиалах</a>
                    <a class="nav-link" href="my_appointments.php">Миний цагууд</a>
                    <?php if ($_SESSION["role"] === "admin") { ?>
                        <a class="nav-link" href="admin.php">Админ</a>
                    <?php } ?>
                    <a class="nav-link" href="logout.php"
                       style="color:rgba(232,213,163,0.5)!important;">
                        <?php echo htmlspecialchars($_SESSION["full_name"]); ?> · Гарах
                    </a>
                <?php } else { ?>
                    <a class="nav-link" href="login.php">Нэвтрэх</a>
                    <a class="nav-link" href="register.php"
                       style="background:rgba(201,169,110,0.15);border-radius:20px;padding:8px 18px!important;margin-left:8px;">
                       Бүртгүүлэх
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
</nav>

<?php if (!in_array(basename($_SERVER['PHP_SELF']), ['index.php'])) { ?>
<div class="container mt-4">
<?php } ?>
