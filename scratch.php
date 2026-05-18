<?php
require_once 'config/Database.php';
$db = (new Database())->getConnection();

// Bước 1: Thêm cột genre vào movies nếu chưa có
try {
    $db->exec("ALTER TABLE movies ADD COLUMN genre VARCHAR(100) DEFAULT 'Hành động' AFTER description");
    echo "✅ Đã thêm cột 'genre'<br>";
} catch (PDOException $e) {
    echo "ℹ️ Cột 'genre' đã tồn tại hoặc lỗi: " . $e->getMessage() . "<br>";
}

// Bước 2: Đổi enum status để thêm 'upcoming'
try {
    $db->exec("ALTER TABLE movies MODIFY COLUMN status ENUM('showing','upcoming','stopped') DEFAULT 'showing'");
    echo "✅ Đã cập nhật enum status (thêm 'upcoming')<br>";
} catch (PDOException $e) {
    echo "❌ Lỗi cập nhật status: " . $e->getMessage() . "<br>";
}

// Kiểm tra kết quả
$stmt = $db->query("DESCRIBE movies");
$cols = $stmt->fetchAll();
echo "<pre>";
foreach ($cols as $col) {
    echo "{$col['Field']} | {$col['Type']} | default: {$col['Default']}\n";
}
echo "</pre>";
?>
