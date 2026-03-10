<?php
session_start();
$filename = 'profile.json';

// Načtení dat
if (!file_exists($filename)) {
    file_put_contents($filename, json_encode(["name"=>"Maksym", "role"=>"Student", "skills"=>[], "interests"=>[]]));
}
$data = json_decode(file_get_contents($filename), true);

// Hlášky
$message = $_SESSION['msg'] ?? '';
$messageType = $_SESSION['type'] ?? '';
unset($_SESSION['msg'], $_SESSION['type']);

// --- LOGIKA MAZÁNÍ ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (isset($data['interests'][$id])) {
        array_splice($data['interests'], $id, 1);
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $_SESSION['msg'] = "Smazáno."; $_SESSION['type'] = "success";
    }
    header("Location: index.php"); exit;
}

// --- LOGIKA PŘIDÁVÁNÍ / EDITACE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['interest_val'])) {
    $val = trim($_POST['interest_val']);
    $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : -1;

    if (!empty($val)) {
        $data['interests'] = $data['interests'] ?? [];
        $exists = false;
        foreach($data['interests'] as $i => $v) {
            if (strtolower($v) === strtolower($val) && $i !== $edit_id) $exists = true;
        }

        if ($exists) {
            $_SESSION['msg'] = "Už existuje!"; $_SESSION['type'] = "error";
        } else {
            if ($edit_id >= 0) $data['interests'][$edit_id] = $val;
            else $data['interests'][] = $val;
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $_SESSION['msg'] = "Uloženo."; $_SESSION['type'] = "success";
        }
    }
    header("Location: index.php"); exit;
}

// Příprava editace
$edit_mode = false; $edit_val = "";
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if (isset($data['interests'][$id])) { $edit_mode = true; $edit_val = $data['interests'][$id]; $curr_id = $id; }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Můj Profil 5.0</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1><?php echo htmlspecialchars($data['name'] ?? 'Jméno'); ?></h1>
    <p><strong><?php echo htmlspecialchars($data['role'] ?? 'Role'); ?></strong></p>

    <h2>Moje zájmy</h2>
    <div class="interests-container">
        <?php if (!empty($data['interests'])): ?>
            <?php foreach ($data['interests'] as $index => $interest): ?>
                <div class="tag">
                    <?php echo htmlspecialchars($interest); ?>
                    <a href="?edit=<?php echo $index; ?>" title="Upravit">✎</a>
                    <a href="?delete=<?php echo $index; ?>" onclick="return confirm('Smazat?')" title="Smazat">✖</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Zatím žádné zájmy.</p>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $messageType; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST">
        <h3><?php echo $edit_mode ? "Upravit zájem" : "Přidat nový zájem"; ?></h3>
        <div class="input-group">
            <input type="text" name="interest_val" value="<?php echo htmlspecialchars($edit_val); ?>" required placeholder="Napište něco...">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="edit_id" value="<?php echo $curr_id; ?>">
            <?php endif; ?>
            <button type="submit" class="<?php echo $edit_mode ? 'edit-btn' : ''; ?>">
                <?php echo $edit_mode ? "Uložit změny" : "Přidat"; ?>
            </button>
        </div>
        <?php if ($edit_mode): ?>
            <a href="index.php" style="display:block; margin-top:10px; color:#666;">Zrušit úpravu</a>
        <?php endif; ?>
    </form>
</body>
</html>