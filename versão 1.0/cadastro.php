<?php
require "conexao.php";

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome      = trim($_POST['nome']);
    $email     = trim($_POST['email']);
    $senha     = $_POST['senha'];
    $confirma  = $_POST['confirmar_senha'];

    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirma)) {
        $erro = "Preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres.";
    } elseif ($senha !== $confirma) {
        $erro = "As senhas não coincidem.";
    } else {
        // Verifica se o e-mail já existe.
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $erro = "Este e-mail já está cadastrado.";
        } else {
            // Criptografa a senha e insere
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nome, $email, $senha_hash);

            if ($stmt->execute()) {
                // Loga automaticamente
                $_SESSION['id']   = $stmt->insert_id;
                $_SESSION['nome'] = $nome;
                header("Location: index.php");
                exit;
            } else {
                $erro = "Erro ao cadastrar. Tente novamente.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Cadastro</title>
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
        max-width: 400px;
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

    input[type="text"],
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

    input[type="text"]:focus,
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
        margin-top: 5px;
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
        font-size: 14px;
        color: #666;
    }

    .rodape a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
    }

    .rodape a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

    <form method="POST">
        <h2>Criar Conta</h2>

        <?php if (!empty($erro)): ?>
            <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <input type="text" name="nome" placeholder="Nome completo" required>
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="senha" placeholder="Senha (mín. 6 caracteres)" required>
        <input type="password" name="confirmar_senha" placeholder="Confirmar senha" required>
        
        <button type="submit">Cadastrar</button>

        <p class="rodape">Já tem uma conta? <a href="login.php">Faça login</a></p>
    </form>

</body>
</html>