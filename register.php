<?php
include "includes/db.php";
include "includes/header.php";

$full_name = $phone = $email = "";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST["full_name"]);
    $phone = trim($_POST["phone"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($full_name) || empty($phone) || empty($email) || empty($password)) {
        $error = "Бүх талбарыг бөглөнө үү.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email хэлбэр буруу байна.";
    } elseif (strlen($password) < 6) {
        $error = "Нууц үг хамгийн багадаа 6 тэмдэгттэй байна.";
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Энэ email бүртгэлтэй байна.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, phone, email, password) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $full_name, $phone, $email, $hashed_password);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Амжилттай бүртгэгдлээ. Одоо нэвтэрнэ үү.";
                $full_name = $phone = $email = "";
            } else {
                $error = "Бүртгэл амжилтгүй боллоо.";
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card p-4">
            <h3 class="text-center mb-3">Бүртгүүлэх</h3>

            <?php if ($error) { ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php } ?>

            <?php if ($success) { ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php } ?>

            <form method="POST">
                <label>Овог нэр</label>
                <input type="text" name="full_name" class="form-control mb-3" value="<?php echo htmlspecialchars($full_name); ?>">

                <label>Утас</label>
                <input type="text" name="phone" class="form-control mb-3" value="<?php echo htmlspecialchars($phone); ?>">

                <label>Email</label>
                <input type="email" name="email" class="form-control mb-3" value="<?php echo htmlspecialchars($email); ?>">

                <label>Нууц үг</label>
                <input type="password" name="password" class="form-control mb-3">

                <button type="submit" class="btn btn-main w-100">Бүртгүүлэх</button>
            </form>

            <p class="mt-3 text-center">
                Бүртгэлтэй юу? <a href="login.php">Нэвтрэх</a>
            </p>
        </div>
    </div>
</div>

</div>
</body>
</html>