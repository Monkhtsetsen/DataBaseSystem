<?php
include "includes/db.php";
include "includes/header.php";

$full_name = $phone = $email = "";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST["full_name"]);
    $phone     = trim($_POST["phone"]);
    $email     = trim($_POST["email"]);
    $password  = $_POST["password"];

    if (empty($full_name) || empty($phone) || empty($email) || empty($password)) {
        $error = "Бүх талбарыг бөглөнө үү.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email хэлбэр буруу байна.";
    } elseif (strlen($password) < 6) {
        $error = "Нууц үг хамгийн багадаа 6 тэмдэгттэй байна.";
    } elseif (!preg_match('/^[0-9]{8}$/', $phone)) {
        $error = "Утасны дугаар 8 оронтой байна.";
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Энэ email аль хэдийн бүртгэлтэй байна.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = mysqli_prepare($conn, "INSERT INTO users (full_name, phone, email, password) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $full_name, $phone, $email, $hashed);

            if (mysqli_stmt_execute($stmt)) {
                $success  = "Амжилттай бүртгэгдлээ.";
                $full_name = $phone = $email = "";
            } else {
                $error = "Бүртгэл амжилтгүй боллоо. Дахин оролдоно уу.";
            }
        }
    }
}
?>

<div class="auth-container">
<div class="container">
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card auth-card">
            <div class="auth-logo">✦ BeautyBook</div>
            <div class="auth-subtitle">Шинэ хэрэглэгч бүртгүүлэх</div>

            <?php if ($error) { ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>
            <?php if ($success) { ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <br><a href="login.php" style="color:#2d6b3e;font-weight:500;">Одоо нэвтрэх →</a>
                </div>
            <?php } ?>

            <form method="POST" id="regForm" novalidate>
                <!-- Нэр -->
                <div class="mb-3">
                    <label for="full_name">Овог нэр</label>
                    <input type="text" id="full_name" name="full_name"
                           class="form-control"
                           placeholder="Жишээ: Болд Бат"
                           value="<?php echo htmlspecialchars($full_name); ?>"
                           autocomplete="name">
                    <div class="validation-hint" id="hint_name"></div>
                </div>

                <!-- Утас -->
                <div class="mb-3">
                    <label for="phone">Утасны дугаар</label>
                    <input type="tel" id="phone" name="phone"
                           class="form-control"
                           placeholder="8 оронтой дугаар"
                           value="<?php echo htmlspecialchars($phone); ?>"
                           maxlength="8">
                    <div class="validation-hint" id="hint_phone">📱 8 оронтой тоо оруулна уу (жишээ: 99112233)</div>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email">И-мэйл хаяг</label>
                    <input type="email" id="email" name="email"
                           class="form-control"
                           placeholder="example@gmail.com"
                           value="<?php echo htmlspecialchars($email); ?>"
                           autocomplete="email">
                    <div class="validation-hint" id="hint_email">📧 Зөв формат: name@domain.com</div>
                </div>

                <!-- Нууц үг -->
                <div class="mb-3">
                    <label for="password">Нууц үг</label>
                    <div style="position:relative;">
                        <input type="password" id="password" name="password"
                               class="form-control"
                               placeholder="Хамгийн багадаа 6 тэмдэгт"
                               autocomplete="new-password">
                        <button type="button" onclick="togglePwd()" id="pwdToggle"
                                style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-light);font-size:1rem;">
                            👁
                        </button>
                    </div>
                    <!-- Password strength -->
                    <div id="pwdStrength" style="margin-top:8px;display:none;">
                        <div style="display:flex;gap:4px;margin-bottom:4px;">
                            <div class="strength-bar" id="sb1" style="height:3px;flex:1;border-radius:2px;background:var(--brown-pale);transition:background 0.3s;"></div>
                            <div class="strength-bar" id="sb2" style="height:3px;flex:1;border-radius:2px;background:var(--brown-pale);transition:background 0.3s;"></div>
                            <div class="strength-bar" id="sb3" style="height:3px;flex:1;border-radius:2px;background:var(--brown-pale);transition:background 0.3s;"></div>
                            <div class="strength-bar" id="sb4" style="height:3px;flex:1;border-radius:2px;background:var(--brown-pale);transition:background 0.3s;"></div>
                        </div>
                        <div class="validation-hint show" id="hint_pwd"></div>
                    </div>
                </div>

                <!-- Нууц үг давтах -->
                <div class="mb-4">
                    <label for="password2">Нууц үг давтах</label>
                    <input type="password" id="password2" name="password2"
                           class="form-control"
                           placeholder="Нууц үгийг дахин оруулна уу">
                    <div class="validation-hint" id="hint_pwd2"></div>
                </div>

                <button type="submit" class="btn-main w-100" id="submitBtn" style="padding:14px;">
                    Бүртгүүлэх
                </button>
            </form>

            <div class="auth-divider">эсвэл</div>

            <p class="text-center" style="font-size:0.88rem;color:var(--text-light);">
                Бүртгэлтэй юу?
                <a href="login.php" style="color:var(--brown-mid);font-weight:500;">Нэвтрэх →</a>
            </p>
        </div>
    </div>
