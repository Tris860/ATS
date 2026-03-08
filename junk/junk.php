<?php
$passkey="3Techhub@2024";
$hashedPasskey = password_hash($passkey, PASSWORD_DEFAULT);
echo "Hashed Passkey: " . $hashedPasskey;

?>