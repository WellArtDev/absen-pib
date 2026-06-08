<?php
// Logout - clear cookies and redirect
setcookie('absen_token', '', time() - 3600, '/');
setcookie('absen_user', '', time() - 3600, '/');
header('Location: index.php');
exit;
