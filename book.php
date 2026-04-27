<?php
date_default_timezone_set("Asia/Ulaanbaatar");

include "includes/db.php";
include "includes/auth.php";
requireLogin();

include "includes/header.php";

$success = "";
$error = "";

// Үйлчилгээний жагсаалт авах
$services = mysqli_query($conn, "SELECT * FROM services ORDER BY name ASC");

// Боломжтой цагууд
$available_times = [
    "09:00:00" => "09:00",
    "10:00:00" => "10:00",
    "11:00:00" => "11:00",
    "12:00:00" => "12:00",
    "13:00:00" => "13:00",
    "14:00:00" => "14:00",
    "15:00:00" => "15:00",
    "16:00:00" => "16:00",
    "17:00:00" => "17:00"
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_SESSION["user_id"];
    $service_id = $_POST["service_id"] ?? "";
    $appointment_date = $_POST["appointment_date"] ?? "";
    $appointment_time = $_POST["appointment_time"] ?? "";
    $note = trim($_POST["note"] ?? "");

    if (empty($service_id) || empty($appointment_date) || empty($appointment_time)) {
        $error = "Үйлчилгээ, өдөр, цагийг сонгоно уу.";
    } elseif (!array_key_exists($appointment_time, $available_times)) {
        $error = "Буруу цаг сонгосон байна.";
    } else {
        $selected_datetime = strtotime($appointment_date . " " . $appointment_time);
        $current_datetime = time();

        if ($selected_datetime === false) {
            $error = "Огноо эсвэл цагийн формат буруу байна.";
        } elseif ($selected_datetime <= $current_datetime) {
            $error = "Өнгөрсөн өдөр эсвэл өнөөдрийн өнгөрсөн цагт захиалга хийх боломжгүй.";
        } else {
            // Давхар захиалга шалгах
            $check = mysqli_prepare(
                $conn,
                "SELECT id FROM appointments 
                 WHERE appointment_date = ? 
                 AND appointment_time = ? 
                 AND status != 'cancelled'"
            );

            mysqli_stmt_bind_param($check, "ss", $appointment_date, $appointment_time);
            mysqli_stmt_execute($check);
            mysqli_stmt_store_result($check);

            if (mysqli_stmt_num_rows($check) > 0) {
                $error = "Энэ цаг аль хэдийн захиалагдсан байна.";
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO appointments 
                    (customer_id, service_id, appointment_date, appointment_time, note) 
                    VALUES (?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "iisss",
                    $customer_id,
                    $service_id,
                    $appointment_date,
                    $appointment_time,
                    $note
                );

                if (mysqli_stmt_execute($stmt)) {
                    $success = "Цаг амжилттай захиалагдлаа.";
                } else {
                    $error = "Цаг захиалга амжилтгүй боллоо.";
                }
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card p-4">
            <h3 class="mb-3">Цаг захиалах</h3>

            <?php if (!empty($success)) { ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php } ?>

            <?php if (!empty($error)) { ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php } ?>

            <form method="POST">
                <label>Үйлчилгээ сонгох</label>
                <select name="service_id" class="form-control mb-3">
                    <option value="">-- Үйлчилгээ сонгох --</option>

                    <?php while ($service = mysqli_fetch_assoc($services)) { ?>
                        <option value="<?php echo $service["id"]; ?>">
                            <?php echo htmlspecialchars($service["name"]); ?>
                            -
                            <?php echo number_format($service["price"]); ?>₮
                        </option>
                    <?php } ?>
                </select>

                <label>Өдөр сонгох</label>
                <input 
                    type="date" 
                    name="appointment_date" 
                    class="form-control mb-3"
                    min="<?php echo date('Y-m-d'); ?>"
                    value="<?php echo htmlspecialchars($_POST["appointment_date"] ?? ""); ?>"
                >

                <label>Цаг сонгох</label>
                <select name="appointment_time" class="form-control mb-3">
                    <option value="">-- Цаг сонгох --</option>

                    <?php foreach ($available_times as $value => $label) { ?>
                        <option 
                            value="<?php echo $value; ?>"
                            <?php 
                                if (isset($_POST["appointment_time"]) && $_POST["appointment_time"] === $value) {
                                    echo "selected";
                                }
                            ?>
                        >
                            <?php echo $label; ?>
                        </option>
                    <?php } ?>
                </select>

                <label>Нэмэлт тэмдэглэл</label>
                <textarea name="note" class="form-control mb-3" rows="3"><?php 
                    echo htmlspecialchars($_POST["note"] ?? ""); 
                ?></textarea>

                <button type="submit" class="btn btn-main w-100">
                    Цаг захиалах
                </button>
            </form>
        </div>
    </div>
</div>

</div>

<script>
const dateInput = document.querySelector('input[name="appointment_date"]');
const timeSelect = document.querySelector('select[name="appointment_time"]');

function getLocalDateString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

function disablePastTimes() {
    if (!dateInput || !timeSelect) return;

    const selectedDate = dateInput.value;
    const today = getLocalDateString(new Date());

    const now = new Date();
    const currentMinutes = now.getHours() * 60 + now.getMinutes();

    for (const option of timeSelect.options) {
        if (!option.value) continue;

        const parts = option.value.split(":");
        const hour = Number(parts[0]);
        const minute = Number(parts[1]);
        const optionMinutes = hour * 60 + minute;

        if (selectedDate === today && optionMinutes <= currentMinutes) {
            option.disabled = true;
            option.textContent = option.textContent.replace(" / өнгөрсөн", "") + " / өнгөрсөн";
        } else {
            option.disabled = false;
            option.textContent = option.textContent.replace(" / өнгөрсөн", "");
        }
    }

    if (timeSelect.selectedOptions.length > 0 && timeSelect.selectedOptions[0].disabled) {
        timeSelect.value = "";
    }
}

dateInput.addEventListener("change", disablePastTimes);
window.addEventListener("load", disablePastTimes);
</script>

</body>
</html>