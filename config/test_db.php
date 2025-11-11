<?php
require_once __DIR__ . '/../config/db.php';

$db = Database::getInstance()->getConnection();

if ($db) {
    echo "Database connection successful!";
} else {
    echo "Database connection failed!";
}
?>
