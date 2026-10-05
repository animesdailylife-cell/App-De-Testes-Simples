<?php
// =============================================================
// 🔒 ENDPOINT AJAX — responde SEMPRE em JSON, ANTES de tudo
// =============================================================
if (isset($_GET['acao']) && $_GET['acao'] === 'rodar') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    // Desliga qualquer exibição de erro em HTML
    ini_set('display_errors', 0);
    error_reporting(0);

    $resposta = [
        'status' => 'erro',
        'msg'    => 'Erro desconhecido',
        'nome'   => '',
        'preco'  => '',
        'qtd'    => 0,
        'hora'   => date('H:i:s'),
        'id'     => null,
    ];

    try {
        require "conexao.php";

        // Verifica login SEM redirecionar (responderia HTML)
        if (!isset($_SESSION['id'])) {
            $resposta['msg'] = 'Sessão expirada. Faça login novamente.';
            echo json_encode($resposta);
            exit;
        }

        // ---------- GERA VALORES ALEATÓRIOS ----------
        $nomes = [
            "", "   ", "TESTE_", "TESTE_A", "TESTE_" . str_repeat("X", 50),
            "TESTE_" . str_repeat("Y", 119), "TESTE_" . str_repeat("Z", 120),
            "TESTE_" . str_repeat("W", 121), "TESTE_" . str_repeat("V", 200),
            "TESTE_🔥 emoji", "TESTE_Ação ção ãõ", "TESTE_'aspas'",
            "TESTE_\"dquote\"", "TESTE_'; DROP TABLE produtos;--",
            "TESTE_<script>alert(1)</script>",
            "TESTE_" . bin2hex(random_bytes(8)),
            "TESTE_Produto " . rand(1, 99999),
        ];

        $precos = [
            "0", "-0.01", "-1", "-99999", "0.01", "1", "1,50", "1.50",
            "99999999.99", "999999999.99", "1.1234567890", "abc", "",
            "1e5", "0x1F", "  10.00  ", "10.005", "10.999", "1,234.56",
            number_format(rand(0, 99999999) / 100, 2, '.', ''),
        ];

        $qtds = [
            0, 1, -1, -99999, 100, 1000, 999999,
            1000000, 2000000000, 2147483647, 2147483648, 3000000000,
            rand(-100, 100), rand(0, 999999),
        ];

        $nome  = $nomes[array_rand($nomes)];
        $preco = $precos[array_rand($precos)];
        $qtd   = $qtds[array_rand($qtds)];

        $resposta['nome']  = strlen($nome) > 60 ? substr($nome, 0, 60) . "…" : $nome;
        $resposta['preco'] = $preco;
        $resposta['qtd']   = $qtd;

        // ---------- VALIDAÇÃO (mesma do index.php) ----------
        $erro = "";
        $precoLimpo = str_replace(",", ".", $preco);

        if (trim($nome) === "") {
            $erro = "Nome é obrigatório.";
        } elseif (!is_numeric($precoLimpo) || $precoLimpo < 0) {
            $erro = "Preço inválido.";
        } elseif ($qtd < 0) {
            $erro = "Quantidade inválida.";
        }

        if ($erro) {
            $resposta['status'] = 'bloqueado';
            $resposta['msg']    = $erro;
        } else {
            $stmt = $conn->prepare("INSERT INTO produtos (nome, preco, quantidade) VALUES (?,?,?)");
            $stmt->bind_param("sdi", $nome, $precoLimpo, $qtd);

            if ($stmt->execute()) {
                $idInserido = $stmt->insert_id;
                $check = $conn->query("SELECT nome, preco, quantidade FROM produtos WHERE id = $idInserido")->fetch_assoc();

                $alertas = [];
                if (strlen($check['nome']) !== strlen($nome)) {
                    $alertas[] = "nome " . strlen($nome) . "→" . strlen($check['nome']);
                }
                if ((float)$check['preco'] != (float)$precoLimpo) {
                    $alertas[] = "preço " . $precoLimpo . "→" . $check['preco'];
                }
                if ((int)$check['quantidade'] != $qtd) {
                    $alertas[] = "qtd " . $qtd . "→" . $check['quantidade'];
                }

                $resposta['status'] = $alertas ? 'aviso' : 'ok';
                $resposta['msg']    = $alertas ? implode(" | ", $alertas) : 'OK';
                $resposta['id']     = $idInserido;
            } else {
                $resposta['status'] = 'erro';
                $resposta['msg']    = $conn->error;
            }
        }
    } catch (Throwable $e) {
        $resposta['status'] = 'erro';
        $resposta['msg']    = 'EXCEPTION: ' . $e->getMessage();
    }

    echo json_encode($resposta);
    exit;
}

// ============================================================
// 📄 PÁGINA NORMAL (HTML)
// ============================================================
require "conexao.php";

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['limpar'])) {
    $conn->query("DELETE FROM produtos WHERE nome LIKE 'TESTE_%'");
    header("Location: loop_teste.php");
    exit;
}

