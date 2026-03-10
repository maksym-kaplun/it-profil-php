<?php
// 1. Musí být úplně první věc
session_start();

$filename = 'profile.json';

// Načtení a kontrola souboru
if (!file_exists($filename)) {
    die("Chyba: Soubor profile.json neexistuje ve stejné složce jako index.php!");
}

$json_data = file_get_contents($filename);
$data = json_decode($json_data, true);

// Pokud se JSON nepodařilo přečíst (např. chyba v syntaxi)
if ($data === null) {
    die("Chyba: profile.json má špatný formát. Zkontroluj uvozovky a čárky v JSONu.");
}

// Načtení hlášek ze session
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';
unset($_SESSION['message'], $_SESSION['messageType']);

// --- MAZÁNÍ ---
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

// --- PŘIDÁVÁNÍ A EDITACE (POST) ---
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
                $_SESSION['message'] = "Zájem byl upraven.";
            } else {
                $data['interests'][] = $value;
                $_SESSION['message'] = "Zájem byl přidán.";
            }
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $_SESSION['messageType'] = "success";
        }
    }
    header("Location: index.php");
    exit;
}

// Příprava na editaci
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
    <title>Profil 5.0 - Opraveno</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($data['name'] ?? 'Jméno nenalezeno'); ?></h1>
        <p><?php echo htmlspecialchars($data['role'] ?? 'Role nenalezena'); ?></p>
    </header>

    <section>
        <h2>Zájmy</h2>
        <ul>
            <?php if (!empty($data['interests'])): ?>
                <?php foreach ($data['interests'] as $index => $interest): ?>
                    <li>
                        <?php echo htmlspecialchars($interest); ?>
                        <a href="?edit=<?php echo $index; ?>" style="color:orange; margin-left:10px;">[Upravit]</a>
                        <a href="?delete=<?php echo $index; ?>" style="color:red; margin-left:10px;" onclick="return confirm('Opravdu smazat?')">[Smazat]</a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Žádné zájmy k zobrazení.</p>
            <?php endif; ?>
        </ul>

        <?php if ($message): ?>
            <p class="<?php echo $messageType; ?>" style="padding:10px; border:1px solid;"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="POST" style="margin-top:20px; background:#eee; padding:15px;">
            <h3><?php echo $edit_mode ? "Upravit zájem" : "Přidat zájem"; ?></h3>
            <input type="text" name="interest_value" value="<?php echo htmlspecialchars($edit_val); ?>" required>
            
            <?php if ($edit_mode): ?>
                <input type="hidden" name="edit_index" value="<?php echo $current_edit_index; ?>">
            <?php endif; ?>

            <button type="submit"><?php echo $edit_mode ? "Uložit změny" : "Přidat"; ?></button>
            <?php if ($edit_mode): ?><a href="index.php">Zrušit</a><?php endif; ?>
        </form>
    </section>
</body>
</html>