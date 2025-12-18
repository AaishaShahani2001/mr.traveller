<?php
require "config.php";

/* ---------- Filters ---------- */
$search = trim($_GET['search'] ?? '');
$maxPrice = $_GET['price'] ?? '';

/* ---------- Pagination ---------- */
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

/* ---------- WHERE clause ---------- */
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(title LIKE ? OR country LIKE ? OR city LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($maxPrice !== '') {
    $where[] = "price <= ?";
    $params[] = $maxPrice;
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* ---------- Count ---------- */
$countStmt = $conn->prepare("SELECT COUNT(*) FROM destinations $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/* ---------- Fetch data ---------- */
$sql = "
    SELECT * FROM destinations
    $whereSql
    ORDER BY dest_id DESC
    LIMIT $perPage OFFSET $offset
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

function q($arr = []) {
    return http_build_query(array_merge($_GET, $arr));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Destinations | Mr.Traveller</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* {
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    margin: 0;
    background: #f5f7ff;
}

/* ================= BACK TO HOME ================= */
.back-home-btn {
    position: fixed;
    top: 50px;
    left: 60px;
    z-index: 1000;
    padding: 10px 16px;
    background: #ffffff;
    color: #007bff;
    border-radius: 30px;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 8px 22px rgba(0,0,0,0.15);
    transition: all 0.3s;
}

.back-home-btn:hover {
    background: #007bff;
    color: #fff;
    transform: translateY(-2px);
}

/* ================= HEADER ================= */
.page-title {
    text-align: center;
    padding: 80px 20px 20px;
}
.page-title h2 {
    font-size: 34px;
    margin-bottom: 10px;
}
.page-title p {
    color: #555;
}

/* ================= FILTERS ================= */
.filters {
    max-width: 1100px;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 18px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}

.filters form {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 14px;
}

.filters input {
    padding: 12px;
    border-radius: 12px;
    border: 1px solid #ccc;
}

.filters button {
    padding: 12px 26px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
}

/* Price range */
.price-range {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.price-range label {
    font-size: 14px;
    font-weight: 600;
}

.price-range input[type=range] {
    width: 100%;
}

/* ================= GRID ================= */
.grid {
    max-width: 1200px;
    margin: 50px auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px,1fr));
    gap: 28px;
    padding: 0 20px;
}

.card {
    background: white;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 14px 35px rgba(0,0,0,0.15);
    transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 45px rgba(0,0,0,0.2);
}

.card img {
    width: 100%;
    height: 260px;
    object-fit: contain;
    background: #f5f7ff;
}

.card-body {
    padding: 18px;
    text-align: center;
}

.card h3 {
    margin: 10px 0 6px;
    font-size: 20px;
}

.card p {
    color: #555;
    font-size: 14px;
    margin: 4px 0;
}

.price {
    font-size: 18px;
    font-weight: bold;
    color: #007bff;
    margin-top: 6px;
}

.btn {
    display: inline-block;
    margin-top: 12px;
    padding: 10px 24px;
    background: #007bff;
    color: white;
    border-radius: 30px;
    text-decoration: none;
}

/* ================= PAGINATION ================= */
.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 60px;
    flex-wrap: wrap;
}

.pagination a {
    padding: 10px 14px;
    background: white;
    border-radius: 10px;
    text-decoration: none;
    color: #333;
    font-weight: bold;
    border: 1px solid #ddd;
}

.pagination a.active {
    background: #007bff;
    color: white;
}

/* ================= RESPONSIVE ================= */
@media (max-width: 900px) {
    .filters form {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .page-title h2 {
        font-size: 26px;
    }

    .card img {
        height: 220px;
    }
}

@media (max-width: 480px) {
    .page-title h2 {
        font-size: 22px;
    }

    .back-home-btn {
        font-size: 14px;
        padding: 8px 14px;
    }
}
</style>
</head>

<body>

<a href="home.php" class="back-home-btn">← Home</a>

<div class="page-title">
    <h2>Explore Our Travel Packages</h2>
    <p>Find your perfect destination & start your journey</p>
</div>

<div class="filters">
<form>
    <input
        type="text"
        name="search"
        placeholder="Search destination, country or city"
        value="<?= htmlspecialchars($search) ?>"
    >

    <div class="price-range">
        <label>
            Max Price: $<span id="priceValue"><?= $maxPrice ?: 2000 ?></span>
        </label>
        <input
            type="range"
            name="price"
            min="100"
            max="5000"
            step="100"
            value="<?= $maxPrice ?: 2000 ?>"
            oninput="priceValue.textContent=this.value"
        >
    </div>

    <button>Search</button>
</form>
</div>

<div class="grid">
<?php if (!$destinations): ?>
    <p style="text-align:center;width:100%">No destinations found.</p>
<?php endif; ?>

<?php foreach ($destinations as $dest): ?>
<div class="card">
    <img src="uploads/<?= htmlspecialchars($dest['image']) ?>" alt="Destination">
    <div class="card-body">
        <h3><?= htmlspecialchars($dest['title']) ?></h3>
        <p><?= htmlspecialchars($dest['country']) ?> - <?= htmlspecialchars($dest['city']) ?></p>
        <p><?= htmlspecialchars($dest['duration']) ?></p>
        <div class="price">$<?= number_format($dest['price'],2) ?></div>

        <a class="btn" href="view_destination.php?id=<?= $dest['dest_id'] ?>">
            View Package
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>

<div class="pagination">
<?php for ($i=1;$i<=$totalPages;$i++): ?>
    <a class="<?= $i==$page?'active':'' ?>" href="?<?= q(['page'=>$i]) ?>">
        <?= $i ?>
    </a>
<?php endfor; ?>
</div>

</body>
</html>
