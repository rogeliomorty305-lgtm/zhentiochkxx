<?php
session_start();
// Si quieres ponerle una contraseña al panel para que nadie entre, descomenta las siguientes líneas:
/*
if (!isset($_SESSION['admin_logged'])) {
    // Aquí podrías redirigir a un login simple
}
*/

// --- 1. CONFIGURACIÓN DE CONEXIÓN (AIVEN) ---
$host = 'mysql-3cf145c3-landah624-7928.f.aivencloud.com';
$port = '17988';
$db   = 'defaultdb';
$user = 'avnadmin';
$pass = 'AVNS_K2DXwCsc4e3SuvHJosa'; // El del "ojo" en Aiven

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la nube: " . $e->getMessage());
}

// --- 2. LÓGICA PARA BORRAR ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM `keys` WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin.php");
    exit;
}

// --- 3. GENERADOR DE KEYS (Formato ZHENTIO) ---
function generateZhentioKey() {
    $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $part1 = "";
    $part2 = "";
    for ($i = 0; $i < 4; $i++) { $part1 .= $chars[rand(0, strlen($chars) - 1)]; }
    for ($i = 0; $i < 4; $i++) { $part2 .= $chars[rand(0, strlen($chars) - 1)]; }
    return "ZHENTIO-" . $part1 . "-" . $part2;
}

if (isset($_POST['create_key'])) {
    $newKey = generateZhentioKey();
    $days = intval($_POST['days']);
    $stmt = $pdo->prepare("INSERT INTO `keys` (license_key, days, status) VALUES (?, ?, 'inactive')");
    $stmt->execute([$newKey, $days]);
}

// Obtener todas las llaves
$stmt = $pdo->query("SELECT * FROM `keys` ORDER BY created_at DESC");
$allKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalKeys = count($allKeys);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZHT | Pro Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg: #000; --side: #080808; --card: #0d0d0d; --border: #1a1a1a; --accent: #fff; --text: #fff; --dim: #555; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        .sidebar { width: 240px; background: var(--side); border-right: 1px solid var(--border); padding: 40px 20px; display: flex; flex-direction: column; }
        .logo { font-weight: 900; letter-spacing: 6px; font-size: 1.4rem; margin-bottom: 50px; text-align: center; border: 2px solid #fff; padding: 10px; }
        .nav-link { color: var(--dim); text-decoration: none; padding: 15px; border-radius: 4px; display: flex; align-items: center; gap: 12px; transition: 0.2s; font-size: 0.8rem; letter-spacing: 1px; }
        .nav-link:hover, .nav-link.active { background: #111; color: var(--accent); }

        .main-content { flex: 1; padding: 50px; overflow-y: auto; }
        .header-main { margin-bottom: 40px; border-bottom: 1px solid var(--border); padding-bottom: 20px; }
        h1 { font-size: 1.5rem; font-weight: 200; letter-spacing: 2px; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: var(--card); border: 1px solid var(--border); padding: 25px; border-radius: 4px; }
        .stat-card h3 { font-size: 0.6rem; color: var(--dim); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px; }
        .stat-card p { font-size: 1.5rem; font-weight: 800; }

        .action-container { background: var(--card); border: 1px solid var(--border); padding: 30px; border-radius: 4px; margin-bottom: 30px; }
        .form-inline { display: flex; gap: 20px; align-items: flex-end; }
        label { display: block; font-size: 0.6rem; color: var(--dim); margin-bottom: 10px; text-transform: uppercase; }
        select { background: #000; border: 1px solid var(--border); color: #fff; padding: 12px; width: 200px; border-radius: 2px; outline: none; }
        .btn-white { background: var(--accent); color: #000; border: none; padding: 12px 30px; font-weight: 900; cursor: pointer; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; }

        .table-card { background: var(--card); border: 1px solid var(--border); border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0a0a0a; padding: 18px; text-align: left; font-size: 0.6rem; color: var(--dim); text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--border); }
        td { padding: 18px; font-size: 0.8rem; border-bottom: 1px solid #111; color: #888; }
        .user-tag { color: #fff; font-weight: 700; font-family: monospace; }
        .key-text { font-family: monospace; color: #fff; background: #161616; padding: 5px 10px; border-radius: 3px; }
        .status-badge { font-size: 0.6rem; padding: 3px 7px; border-radius: 3px; text-transform: uppercase; }
        .status-active { background: #1a3a1a; color: #0f0; }
        .status-inactive { background: #333; color: #aaa; }

        .btn-icon { background: none; border: none; color: var(--dim); cursor: pointer; padding: 5px; font-size: 0.9rem; margin-left: 10px; }
        .btn-icon:hover { color: #fff; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">ZHT</div>
        <nav>
            <a href="admin.php" class="nav-link active"><i class="fas fa-th-large"></i> OVERVIEW</a>
            <a href="index.php" class="nav-link"><i class="fas fa-terminal"></i> CHECKER</a>
            <a href="#ajustes" class="nav-link"><i class="fas fa-sliders"></i> AJUSTES</a>
        </nav>
        <div style="margin-top: auto; font-size: 0.6rem; color: #333; text-align: center;">ZHENTIO CLOUD v2.1</div>
    </aside>

    <main class="main-content">
        <div class="header-main">
            <h1>ADMINISTRADOR DE LICENCIAS</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>LICENCIAS TOTALES</h3>
                <p><?php echo $totalKeys; ?></p>
            </div>
            <div class="stat-card">
                <h3>SERVIDOR DB</h3>
                <p style="color: #fff; font-size: 0.9rem;">AIVEN CLOUD OK</p>
            </div>
            <div class="stat-card">
                <h3>FORMATO</h3>
                <p style="font-size: 0.9rem;">ZHENTIO-XXXX</p>
            </div>
        </div>

        <div class="action-container">
            <form method="POST" class="form-inline">
                <div>
                    <label>DURACIÓN DEL ACCESO</label>
                    <select name="days">
                        <option value="1">1 DÍA</option>
                        <option value="7">7 DÍAS</option>
                        <option value="15">15 DÍAS</option>
                        <option value="30">30 DÍAS</option>
                        <option value="999">LIFETIME</option>
                    </select>
                </div>
                <button type="submit" name="create_key" class="btn-white">GENERAR NUEVA KEY</button>
            </form>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>USUARIO</th>
                        <th>LICENSE KEY</th>
                        <th>DÍAS</th>
                        <th>ESTADO</th>
                        <th style="text-align: right;">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($allKeys as $k): ?>
                    <tr>
                        <td class="user-tag">
                            <?php echo !empty($k['username']) ? '@'.$k['username'] : '<span style="color:#222">SIN USO</span>'; ?>
                        </td>
                        <td><span class="key-text"><?php echo $k['license_key']; ?></span></td>
                        <td><?php echo ($k['days'] == 999) ? 'INF' : $k['days'].' D'; ?></td>
                        <td>
                            <span class="status-badge <?php echo ($k['status'] == 'active') ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $k['status']; ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button class="btn-icon" onclick="copyKey('<?php echo $k['license_key']; ?>')" title="Copiar"><i class="fas fa-copy"></i></button>
                            <a href="?delete=<?php echo $k['id']; ?>" class="btn-icon" onclick="return confirm('¿Eliminar esta llave permanentemente?')" style="color: #441111;"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        function copyKey(text) {
            navigator.clipboard.writeText(text);
            alert('¡Key copiada!');
        }
    </script>
</body>
</html>
