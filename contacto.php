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

// Configuración opcional para enviar por SMTP con un buzón real.
// Se crea a mano en el servidor (no está en git, el repositorio es público):
//   <?php return array('host' => 'mx.caribehost.email', 'puerto' => 465,
//                      'usuario' => 'webmaster@orcal.com.co', 'clave' => '...');
$smtp = is_file(__DIR__ . '/contacto-config.php') ? include __DIR__ . '/contacto-config.php' : null;

$SMTP_FALLO = '';

// Envía por SMTP autenticado. $tls: 'ssl' (puerto 465), 'starttls' (587) o '' (sin cifrar, solo local).
function smtp_enviar($host, $puerto, $tls, $usuario, $clave, $de, $para, $titulo, $cabeceras, $cuerpo) {
    global $SMTP_FALLO;
    $ctx = stream_context_create(array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false)));
    $f = @stream_socket_client(($tls === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $puerto, $en, $es, 10, STREAM_CLIENT_CONNECT, $ctx);
    if (!$f) { $SMTP_FALLO = "$host:$puerto conexion"; error_log("contacto.php SMTP $host:$puerto conexión: $es"); return false; }
    stream_set_timeout($f, 15);
    $leer = function () use ($f) { $r = ''; while (($l = fgets($f, 515)) !== false) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $r; };
    $paso = '';
    $cmd = function ($t, $ok, $nombre) use ($f, $leer, &$paso) {
        $paso = $nombre;
        if ($t !== null) fwrite($f, $t . "\r\n");
        $r = $leer();
        if (strpos($r, (string) $ok) !== 0) { error_log("contacto.php SMTP $nombre: " . trim($r)); return false; }
        return true;
    };
    $datos = "To: $para\r\nSubject: $titulo\r\n" . implode("\r\n", $cabeceras) . "\r\n\r\n"
           . preg_replace('/^\./m', '..', str_replace("\n", "\r\n", $cuerpo)) . "\r\n.";
    $ok = $cmd(null, 220, 'saludo') && $cmd('EHLO orcalpiscinas.com', 250, 'ehlo');
    if ($ok && $tls === 'starttls') {
        $ok = $cmd('STARTTLS', 220, 'starttls')
           && stream_socket_enable_crypto($f, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)
           && $cmd('EHLO orcalpiscinas.com', 250, 'ehlo2');
    }
    $ok = $ok && $cmd('AUTH LOGIN', 334, 'auth') && $cmd(base64_encode($usuario), 334, 'usuario') && $cmd(base64_encode($clave), 235, 'clave')
       && $cmd("MAIL FROM:<$de>", 250, 'remitente') && $cmd("RCPT TO:<$para>", 250, 'destino')
       && $cmd('DATA', 354, 'data') && $cmd($datos, 250, 'mensaje');
    if (!$ok) $SMTP_FALLO = "$host:$puerto $paso";
    @fwrite($f, "QUIT\r\n"); fclose($f);
    return $ok;
}

$tituloCodificado = '=?UTF-8?B?' . base64_encode($titulo) . '?=';
$via = '';
if (is_array($smtp)) {
    $remitenteSmtp = $smtp['usuario'];
    $cab = $cabeceras; $cab[0] = 'From: OrCal Web <' . $remitenteSmtp . '>';
    $cab[] = 'Date: ' . date('r');
    // Rutas posibles, en orden: la configurada y luego el servidor de correo local de Plesk
    $rutas = array(
        array($smtp['host'], (int) $smtp['puerto'], (int) $smtp['puerto'] === 465 ? 'ssl' : 'starttls'),
        array($smtp['host'], 587, 'starttls'),
        array('localhost', 587, 'starttls'),
        array('localhost', 25, ''),
    );
    $fallos = array();
    $enviado = false;
    foreach ($rutas as $r) {
        if (smtp_enviar($r[0], $r[1], $r[2], $smtp['usuario'], $smtp['clave'], $remitenteSmtp, $DESTINO, $tituloCodificado, $cab, $cuerpo)) {
            $enviado = true; $SMTP_FALLO = $r[0] . ':' . $r[1];
            break;
        }
        $fallos[] = $SMTP_FALLO;
    }
    $via = 'smtp';
} else {
    $enviado = mail($DESTINO, $tituloCodificado, $cuerpo, implode("\r\n", $cabeceras), '-f' . $REMITENTE);
    $via = 'mail-f';
    if (!$enviado) {
        $e = error_get_last();
        error_log('contacto.php mail() con -f falló: ' . ($e ? $e['message'] : 'sin detalle'));
        $enviado = mail($DESTINO, $tituloCodificado, $cuerpo, implode("\r\n", $cabeceras));
        $via = 'mail';
    }
}

http_response_code($enviado ? 200 : 500);
echo json_encode(array('ok' => $enviado, 'via' => $via . ($via === 'smtp' ? ' ' . ($enviado ? $SMTP_FALLO : implode(', ', $fallos)) : '')));
