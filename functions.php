<?php

// Get file
function getFile($url, $requestType, $cachedFileLocation, $cacheExpiration, $accessToken)
{
    if ($requestType == "redditJSON") {
        // Get Reddit JSON file
        // Use cached file if present
        if (file_exists($cachedFileLocation) && time() - filemtime($cachedFileLocation) < $cacheExpiration) {
            return file_get_contents($cachedFileLocation, true);
        } else {
            // Otherwise, CURL the file and cache it
            $ua   = 'web:toprss:1.0 (by /u/' . getenv('REDDIT_USER') . ')';

            $maxRetries      = 3;
            $connectTimeout  = 5;
            $timeout         = 10;
            $delayMs         = 250;

            $response = null;
            $code = 0;
            $err  = '';

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $ch = curl_init();
                $setOptArray = [
                    CURLOPT_URL            => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_USERAGENT      => $ua,
                    CURLOPT_HTTPHEADER     => [
                        'Authorization: Bearer ' . $accessToken,
                    ],
                    CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                    CURLOPT_TIMEOUT        => $timeout,
                ];

                $proxyUrl = getenv('PROXY_URL') ?: '';
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
                throw new \RuntimeException("Reddit API fail code=$code err=$err body=$response");
            }

            file_put_contents($cachedFileLocation, $response);

            return $response;
        }
    } elseif ($requestType == "redditScore") {
        // Get Reddit score file
        // Use cached file if present
        if (file_exists($cachedFileLocation) && time() - filemtime($cachedFileLocation) < $cacheExpiration) {
            return file_get_contents($cachedFileLocation, true);
        } else {
            // Otherwise, cache the score file
            file_put_contents($cachedFileLocation, $url);
            return $url;
        }
    } else {
        throw new \RuntimeException('Unknown request');
    }
}


// Get directory size
// https://www.a2zwebhelp.com/folder-size-php
function directorySize($dir)
{
    $countSize = 0;
    $count = 0;
    $dirArray = scandir($dir);
    foreach ($dirArray as $key => $filename) {
        if ($filename != ".." && $filename != ".") {
            if (is_dir($dir . "/" . $filename)) {
                $newFolderSize = directorySize($dir . "/" . $filename);
                $countSize = $countSize + $newFolderSize;
            } elseif (is_file($dir . "/" . $filename)) {
                $countSize = $countSize + filesize($dir . "/" . $filename);
                $count++;
            }
        }
    }
    return $countSize;
}


// Format size in bytes
// https://www.a2zwebhelp.com/folder-size-php
function sizeFormat($bytes)
{
    $kb = 1024;
    $mb = $kb * 1024;
    $gb = $mb * 1024;
    $tb = $gb * 1024;
    if (($bytes >= 0) && ($bytes < $kb)) {
        return $bytes . " B";
    } elseif (($bytes >= $kb) && ($bytes < $mb)) {
        return ceil($bytes / $kb) . " KB";
    } elseif (($bytes >= $mb) && ($bytes < $gb)) {
        return ceil($bytes / $mb) . " MB";
    } elseif (($bytes >= $gb) && ($bytes < $tb)) {
        return ceil($bytes / $gb) . " GB";
    } elseif ($bytes >= $tb) {
        return ceil($bytes / $tb) . " TB";
    } else {
        return $bytes . " B";
    }
}
