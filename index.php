<?php
include "includes/db.php";
include "includes/header.php";

// Үйлчилгээнүүдийг ангиллаар авах
$result = mysqli_query($conn, "SELECT * FROM services ORDER BY name ASC");
$services = [];
while ($row = mysqli_fetch_assoc($result)) {
    $services[] = $row;
}

// Ангиллын тохиргоо
$categories = [
    [
        "key"      => "hair",
        "title"    => "Үс засалт",
        "img"      => "category-img-hair",
        "badge"    => "Хит",
        "desc"     => "Таны үсийг мэргэжлийн гарт найдаарай. Засалт, тайрах үйлчилгээ.",
        "keywords" => ["Үс засалт", "засалт", "тайрах"],
    ],
    [
        "key"      => "color",
        "title"    => "Үс будалт",
        "img"      => "category-img-color",
        "badge"    => "",
        "desc"     => "Балаяж, омбрэ, бүтэн будалт — таны хүссэн өнгийг бодитоор.",
        "keywords" => ["Үс будалт", "будалт"],
    ],
    [
        "key"      => "nails",
        "title"    => "Маникюр & Педикюр",
        "img"      => "category-img-nails",
        "badge"    => "",
        "desc"     => "Гар хөлийн хумс арчилгаа, дизайн, гелийн бүрэлт.",
        "keywords" => ["Маникюр", "Педикюр", "хумс"],
    ],
    [
        "key"      => "makeup",
        "title"    => "Нүүр будалт",
        "img"      => "category-img-makeup",
        "badge"    => "Premium",
        "desc"     => "Өдөр тутмын болон тусгай үеийн нүүр будалт.",
        "keywords" => ["Make-up", "будалт", "нүүр"],
    ],
    [
        "key"      => "lash",
        "title"    => "Сормуус",
        "img"      => "category-img-lash",
        "badge"    => "",
        "desc"     => "Сормуус суулгалт, сунгалт, засвар — харцыг гэрэлтүүлэх.",
        "keywords" => ["Сормуус"],
    ],
];

// Үйлчилгээг ангилалд харгалзуулах
foreach ($categories as &$cat) {
    $cat["services"] = [];
    foreach ($services as $s) {
        foreach ($cat["keywords"] as $kw) {
            if (mb_stripos($s["name"], $kw) !== false || mb_stripos($s["description"] ?? "", $kw) !== false) {
                $cat["services"][] = $s;
                break;
            }
        }
    }
}
unset($cat);

// Ангилалд орохгүй үйлчилгээнүүд → "Бусад" ангилал
$usedIds = [];
foreach ($categories as $cat) {
    foreach ($cat["services"] as $s) {
        $usedIds[] = $s["id"];
    }
}

$others = array_filter($services, fn($s) => !in_array($s["id"], $usedIds));
if (!empty($others)) {
    $categories[] = [
        "key"      => "other",
        "title"    => "Бусад үйлчилгээ",
        "img"      => "category-img-hair",
        "badge"    => "",
        "desc"     => "Бусад тусгай үйлчилгээнүүд.",
        "keywords" => [],
        "services" => array_values($others),
    ];
}
?>

