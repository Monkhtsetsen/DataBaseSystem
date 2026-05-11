<?php
date_default_timezone_set("Asia/Ulaanbaatar");

include "includes/db.php";
include "includes/auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];
$message = "";
$error = "";

/* Хэрэглэгч захиалгаа цуцлах хэсэг
   Дүрэм: Захиалсан цаг эхлэхээс 24 цагаас дээш хугацаа үлдсэн үед л цуцална. */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_appointment_id"])) {
    $appointment_id = (int)$_POST["cancel_appointment_id"];

    $check = mysqli_prepare($conn,
        "SELECT id, appointment_date, appointment_time, status
         FROM appointments
         WHERE id = ? AND customer_id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($check, "ii", $appointment_id, $user_id);
    mysqli_stmt_execute($check);
    $check_result = mysqli_stmt_get_result($check);
    $appointment = mysqli_fetch_assoc($check_result);

    if (!$appointment) {
        $error = "Захиалга олдсонгүй.";
    } elseif ($appointment["status"] === "cancelled") {
        $error = "Энэ захиалга аль хэдийн цуцлагдсан байна.";
    } elseif ($appointment["status"] === "completed") {
        $error = "Дууссан захиалгыг цуцлах боломжгүй.";
    } else {
        $appointment_ts = strtotime($appointment["appointment_date"] . " " . $appointment["appointment_time"]);
        $limit_ts = time() + (24 * 60 * 60);

        if ($appointment_ts <= $limit_ts) {
            $error = "Захиалсан цаг эхлэхээс 24 цагаас бага хугацаа үлдсэн тул цуцлах боломжгүй.";
        } else {
            $upd = mysqli_prepare($conn,
                "UPDATE appointments
                 SET status = 'cancelled'
                 WHERE id = ? AND customer_id = ? AND status IN ('pending', 'confirmed')"
            );
            mysqli_stmt_bind_param($upd, "ii", $appointment_id, $user_id);

            if (mysqli_stmt_execute($upd) && mysqli_stmt_affected_rows($upd) > 0) {
                $message = "Захиалга амжилттай цуцлагдлаа.";
            } else {
                $error = "Захиалгыг цуцлах үед алдаа гарлаа.";
            }
        }
    }
}

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

include "includes/header.php";
?>

<div class="page-header">
    <span class="section-eyebrow">Хувийн хуудас</span>
    <h3>Миний захиалсан цагууд</h3>
</div>

<div class="alert alert-danger mb-3" style="border-left:5px solid #b00020;">
    <strong>Анхааруулга:</strong>
    Захиалсан цаг эхлэхээс хамгийн багадаа <strong>24 цагийн өмнө</strong> цуцлах боломжтой.
    24 цагаас бага хугацаа үлдсэн бол системээр цуцлах боломжгүй.
</div>

<?php if (!empty($message)) { ?>
    <div class="alert alert-success mb-3">✓ <?php echo htmlspecialchars($message); ?></div>
<?php } ?>

<?php if (!empty($error)) { ?>
    <div class="alert alert-danger mb-3">✗ <?php echo htmlspecialchars($error); ?></div>
<?php } ?>

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
                <th>Үйлдэл</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) {
                $s = $statusMap[$row['status']] ?? ['Тодорхойгүй', 'status-pending'];
                $appointment_ts = strtotime($row["appointment_date"] . " " . $row["appointment_time"]);
                $canCancel = in_array($row["status"], ["pending", "confirmed"], true)
                             && $appointment_ts > time() + (24 * 60 * 60);
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
                <td><?php echo htmlspecialchars($row["appointment_date"]); ?></td>
                <td><?php echo substr($row["appointment_time"], 0, 5); ?></td>
                <td><?php echo number_format($row["price"]); ?>₮</td>
                <td><span class="badge <?php echo $s[1]; ?>"><?php echo $s[0]; ?></span></td>
                <td><?php echo htmlspecialchars($row["note"] ?? ''); ?></td>
                <td>
                    <?php if ($canCancel) { ?>
                        <form method="POST" style="margin:0;" onsubmit="return confirm('Энэ захиалгыг цуцлах уу?');">
                            <input type="hidden" name="cancel_appointment_id" value="<?php echo (int)$row['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Цуцлах</button>
                        </form>
                    <?php } elseif (in_array($row["status"], ["pending", "confirmed"], true)) { ?>
                        <span style="font-size:0.78rem;color:#b00020;">24 цагаас бага үлдсэн</span>
                    <?php } else { ?>
                        <span style="color:var(--text-light);">—</span>
                    <?php } ?>
                </td>
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
