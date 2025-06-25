<?php
// src/utils/generate_password_hash.php

// The plain text password to hash
$plainPassword = 'admin_password';

// Generate the password hash
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

// Output the information
echo "Password to hash: " . htmlspecialchars($plainPassword) . "\n";
echo "Generated hash: " . htmlspecialchars($hashedPassword) . "\n";
echo "\n";
echo "SQL Update statement example:\n";
// Note: In a real SQL query, ensure the hash is properly escaped if not using prepared statements.
// However, since this output is for manual use or a script, direct interpolation is shown.
echo "UPDATE users SET password_hash = '" . $hashedPassword . "' WHERE username = 'admin';\n";

?>
