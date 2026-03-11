<?php
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* --- 1. DATOS DE CONEXIÓN --- */
$host = 'mysql-3cf145c3-landah624-7928.f.aivencloud.com';
$port = '17988';
$db   = 'defaultdb';
$user = 'avnadmin';
$pass = 'AVNS_K2DXwCsc4e3SuvHJosa';

try {

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);

} catch (PDOException $e) {

    echo json_encode([
        "status" => "error",
        "message" => "Error de conexión: " . $e->getMessage()
    ]);
    exit;
}

/* --- 2. SOLO POST --- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        "status" => "error",
        "message" => "Método no permitido"
    ]);
    exit;
}

/* --- 3. RECIBIR DATOS --- */
$keyInput = trim($_POST['key'] ?? '');
$userTag  = str_replace('@', '', trim($_POST['user'] ?? ''));

if (!$keyInput || !$userTag) {

    echo json_encode([
        "status" => "error",
        "message" => "Faltan datos"
    ]);
    exit;
}

/* --- 4. BUSCAR KEY --- */
$stmt = $pdo->prepare("SELECT * FROM `keys` WHERE license_key = ?");
$stmt->execute([$keyInput]);
$keyData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$keyData) {

    echo json_encode([
        "status" => "error",
        "message" => "Clave inválida"
    ]);
    exit;
}

$now = new DateTime();

/* --- 5. ACTIVAR KEY SI NO TIENE USUARIO --- */
if (empty($keyData['username'])) {

    $days = (int)$keyData['days'];

    $expiresAt = (new DateTime())
        ->modify("+$days days")
        ->format('Y-m-d H:i:s');

    $update = $pdo->prepare(
        "UPDATE `keys` SET username=?, expires_at=?, status='active' WHERE id=?"
    );

    $update->execute([
        $userTag,
        $expiresAt,
        $keyData['id']
    ]);

    $keyData['username'] = $userTag;
    $keyData['expires_at'] = $expiresAt;

} else {

    if ($keyData['username'] !== $userTag) {

        echo json_encode([
            "status" => "error",
            "message" => "Esta key pertenece a otro usuario"
        ]);
        exit;
    }
}

/* --- 6. VERIFICAR EXPIRACIÓN --- */
$expiration = new DateTime($keyData['expires_at']);

if ($now > $expiration || $keyData['status'] === 'expired') {

    if ($keyData['status'] !== 'expired') {
        $pdo->prepare("UPDATE `keys` SET status='expired' WHERE id=?")
            ->execute([$keyData['id']]);
    }

    echo json_encode([
        "status" => "error",
        "message" => "Licencia expirada el " . $expiration->format('d/m/Y')
    ]);
    exit;
}

/* --- 7. CREAR SESIÓN --- */
session_regenerate_id(true);

$_SESSION['zhentio_auth'] = true;
$_SESSION['zhentio_user'] = $userTag;
$_SESSION['license_key']  = $keyInput;

echo json_encode([
    "status" => "success"
]);
exit;
?>