<!-- HERO -->
<div class="hero-section">
    <div class="hero-left">
        <div class="hero-eyebrow">Beauty Book Salon</div>
        <h1 class="hero-title">
            Таны гоо <em>сайхныг</em><br>
            хамгаалах газар
        </h1>
        <p class="hero-subtitle">
            Мэргэжлийн үсчин, гоо сайхны мэргэжилтнүүдтэй цаг захиалж,
            өөрийгөө шинэчил.
        </p>
        <div class="hero-actions">
            <?php if (!isset($_SESSION["user_id"])) { ?>
                <a href="register.php" class="btn-main">Эхлэх</a>
                <a href="login.php" class="btn-outline">Нэвтрэх</a>
            <?php } else { ?>
                <a href="book.php" class="btn-main">Цаг захиалах</a>
                <a href="my_appointments.php" class="btn-outline">Миний цагууд</a>
            <?php } ?>
        </div>
    </div>

    <div class="hero-right">
        <div class="slideshow" id="slideshow">
            <div class="slide slide-1"></div>
            <div class="slide slide-2"></div>
            <div class="slide slide-3"></div>
            <div class="slide slide-4"></div>
        </div>
        <div class="slide-overlay"></div>

        <div class="slide-arrows">
            <button class="slide-arrow" id="prevSlide">&#8592;</button>
            <button class="slide-arrow" id="nextSlide">&#8594;</button>
        </div>

        <div class="slideshow-controls" id="dots">
            <button class="slide-dot active" data-index="0"></button>
            <button class="slide-dot" data-index="1"></button>
            <button class="slide-dot" data-index="2"></button>
            <button class="slide-dot" data-index="3"></button>
        </div>
    </div>
</div>

<!-- SERVICES -->
<div class="section-container">
    <div class="section-header">
        <span class="section-eyebrow">Манай үйлчилгээ</span>
        <h2 class="section-title">Та юу хайж байна вэ?</h2>
    </div>

    <div class="service-categories">
        <?php foreach ($categories as $cat) { ?>
            <div class="category-card" data-cat="<?php echo $cat['key']; ?>">
                <div class="category-img <?php echo $cat['img']; ?>">
                    <?php if (!empty($cat['badge'])) { ?>
                        <span class="category-badge"><?php echo $cat['badge']; ?></span>
                    <?php } ?>
                </div>
                <div class="category-body">
                    <div class="category-name"><?php echo $cat['title']; ?></div>
                    <div class="category-desc"><?php echo $cat['desc']; ?></div>
                    <?php if (!empty($cat['services'])) { ?>
                        <button class="toggle-btn" onclick="toggleCat(this)">
                            <span class="toggle-icon">+</span>
                            <span class="toggle-text">Үзэх (<?php echo count($cat['services']); ?>)</span>
                        </button>
                    <?php } ?>
                </div>

                <?php if (!empty($cat['services'])) { ?>
                    <div class="category-services">
                        <?php foreach ($cat['services'] as $s) { ?>
                            <div class="service-item">
                                <span class="service-item-name"><?php echo htmlspecialchars($s['name']); ?></span>
                                <span class="service-item-info">
                                    <span><?php echo $s['duration_minutes']; ?> мин</span>
                                    <span class="service-item-price"><?php echo number_format($s['price']); ?>₮</span>
                                </span>
                            </div>
                        <?php } ?>
                        <div style="margin-top:14px;">
                            <a href="<?php echo isset($_SESSION['user_id']) ? 'book.php' : 'login.php'; ?>" class="btn-main" style="font-size:0.78rem; padding:10px 22px;">
                                Цаг захиалах
                            </a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>

<script>
// Slideshow
let current = 0;
const total = 4;
const slideshow = document.getElementById('slideshow');
const dots = document.querySelectorAll('.slide-dot');

function goTo(n) {
    current = (n + total) % total;
    slideshow.style.transform = `translateX(-${current * 100}%)`;
    dots.forEach((d, i) => d.classList.toggle('active', i === current));
}

document.getElementById('nextSlide').onclick = () => goTo(current + 1);
document.getElementById('prevSlide').onclick = () => goTo(current - 1);
dots.forEach(d => d.onclick = () => goTo(+d.dataset.index));

setInterval(() => goTo(current + 1), 5000);

// Category toggle
function toggleCat(btn) {
    const card = btn.closest('.category-card');
    const isOpen = card.classList.toggle('open');
    btn.querySelector('.toggle-text').textContent = isOpen
        ? 'Хаах'
        : `Үзэх (${card.querySelectorAll('.service-item').length})`;
}
</script>

</div>
</body>
</html>
