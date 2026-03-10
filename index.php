<?php
session_start();
$filename = 'profile.json';

// Načtení dat
if (!file_exists($filename)) {
    die("Soubor profile.json neexistuje!");
}
$data = json_decode(file_get_contents($filename), true);

// Hlášky
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';
unset($_SESSION['message'], $_SESSION['messageType']);

// MAZÁNÍ
if (isset($_GET['delete'])) {
    $index = (int)$_GET['delete'];
    if (isset($data['interests'][$index])) {
        array_splice($data['interests'], $index, 1);
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $_SESSION['message'] = "Zájem byl odstraněn.";
        $_SESSION['messageType'] = "success";
    }
    header("Location: index.php");
    exit;
}

// PŘIDÁVÁNÍ / EDITACE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['interest_value'])) {
    $value = trim($_POST['interest_value']);
    $edit_index = isset($_POST['edit_index']) ? (int)$_POST['edit_index'] : -1;

    if (empty($value)) {
        $_SESSION['message'] = "Pole nesmí být prázdné.";
        $_SESSION['messageType'] = "error";
    } else {
        $is_duplicate = false;
        foreach ($data['interests'] as $idx => $existing) {
            if (strtolower($existing) === strtolower($value) && $idx !== $edit_index) {
                $is_duplicate = true;
                break;
            }
        }

        if ($is_duplicate) {
            $_SESSION['message'] = "Tento zájem už existuje.";
            $_SESSION['messageType'] = "error";
        } else {
            if ($edit_index !== -1) {
                $data['interests'][$edit_index] = $value;
            } else {
                $data['interests'][] = $value;
            }
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $_SESSION['message'] = "Uloženo.";
            $_SESSION['messageType'] = "success";
        }
    }
    header("Location: index.php");
    exit;
}

$edit_mode = false;
$edit_val = "";
if (isset($_GET['edit'])) {
    $idx = (int)$_GET['edit'];
    if (isset($data['interests'][$idx])) {
        $edit_mode = true;
        $edit_val = $data['interests'][$idx];
        $current_edit_index = $idx;
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Profil 5.0</title>
</head>
<body>
    <h1><?php echo htmlspecialchars($data['name'] ?? 'Jméno'); ?></h1>
    
    <h2>Zájmy</h2>
    <?php foreach (($data['interests'] ?? []) as $index => $interest): ?>
        <p>
            <?php echo htmlspecialchars($interest); ?>
            <a href="?edit=<?php echo $index; ?>">[Upravit]</a>
            <a href="?delete=<?php echo $index; ?>" onclick="return confirm('Smazat?')">[Smazat]</a>
        </p>
    <?php endforeach; ?>

    <?php if ($message): ?>
        <p class="<?php echo $messageType; ?>"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="interest_value" value="<?php echo htmlspecialchars($edit_val); ?>" required>
        <?php if ($edit_mode): ?>
            <input type="hidden" name="edit_index" value="<?php echo $current_edit_index; ?>">
        <?php endif; ?>
        <button type="submit"><?php echo $edit_mode ? "Upravit" : "Přidat"; ?></button>
    </form>
</body>
</html>