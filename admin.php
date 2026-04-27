<?php
include "includes/db.php";
include "includes/auth.php";
requireLogin();
requireAdmin();

include "includes/header.php";

if (isset($_POST["update_status"])) {
    $appointment_id = $_POST["appointment_id"];
    $status = $_POST["status"];

    $stmt = mysqli_prepare($conn, "UPDATE appointments SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $status, $appointment_id);
    mysqli_stmt_execute($stmt);
}

$query = "
    SELECT 
        appointments.*,
        users.full_name,
        users.phone,
        services.name AS service_name,
        services.price
    FROM appointments
    JOIN users ON appointments.customer_id = users.id
    JOIN services ON appointments.service_id = services.id
    ORDER BY appointments.appointment_date DESC, appointments.appointment_time DESC
";

$result = mysqli_query($conn, $query);
?>

<h3 class="mb-4">Админ самбар — Бүх цаг захиалга</h3>

<div class="card p-4">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Үйлчлүүлэгч</th>
                <th>Утас</th>
                <th>Үйлчилгээ</th>
                <th>Өдөр</th>
                <th>Цаг</th>
                <th>Үнэ</th>
                <th>Төлөв</th>
                <th>Шинэчлэх</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                    <td><?php echo htmlspecialchars($row["phone"]); ?></td>
                    <td><?php echo htmlspecialchars($row["service_name"]); ?></td>
                    <td><?php echo $row["appointment_date"]; ?></td>
                    <td><?php echo substr($row["appointment_time"], 0, 5); ?></td>
                    <td><?php echo number_format($row["price"]); ?>₮</td>

                    <td>
                        <span class="badge bg-info text-dark">
                            <?php echo $row["status"]; ?>
                        </span>
                    </td>

                    <td>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="appointment_id" value="<?php echo $row["id"]; ?>">

                            <select name="status" class="form-control form-control-sm">
                                <option value="pending" <?php if ($row["status"] == "pending") echo "selected"; ?>>pending</option>
                                <option value="confirmed" <?php if ($row["status"] == "confirmed") echo "selected"; ?>>confirmed</option>
                                <option value="completed" <?php if ($row["status"] == "completed") echo "selected"; ?>>completed</option>
                                <option value="cancelled" <?php if ($row["status"] == "cancelled") echo "selected"; ?>>cancelled</option>
                            </select>

                            <button type="submit" name="update_status" class="btn btn-sm btn-main">
                                OK
                            </button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</div>
</body>
</html>