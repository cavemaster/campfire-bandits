<?php
/**
 * Campfire Bandits - Passwortgeschützter Mitgliederbereich
 * Funktionen:
 * 1. Passwortschutz (Session-basiert)
 * 2. E-Mail-Verteiler Verwaltung (Campfire-Bandits@juliejung.de)
 * 3. Geschützter Dateidownload (Noten, Setlisten, MP3s)
 */

session_start();

$CORRECT_PASSWORD = 'kampfire';
$FILES_DIR = __DIR__ . '/intern_files';
$VERTEILER_FILE = __DIR__ . '/verteiler_liste.json';
$MAIN_BAND_EMAIL = 'Campfire-Bandits@juliejung.de';

// -----------------------------------------------------------------------------
// 1. LOGOUT
// -----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['campfire_auth']);
    header('Location: intern.php');
    exit;
}

// -----------------------------------------------------------------------------
// 2. LOGIN PRÜFEN
// -----------------------------------------------------------------------------
$loginError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (trim($_POST['password']) === $CORRECT_PASSWORD) {
        $_SESSION['campfire_auth'] = true;
        header('Location: intern.php');
        exit;
    } else {
        $loginError = true;
    }
}

$isLoggedIn = !empty($_SESSION['campfire_auth']);

// -----------------------------------------------------------------------------
// 3. E-MAIL-VERTEILER VERWALTUNG (NUR EINGELOGGT)
// -----------------------------------------------------------------------------
$verteilerMessage = '';
$verteilerError = '';

