<?php
/*
 * Formulario de contacto de orcalpiscinas.com (hosting CaribeHost / Plesk).
 * Recibe el formulario de /contactenos/ y lo envía por correo.
 */

// A dónde llegan los mensajes del formulario
$DESTINO = 'orcalpiscinas@orcal.com.co';
// Remitente técnico: debe ser un correo del dominio alojado en este servidor
$REMITENTE = 'webmaster@orcal.com.co';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

function responder($ok, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(array('ok' => $ok));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 405);
}

// Trampa para robots: un humano nunca llena este campo oculto
if (!empty($_POST['bot-field'])) {
    responder(true);
}

function campo($nombre, $max = 200) {
    $v = isset($_POST[$nombre]) ? trim((string) $_POST[$nombre]) : '';
    $v = str_replace(array("\r", "\0"), '', $v);
    return mb_substr($v, 0, $max, 'UTF-8');
}

$nombre   = campo('first-name', 100);
$apellido = campo('last-name', 100);
$email    = campo('your-email', 150);
$telefono = campo('tel-879', 50);
$asunto   = campo('your-subject', 200);
$mensaje  = isset($_POST['your-message']) ? mb_substr(trim((string) $_POST['your-message']), 0, 5000, 'UTF-8') : '';

if ($nombre === '' || $apellido === '' || $telefono === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responder(false, 400);
}

// Evitar que alguien inyecte cabeceras de correo a través de los campos
foreach (array($nombre, $apellido, $email, $telefono, $asunto) as $v) {
    if (preg_match('/[\n\r]/', $v)) {
        responder(false, 400);
    }
}

$titulo = 'Contacto web: ' . ($asunto !== '' ? $asunto : $nombre . ' ' . $apellido);
$cuerpo = "Nuevo mensaje desde el formulario de orcalpiscinas.com\n\n"
        . "Nombre:   $nombre $apellido\n"
        . "Correo:   $email\n"
        . "Teléfono: $telefono\n"
        . "Asunto:   $asunto\n\n"
        . "Mensaje:\n$mensaje\n\n"
        . "---\nEnviado el " . date('d/m/Y H:i') . " desde " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n";

$cabeceras = array(
    'From: OrCal Web <' . $REMITENTE . '>',
    'Reply-To: ' . $nombre . ' ' . $apellido . ' <' . $email . '>',
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
);

$enviado = mail(
    $DESTINO,
    '=?UTF-8?B?' . base64_encode($titulo) . '?=',
    $cuerpo,
    implode("\r\n", $cabeceras),
    '-f' . $REMITENTE
);

responder($enviado, $enviado ? 200 : 500);
