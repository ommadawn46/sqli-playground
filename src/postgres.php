<?php
include "config.php";

echo "<h1>PostgreSQL</h1>";
echo "<h2>params:</h2>";
$user = $_REQUEST['user'];
$pass = $_REQUEST['pass'];
echo "user=" . htmlspecialchars($user) . ", ";
echo "pass=" . htmlspecialchars($pass);

echo "<h2>query:</h2>";
$query = "SELECT id, username, password FROM users WHERE username = '$user' and password = '$pass'";
echo htmlspecialchars($query);

echo "<h2>result:</h2>";
$result = pg_query($pg_conn, $query);
$rows = pg_fetch_all($result);
if ($rows) {
    foreach ($rows as $row) {
        echo "id=" . htmlspecialchars($row['id']) . ", ";
        echo "username=" . htmlspecialchars($row['username']) . ", ";
        echo "password=" . htmlspecialchars($row['password']);
        echo "<br/>";
    }
}
