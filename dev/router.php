<?php
// router.php
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js)$/', $_SERVER["REQUEST_URI"])) {
    return false;
} else {
    // Parse and fix the URL before passing to index.php
    $uri = $_SERVER['REQUEST_URI'];
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '';  // This is important - tells Flight not to strip the path
    $_SERVER['PHP_SELF'] = $uri;
    include __DIR__ . '/../WebSite/index.php';
}