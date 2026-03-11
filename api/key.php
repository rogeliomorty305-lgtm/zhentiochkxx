<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ZHENTIO - ACCESO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #050505;
            --card-bg: #0d0d0d;
            --border: #1a1a1a;
            --text-main: #ffffff;
            --text-dim: #555;
            --accent: #fff;
            --error: #ff4b4b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { 
            background-color: var(--bg); 
            color: var(--text-main); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            overflow: hidden;
        }

        .login-card {
            width: 100%;
            max-width: 380px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .header h1 {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 4px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .header p {
            color: var(--text-dim);
            font-size: 0.8rem;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .field {
            margin-bottom: 20px;
            text-align: left;
        }

        .field label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-dim);
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            font-size: 0.9rem;
        }

        input[type="text"] {
            width: 100%;
            background: #000;
            border: 1px solid var(--border);
            color: #fff;
            padding: 14px 14px 14px 45px;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: 0.3s;
            letter-spacing: 1px;
        }

        input[type="text"]:focus {
            border-color: var(--accent);
            box-shadow: 0 0 10px rgba(255,255,255,0.05);
        }

        .btn-login {
            width: 100%;
            background: var(--accent);
            color: #000;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 800;
            cursor: pointer;
            text-transform: uppercase;
            transition: 0.2s;
            margin-top: 10px;
        }

        .btn-login:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        #msg {
            margin-top: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            min-height: 18px;
        }

        .error { color: var(--error); }
        .success { color: #48bb78; }

        .footer-link {
            margin-top: 30px;
            font-size: 0.7rem;
            color: var(--text-dim);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="header">
        <h1>ZHENTIO</h1>
        <p>Sistema de Acceso</p>
    </div>

    <form id="login-form">
        <div class="field">
            <label>@ Usuario</label>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" id="username" placeholder="@" autocomplete="off">
            </div>
        </div>

        <div class="field">
            <label>Clave de Licencia</label>
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="text" id="license-key" placeholder="zht-xxxx-xxxx" autocomplete="off">
            </div>
        </div>

        <button type="submit" class="btn-login" id="btn-text">Entrar al Sistema</button>
        
        <div id="msg"></div>
    </form>

    <div class="footer-link">
        &copy; 2026 
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $('#login-form').submit(function(e) {
        e.preventDefault();
        
        const user = $('#username').val().trim();
        const key = $('#license-key').val().trim();
        const btn = $('#btn-text');
        const msg = $('#msg');

        if (!user || !key) {
            msg.html('<span class="error">Completa todos los campos.</span>');
            return;
        }

        btn.prop('disabled', true).text('Accediendo...');
        msg.html('');

        $.ajax({
            url: 'auth.php',
            type: 'POST',
            data: { user: user, key: key },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    msg.html('<span class="success">Acceso concedido. Redirigiendo...</span>');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1200);
                } else {
                    msg.html(`<span class="error">${res.message}</span>`);
                    btn.prop('disabled', false).text('Entrar al Sistema');
                }
            },
            error: function() {
                msg.html('<span class="error">Error en el servidor.</span>');
                btn.prop('disabled', false).text('Entrar al Sistema');
            }
        });
    });
</script>
</body>
</html>
