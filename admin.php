<?php
include "includes/db.php";
include "includes/auth.php";
requireLogin();
requireAdmin();
include "includes/header.php";

if (isset($_POST["update_status"])) {
    $appointment_id = (int)$_POST["appointment_id"];
    $status         = $_POST["status"];
    $allowed        = ['pending','confirmed','completed','cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = mysqli_prepare($conn, "UPDATE appointments SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $appointment_id);
        mysqli_stmt_execute($stmt);
    }
}

$query = "
    SELECT 
        appointments.*,
        users.full_name,
        users.phone,
        services.name  AS service_name,
        services.price,
        staff.full_name AS staff_name,
        staff.role_label
    FROM appointments
    JOIN users    ON appointments.customer_id = users.id
    JOIN services ON appointments.service_id  = services.id
    LEFT JOIN staff ON appointments.staff_id  = staff.id
    ORDER BY appointments.appointment_date DESC, appointments.appointment_time DESC
";
$result = mysqli_query($conn, $query);

$statusLabels = [
    'pending'   => ['Хүлээгдэж байна', 'status-pending'],
    'confirmed' => ['Баталгаажсан',    'status-confirmed'],
    'completed' => ['Дууссан',         'status-completed'],
    'cancelled' => ['Цуцлагдсан',      'status-cancelled'],
];
?>

<div class="page-header">
    <span class="section-eyebrow">Удирдлага</span>
    <h3>Бүх цаг захиалга</h3>
</div>

<div class="card p-3">
    <div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Үйлчлүүлэгч</th>
                <th>Утас</th>
                <th>Үйлчилгээ</th>
                <th>Мэргэжилтэн</th>
                <th>Өдөр</th>
                <th>Цаг</th>
                <th>Үнэ</th>
                <th>Төлөв</th>
                <th>Шинэчлэх</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) {
                $s  = $statusLabels[$row['status']] ?? ['Тодорхойгүй', 'status-pending'];
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
                <td><?php echo htmlspecialchars($row["phone"]); ?></td>
                <td><?php echo htmlspecialchars($row["service_name"]); ?></td>
                <td>
                    <?php if ($row["staff_name"]) { ?>
                        <div style="font-size:0.85rem;"><?php echo htmlspecialchars($row["staff_name"]); ?></div>
                        <div style="font-size:0.75rem;color:var(--text-light);"><?php echo htmlspecialchars($row["role_label"] ?? ''); ?></div>
                    <?php } else { ?>
                        <span style="color:var(--text-light);font-size:0.8rem;">—</span>
                    <?php } ?>
                </td>
                <td><?php echo $row["appointment_date"]; ?></td>
                <td><?php echo substr($row["appointment_time"], 0, 5); ?></td>
                <td><?php echo number_format($row["price"]); ?>₮</td>
                <td>
                    <span class="badge <?php echo $s[1]; ?>"><?php echo $s[0]; ?></span>
                </td>
                <td>
                    <form method="POST" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="appointment_id" value="<?php echo $row["id"]; ?>">
                        <select name="status" class="form-control form-control-sm" style="width:auto;min-width:130px;">
                            <?php foreach ($statusLabels as $val => $info) { ?>
                                <option value="<?php echo $val; ?>" <?php if ($row["status"] == $val) echo "selected"; ?>>
                                    <?php echo $info[0]; ?>
                                </option>
                            <?php } ?>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-sm btn-main" style="white-space:nowrap;">
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
