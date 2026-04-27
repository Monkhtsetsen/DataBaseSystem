<?php
include "includes/db.php";
include "includes/header.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $error = "Email болон нууц үгээ оруулна уу.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, full_name, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];

                header("Location: index.php");
                exit();
            } else {
                $error = "Нууц үг буруу байна.";
            }
        } else {
            $error = "Ийм email бүртгэлгүй байна.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card p-4">
            <h3 class="text-center mb-3">Нэвтрэх</h3>

            <?php if ($error) { ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php } ?>

            <form method="POST">
                <label>Email</label>
                <input type="email" name="email" class="form-control mb-3">

                <label>Нууц үг</label>
                <input type="password" name="password" class="form-control mb-3">

                <button type="submit" class="btn btn-main w-100">Нэвтрэх</button>
            </form>

            <p class="mt-3 text-center">
                Бүртгэлгүй юу? <a href="register.php">Бүртгүүлэх</a>
            </p>
        </div>
    </div>
</div>

</div>
</body>
</html>