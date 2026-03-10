<?php
session_start();

$filename = 'profile.json';
$json_data = file_get_contents($filename);
$data = json_decode($json_data, true);

if (!$data) {
    die('Chyba: Nepodařilo se načíst profile.json');
}

// Načtení hlášek ze session
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';
unset($_SESSION['message'], $_SESSION['messageType']);

// --- LOGIKA MAZÁNÍ (DELETE) ---
if (isset($_GET['delete'])) {
    $index = (int)$_GET['delete'];
    if (isset($data['interests'][$index])) {
        array_splice($data['interests'], $index, 1); // Odstraní prvek a přečísluje pole
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $_SESSION['message'] = "Zájem byl odstraněn.";
        $_SESSION['messageType'] = "success";
    }
    header("Location: index.php");
    exit;
}

// --- LOGIKA PŘIDÁVÁNÍ A EDITACE (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['interest_value'])) {
    $value = trim($_POST['interest_value']);
    $edit_index = isset($_POST['edit_index']) ? (int)$_POST['edit_index'] : -1;

    if (empty($value)) {
        $_SESSION['message'] = "Pole nesmí být prázdné.";
        $_SESSION['messageType'] = "error";
    } else {
        // Kontrola duplicity (vynecháme aktuálně editovaný prvek)
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
                // EDITACE
                $data['interests'][$edit_index] = $value;
                $_SESSION['message'] = "Zájem byl upraven.";
            } else {
                // PŘIDÁVÁNÍ
                $data['interests'][] = $value;
                $_SESSION['message'] = "Zájem byl úspěšně přidán.";
            }
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $_SESSION['messageType'] = "success";
        }
    }
    header("Location: index.php");
    exit;
}

// Příprava pro editační formulář
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
    <title>Můj PHP Profil 5.0</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .actions { margin-left: 10px; font-size: 0.8em; }
        .actions a { text-decoration: none; margin-right: 5px; }
        .btn-delete { color: #e74c3c; }
        .btn-edit { color: #f39c12; }
        .tag { display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px; background: white; padding: 10px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($data['name']); ?></h1>
        <p><?php echo htmlspecialchars($data['role']); ?></p>
    </header>

    <section>
        <h2>Zájmy (CRUD)</h2>
        <div class="interests-list">
            <?php foreach ($data['interests'] as $index => $interest): ?>
                <div class="tag">
                    <span><?php echo htmlspecialchars($interest); ?></span>
                    <div class="actions">
                        <a href="?edit=<?php echo $index; ?>" class="btn-edit">Upravit</a>
                        <a href="?delete=<?php echo $index; ?>" class="btn-delete" onclick="return confirm('Opravdu smazat?')">Smazat</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
        
        <?php if (!empty($message)): ?>
            <p class="<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="POST">
            <h3><?php echo $edit_mode ? "Upravit zájem" : "Přidat nový zájem"; ?></h3>
            <input type="text" name="interest_value" required value="<?php echo htmlspecialchars($edit_val); ?>">
            
            <?php if ($edit_mode): ?>
                <input type="hidden" name="edit_index" value="<?php echo $current_edit_index; ?>">
            <?php endif; ?>

            <button type="submit"><?php echo $edit_mode ? "Uložit změny" : "Přidat"; ?></button>
            <?php if ($edit_mode): ?>
                <a href="index.php">Zrušit</a>
            <?php endif; ?>
        </form>
    </section>
</body>
</html>