<?php

// Create cache directories if they don't exist and remove expired cache files

if (!file_exists("cache/reddit")) {
    mkdir("cache/reddit", 0755, true);
}
// Remove Reddit JSON files older than 5 minutes
$dir = "cache/reddit/";
foreach (glob($dir . "*") as $file) {
    if(time() - filectime($file) > 60 * 5) {
        unlink($file);
    }
}
if (!file_exists("cache/scores")) {
    mkdir("cache/scores", 0755, true);
}
// Remove score files older than 1 hour
$dir = "cache/scores/";
foreach (glob($dir . "*") as $file) {
    if(time() - filectime($file) > 60 * 60) {
        unlink($file);
    }
}

if (!file_exists("cache/rss")) {
    mkdir("cache/rss", 0755, true);
}
// Remove RSS feed files older than 1 hour
$dir = "cache/rss/";
foreach (glob($dir . "*") as $file) {
    if(time() - filectime($file) > 60 * 60 ) {
        unlink($file);
    }
}
