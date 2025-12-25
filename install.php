<?php
session_start();
error_reporting(E_ALL);

function generateApiKey() { return bin2hex(random_bytes(32)); }

$step = isset($_GET['step']) ? $_GET['step'] : 1;
$message = "";

if (isset($_POST['connect_db'])) {
    try {
        $dsn = "mysql:host=" . $_POST['host'] . ";dbname=" . $_POST['dbname'] . ";charset=utf8mb4";
        $pdo = new PDO($dsn, $_POST['user'], $_POST['pass']);
        $_SESSION['db_temp'] = [
            'host' => $_POST['host'], 'dbname' => $_POST['dbname'],
            'user' => $_POST['user'], 'pass' => $_POST['pass']
        ];
        header("Location: install.php?step=2");
        exit;
    } catch (PDOException $e) { $message = "Hata: " . $e->getMessage(); }
}

if (isset($_POST['generate'])) {
    $selectedTables = $_POST['tables'] ?? [];
    $tableSchemas = [];

    try {
        $dsn = "mysql:host=".$_SESSION['db_temp']['host'].";dbname=".$_SESSION['db_temp']['dbname'];
        $pdo = new PDO($dsn, $_SESSION['db_temp']['user'], $_SESSION['db_temp']['pass']);
        
        foreach ($selectedTables as $table) {
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tableSchemas[$table] = $columns;
        }

        $apiKey = generateApiKey();
        $config = [
            'db' => $_SESSION['db_temp'],
            'api_key' => $apiKey,
            'tables' => $tableSchemas 
        ];

        $content = "<?php\nreturn " . var_export($config, true) . ";\n?>";
        if(file_put_contents('config.php', $content)){
            header("Location: docs.php");
            exit;
        } else { $message = "config.php dosyası yazılamadı!"; }

    } catch (Exception $e) { $message = "Analiz Hatası: " . $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sistem Kurulumu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
<div class="container" style="max-width: 600px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">API Kurulum Sihirbazı</div>
        <div class="card-body">
            <?php if($message): ?><div class="alert alert-danger"><?php echo $message; ?></div><?php endif; ?>
            
            <?php if($step == 1): ?>
            <form method="POST">
                <div class="mb-3"><label>Host</label><input type="text" name="host" class="form-control" value="localhost"></div>
                <div class="mb-3"><label>DB Adı</label><input type="text" name="dbname" class="form-control"></div>
                <div class="mb-3"><label>Kullanıcı</label><input type="text" name="user" class="form-control"></div>
                <div class="mb-3"><label>Şifre</label><input type="password" name="pass" class="form-control"></div>
                <button type="submit" name="connect_db" class="btn btn-primary w-100">Analiz Et</button>
            </form>
            <?php elseif($step == 2): ?>
            <form method="POST">
                <h5>Tabloları Seçin</h5>
                <div class="list-group mb-3" style="max-height: 300px; overflow-y:auto">
                    <?php 
                    $dsn = "mysql:host=".$_SESSION['db_temp']['host'].";dbname=".$_SESSION['db_temp']['dbname'];
                    $pdo = new PDO($dsn, $_SESSION['db_temp']['user'], $_SESSION['db_temp']['pass']);
                    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                    foreach($tables as $t): ?>
                        <label class="list-group-item">
                            <input class="form-check-input me-2" type="checkbox" name="tables[]" value="<?php echo $t; ?>" checked>
                            <?php echo $t; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="generate" class="btn btn-success w-100">API Motorunu Oluştur</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>