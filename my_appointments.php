<?php
include "includes/db.php";
include "includes/auth.php";
requireLogin();
include "includes/header.php";

$user_id = $_SESSION["user_id"];

$stmt = mysqli_prepare($conn,
    "SELECT a.*, s.name AS service_name, s.price, st.full_name AS staff_name, st.role_label
     FROM appointments a
     JOIN services s ON a.service_id = s.id
     LEFT JOIN staff st ON a.staff_id = st.id
     WHERE a.customer_id = ?
     ORDER BY a.appointment_date DESC, a.appointment_time DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$statusMap = [
    'pending'   => ['Хүлээгдэж байна', 'status-pending'],
    'confirmed' => ['Баталгаажсан',    'status-confirmed'],
    'completed' => ['Дууссан',         'status-completed'],
    'cancelled' => ['Цуцлагдсан',      'status-cancelled'],
];
?>

<div class="page-header">
    <span class="section-eyebrow">Хувийн хуудас</span>
    <h3>Миний захиалсан цагууд</h3>
</div>

<div class="card p-3">
    <div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Үйлчилгээ</th>
                <th>Мэргэжилтэн</th>
                <th>Өдөр</th>
                <th>Цаг</th>
                <th>Үнэ</th>
                <th>Төлөв</th>
                <th>Тэмдэглэл</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) {
                $s = $statusMap[$row['status']] ?? ['Тодорхойгүй', 'status-pending'];
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row["service_name"]); ?></td>
                <td>
                    <?php if ($row["staff_name"]) { ?>
                        <div style="font-size:0.85rem;"><?php echo htmlspecialchars($row["staff_name"]); ?></div>
                        <div style="font-size:0.75rem;color:var(--text-light);"><?php echo htmlspecialchars($row["role_label"] ?? ''); ?></div>
                    <?php } else { ?>
                        <span style="color:var(--text-light);">—</span>
                    <?php } ?>
                </td>
                <td><?php echo $row["appointment_date"]; ?></td>
                <td><?php echo substr($row["appointment_time"], 0, 5); ?></td>
                <td><?php echo number_format($row["price"]); ?>₮</td>
                <td><span class="badge <?php echo $s[1]; ?>"><?php echo $s[0]; ?></span></td>
                <td><?php echo htmlspecialchars($row["note"] ?? ''); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    </div>
</div>

<div style="margin-top:20px;">
    <a href="book.php" class="btn-main">+ Шинэ цаг захиалах</a>
</div>

</div>
</body>
</html>
