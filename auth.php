<?php
session_start();
header('Content-Type: application/json');

// --- 1. DATOS DE CONEXIÓN (AIVEN CLOUD) ---
$host = 'mysql-3cf145c3-landah624-7928.f.aivencloud.com';
$port = '17988';
$db   = 'defaultdb';
$user = 'avnadmin';
$pass = 'AVNS_K2DXwCsc4e3SuvHJosa'; // <--- PEGA AQUÍ LA CLAVE QUE SALE EN TU IMAGEN

try {
    // Conexión PDO ajustada para Aiven
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    
    // Configuración de errores y seguridad
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la nube: ' . $e->getMessage()]);
    exit;
}

// --- 2. RECIBIR DATOS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keyInput = trim($_POST['key'] ?? '');
    $userTag  = str_replace('@', '', trim($_POST['user'] ?? ''));

    if (empty($keyInput) || empty($userTag)) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan datos (Usuario o Key).']);
        exit;
    }

    // Buscamos la clave en la tabla 'keys'
    $stmt = $pdo->prepare("SELECT * FROM `keys` WHERE license_key = ?");
    $stmt->execute([$keyInput]);
    $keyData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si la clave NO existe
    if (!$keyData) {
        echo json_encode(['status' => 'error', 'message' => 'Clave de licencia no válida.']);
        exit;
    }

    $now = new DateTime();

    // --- 3. VINCULACIÓN DE USUARIO Y ACTIVACIÓN ---
    if (empty($keyData['username'])) {
        $days = (int)$keyData['days'];
        $expiresAt = (new DateTime())->modify("+$days days")->format('Y-m-d H:i:s');
        
        $update = $pdo->prepare("UPDATE `keys` SET username = ?, expires_at = ?, status = 'active' WHERE id = ?");
        $update->execute([$userTag, $expiresAt, $keyData['id']]);
        
        $keyData['username'] = $userTag;
        $keyData['expires_at'] = $expiresAt;
    } 
    else {
        if ($keyData['username'] !== $userTag) {
            echo json_encode(['status' => 'error', 'message' => 'Esta Key ya pertenece a otro usuario.']);
            exit;
        }
    }

    // --- 4. VERIFICAR SI YA EXPIRÓ ---
    $expiration = new DateTime($keyData['expires_at']);
    if ($now > $expiration || $keyData['status'] === 'expired') {
        if ($keyData['status'] !== 'expired') {
            $pdo->prepare("UPDATE `keys` SET status = 'expired' WHERE id = ?")->execute([$keyData['id']]);
        }
        
        echo json_encode(['status' => 'error', 'message' => 'Tu licencia ha caducado el: ' . $expiration->format('d/m/Y')]);
        exit;
    }

    // --- 5. TODO OK: CREAR SESIÓN ---
    $_SESSION['zhentio_auth'] = true;
    $_SESSION['zhentio_user'] = $userTag;
    $_SESSION['license_key']  = $keyInput;
    
    echo json_encode(['status' => 'success']);
    exit;
}