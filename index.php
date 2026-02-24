<?php
$json_data = file_get_contents('profile.json');
$data = json_decode($json_data, true);

if (!$data) {
    die('Chyba: Nepodařilo se načíst profile.json');
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Můj PHP Profil</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    </body>
</html>