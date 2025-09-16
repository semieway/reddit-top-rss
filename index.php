<?php

const DEFAULT_SUBREDDIT = 'pics';

// Auth
include 'auth.php';

// Globals
global $subreddit;
global $thresholdScore;
global $thresholdPercentage;
global $thresholdPostsPerDay;
global $mercuryJSON;

// Cache
include 'cache.php';

// View format
if(isset($_GET['view']) && $_GET['view'] == 'rss') {
	include 'rss.php';
} else {
	include 'html.php';
}
