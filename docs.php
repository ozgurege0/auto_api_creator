<?php
if (!file_exists('config.php')) die("Lütfen install.php çalıştırın.");
$config = include('config.php');
$baseUrl = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/api.php";

function getExampleValue($type) {
    if (strpos($type, 'int') !== false) return 1;
    if (strpos($type, 'decimal') !== false || strpos($type, 'float') !== false) return 10.50;
    if (strpos($type, 'datetime') !== false) return date("Y-m-d H:i:s");
    if (strpos($type, 'date') !== false) return date("Y-m-d");
    if (strpos($type, 'text') !== false) return "Uzun metin içeriği...";
    return "deneme verisi";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>API Dokümantasyonu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; }
        .sidebar { position: fixed; top: 0; bottom: 0; left: 0; z-index: 100; padding: 48px 0 0; box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1); background-color: #fff; }
        .code-block { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 6px; font-family: 'Consolas', monospace; font-size: 0.9rem; position: relative; }
        .method { font-weight: bold; width: 60px; display: inline-block; }
        .get { color: #61affe; } .post { color: #49cc90; } .put { color: #fca130; } .delete { color: #f93e3e; }
        .param-table th { font-size: 0.85rem; text-transform: uppercase; color: #666; }
        .nav-link { color: #333; } .nav-link:hover { color: #0d6efd; background: #f8f9fa; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse overflow-auto">
            <div class="position-sticky pt-3">
                <h5 class="px-3 pb-2 border-bottom">Tablolar</h5>
                <ul class="nav flex-column">
                    <?php foreach ($config['tables'] as $tableName => $cols): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#section-<?php echo $tableName; ?>">
                            <i class="fa-solid fa-table me-2"></i> <?php echo $tableName; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-4 px-3">
                    <small class="text-muted fw-bold">API KEY:</small>
                    <input type="text" class="form-control form-control-sm mt-1" value="<?php echo $config['api_key']; ?>" readonly>
                </div>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">API Entegrasyon Rehberi</h1>
            </div>

            <div class="alert alert-info shadow-sm">
                <i class="fa-solid fa-circle-info me-2"></i>
                Tüm isteklerde Header olarak <code>X-API-KEY: <?php echo $config['api_key']; ?></code> gönderilmelidir.
            </div>

            <?php foreach ($config['tables'] as $tableName => $columns): 
                $postData = [];
                $primaryKey = 'id';
                foreach($columns as $col) {
                    if($col['Key'] == 'PRI') { $primaryKey = $col['Field']; continue; }
                    $postData[$col['Field']] = getExampleValue($col['Type']);
                }
                $jsonExample = json_encode($postData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            ?>
            
            <div id="section-<?php echo $tableName; ?>" class="card mb-5 shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h3 class="card-title text-primary m-0"><i class="fa-solid fa-database"></i> <?php echo $tableName; ?></h3>
                </div>
                <div class="card-body">
                    
                    <h5 class="border-bottom pb-2">Tablo Yapısı</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-hover param-table">
                            <thead class="table-light"><tr><th>Sütun</th><th>Tip</th><th>Özellik</th></tr></thead>
                            <tbody>
                                <?php foreach($columns as $col): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $col['Field']; ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo $col['Type']; ?></span></td>
                                    <td><?php echo ($col['Key'] == 'PRI') ? '<span class="badge bg-warning text-dark">PRIMARY</span>' : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-4">
                        <h5 class="text-primary"><i class="fa-solid fa-list"></i> Verileri Çekme (GET)</h5>
                        <p>Verileri listelemek, arama yapmak ve filtrelemek için kullanılır.</p>
                        
                        <div class="code-block mb-2">
                            <span class="method get">GET</span> <?php echo $baseUrl; ?>?table=<?php echo $tableName; ?>
                        </div>

                        <h6>Varyasyonlu Çekim (Filtreleme) Örnekleri:</h6>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item bg-light">
                                <strong>Tam Eşleşme:</strong> <br>
                                <code>?table=<?php echo $tableName; ?>&<?php echo array_keys($postData)[0] ?? 'col'; ?>=değer</code>
                            </li>
                            <li class="list-group-item bg-light">
                                <strong>İçerir (LIKE):</strong> <br>
                                <code>?table=<?php echo $tableName; ?>&<?php echo array_keys($postData)[0] ?? 'col'; ?>_like=aranan</code>
                            </li>
                            <li class="list-group-item bg-light">
                                <strong>Büyüktür/Küçüktür:</strong> <br>
                                <code>?table=<?php echo $tableName; ?>&fiyat_gt=100</code> (100'den büyük), <code>&fiyat_lt=500</code> (500'den küçük)
                            </li>
                            <li class="list-group-item bg-light">
                                <strong>Sıralama ve Limit:</strong> <br>
                                <code>?table=<?php echo $tableName; ?>&sort=<?php echo $primaryKey; ?>&order=DESC&limit=10</code>
                            </li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <h5 class="text-success"><i class="fa-solid fa-plus-circle"></i> Yeni Veri Ekleme (POST)</h5>
                        <div class="code-block">
                            <span class="method post">POST</span> <?php echo $baseUrl; ?>?table=<?php echo $tableName; ?>
                            <hr class="border-secondary">
                            <div class="text-muted small mb-1">// Header: Content-Type: application/json</div>
                            <pre class="m-0 text-warning"><?php echo $jsonExample; ?></pre>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5 class="text-warning"><i class="fa-solid fa-pen-to-square"></i> Düzenleme (PUT)</h5>
                        <p>Veriyi güncellemek için ID zorunludur.</p>
                        <div class="code-block">
                            <span class="method put">PUT</span> <?php echo $baseUrl; ?>?table=<?php echo $tableName; ?>&id=1
                            <hr class="border-secondary">
                            <pre class="m-0 text-warning"><?php echo $jsonExample; ?></pre>
                        </div>
                    </div>

                    <div>
                        <h5 class="text-danger"><i class="fa-solid fa-trash"></i> Silme (DELETE)</h5>
                        <div class="code-block">
                            <span class="method delete">DELETE</span> <?php echo $baseUrl; ?>?table=<?php echo $tableName; ?>&id=1
                        </div>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>

        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>