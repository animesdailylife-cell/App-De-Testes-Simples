<?php
require "conexao.php";

// cria tabelas
$conn->query("CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    email VARCHAR(150) UNIQUE,
    senha VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120),
    preco DECIMAL(10,2),
    quantidade INT
)");

// cria usuário admin (senha: 123456)
$senha = password_hash("123456", PASSWORD_DEFAULT);
$conn->query("INSERT IGNORE INTO usuarios (nome, email, senha)
              VALUES ('Admin', 'admin@teste.com', '$senha')");

echo "OK!! Usuário: admin@teste.com / senha: 123456<br>";
echo "<a href='login.php'>Ir para o login</a>";