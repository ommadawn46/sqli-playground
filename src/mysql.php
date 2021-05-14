<?php
include "config.php";

echo "<h1>MySQL</h1>";
echo "<h2>params:</h2>";
$user = $_REQUEST['user'];
$pass = $_REQUEST['pass'];
echo "user=" . htmlspecialchars($user) . ", ";
echo "pass=" . htmlspecialchars($pass);

echo "<h2>query:</h2>";
$query = "SELECT id, username, password FROM users WHERE username = '$user' and password = '$pass'";
echo htmlspecialchars($query);

echo "<h2>result:</h2>";
$rows = $mysql_db->query($query);
if ($rows) {
    foreach ($rows as $row) {
        echo "id=" . htmlspecialchars($row[0]) . ", ";
        echo "username=" . htmlspecialchars($row[1]) . ", ";
        echo "password=" . htmlspecialchars($row[2]);
        echo "<br/>";
    }
}
