<?php
include "includes/db.php";
include "includes/auth.php";
requireLogin();
requireAdmin();
include "includes/header.php";

/* Dashboard тоонууд */
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"))['c'];
$totalAppointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM appointments"))['c'];
$pendingAppointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM appointments WHERE status='pending'"))['c'];
$totalServices = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM services"))['c'];

/* Status update */
if (isset($_POST["update_status"])) {
    $appointment_id = (int)$_POST["appointment_id"];
    $status = $_POST["status"];

    $allowed = ['pending','confirmed','completed','cancelled'];

    if (in_array($status, $allowed)) {
        $stmt = mysqli_prepare($conn, "UPDATE appointments SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $appointment_id);
        mysqli_stmt_execute($stmt);

        header("Location: admin.php");
        exit();
    }
}

/* Захиалгууд */
$query = "
    SELECT 
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.note,
        u.full_name AS customer_name,
        u.phone,
        u.email,
        s.name AS service_name,
        s.price,
        st.full_name AS staff_name,
        st.role_label
    FROM appointments a
    JOIN users u ON a.customer_id = u.id
    JOIN services s ON a.service_id = s.id
    LEFT JOIN staff st ON a.staff_id = st.id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
";

$result = mysqli_query($conn, $query);

$statusLabels = [
    'pending'   => 'Хүлээгдэж байна',
    'confirmed' => 'Баталгаажсан',
    'completed' => 'Дууссан',
    'cancelled' => 'Цуцлагдсан'
];
?>

<div class="page-header">
    <span class="section-eyebrow">Admin Dashboard</span>
    <h3>Админ удирдлагын хэсэг</h3>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card p-3 text-center">
            <h4><?php echo $totalUsers; ?></h4>
            <p>Нийт хэрэглэгч</p>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card p-3 text-center">
            <h4><?php echo $totalAppointments; ?></h4>
            <p>Нийт захиалга</p>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card p-3 text-center">
            <h4><?php echo $pendingAppointments; ?></h4>
            <p>Хүлээгдэж буй</p>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card p-3 text-center">
            <h4><?php echo $totalServices; ?></h4>
            <p>Үйлчилгээ</p>
        </div>
    </div>
</div>

<div class="card p-3">
    <h5 class="mb-3">Бүх цаг захиалга</h5>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th>Үйлчлүүлэгч</th>
                    <th>Утас</th>
                    <th>Email</th>
                    <th>Үйлчилгээ</th>
                    <th>Мэргэжилтэн</th>
                    <th>Өдөр</th>
                    <th>Цаг</th>
                    <th>Үнэ</th>
                    <th>Тэмдэглэл</th>
                    <th>Төлөв</th>
                    <th>Үйлдэл</th>
                </tr>
            </thead>

            <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row["customer_name"]); ?></td>
                    <td><?php echo htmlspecialchars($row["phone"]); ?></td>
                    <td><?php echo htmlspecialchars($row["email"]); ?></td>
                    <td><?php echo htmlspecialchars($row["service_name"]); ?></td>
                    <td>
                        <?php if ($row["staff_name"]) { ?>
                            <?php echo htmlspecialchars($row["staff_name"]); ?><br>
                            <small><?php echo htmlspecialchars($row["role_label"]); ?></small>
                        <?php } else { ?>
                            —
                        <?php } ?>
                    </td>
                    <td><?php echo htmlspecialchars($row["appointment_date"]); ?></td>
                    <td><?php echo substr($row["appointment_time"], 0, 5); ?></td>
                    <td><?php echo number_format($row["price"]); ?>₮</td>
                    <td><?php echo htmlspecialchars($row["note"] ?? ""); ?></td>
                    <td>
                        <?php echo $statusLabels[$row["status"]] ?? "Тодорхойгүй"; ?>
                    </td>
                    <td>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="appointment_id" value="<?php echo $row["id"]; ?>">

                            <select name="status" class="form-control form-control-sm">
                                <?php foreach ($statusLabels as $key => $label) { ?>
                                    <option value="<?php echo $key; ?>"
                                        <?php if ($row["status"] == $key) echo "selected"; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php } ?>
                            </select>

                            <button type="submit" name="update_status" class="btn btn-sm btn-main">
                                Хадгалах
                            </button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</div>
</body>
</html>