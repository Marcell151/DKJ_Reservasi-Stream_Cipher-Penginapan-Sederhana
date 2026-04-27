<?php
include 'config/config.php';
$db = new PDO('sqlite:' . DB_PATH);
echo "--- DATA RAW DATABASE (TERENKRIP) ---\n\n";
$stmt = $db->query("SELECT id, amount, method FROM payments LIMIT 5");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . "\n";
    echo "Nominal (Raw DB): " . $row['amount'] . "\n";
    echo "Metode: " . $row['method'] . "\n";
    echo "------------------------------------\n";
}
?>
