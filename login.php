<?php
require "conexao.php";

// logout
if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['id']   = $user['id'];
        $_SESSION['nome'] = $user['nome'];
        header("Location: index.php");
        exit;
    } else {
        $erro = "E-mail ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Login</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Arial, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    form {
        background: #fff;
        padding: 40px 35px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 380px;
    }

    h2 {
        text-align: center;
        color: #333;
        margin-bottom: 25px;
        font-size: 24px;
    }

    .erro {
        background: #ffe0e0;
        color: #c0392b;
        padding: 10px;
        border-radius: 6px;
        text-align: center;
        margin-bottom: 15px;
        font-size: 14px;
        border-left: 4px solid #c0392b;
    }

    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 12px 15px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.3s, box-shadow 0.3s;
        outline: none;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
    }

    button {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
    }

    button:active {
        transform: translateY(0);
    }

    .rodape {
        text-align: center;
        margin-top: 20px;
        font-size: 13px;
        color: #888;
    }

    .rodape code {
        background: #f1f1f1;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        color: #555;
    }
</style>
</head>
<body>

    <form method="POST">
        <h2>Login</h2>

        <?php if (!empty($erro)): ?>
            <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="senha" placeholder="Senha" required>
        <button type="submit">Entrar</button>

        <p class="rodape">Não tem conta? <a href="cadastro.php" style="color:#667eea;text-decoration:none;font-weight:600;">Cadastre-se</a></p>
    </form>

</body>
</html>