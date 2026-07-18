<?php
/**
 * Conexão com o banco de dados (PDO / MySQL)
 *
 * Ajuste as constantes abaixo com os dados do seu servidor.
 * No XAMPP local, os valores padrão normalmente já funcionam:
 * usuário "root" e senha em branco.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'delicias_de_maria');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Caminho para a pasta de uploads (fotos dos bolos) e sua URL pública
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', '/uploads/');

// Número de WhatsApp da confeitaria (com DDI + DDD, só números)
define('WHATSAPP_NUMERO', '5512982037844');

function getConexao(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die('Erro ao conectar ao banco de dados. Verifique se o MySQL está rodando e se o banco "delicias_de_maria" foi criado (rode o database.sql). Detalhe técnico: ' . $e->getMessage());
        }
    }

    return $pdo;
}