if ($isLoggedIn) {
    // Liste laden
    $members = [];
    if (file_exists($VERTEILER_FILE)) {
        $members = json_decode(file_get_contents($VERTEILER_FILE), true) ?: [];
    }

    // A) Mitglied hinzufügen
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_member') {
        $name = trim(strip_tags($_POST['name'] ?? ''));
        $email = trim($_POST['email'] ?? '');

        if (empty($name) || empty($email)) {
            $verteilerError = 'Bitte Name und E-Mail-Adresse angeben.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $verteilerError = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        } else {
            $members[] = [
                'id' => uniqid(),
                'name' => $name,
                'email' => $email
            ];
            file_put_contents($VERTEILER_FILE, json_encode($members, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $verteilerMessage = "Mitglied „" . htmlspecialchars($name) . "“ erfolgreich hinzugefügt!";
        }
    }

    // B) Mitglied löschen
    if (isset($_GET['action']) && $_GET['action'] === 'delete_member' && isset($_GET['id'])) {
        $delId = $_GET['id'];
        $members = array_values(array_filter($members, function($m) use ($delId) {
            return ($m['id'] ?? '') !== $delId;
        }));
        file_put_contents($VERTEILER_FILE, json_encode($members, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $verteilerMessage = "E-Mail-Adresse erfolgreich entfernt.";
    }
}

// -----------------------------------------------------------------------------
// 4. SICHERER DATEIDOWNLOAD
// -----------------------------------------------------------------------------
if (isset($_GET['download'])) {
    if (!$isLoggedIn) {
        http_response_code(403);
        die('Zugriff verweigert. Bitte zuerst einloggen.');
    }

    $fileName = basename($_GET['download']);
    $filePath = $FILES_DIR . '/' . $fileName;

    if (file_exists($filePath) && is_file($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        flush();
        readfile($filePath);
        exit;
    } else {
        http_response_code(404);
        die('Datei nicht gefunden.');
    }
}

// -----------------------------------------------------------------------------
// 5. DATEIEN IM ORDNER AUSLESEN
// -----------------------------------------------------------------------------
$fileList = [];
if ($isLoggedIn && is_dir($FILES_DIR)) {
    $dirItems = scandir($FILES_DIR);
    foreach ($dirItems as $item) {
        if ($item === '.' || $item === '..' || substr($item, 0, 1) === '.') {
            continue;
        }
        $fullPath = $FILES_DIR . '/' . $item;
        if (is_file($fullPath)) {
            $bytes = filesize($fullPath);
            $sizeFormatted = $bytes >= 1048576 
                ? number_format($bytes / 1048576, 2) . ' MB' 
                : number_format($bytes / 1024, 1) . ' KB';
            
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            $icon = '📄';
            if (in_array($ext, ['mp3', 'wav', 'm4a', 'flac'])) $icon = '🎵';
            elseif (in_array($ext, ['pdf'])) $icon = '📑';
            elseif (in_array($ext, ['zip', 'rar', 'tar', 'gz'])) $icon = '📦';
            elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) $icon = '🖼️';

            $fileList[] = [
                'name' => $item,
                'size' => $sizeFormatted,
                'date' => date('d.m.Y H:i', filemtime($fullPath)),
                'icon' => $icon,
                'download_url' => 'intern.php?download=' . urlencode($item)
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mitgliederbereich // Campfire Bandits</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Cinzel:wght@600;800&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-dark: #0a0705;
      --bg-card: rgba(26, 18, 13, 0.94);
      --bg-card-border: rgba(243, 156, 18, 0.28);
      --color-amber: #f39c12;
      --color-gold: #ffd369;
      --color-text: #f5ede6;
      --color-muted: #b8a698;
      --font-display: 'Bebas Neue', sans-serif;
      --font-serif: 'Cinzel', serif;
      --font-body: 'Montserrat', sans-serif;
      --transition: all 0.25s ease;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background-color: var(--bg-dark);
      background-image: radial-gradient(circle at top, rgba(38, 26, 18, 0.7) 0%, rgba(10, 7, 5, 0.98) 75%);
      color: var(--color-text);
      font-family: var(--font-body);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    header {
      padding: 18px 24px;
      background: rgba(10, 7, 5, 0.95);
      border-bottom: 1px solid rgba(243, 156, 18, 0.2);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: var(--color-gold);
    }
    .brand-title {
      font-family: var(--font-display);
      font-size: 24px;
      letter-spacing: 2px;
    }
    .header-links a {
      color: var(--color-muted);
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 1px;
      transition: color 0.2s ease;
      margin-left: 20px;
    }
    .header-links a:hover { color: var(--color-gold); }
    .btn-logout { color: #e74c3c !important; }
    
    .container {
      max-width: 980px;
      width: 100%;
      margin: 40px auto;
      padding: 0 20px;
      flex: 1;
    }

    /* LOGIN CARD */
    .login-wrapper {
      max-width: 440px;
      margin: 60px auto;
      background: var(--bg-card);
      border: 1px solid var(--bg-card-border);
      border-radius: 18px;
      padding: 40px 32px;
      text-align: center;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.7);
    }
    .lock-icon { font-size: 48px; margin-bottom: 16px; display: inline-block; }
    .login-title {
      font-family: var(--font-serif);
      font-size: 24px;
      color: var(--color-gold);
      margin-bottom: 10px;
    }
    .login-desc { font-size: 14px; color: var(--color-muted); margin-bottom: 26px; }
    .input-group { margin-bottom: 20px; }
    .pw-input {
      width: 100%;
      padding: 14px 18px;
      border-radius: 10px;
      background: rgba(10, 7, 5, 0.85);
      border: 1px solid rgba(243, 156, 18, 0.4);
      color: #fff;
      font-size: 16px;
      text-align: center;
      letter-spacing: 2px;
      outline: none;
      transition: var(--transition);
    }
    .pw-input:focus {
      border-color: var(--color-gold);
      box-shadow: 0 0 12px rgba(243, 156, 18, 0.3);
    }
    .btn-submit {
      width: 100%;
      padding: 14px;
      border-radius: 10px;
      background: linear-gradient(135deg, #e65c00, #f39c12);
      border: none;
      color: #fff;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      font-size: 14px;
      cursor: pointer;
      transition: var(--transition);
    }
    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(230, 92, 0, 0.4);
    }
    .error-box {
      margin-bottom: 20px;
      padding: 10px;
      border-radius: 8px;
      background: rgba(231, 76, 60, 0.15);
      border: 1px solid rgba(231, 76, 60, 0.4);
      color: #ff6b6b;
      font-size: 13px;
    }
    .success-box {
      margin-bottom: 20px;
      padding: 12px 18px;
      border-radius: 8px;
      background: rgba(46, 204, 113, 0.15);
      border: 1px solid rgba(46, 204, 113, 0.4);
      color: #2ecc71;
      font-size: 14px;
    }

    /* DASHBOARD */
    .dashboard-header {
      margin-bottom: 30px;
    }
    .dashboard-header h1 {
      font-family: var(--font-serif);
      font-size: 32px;
      background: linear-gradient(180deg, #fff, #ffd369);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .card {
      background: var(--bg-card);
      border: 1px solid var(--bg-card-border);
      border-radius: 16px;
      padding: 28px;
      margin-bottom: 28px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }
    .card-title {
      font-family: var(--font-serif);
      font-size: 20px;
      color: var(--color-gold);
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* VERTEILER BOX */
    .verteiler-box {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 18px;
      background: rgba(10, 7, 5, 0.7);
      border: 1px solid rgba(243, 156, 18, 0.35);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 24px;
    }
    .verteiler-address {
      font-size: 20px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .verteiler-address span {
      background: linear-gradient(135deg, #ffd369, #f39c12);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .verteiler-info {
      font-size: 13px;
      color: var(--color-muted);
      margin-top: 6px;
    }
    .btn-mail {
      padding: 10px 22px;
      border-radius: 8px;
      background: linear-gradient(135deg, #e65c00, #f39c12);
      color: #fff;
      text-decoration: none;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 1px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: var(--transition);
    }
    .btn-mail:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(230, 92, 0, 0.4); }

    /* MEMBER TABLE */
    .member-table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
      flex-wrap: wrap;
      gap: 10px;
    }
    .member-table-title {
      font-size: 15px;
      font-weight: 700;
      color: #fff;
    }
    .btn-copy-plesk {
      padding: 7px 16px;
      border-radius: 6px;
      background: rgba(243, 156, 18, 0.15);
      border: 1px solid rgba(243, 156, 18, 0.4);
      color: var(--color-gold);
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: var(--transition);
    }
    .btn-copy-plesk:hover {
      background: var(--color-amber);
      color: #000;
    }
    .member-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 24px;
      background: rgba(10, 7, 5, 0.5);
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid rgba(243, 156, 18, 0.15);
    }
    .member-table th {
      text-align: left;
      font-size: 12px;
      letter-spacing: 1px;
      color: var(--color-amber);
      padding: 10px 14px;
      background: rgba(20, 14, 10, 0.8);
      border-bottom: 1px solid rgba(243, 156, 18, 0.2);
    }
    .member-table td {
      padding: 12px 14px;
      font-size: 14px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      vertical-align: middle;
    }
    .member-table tr:last-child td { border-bottom: none; }
    .btn-del {
      color: #e74c3c;
      text-decoration: none;
      font-size: 12px;
      padding: 4px 10px;
      border-radius: 4px;
      background: rgba(231, 76, 60, 0.1);
      border: 1px solid rgba(231, 76, 60, 0.3);
      transition: var(--transition);
    }
    .btn-del:hover {
      background: #e74c3c;
      color: #fff;
    }

    /* ADD FORM */
    .add-member-form {
      display: flex;
      gap: 12px;
      background: rgba(15, 10, 7, 0.7);
      padding: 16px;
      border-radius: 10px;
      border: 1px dashed rgba(243, 156, 18, 0.35);
      align-items: center;
      flex-wrap: wrap;
    }
    .add-member-form input {
      flex: 1;
      min-width: 180px;
      padding: 10px 14px;
      border-radius: 6px;
      background: rgba(10, 7, 5, 0.9);
      border: 1px solid rgba(243, 156, 18, 0.35);
      color: #fff;
      font-size: 14px;
      outline: none;
    }
    .add-member-form input:focus { border-color: var(--color-gold); }
    .btn-add {
      padding: 10px 20px;
      border-radius: 6px;
      background: linear-gradient(135deg, #e65c00, #f39c12);
      border: none;
      color: #fff;
      font-weight: 700;
      font-size: 13px;
      cursor: pointer;
      transition: var(--transition);
    }
    .btn-add:hover { transform: translateY(-1px); }

    /* DOWNLOAD LIST */
    .file-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 14px;
    }
    .file-table th {
      text-align: left;
      font-size: 12px;
      letter-spacing: 1.5px;
      color: var(--color-amber);
      padding: 10px 14px;
      border-bottom: 1px solid rgba(243, 156, 18, 0.2);
      text-transform: uppercase;
    }
    .file-table td {
      padding: 14px;
      font-size: 14px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      vertical-align: middle;
    }
    .file-table tr:hover td { background: rgba(243, 156, 18, 0.05); }
    .file-name-cell {
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 600;
      color: #fff;
    }
    .file-icon { font-size: 20px; }
    .file-size { color: var(--color-muted); font-size: 13px; }
    .file-date { color: var(--color-muted); font-size: 12px; }
    .btn-download {
      padding: 8px 18px;
      border-radius: 6px;
      background: rgba(243, 156, 18, 0.15);
      border: 1px solid rgba(243, 156, 18, 0.4);
      color: var(--color-gold);
      text-decoration: none;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 1px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: var(--transition);
    }
    .btn-download:hover { background: var(--color-amber); color: #000; }
    .empty-notice { text-align: center; padding: 30px; color: var(--color-muted); font-size: 14px; }

    /* FOOTER */
    footer {
      padding: 20px;
      text-align: center;
      font-size: 12px;
      color: var(--color-muted);
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      background: #060403;
    }

    /* TOAST ALERT */
    #copyToast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: #2ecc71;
      color: #000;
      font-weight: 700;
      font-size: 13px;
      padding: 12px 24px;
      border-radius: 30px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.5);
      display: none;
      z-index: 1000;
      animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>

  <header>
    <a href="index.html" class="brand">
      <span>🎸</span>
      <span class="brand-title">CAMPFIRE BANDITS</span>
    </a>
    <div class="header-links">
      <a href="index.html">← Zur Startseite</a>
      <?php if ($isLoggedIn): ?>
        <a href="intern.php?action=logout" class="btn-logout">Abmelden ⎋</a>
      <?php endif; ?>
    </div>
  </header>

  <div class="container">
    <?php if (!$isLoggedIn): ?>
      <!-- LOGIN FORM -->
      <div class="login-wrapper">
        <span class="lock-icon">🔒</span>
        <h1 class="login-title">Mitglieder-Bereich</h1>
        <p class="login-desc">Bitte gib das Band-Passwort ein, um auf den internen Bereich, die Verteilerliste und die Downloads zuzugreifen.</p>

        <?php if ($loginError): ?>
          <div class="error-box">⚠️ Falsches Passwort. Bitte erneut versuchen.</div>
        <?php endif; ?>

        <form method="POST" action="intern.php">
          <div class="input-group">
            <input type="password" name="password" class="pw-input" placeholder="Passwort eingeben" autofocus required>
          </div>
          <button type="submit" class="btn-submit">Einloggen</button>
        </form>
      </div>

    <?php else: ?>
      <!-- EINGELOGGTER MITGLIEDERBEREICH -->
      <div class="dashboard-header">
        <h1>Interne Band-Zentrale</h1>
        <p style="color: var(--color-muted); font-size: 14px; margin-top: 4px;">Campfire Bandits // Mitgliederverwaltung, E-Mail-Verteiler & Dateidownloads</p>
      </div>

      <?php if (!empty($verteilerMessage)): ?>
        <div class="success-box">✓ <?= htmlspecialchars($verteilerMessage) ?></div>
      <?php endif; ?>

      <?php if (!empty($verteilerError)): ?>
        <div class="error-box">⚠️ <?= htmlspecialchars($verteilerError) ?></div>
      <?php endif; ?>

      <!-- CARD 1: E-MAIL VERTEILER MIT VERWALTUNG -->
      <div class="card">
        <h2 class="card-title">
          <span>✉️</span>
          <span>Band-E-Mail-Verteiler</span>
        </h2>
        
        <div class="verteiler-box">
          <div>
            <div class="verteiler-address">
              <span><?= htmlspecialchars($MAIN_BAND_EMAIL) ?></span>
            </div>
            <p class="verteiler-info">
              Mails an diese Adresse werden automatisch an alle unten eingetragenen Bandmitglieder weitergeleitet.
            </p>
          </div>
          <a href="mailto:<?= htmlspecialchars($MAIN_BAND_EMAIL) ?>" class="btn-mail">
            <span>✉️ Mail an alle senden</span>
          </a>
        </div>

        <!-- MITGLIEDERLISTE & PFLEGE -->
        <div class="member-table-header">
          <span class="member-table-title">Eingetragene Empfänger (<?= count($members) ?>)</span>
          <button type="button" class="btn-copy-plesk" id="btnCopyPlesk" title="Alle Adressen kopieren, um sie direkt in Plesk einzufügen">
            <span>📋 Alle für Plesk kopieren</span>
          </button>
        </div>

        <table class="member-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>E-Mail-Adresse</th>
              <th style="text-align: right; width: 90px;">Aktion</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($members)): ?>
              <tr>
                <td colspan="3" style="text-align: center; color: var(--color-muted); padding: 18px;">
                  Noch keine Adressen eingetragen. Nutze das Formular unten, um Mitglieder hinzuzufügen.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($members as $m): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                  <td style="color: var(--color-gold);"><?= htmlspecialchars($m['email']) ?></td>
                  <td style="text-align: right;">
                    <a href="intern.php?action=delete_member&id=<?= urlencode($m['id']) ?>" 
                       class="btn-del" 
                       onclick="return confirm('Möchtest du <?= htmlspecialchars($m['name']) ?> wirklich aus dem Verteiler entfernen?');">
                      Entfernen
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- NEUES MITGLIED HINZUFÜGEN -->
        <form method="POST" action="intern.php" class="add-member-form">
          <input type="hidden" name="action" value="add_member">
          <input type="text" name="name" placeholder="Name (z. B. Gitarre / Michael)" required>
          <input type="email" name="email" placeholder="E-Mail-Adresse" required>
          <button type="submit" class="btn-add">+ Mitglied eintragen</button>
        </form>
      </div>

      <!-- CARD 2: DATEIEN ZUM DOWNLOAD -->
      <div class="card">
        <h2 class="card-title">
          <span>📁</span>
          <span>Dokumente, Setlisten & Demos</span>
        </h2>
        <p style="color: var(--color-muted); font-size: 13px; margin-bottom: 12px;">
          Alle Dateien werden direkt mit dem Mac-Ordner (<code>intern_files</code>) synchronisiert.
        </p>

        <?php if (empty($fileList)): ?>
          <div class="empty-notice">
            Aktuell sind noch keine Dateien im Download-Ordner hinterlegt.
          </div>
        <?php else: ?>
          <table class="file-table">
            <thead>
              <tr>
                <th>Datei</th>
                <th>Größe</th>
                <th>Stand</th>
                <th style="text-align: right;">Aktion</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($fileList as $f): ?>
                <tr>
                  <td>
                    <div class="file-name-cell">
                      <span class="file-icon"><?= $f['icon'] ?></span>
                      <span><?= htmlspecialchars($f['name']) ?></span>
                    </div>
                  </td>
                  <td class="file-size"><?= $f['size'] ?></td>
                  <td class="file-date"><?= $f['date'] ?></td>
                  <td style="text-align: right;">
                    <a href="<?= $f['download_url'] ?>" class="btn-download">
                      <span>Herunterladen</span>
                      <span>↓</span>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <footer>
    © 2026 Campfire Bandits · Interner Bereich
  </footer>

  <div id="copyToast">✓ E-Mail-Adressen in die Zwischenablage kopiert!</div>

  <script>
    // Copy emails for Plesk
    const copyBtn = document.getElementById('btnCopyPlesk');
    const toast = document.getElementById('copyToast');

    if (copyBtn) {
      copyBtn.addEventListener('click', () => {
        const emails = <?= json_encode(array_column($members ?? [], 'email')) ?>;
        if (!emails.length) {
          alert('Keine E-Mail-Adressen in der Liste vorhanden.');
          return;
        }
        // Format: comma-separated or newline (Plesk accepts newline)
        const textToCopy = emails.join('\n');
        navigator.clipboard.writeText(textToCopy).then(() => {
          toast.style.display = 'block';
          setTimeout(() => { toast.style.display = 'none'; }, 2500);
        }).catch(err => {
          prompt('Hier sind die Adressen für Plesk:', textToCopy);
        });
      });
    }
  </script>

</body>
</html>
