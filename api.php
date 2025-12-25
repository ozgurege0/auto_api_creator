<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

if (!file_exists('config.php')) {
    http_response_code(500);
    echo json_encode(["error" => "Sistem kurulu değil. install.php çalıştırın."]);
    exit;
}
$config = include('config.php');

$headers = getallheaders();
$apiKey = $headers['X-API-KEY'] ?? ($_GET['api_key'] ?? '');

if ($apiKey !== $config['api_key']) {
    http_response_code(401);
    echo json_encode(["error" => "Yetkisiz Erişim. Geçersiz API Key."]);
    exit;
}

try {
    $db = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset=utf8mb4",
        $config['db']['user'],
        $config['db']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    http_response_code(500); echo json_encode(["error" => "DB Hatası"]); exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$table = $_GET['table'] ?? null;
$id = $_GET['id'] ?? null; 
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (!$table || !array_key_exists($table, $config['tables'])) {
    http_response_code(400); 
    echo json_encode(["error" => "Geçersiz tablo adı."]); 
    exit;
}

try {
    switch ($method) {
        case 'GET':
            $sql = "SELECT * FROM `$table`";
            $where = [];
            $params = [];

            $reserved = ['table', 'limit', 'offset', 'sort', 'order', 'api_key'];
            
            foreach ($_GET as $key => $val) {
                if (in_array($key, $reserved)) continue;

                if (strpos($key, '_like') !== false) {
                    $col = str_replace('_like', '', $key);
                    $where[] = "`$col` LIKE ?";
                    $params[] = "%$val%";
                } elseif (strpos($key, '_gt') !== false) {
                    $col = str_replace('_gt', '', $key);
                    $where[] = "`$col` > ?";
                    $params[] = $val;
                } elseif (strpos($key, '_lt') !== false) {
                    $col = str_replace('_lt', '', $key);
                    $where[] = "`$col` < ?";
                    $params[] = $val;
                } else {
                    $where[] = "`$key` = ?";
                    $params[] = $val;
                }
            }

            if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);

            if (isset($_GET['sort'])) {
                $sort = preg_replace("/[^a-zA-Z0-9_]/", "", $_GET['sort']);
                $order = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';
                $sql .= " ORDER BY `$sort` $order";
            }

            if (isset($_GET['limit'])) {
                $sql .= " LIMIT " . (int)$_GET['limit'];
                if (isset($_GET['offset'])) $sql .= " OFFSET " . (int)$_GET['offset'];
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(["data" => $stmt->fetchAll(), "count" => $stmt->rowCount()]);
            break;

        case 'POST':
            if (!$input) { throw new Exception("Veri gönderilmedi"); }
            $columns = array_keys($input);
            $placeholders = array_fill(0, count($columns), '?');
            $sql = "INSERT INTO `$table` (`" . implode("`,`", $columns) . "`) VALUES (" . implode(",", $placeholders) . ")";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(array_values($input));
            echo json_encode(["status" => "success", "id" => $db->lastInsertId()]);
            break;

        case 'PUT':
            if (!$id) throw new Exception("Güncelleme için ID gerekli (?id=1)");
            if (!$input) throw new Exception("Veri gönderilmedi");
            
            $sets = [];
            $params = [];
            foreach ($input as $key => $val) {
                $sets[] = "`$key` = ?";
                $params[] = $val;
            }
            $params[] = $id;

            $sql = "UPDATE `$table` SET " . implode(", ", $sets) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(["status" => "success", "affected" => $stmt->rowCount()]);
            break;

        case 'DELETE':
            if (!$id) throw new Exception("Silme işlemi için ID gerekli (?id=1)");
            $stmt = $db->prepare("DELETE FROM `$table` WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(["status" => "success", "affected" => $stmt->rowCount()]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage(), "sql_debug" => $sql ?? '']);
}