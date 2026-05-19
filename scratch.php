<?php
require_once 'config/Database.php';
$db = (new Database())->getConnection();

echo "=== DESCRIBE cinemas ===\n";
foreach ($db->query("DESCRIBE cinemas")->fetchAll() as $c)
    echo "  {$c['Field']} | {$c['Type']} | key:{$c['Key']}\n";

echo "\n=== DESCRIBE bookings ===\n";
foreach ($db->query("DESCRIBE bookings")->fetchAll() as $c)
    echo "  {$c['Field']} | {$c['Type']} | key:{$c['Key']}\n";

// Migration: thêm bank fields vào cinemas
$cols = ['bank_id VARCHAR(20) DEFAULT NULL', 'bank_account_no VARCHAR(30) DEFAULT NULL',
         'bank_account_name VARCHAR(100) DEFAULT NULL'];
foreach ($cols as $col) {
    $name = explode(' ', $col)[0];
    try { $db->exec("ALTER TABLE cinemas ADD COLUMN $col"); echo "\n✅ Thêm $name vào cinemas"; }
    catch(Exception $e) { echo "\nℹ️ $name: " . $e->getMessage(); }
}
echo "\n✅ Migration hoàn tất\n";
?>
