<?php
include "includes/db.php";
include "includes/header.php";

$query = "SELECT * FROM services";
$result = mysqli_query($conn, $query);
?>

<div class="hero text-center mb-5">
    <h1 class="fw-bold">Beauty Salon цаг захиалгын систем</h1>
    <p class="mt-3">
        Үйлчилгээ сонгоод өөрт тохирох өдөр, цагтаа амархан захиалга хийгээрэй.
    </p>

    <?php if (!isset($_SESSION["user_id"])) { ?>
        <a href="register.php" class="btn btn-main mt-3">Эхлэх</a>
    <?php } else { ?>
        <a href="book.php" class="btn btn-main mt-3">Цаг захиалах</a>
    <?php } ?>
</div>

<h3 class="mb-4">Манай үйлчилгээ</h3>

<div class="row">
    <?php while ($service = mysqli_fetch_assoc($result)) { ?>
        <div class="col-md-4 mb-4">
            <div class="card p-4 h-100">
                <h5 class="fw-bold"><?php echo htmlspecialchars($service["name"]); ?></h5>
                <p><?php echo htmlspecialchars($service["description"]); ?></p>
                <p>Хугацаа: <?php echo $service["duration_minutes"]; ?> минут</p>
                <p class="price"><?php echo number_format($service["price"]); ?>₮</p>
            </div>
        </div>
    <?php } ?>
</div>

</div>
</body>
</html>