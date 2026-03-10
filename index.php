<?php
// 1. Start session - musí být úplně první
session_start();

$filename = 'profile.json';

// Načtení dat a základní kontrola
if (!file_exists($filename)) {
    die("Chyba: Soubor profile.json nebyl nalezen!");
}

$json_content = file_get_contents($filename);
$data = json_decode($json_content, true);

if ($data === null) {
    die("Chyba: profile.json má poškozený formát (zkontroluj čárky a uvozovky).");
}

// Načtení hlášek ze session a jejich smazání
$message = $_SESSION['msg'] ?? '';
$messageType = $_SESSION['type'] ?? '';
unset($_SESSION['msg'], $_SESSION['type']);

// --- LOGIKA: MAZÁNÍ (DELETE) ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (isset($data['interests'][$id])) {
        array_splice($data['interests'], $id, 1);
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $_SESSION['msg'] = "Zájem byl smazán.";
        $_SESSION['type'] = "success";
    }
    header("Location: index.php");
    exit;
}

// --- LOGIKA: PŘIDÁVÁNÍ A EDITACE (POST + PRG) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['interest_text'])) {
    $text = trim($_POST['interest_text']);
    $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : -1;

    if (empty($text)) {
        $_SESSION['msg'] = "Pole nesmí být prázdné.";
        $_SESSION['type'] = "error";
    } else {
        // Kontrola duplicity (ignoruje aktuálně editovaný prvek)
        $is_duplicate = false;
        foreach ($data['interests'] as $idx => $val) {
            if (strtolower($val) === strtolower($text) && $idx !== $edit_id) {
                $is_duplicate = true;
                break;
            }
        }

        if ($is_duplicate) {
            $_SESSION['msg'] = "Tento zájem už v seznamu je.";
            $_SESSION['type'] = "error";
        } else {
            if ($edit_id >= 0) {
                $data['interests'][$edit_id] = $text;
                $_SESSION['msg'] = "Zájem byl upraven.";
            } else {
                $data['interests'][] = $text;
                $_SESSION['msg'] = "Zájem byl přidán.";
            }
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $_SESSION['type'] = "success";
        }
    }
    header("Location: index.php");
    exit;
}

// Příprava na editaci (naplnění formuláře)
$edit_mode = false;
$edit_val = "";
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if (isset($data['interests'][$id])) {
        $edit_mode = true;
        $edit_val = $data['interests'][$id];
        $current_id = $id;
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>IT Profil 5.0</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="max-width: 600px; margin: auto; padding: 20px;">
        <h1><?php echo htmlspecialchars($data['name']); ?></h1>
        <p><strong>Pozice:</strong> <?php echo htmlspecialchars($data['role']); ?></p>

        <h3>Moje zájmy:</h3>
        <ul>
            <?php foreach ($data['interests'] as $index => $interest): ?>
                <li style="margin-bottom: 10px;">
                    <?php echo htmlspecialchars($interest); ?> 
                    <a href="?edit=<?php echo $index; ?>" style="color: blue; margin-left: 10px;">[Upravit]</a>
                    <a href="?delete=<?php echo $index; ?>" style="color: red; margin-left: 5px;" onclick="return confirm('Smazat?')">[Smazat]</a>
                </li>
            <?php endforeach; ?>
        </ul>

        <hr>

        <?php if ($message): ?>
            <div class="<?php echo $messageType; ?>" style="padding: 10px; margin-bottom: 10px; border: 1px solid;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="background: #f4f4f4; padding: 15px;">
            <h4><?php echo $edit_mode ? "Upravit zájem" : "Přidat nový zájem"; ?></h4>
            <input type="text" name="interest_text" value="<?php echo htmlspecialchars($edit_val); ?>" required>
            
            <?php if ($edit_mode): ?>
                <input type="hidden" name="edit_id" value="<?php echo $current_id; ?>">
            <?php endif; ?>

            <button type="submit"><?php echo $edit_mode ? "Uložit změny" : "Přidat"; ?></button>
            <?php if ($edit_mode): ?>
                <a href="index.php">Zrušit</a>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>