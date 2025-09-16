<?php

if (empty(getenv('REDDIT_USER')) || empty(getenv('REDDIT_CLIENT_ID')) || empty(getenv('REDDIT_CLIENT_SECRET'))) {
    exit("Please set your Reddit username, client ID, and client secret in your Docker environment variables or config.php");
}

if(!file_exists('cache/token')) {
  mkdir('cache/token', 0755, true);
}
if(!file_exists('cache/token/token.txt')) {
  $tokenFile = fopen('cache/token/token.txt', 'w');
  fclose($tokenFile);
} elseif(time() - filectime('cache/token/token.txt') > 60 * 60 * 20 ) {
  unlink('cache/token/token.txt');
  $tokenFile = fopen('cache/token/token.txt', 'w');
  fclose($tokenFile);
}

$accessToken = file_get_contents('cache/token/token.txt');

if(empty($accessToken)) {
    $clientId     = getenv('REDDIT_CLIENT_ID') ?: '';
    $clientSecret = getenv('REDDIT_CLIENT_SECRET') ?: '';
    $redditUser   = getenv('REDDIT_USER') ?: '';
    $proxyUrl = getenv('PROXY_URL') ?: '';

    $auth = base64_encode($clientId . ':' . $clientSecret);
    $url  = 'https://www.reddit.com/api/v1/access_token';
    $ua   = 'web:toprss:1.0 (by /u/' . $redditUser . ')';

    $maxRetries      = 3;
    $connectTimeout  = 5;
    $timeout         = 10;
    $delayMs         = 250;

    $response = null;
    $code = 0;
    $err  = '';

    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        $ch = curl_init($url);
        $setOptArray = [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['grant_type' => 'client_credentials']),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => $ua,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_TIMEOUT        => $timeout,
        ];

        if (! empty($proxyUrl)) {
            $setOptArray[CURLOPT_PROXY] = $proxyUrl;
            $setOptArray[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5_HOSTNAME;
        }

        curl_setopt_array($ch, $setOptArray);

        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($response !== false && $code >= 200 && $code < 300) {
            break;
        }

        if ($attempt < $maxRetries) {
            usleep($delayMs * 1000);
            $delayMs = min($delayMs * 2, 4000);
        }
    }

    if ($response === false || $code < 200 || $code >= 300) {
        echo "Reddit OAuth fail code=$code err=$err body=$response";
        return;
    }

    $json = json_decode($response, true);
    $accessToken = $json['access_token'] ?? null;
    if (! $accessToken) {
        echo "No access_token in response : " . $response;
        return;
    }

  $tokenFile = fopen('cache/token/token.txt', 'w');
  fwrite($tokenFile, $accessToken);
  fclose($tokenFile);
}