</div>
</div>
</div>

<script>
function showHint(id, msg, type) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.className = 'validation-hint show ' + type;
}

function setValid(inputId, hintId, msg) {
    document.getElementById(inputId).className = 'form-control is-valid';
    showHint(hintId, '✓ ' + msg, 'valid');
}

function setInvalid(inputId, hintId, msg) {
    document.getElementById(inputId).className = 'form-control is-invalid';
    showHint(hintId, '✗ ' + msg, 'invalid');
}

// Name
document.getElementById('full_name').addEventListener('input', function () {
    const v = this.value.trim();
    if (v.length === 0) {
        this.className = 'form-control';
        document.getElementById('hint_name').className = 'validation-hint';
    } else if (v.length < 2) {
        setInvalid('full_name', 'hint_name', 'Нэр хэтэрхий богино байна');
    } else {
        setValid('full_name', 'hint_name', 'Сайн байна');
    }
});

// Phone
document.getElementById('phone').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 8);
    if (this.value.length === 0) {
        this.className = 'form-control';
        document.getElementById('hint_phone').className = 'validation-hint show';
        document.getElementById('hint_phone').textContent = '📱 8 оронтой тоо оруулна уу (жишээ: 99112233)';
        document.getElementById('hint_phone').classList.remove('valid', 'invalid');
    } else if (this.value.length < 8) {
        setInvalid('phone', 'hint_phone', `${8 - this.value.length} оронтой дугаар оруулах хэрэгтэй`);
    } else {
        setValid('phone', 'hint_phone', '8 оронтой дугаар баталгаажлаа');
    }
});

// Email
document.getElementById('email').addEventListener('blur', function () {
    const v = this.value.trim();
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!v) return;
    if (!re.test(v)) {
        setInvalid('email', 'hint_email', 'Email формат буруу байна. Жишээ: name@gmail.com');
    } else {
        setValid('email', 'hint_email', 'Email хаяг зөв байна');
    }
});

// Password strength
document.getElementById('password').addEventListener('input', function () {
    const v = this.value;
    const strength = document.getElementById('pwdStrength');
    strength.style.display = v.length ? 'block' : 'none';

    let score = 0;
    if (v.length >= 6) score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v) || /[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;

    const colors = ['#e74c3c', '#e67e22', '#f1c40f', '#27ae60'];
    const labels = ['Маш сул', 'Сул', 'Дунд', 'Хүчтэй'];
    for (let i = 1; i <= 4; i++) {
        document.getElementById('sb' + i).style.background = i <= score ? colors[score - 1] : 'var(--brown-pale)';
    }
    const hint = document.getElementById('hint_pwd');
    if (v.length < 6) {
        hint.textContent = '✗ Хамгийн багадаа 6 тэмдэгт шаардагдана';
        hint.className = 'validation-hint show invalid';
        document.getElementById('password').className = 'form-control is-invalid';
    } else {
        hint.textContent = '✓ ' + labels[score - 1];
        hint.className = 'validation-hint show valid';
        document.getElementById('password').className = 'form-control is-valid';
    }

    // Re-check confirm
    const v2 = document.getElementById('password2').value;
    if (v2) checkConfirm(v2);
});

function checkConfirm(v2) {
    const v = document.getElementById('password').value;
    if (v2 === v && v.length >= 6) {
        setValid('password2', 'hint_pwd2', 'Нууц үг таарч байна');
    } else {
        setInvalid('password2', 'hint_pwd2', 'Нууц үг таарахгүй байна');
    }
}

document.getElementById('password2').addEventListener('input', function () {
    checkConfirm(this.value);
});

function togglePwd() {
    const inp = document.getElementById('password');
    inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>
