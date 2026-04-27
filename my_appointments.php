<?php
include "includes/db.php";
include "includes/auth.php";
requireLogin();

include "includes/header.php";

$user_id = $_SESSION["user_id"];

$stmt = mysqli_prepare(
    $conn,
    "SELECT appointments.*, services.name AS service_name, services.price
     FROM appointments
     JOIN services ON appointments.service_id = services.id
     WHERE appointments.customer_id = ?
     ORDER BY appointments.appointment_date DESC, appointments.appointment_time DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<h3 class="mb-4">Миний захиалсан цагууд</h3>

<div class="card p-4">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Үйлчилгээ</th>
                <th>Өдөр</th>
                <th>Цаг</th>
                <th>Үнэ</th>
                <th>Төлөв</th>
                <th>Тэмдэглэл</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["service_name"]); ?></td>
                    <td><?php echo $row["appointment_date"]; ?></td>
                    <td><?php echo substr($row["appointment_time"], 0, 5); ?></td>
                    <td><?php echo number_format($row["price"]); ?>₮</td>
                    <td>
                        <span class="badge bg-secondary">
                            <?php echo $row["status"]; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($row["note"]); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</div>
</body>
</html>