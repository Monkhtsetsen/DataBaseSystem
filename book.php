<?php
date_default_timezone_set("Asia/Ulaanbaatar");

include "includes/db.php";
include "includes/auth.php";
requireLogin();

$success = "";
$error   = "";

$services = [];
$res = mysqli_query($conn, "SELECT * FROM services ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($res)) {
    $services[$row["id"]] = $row;
}

$service_role_map = [
    "hairstylist"   => ["Үс засалт", "Үс будалт", "засалт", "будалт"],
    "nail_tech"     => ["Маникюр", "Педикюр", "хумс"],
    "makeup_artist" => ["Make-up", "нүүр"],
    "lash_tech"     => ["Сормуус"],
];

function serviceRole($serviceName, $map) {
    foreach ($map as $role => $keywords) {
        foreach ($keywords as $kw) {
            if (stripos($serviceName, $kw) !== false) {
                return $role;
            }
        }
    }
    return null;
}

$all_times = [];
for ($h = 9; $h <= 17; $h++) {
    $all_times[] = sprintf("%02d:00:00", $h);
    if ($h < 17) {
        $all_times[] = sprintf("%02d:30:00", $h);
    }
}

/* AJAX: staff */
if (isset($_GET["ajax_staff"])) {
    header("Content-Type: application/json; charset=utf-8");

    $date    = $_GET["date"] ?? "";
    $serv_id = (int)($_GET["service_id"] ?? 0);

    if (!$date || !$serv_id) {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $serv_name = $services[$serv_id]["name"] ?? "";
    $role = serviceRole($serv_name, $service_role_map);

    $sql = "
        SELECT s.id, s.full_name, s.role_label, s.avatar_initials, s.bio
        FROM staff s
        JOIN staff_schedule ss ON ss.staff_id = s.id
        WHERE ss.work_date = ?
          AND ss.is_working = 1
          AND s.is_active = 1
    ";

    if ($role) {
        $sql .= " AND s.role = ?";
    }

    $stmt = mysqli_prepare($conn, $sql);

    if ($role) {
        mysqli_stmt_bind_param($stmt, "ss", $date, $role);
    } else {
        mysqli_stmt_bind_param($stmt, "s", $date);
    }

    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);

    $staff = [];
    while ($row = mysqli_fetch_assoc($r)) {
        $staff[] = $row;
    }

    echo json_encode($staff, JSON_UNESCAPED_UNICODE);
    exit;
}

