<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

// Get the last registered user
$query = "SELECT user_id, username, email, verification_code, verification_expires, email_verified 
          FROM users 
          ORDER BY user_id DESC 
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Last Registered User:</h2>";
echo "<pre>";
echo "User ID: " . $user['user_id'] . "\n";
echo "Username: " . $user['username'] . "\n";
echo "Email: " . $user['email'] . "\n";
echo "Verification Code: " . $user['verification_code'] . "\n";
echo "Code Expires: " . $user['verification_expires'] . "\n";
echo "Email Verified: " . ($user['email_verified'] ? 'YES' : 'NO') . "\n";
echo "\n";
echo "Current Time: " . date('Y-m-d H:i:s') . "\n";

if($user['verification_expires']) {
    if(strtotime($user['verification_expires']) > time()) {
        echo "<span style='color: green; font-weight: bold;'>✓ Code is VALID</span>\n";
    } else {
        echo "<span style='color: red; font-weight: bold;'>✗ Code EXPIRED</span>\n";
    }
}
echo "</pre>";
?>