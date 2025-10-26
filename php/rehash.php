<?php
// Generate a hashed password
$plain_password = 'admin@1234';
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

echo "Your hashed password is:<br>";
echo "<pre>$hashed_password</pre>";
?>
