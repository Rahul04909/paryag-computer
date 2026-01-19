<?php
if (!isset($_GET['id'])) {
    echo "No test selected.";
    exit;
}
echo "<h1>Steno Test Player - To Be Implemented</h1>";
echo "<p>Test ID: " . htmlspecialchars($_GET['id']) . "</p>";
echo "<a href='steno/index.php'>Back to List</a>";
?>
