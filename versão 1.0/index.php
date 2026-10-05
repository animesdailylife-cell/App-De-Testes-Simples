<?php
require "conexao.php";

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$erro = "";

// --- ExCLUIR -
if (isset($_GET['excluir'])) {
    $id = (int) $_GET['excluir'];
    $conn->query("DELETE FROM produtos WHERE id = $id");
    header("Location: index.php");
    exit;
}

// --- SALVAR (novo ou editar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = (int) ($_POST['id'] ?? 0);
    $nome  = trim($_POST['nome']);
    $preco = str_replace(",", ".", $_POST['preco']);
    $qtd   = (int) $_POST['quantidade'];

    if ($nome === "") {
        $erro = "Nome é obrigatório.";
    } elseif (!is_numeric($preco) || $preco < 0) {
        $erro = "Preço inválido.";
    } elseif ($qtd < 0) {
        $erro = "Quantidade inválida.";
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE produtos SET nome=?, preco=?, quantidade=? WHERE id=?");
            $stmt->bind_param("sdii", $nome, $preco, $qtd, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO produtos (nome, preco, quantidade) VALUES (?,?,?)");
            $stmt->bind_param("sdi", $nome, $preco, $qtd);
        }
        $stmt->execute();
        header("Location: index.php");
        exit;
    }
}

// --- EDITAR (carrega dados) ---
$editando = null;
if (isset($_GET['editar'])) {
    $id = (int) $_GET['editar'];
    $editando = $conn->query("SELECT * FROM produtos WHERE id = $id")->fetch_assoc();
}

// --- LISTAR ---
$lista = $conn->query("SELECT * FROM produtos ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Produtos</title>
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
        padding: 30px 20px;
        color: #333;
    }

    /* ---------- TOPO ---------- */
    .topbar {
        background: #fff;
        padding: 15px 25px;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1000px;
        margin: 0 auto 25px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .topbar b {
        color: #667eea;
    }

    .topbar a {
        color: #c0392b;
        text-decoration: none;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 6px;
        transition: background 0.2s;
    }

    .topbar a:hover {
        background: #ffe0e0;
    }

    /* ---------- CONTAINER ---------- */
    .container {
        max-width: 1000px;
        margin: 0 auto;
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    h2 {
        color: #333;
        margin-bottom: 20px;
        font-size: 22px;
        border-left: 4px solid #667eea;
        padding-left: 12px;
    }

    /* ---------- MENSAGEM DE ERRO ---------- */
    .erro {
        background: #ffe0e0;
        color: #c0392b;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 14px;
        border-left: 4px solid #c0392b;
    }

    /* ---------- FORMULÁRIO ---------- */
    form {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: 15px;
        align-items: end;
        background: #f8f9ff;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        border: 1px solid #e6e9ff;
    }

    .campo {
        display: flex;
        flex-direction: column;
    }

    .campo label {
        font-size: 13px;
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
    }

    .campo input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .campo input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
    }

    .acoes-form {
        display: flex;
        gap: 10px;
    }

    /* ---------- BOTÕES ---------- */
    button {
        padding: 11px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        white-space: nowrap;
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
    }

    .cancelar {
        display: inline-flex;
        align-items: center;
        padding: 11px 20px;
        background: #eee;
        color: #555;
        text-decoration: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        transition: background 0.2s;
        white-space: nowrap;
    }

    .cancelar:hover {
        background: #ddd;
    }

    /* ---------- TABELA ---------- */
    .tabela-wrapper {
        overflow-x: auto;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        padding: 14px 12px;
        text-align: left;
        font-size: 14px;
        font-weight: 600;
    }

    table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        font-size: 14px;
        vertical-align: middle;
    }

    table tr:last-child td {
        border-bottom: none;
    }

    table tr:nth-child(even) {
        background: #f8f9ff;
    }

    table tr:hover {
        background: #eef1ff;
    }

    /* Estilo dos botões de ação na tabela */
    .btn-acao {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        transition: all 0.2s;
    }

    .btn-editar {
        color: #2980b9;
        background: #d6eaff;
    }

    .btn-editar:hover {
        background: #2980b9;
        color: #fff;
    }

    .btn-excluir {
        color: #c0392b;
        background: #ffe0e0;
    }

    .btn-excluir:hover {
        background: #c0392b;
        color: #fff;
    }

    /* ---------- RESPONSIVO ---------- */
    @media (max-width: 768px) {
        form {
            grid-template-columns: 1fr;
        }
        
        .container {
            padding: 20px 15px;
        }
    }
</style>
</head>
<body>

    <!-- TOPO -->
    <div class="topbar">
        <span>Olá, <b><?= htmlspecialchars($_SESSION['nome']) ?></b></span>
        <a href="login.php?sair=1">Sair</a>
    </div>

    <!-- CONTEÚDO PRINCIPAL -->
    <div class="container">
        <h2><?= $editando ? "Editar produto" : "Novo produto" ?></h2>

        <?php if ($erro): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="id" value="<?= $editando['id'] ?? 0 ?>">
            
            <div class="campo">
                <label>Nome</label>
                <input type="text" name="nome" value="<?= htmlspecialchars($editando['nome'] ?? '') ?>" required>
            </div>
            
            <div class="campo">
                <label>Preço</label>
                <input type="text" name="preco" value="<?= htmlspecialchars($editando['preco'] ?? '') ?>" required>
            </div>
            
            <div class="campo">
                <label>Qtd</label>
                <input type="number" name="quantidade" value="<?= (int)($editando['quantidade'] ?? 0) ?>" min="0" required>
            </div>
            
            <div class="acoes-form">
                <button type="submit"><?= $editando ? "Atualizar" : "Cadastrar" ?></button>
                <?php if ($editando): ?>
                    <a href="index.php" class="cancelar">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>

        <h2>Produtos cadastrados</h2>

        <div class="tabela-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Preço</th>
                        <th>Qtd</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = $lista->fetch_assoc()): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['nome']) ?></td>
                        <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                        <td><?= $p['quantidade'] ?></td>
                        <td>
                            <a href="?editar=<?= $p['id'] ?>" class="btn-acao btn-editar">Editar</a>
                            <a href="?excluir=<?= $p['id'] ?>" class="btn-acao btn-excluir" onclick="return confirm('Excluir?')">Excluir</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>