<?php
// 1. Start session pro přenos hlášek po přesměrování
session_start();

$filename = 'profile.json';

// Načtení dat ze souboru
$json_data = file_get_contents($filename);
$data = json_decode($json_data, true);

if (!$data) {
    die('Chyba: Nepodařilo se načíst profile.json');
}

// 2. Načtení hlášek ze session a jejich okamžité smazání (aby se neukázaly znovu)
$message = $_SESSION['message'] ?? '';
$messageType = $_SESSION['messageType'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['messageType']);

// --- LOGIKA PŘIDÁVÁNÍ (POST) - PRG PATTERN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_interest'])) {
    $new_interest = trim($_POST['new_interest']);

    if (empty($new_interest)) {
        $_SESSION['message'] = "Pole nesmí být prázdné.";
        $_SESSION['messageType'] = "error";
    } else {
        // Kontrola duplicity (case-insensitive)
        $is_duplicate = false;
        foreach ($data['interests'] as $existing_interest) {
            if (strtolower($existing_interest) === strtolower($new_interest)) {
                $is_duplicate = true;
                break;
            }
        }

        if ($is_duplicate) {
            $_SESSION['message'] = "Tento zájem už existuje.";
            $_SESSION['messageType'] = "error";
        } else {
            // Přidání do pole a uložení do JSON
            $data['interests'][] = $new_interest;
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $_SESSION['message'] = "Zájem byl úspěšně přidán.";
            $_SESSION['messageType'] = "success";
        }
    }

    // PŘESMĚROVÁNÍ (Klíčová část PRG patternu)
    header("Location: index.php");
    exit; 
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
    <header>
        <h1><?php echo htmlspecialchars($data['name']); ?></h1>
        <p><?php echo htmlspecialchars($data['role']); ?></p>
    </header>

    <section>
        <h2>Dovednosti</h2>
        <ul>
            <?php foreach ($data['skills'] as $skill): ?>
                <li><?php echo htmlspecialchars($skill); ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section>
        <h2>Zájmy</h2>
        <div class="interests-list">
            <?php foreach ($data['interests'] as $interest): ?>
                <span class="tag"><?php echo htmlspecialchars($interest); ?></span>
            <?php endforeach; ?>
        </div>

        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
        
        <?php if (!empty($message)): ?>
            <p class="<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="new_interest" required placeholder="Napiš nový zájem...">
            <button type="submit">Přidat zájem</button>
        </form>
    </section>
</body>
</html>