/* AJAX: time slots */
if (isset($_GET["ajax_slots"])) {
    header("Content-Type: application/json; charset=utf-8");

    $date     = $_GET["date"] ?? "";
    $staff_id = (int)($_GET["staff_id"] ?? 0);
    $serv_id  = (int)($_GET["service_id"] ?? 0);

    if (!$date || !$staff_id || !$serv_id || !isset($services[$serv_id])) {
        echo json_encode(["slots" => [], "duration" => 30], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $duration = (int)$services[$serv_id]["duration_minutes"];
    $openMin  = 9 * 60;   // 09:00
    $closeMin = 17 * 60;  // 17:00

    // Тухайн артист энэ өдөр ажиллаж байгаа эсэх
    $workStmt = mysqli_prepare($conn,
        "SELECT 1 FROM staff_schedule
         WHERE staff_id = ? AND work_date = ? AND is_working = 1
         LIMIT 1"
    );
    mysqli_stmt_bind_param($workStmt, "is", $staff_id, $date);
    mysqli_stmt_execute($workStmt);
    $workRes = mysqli_stmt_get_result($workStmt);

    if (!mysqli_fetch_assoc($workRes)) {
        echo json_encode(["slots" => [], "duration" => $duration], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Одоо байгаа захиалгуудын эхлэх/дуусах интервалыг авна
    $stmt = mysqli_prepare($conn,
        "SELECT a.appointment_time, s.duration_minutes
         FROM appointments a
         JOIN services s ON a.service_id = s.id
         WHERE a.appointment_date = ?
           AND a.staff_id = ?
           AND a.status != 'cancelled'"
    );

    mysqli_stmt_bind_param($stmt, "si", $date, $staff_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $busy = [];
    while ($r = mysqli_fetch_assoc($result)) {
        $exStart = (int)((strtotime($r["appointment_time"]) - strtotime("00:00:00")) / 60);
        $exEnd   = $exStart + (int)$r["duration_minutes"];
        $busy[] = ["start" => $exStart, "end" => $exEnd];
    }

    $today = date('Y-m-d');
    $nowMin = (int)date('H') * 60 + (int)date('i');

    $slots = [];
    for ($m = $openMin; $m < $closeMin; $m += 30) {
        $slotEnd = $m + $duration;
        $slot = sprintf("%02d:%02d:00", floor($m / 60), $m % 60);

        $available = true;
        $reason = "";

        if ($slotEnd > $closeMin) {
            $available = false;
            $reason = "Үйлчилгээний хугацаа ажлын цагт багтахгүй";
        }

        if ($available && $date === $today && $m <= $nowMin) {
            $available = false;
            $reason = "Өнгөрсөн цаг";
        }

        if ($available) {
            foreach ($busy as $b) {
                // Интервал давхцаж байвал сонгох боломжгүй.
                // Жишээ: өмнөх захиалга 10:00-11:30 бол 09:30, 10:00, 10:30, 11:00 эхлэх цагууд хаагдана.
                if ($m < $b["end"] && $slotEnd > $b["start"]) {
                    $available = false;
                    $reason = "Энэ цагт давхцалтай";
                    break;
                }
            }
        }

        $slots[] = [
            "time" => $slot,
            "label" => substr($slot, 0, 5),
            "available" => $available,
            "reason" => $reason
        ];
    }

    echo json_encode([
        "slots" => $slots,
        "duration" => $duration
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* POST: booking */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $customer_id      = $_SESSION["user_id"];
    $service_id       = (int)($_POST["service_id"] ?? 0);
    $appointment_date = $_POST["appointment_date"] ?? "";
    $appointment_time = $_POST["appointment_time"] ?? "";
    $staff_id         = (int)($_POST["staff_id"] ?? 0);
    $note             = trim($_POST["note"] ?? "");

    if (!$service_id || !$appointment_date || !$appointment_time || !$staff_id) {
        $error = "Бүх мэдээллийг бөглөнө үү.";
    } elseif (!in_array($appointment_time, $all_times)) {
        $error = "Цаг сонгогдоогүй байна.";
    } else {
        $selected_dt = strtotime($appointment_date . " " . $appointment_time);

        if ($selected_dt <= time()) {
            $error = "Өнгөрсөн цагт захиалга хийх боломжгүй.";
        } else {
            $duration = (int)($services[$service_id]["duration_minutes"] ?? 30);

            $start_min = (int)((strtotime($appointment_time) - strtotime("00:00:00")) / 60);
            $end_min   = $start_min + $duration;
            $open_min  = 9 * 60;
            $close_min = 17 * 60;

            $serv_name = $services[$service_id]["name"] ?? "";
            $required_role = serviceRole($serv_name, $service_role_map);

            $staffSql = "
                SELECT s.id
                FROM staff s
                JOIN staff_schedule ss ON ss.staff_id = s.id
                WHERE s.id = ?
                  AND s.is_active = 1
                  AND ss.work_date = ?
                  AND ss.is_working = 1
            ";
            if ($required_role) {
                $staffSql .= " AND s.role = ?";
            }
            $staffSql .= " LIMIT 1";

            $staffStmt = mysqli_prepare($conn, $staffSql);
            if ($required_role) {
                mysqli_stmt_bind_param($staffStmt, "iss", $staff_id, $appointment_date, $required_role);
            } else {
                mysqli_stmt_bind_param($staffStmt, "is", $staff_id, $appointment_date);
            }
            mysqli_stmt_execute($staffStmt);
            $staffRes = mysqli_stmt_get_result($staffStmt);

            if (!mysqli_fetch_assoc($staffRes)) {
                $error = "Сонгосон мэргэжилтэн энэ өдөр тухайн үйлчилгээнд ажиллахгүй байна.";
            } elseif ($start_min < $open_min || $end_min > $close_min) {
                $error = "Сонгосон үйлчилгээний хугацаа ажлын цагт багтахгүй байна.";
            } else {
                $conflict = false;

                $stmt = mysqli_prepare($conn,
                    "SELECT a.appointment_time, s.duration_minutes
                     FROM appointments a
                     JOIN services s ON a.service_id = s.id
                     WHERE a.appointment_date = ?
                       AND a.staff_id = ?
                       AND a.status != 'cancelled'"
                );

                mysqli_stmt_bind_param($stmt, "si", $appointment_date, $staff_id);
                mysqli_stmt_execute($stmt);
                $res2 = mysqli_stmt_get_result($stmt);

                while ($r = mysqli_fetch_assoc($res2)) {
                    $ex_start = (int)((strtotime($r["appointment_time"]) - strtotime("00:00:00")) / 60);
                    $ex_end = $ex_start + (int)$r["duration_minutes"];

                    if ($start_min < $ex_end && $end_min > $ex_start) {
                        $conflict = true;
                        break;
                    }
                }

                if ($conflict) {
                    $error = "Энэ мэргэжилтний тухайн цаг үйлчилгээний хугацаатай давхцаж байна. Өөр цаг сонгоно уу.";
                } else {
                $ins = mysqli_prepare($conn,
                    "INSERT INTO appointments
                     (customer_id, service_id, appointment_date, appointment_time, staff_id, note)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $ins,
                    "iissis",
                    $customer_id,
                    $service_id,
                    $appointment_date,
                    $appointment_time,
                    $staff_id,
                    $note
                );

                if (mysqli_stmt_execute($ins)) {
                    $success = "Цаг амжилттай захиалагдлаа! " .
                        htmlspecialchars($appointment_date) . " өдрийн " .
                        substr($appointment_time, 0, 5) . " цагт.";
                } else {
                    $error = mysqli_error($conn);
                }
            }
        }
    }
}

    }

$user_id = $_SESSION["user_id"];
$prev_staff = null;

$prev_stmt = mysqli_prepare($conn,
    "SELECT staff_id FROM appointments
     WHERE customer_id = ?
       AND staff_id IS NOT NULL
     ORDER BY created_at DESC
     LIMIT 1"
);

mysqli_stmt_bind_param($prev_stmt, "i", $user_id);
mysqli_stmt_execute($prev_stmt);
$prev_res = mysqli_stmt_get_result($prev_stmt);

if ($row = mysqli_fetch_assoc($prev_res)) {
    $prev_staff = $row["staff_id"];
}

include "includes/header.php";
?>

<div class="row justify-content-center">
<div class="col-md-8 col-lg-7">

<div class="booking-progress mb-4">
    <div class="progress-step">
        <div class="step-num active" id="sn1">1</div>
        <div class="step-label active" id="sl1">Үйлчилгээ</div>
    </div>
    <div class="progress-line"></div>

    <div class="progress-step">
        <div class="step-num" id="sn2">2</div>
        <div class="step-label" id="sl2">Өдөр & Мэргэжилтэн</div>
    </div>
    <div class="progress-line"></div>

    <div class="progress-step">
        <div class="step-num" id="sn3">3</div>
        <div class="step-label" id="sl3">Цаг сонгох</div>
    </div>
    <div class="progress-line"></div>

    <div class="progress-step">
        <div class="step-num" id="sn4">4</div>
        <div class="step-label" id="sl4">Баталгаажуулах</div>
    </div>
</div>

<div class="card p-4 p-md-5">
    <div class="page-header">
        <h3>Цаг захиалах</h3>
    </div>

    <?php if (!empty($success)) { ?>
        <div class="alert alert-success mb-4">
            ✓ <?php echo $success; ?>
            <br>
            <a href="my_appointments.php" style="color:#2d6b3e;font-weight:500;font-size:0.88rem;">
                Миний цагуудыг үзэх →
            </a>
        </div>
    <?php } ?>

    <?php if (!empty($error)) { ?>
        <div class="alert alert-danger mb-4">
            ✗ <?php echo htmlspecialchars($error); ?>
        </div>
    <?php } ?>

    <form method="POST" id="bookForm">

        <div class="mb-4" id="step1">
            <label>Үйлчилгээ сонгох</label>
            <select name="service_id" id="service_id" class="form-control">
                <option value="">── Үйлчилгээ сонгоно уу ──</option>

                <?php foreach ($services as $s) { ?>
                    <option value="<?php echo $s['id']; ?>"
                            data-duration="<?php echo $s['duration_minutes']; ?>"
                        <?php if (isset($_POST['service_id']) && $_POST['service_id'] == $s['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($s['name']); ?>
                        — <?php echo $s['duration_minutes']; ?> мин
                        — <?php echo number_format($s['price']); ?>₮
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="mb-4" id="step2" style="display:none;">
            <label>Өдөр сонгох</label>
            <input type="date"
                   name="appointment_date"
                   id="appointment_date"
                   class="form-control"
                   min="<?php echo date('Y-m-d'); ?>"
                   value="<?php echo htmlspecialchars($_POST['appointment_date'] ?? ''); ?>">
        </div>

        <div class="mb-4" id="step3" style="display:none;">
            <label>Мэргэжилтэн сонгох</label>
            <div id="staffLoading" style="display:none;font-size:0.85rem;color:var(--text-light);padding:10px 0;">
                ⏳ Уншиж байна...
            </div>
            <div id="staffGrid" class="staff-grid"></div>
            <input type="hidden" name="staff_id" id="staff_id_input" value="">
        </div>

        <div class="mb-4" id="step4" style="display:none;">
            <label>Цаг сонгох</label>
            <div id="timeLoading" style="display:none;font-size:0.85rem;color:var(--text-light);padding:10px 0;">
                ⏳ Боломжит цагуудыг шалгаж байна...
            </div>
            <div id="timeGrid" class="time-grid"></div>
            <input type="hidden" name="appointment_time" id="appointment_time_input" value="">
        </div>

        <div id="step5" style="display:none;">
            <div id="bookingSummary"
                 style="background:var(--warm-white);border-radius:var(--radius-sm);padding:18px 20px;margin-bottom:20px;border:1.5px solid var(--brown-pale);">
                <div style="font-size:0.72rem;letter-spacing:2px;text-transform:uppercase;color:var(--text-light);margin-bottom:10px;">
                    Захиалгын мэдээлэл
                </div>
                <div id="summaryContent" style="font-size:0.9rem;color:var(--text-dark);line-height:2;"></div>
            </div>

            <div class="mb-4">
                <label>
                    Нэмэлт тэмдэглэл
                    <span style="color:var(--text-light);font-size:0.75rem;letter-spacing:0;">(заавал биш)</span>
                </label>
                <textarea name="note"
                          class="form-control"
                          rows="3"
                          placeholder="Тусгай хүсэлт байвал бичнэ үү..."><?php echo htmlspecialchars($_POST['note'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn-main w-100" style="padding:15px;font-size:0.9rem;">
                Цаг захиалах →
            </button>
        </div>

    </form>
</div>
</div>
</div>

<script>
const prevStaffId = <?php echo $prev_staff ? (int)$prev_staff : 'null'; ?>;
let selectedStaffId = null;
let selectedTime = null;
let currentDuration = 30;

function updateProgress(step) {
    for (let i = 1; i <= 4; i++) {
        const num = document.getElementById('sn' + i);
        const lbl = document.getElementById('sl' + i);

        if (i < step) {
            num.className = 'step-num done';
            num.textContent = '✓';
            lbl.className = 'step-label active';
        } else if (i === step) {
            num.className = 'step-num active';
            num.textContent = i;
            lbl.className = 'step-label active';
        } else {
            num.className = 'step-num';
            num.textContent = i;
            lbl.className = 'step-label';
        }
    }
}

function showSteps(...ids) {
    ['step2', 'step3', 'step4', 'step5'].forEach(id => {
        document.getElementById(id).style.display = ids.includes(id) ? 'block' : 'none';
    });
}

document.getElementById('service_id').addEventListener('change', function () {
    const sid = this.value;

    selectedStaffId = null;
    selectedTime = null;

    document.getElementById('staff_id_input').value = '';
    document.getElementById('appointment_time_input').value = '';
    document.getElementById('staffGrid').innerHTML = '';
    document.getElementById('timeGrid').innerHTML = '';
    document.getElementById('appointment_date').value = '';

    if (!sid) {
        showSteps();
        updateProgress(1);
        return;
    }

    currentDuration = parseInt(this.options[this.selectedIndex].dataset.duration) || 30;

    showSteps('step2');
    updateProgress(1);
});

document.getElementById('appointment_date').addEventListener('change', function () {
    const date = this.value;
    const servId = document.getElementById('service_id').value;

    if (!date || !servId) return;

    selectedStaffId = null;
    selectedTime = null;

    document.getElementById('staff_id_input').value = '';
    document.getElementById('appointment_time_input').value = '';
    document.getElementById('staffGrid').innerHTML = '';
    document.getElementById('timeGrid').innerHTML = '';

    showSteps('step2', 'step3');
    updateProgress(2);

    loadStaff(date, servId);
});

function loadStaff(date, servId) {
    const grid = document.getElementById('staffGrid');
    const loading = document.getElementById('staffLoading');

    grid.innerHTML = '';
    loading.style.display = 'block';

    fetch(`book.php?ajax_staff=1&date=${encodeURIComponent(date)}&service_id=${encodeURIComponent(servId)}`)
        .then(r => r.json())
        .then(staff => {
            loading.style.display = 'none';

            if (!staff.length) {
                grid.innerHTML = '<div style="color:var(--text-light);font-size:0.85rem;padding:10px 0;">⚠️ Энэ өдөр боломжит мэргэжилтэн байхгүй байна. Өөр өдөр сонгоно уу.</div>';
                return;
            }

            let autoId = null;

            if (staff.length === 1) {
                autoId = staff[0].id;
            } else if (prevStaffId && staff.find(s => s.id == prevStaffId)) {
                autoId = prevStaffId;
            }

            renderStaff(staff, autoId);

            if (autoId) {
                const autoStaff = staff.find(s => s.id == autoId);
                selectStaff(autoStaff, staff);
            }
        })
        .catch(err => {
            console.error(err);
            loading.style.display = 'none';
            grid.innerHTML = '<div style="color:#c0392b;font-size:0.85rem;">Алдаа гарлаа. Дахин оролдоно уу.</div>';
        });
}

function renderStaff(staff, selectedId = null) {
    const grid = document.getElementById('staffGrid');
    grid.innerHTML = '';

    staff.forEach(s => {
        const btn = document.createElement('div');
        const isSelected = selectedId && s.id == selectedId;

        btn.className = 'staff-btn' + (isSelected ? ' selected' : '');
        btn.dataset.id = s.id;

        btn.innerHTML = `
            <div class="staff-avatar">${s.avatar_initials ?? ''}</div>
            <div class="staff-name">${s.full_name ?? ''}</div>
            <div class="staff-role">${s.role_label ?? ''}</div>
            ${isSelected ? '<span class="staff-badge">✓ Сонгогдсон</span>' : ''}
        `;

        btn.onclick = () => selectStaff(s, staff);
        grid.appendChild(btn);
    });
}

function selectStaff(s, allStaff) {
    selectedStaffId = s.id;

    document.getElementById('staff_id_input').value = s.id;
    document.getElementById('appointment_time_input').value = '';

    selectedTime = null;

    renderStaff(allStaff, s.id);

    showSteps('step2', 'step3', 'step4');
    updateProgress(3);

    loadTimes();
}

function loadTimes() {
    const date = document.getElementById('appointment_date').value;
    const servId = document.getElementById('service_id').value;

    if (!date || !servId || !selectedStaffId) return;

    const grid = document.getElementById('timeGrid');
    const loading = document.getElementById('timeLoading');

    grid.innerHTML = '';
    loading.style.display = 'block';

    fetch(`book.php?ajax_slots=1&date=${encodeURIComponent(date)}&staff_id=${encodeURIComponent(selectedStaffId)}&service_id=${encodeURIComponent(servId)}`)
        .then(r => r.json())
        .then(data => {
            loading.style.display = 'none';

            const slots = data.slots || [];
            const duration = data.duration || 30;
            currentDuration = duration;

            if (!slots.length) {
                grid.innerHTML = '<div style="color:var(--text-light);font-size:0.85rem;padding:10px 0;">⚠️ Энэ артист энэ өдөр ажиллахгүй байна.</div>';
                return;
            }

            slots.forEach(item => {
                const btn = document.createElement('div');
                btn.textContent = item.label;

                if (item.available) {
                    btn.className = 'time-btn';
                    btn.onclick = () => selectTime(item.time, btn);
                } else {
                    btn.className = item.reason === 'Өнгөрсөн цаг' ? 'time-btn past' : 'time-btn booked';
                    btn.title = item.reason || 'Боломжгүй цаг';
                }

                grid.appendChild(btn);
            });
        })
        .catch(err => {
            console.error(err);
            loading.style.display = 'none';
            grid.innerHTML = '<div style="color:#c0392b;font-size:0.85rem;">Цаг татахад алдаа гарлаа.</div>';
        });
}

function selectTime(slot, btn) {
    document.querySelectorAll('.time-btn').forEach(b => b.classList.remove('selected'));

    btn.classList.add('selected');

    selectedTime = slot;
    document.getElementById('appointment_time_input').value = slot;

    updateSummary();

    showSteps('step2', 'step3', 'step4', 'step5');
    updateProgress(4);

    document.getElementById('step5').scrollIntoView({
        behavior: 'smooth',
        block: 'nearest'
    });
}

function updateSummary() {
    const servSel = document.getElementById('service_id');
    const servName = servSel.options[servSel.selectedIndex]?.text || '';
    const date = document.getElementById('appointment_date').value;
    const time = selectedTime ? selectedTime.substring(0, 5) : '';
    const staffName = document.querySelector('.staff-btn.selected .staff-name')?.textContent || '';

    document.getElementById('summaryContent').innerHTML = `
        <div><strong>Үйлчилгээ:</strong> ${servName}</div>
        <div><strong>Өдөр:</strong> ${date}</div>
        <div><strong>Цаг:</strong> ${time}</div>
        <div><strong>Мэргэжилтэн:</strong> ${staffName}</div>
    `;
}
</script>

</body>
</html>