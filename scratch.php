<?php
require_once 'config/Database.php';
$db = (new Database())->getConnection();
echo "=== DESCRIBE booking_details ===\n";
$cols = $db->query("DESCRIBE booking_details")->fetchAll();
foreach ($cols as $c) echo "  {$c['Field']} | {$c['Type']} | key:{$c['Key']}\n";

echo "\n=== SAMPLE DATA in seats ===\n";
$rows = $db->query("SELECT * FROM seats LIMIT 10")->fetchAll();
foreach ($rows as $r) echo "  id:{$r['id']} room:{$r['room_id']} row:{$r['row_name']} col:{$r['col_number']} type:{$r['type']}\n";
if (empty($rows)) echo "  (no seats yet)\n";

echo "\n=== SAMPLE DATA in rooms ===\n";
$rows = $db->query("SELECT * FROM rooms LIMIT 10")->fetchAll();
foreach ($rows as $r) echo "  id:{$r['id']} cinema:{$r['cinema_id']} name:{$r['name']} rows:{$r['total_rows']} cols:{$r['total_columns']}\n";
?>
