<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysql_db = new PDO("mysql:host=mysql;dbname=sqli", "root", "password");
$pg_conn = pg_connect("host=postgres port=5432 dbname=sqli user=postgres password=password");
