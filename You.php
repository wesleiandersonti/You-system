<?php
// YouTube video ID
$videoId = $_GET['id'];

// Fetch YouTube page content
$url = "https://www.youtube.com/watch?v=$videoId";
$html = file_get_contents($url);

// Find HLS manifest URL
if (preg_match('/"hlsManifestUrl":"([^"]+)"/', $html, $matches)) {
    $hlsManifestUrl = json_decode('"' . $matches[1] . '"'); // Decode the URL if necessary
    header("Location: $hlsManifestUrl");
    exit;
} else {
    echo 'HLS manifest URL not found.';
}
?>