$totalTeste = $conn->query("SELECT COUNT(*) AS t FROM produtos WHERE nome LIKE 'TESTE_%'")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>🔁 Loop Infinito de Testes</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }

    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
        color: #333;
    }

    .topbar {
        background: #fff; padding: 15px 25px; border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0,0,0,.15);
        display: flex; justify-content: space-between; align-items: center;
        max-width: 1200px; margin: 0 auto 20px; flex-wrap: wrap; gap: 10px;
    }
    .topbar b { color: #667eea; }
    .topbar a {
        color: #667eea; text-decoration: none; font-weight: 600;
        padding: 6px 12px; border-radius: 6px;
    }
    .topbar a:hover { background: #eef1ff; }

    .container {
        max-width: 1200px; margin: 0 auto; background: #fff;
        padding: 25px; border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,.2);
    }

    h1 {
        font-size: 22px; margin-bottom: 6px;
        border-left: 4px solid #667eea; padding-left: 12px;
    }
    .sub { color: #777; font-size: 13px; padding-left: 16px; margin-bottom: 18px; }

    .painel {
        display: flex; gap: 12px; flex-wrap: wrap;
        align-items: center; margin-bottom: 18px;
        padding: 15px; background: #f8f9ff;
        border: 1px solid #e6e9ff; border-radius: 10px;
    }

    .btn {
        padding: 10px 20px; border-radius: 8px; border: none;
        font-weight: 700; font-size: 14px; cursor: pointer;
        transition: all .2s; text-decoration: none; display: inline-block;
    }
    .btn-iniciar {
        background: linear-gradient(135deg, #27ae60 0%, #16a085 100%);
        color: #fff;
    }
    .btn-iniciar:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(39,174,96,.4); }

    .btn-parar {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: #fff;
    }
    .btn-parar:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(231,76,60,.4); }

    .btn:disabled { opacity: .4; cursor: not-allowed; transform: none !important; box-shadow: none !important; }

    .btn-limpar { background: #eee; color: #555; }
    .btn-limpar:hover { background: #ddd; }

    .stat {
        background: #fff; border: 1px solid #e6e9ff;
        padding: 8px 14px; border-radius: 8px;
        font-size: 13px; font-weight: 600;
    }
    .stat b { color: #667eea; font-size: 15px; }
    .stat.verde   b { color: #27ae60; }
    .stat.azul    b { color: #3498db; }
    .stat.amarelo b { color: #f39c12; }
    .stat.vermelho b { color: #c0392b; }

    #log {
        height: 520px; overflow-y: auto;
        background: #1e1e2e; border-radius: 10px;
        padding: 15px; font-family: 'Consolas', 'Monaco', monospace;
        font-size: 13px; color: #cdd6f4; line-height: 1.6;
    }
    #log::-webkit-scrollbar { width: 10px; }
    #log::-webkit-scrollbar-track { background: #181825; border-radius: 5px; }
    #log::-webkit-scrollbar-thumb { background: #45475a; border-radius: 5px; }
    #log::-webkit-scrollbar-thumb:hover { background: #585b70; }

    .linha { padding: 4px 8px; border-radius: 4px; margin-bottom: 2px; white-space: pre-wrap; word-break: break-word; }
    .linha.ok        { background: rgba(39,174,96,.12);  color: #a6e3a1; }
    .linha.bloqueado { background: rgba(52,152,219,.10); color: #89b4fa; }
    .linha.aviso     { background: rgba(243,156,18,.12); color: #f9e2af; }
    .linha.erro      { background: rgba(231,76,60,.15);  color: #f38ba8; }

    .tag {
        display: inline-block; padding: 1px 6px; border-radius: 3px;
        font-size: 11px; font-weight: 700; margin-right: 6px;
    }
    .tag.ok        { background: #a6e3a1; color: #1e1e2e; }
    .tag.bloqueado { background: #89b4fa; color: #1e1e2e; }
    .tag.aviso     { background: #f9e2af; color: #1e1e2e; }
    .tag.erro      { background: #f38ba8; color: #1e1e2e; }

    .hora { color: #6c7086; margin-right: 8px; }

    #pulso {
        display: inline-block; width: 8px; height: 8px;
        border-radius: 50%; background: #6c7086; margin-right: 8px;
        vertical-align: middle;
    }
    #pulso.ativo { background: #27ae60; animation: pulsar 1s infinite; }
    @keyframes pulsar {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%      { opacity: .4; transform: scale(1.4); }
    }
</style>
</head>
<body>

<div class="topbar">
    <span>Olá, <b><?= htmlspecialchars($_SESSION['nome']) ?></b></span>
    <div>
        <a href="index.php">← Voltar aos produtos</a>
        <a href="login.php?sair=1" style="color:#c0392b">Sair</a>
    </div>
</div>

<div class="container">
    <h1>🔁 Loop Infinito de Testes</h1>
    <p class="sub">Testes gerados aleatoriamente, rodando até você clicar em <b>Parar</b>.</p>

    <div class="painel">
        <button id="btnIniciar" class="btn btn-iniciar" onclick="iniciar()">▶ Iniciar loop</button>
        <button id="btnParar"   class="btn btn-parar" onclick="parar()" disabled>■ Parar</button>
        <a href="loop_teste.php?limpar=1" class="btn btn-limpar" onclick="return confirm('Apagar TODOS os produtos TESTE_?')">🗑️ Limpar dados</a>
        <label style="font-size:13px; margin-left:8px;">Velocidade:</label>
        <input type="range" id="vel" min="0" max="900" value="400" step="100" style="vertical-align:middle;">
        <span id="velLabel" style="font-size:12px;color:#777;">400ms</span>
    </div>

    <div class="painel" style="margin-bottom:15px;">
        <span id="pulso"></span>
        <span id="status" style="font-weight:600;">Parado</span>
        <span class="stat verde"    style="margin-left:auto;">✅ OK:        <b id="nOk">0</b></span>
        <span class="stat azul">🔵 Bloqueado: <b id="nBloq">0</b></span>
        <span class="stat amarelo">⚠️  Aviso:     <b id="nAviso">0</b></span>
        <span class="stat vermelho">💥 Erro:      <b id="nErro">0</b></span>
        <span class="stat">📦 No banco: <b id="nTotal"><?= $totalTeste ?></b></span>
    </div>

    <div id="log"></div>
</div>

<script>
let rodando = false;
let timer   = null;
const log   = document.getElementById('log');
const cont  = { ok: 0, bloqueado: 0, aviso: 0, erro: 0 };

function escapar(s) {
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function registrar(html, classe = '') {
    const div = document.createElement('div');
    div.className = 'linha ' + classe;
    div.innerHTML = html;
    log.appendChild(div);
    while (log.children.length > 5000) log.removeChild(log.firstChild);
    log.scrollTop = log.scrollHeight;
}

function atualizarContadores() {
    document.getElementById('nOk').textContent    = cont.ok;
    document.getElementById('nBloq').textContent  = cont.bloqueado;
    document.getElementById('nAviso').textContent = cont.aviso;
    document.getElementById('nErro').textContent  = cont.erro;
}

async function rodarUm() {
    try {
        const r = await fetch('loop_teste.php?acao=rodar&_=' + Date.now());

        // 🔎 Diagnóstico: mostra o que veio se não for JSON
        const texto = await r.text();
        let d;
        try {
            d = JSON.parse(texto);
        } catch (e) {
            // Não é JSON — mostra o começo pra você ver o que veio
            cont.erro++;
            atualizarContadores();
            registrar(
                `[ERRO] Resposta não-JSON (HTTP ${r.status}): <b>${escapar(texto.substring(0,120))}…</b>`,
                'erro'
            );
            return;
        }

        const nomeExib  = d.nome  === "" ? "<vazio>" : d.nome;
        const precoExib = d.preco === "" ? "<vazio>" : d.preco;

        let linha = `<span class="hora">[${d.hora}]</span>`;
        linha += `<span class="tag ${d.status}">${d.status.toUpperCase()}</span>`;
        linha += `nome=<b>${escapar(nomeExib)}</b> `;
        linha += `preco=<b>${escapar(precoExib)}</b> `;
        linha += `qtd=<b>${d.qtd}</b>`;
        if (d.id)   linha += ` <span style="color:#6c7086">→ id=${d.id}</span>`;
        if (d.msg && d.msg !== 'OK') linha += ` <span style="color:#f9e2af">│ ${escapar(d.msg)}</span>`;

        cont[d.status] = (cont[d.status] || 0) + 1;
        atualizarContadores();
        registrar(linha, d.status);

    } catch (e) {
        cont.erro++;
        atualizarContadores();
        registrar(`[ERRO JS] ${escapar(e.message)}`, 'erro');
    }
}

function loop() {
    if (!rodando) return;
    rodarUm().finally(() => {
        if (!rodando) return;
        timer = setTimeout(loop, parseInt(document.getElementById('vel').value));
    });
}

function iniciar() {
    if (rodando) return;
    rodando = true;
    document.getElementById('btnIniciar').disabled = true;
    document.getElementById('btnParar').disabled   = false;
    document.getElementById('status').textContent  = 'Rodando…';
    document.getElementById('pulso').classList.add('ativo');
    loop();
}

function parar() {
    rodando = false;
    clearTimeout(timer);
    document.getElementById('btnIniciar').disabled = false;
    document.getElementById('btnParar').disabled   = true;
    document.getElementById('status').textContent  = 'Parado';
    document.getElementById('pulso').classList.remove('ativo');
    registrar(`───── LOOP PARADO ─────`, 'ok');
}

document.getElementById('vel').addEventListener('input', e => {
    document.getElementById('velLabel').textContent = e.target.value + 'ms';
});
</script>

</body>
</html>