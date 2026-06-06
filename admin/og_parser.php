<?php

header('Content-Type: application/json');

if (!isset($_GET['url'])) {
    echo json_encode(['og_image' => null]);
    exit;
}

$url = filter_var($_GET['url'], FILTER_VALIDATE_URL);
if (!$url) {
    echo json_encode(['og_image' => null]);
    exit;
}

$ctx = stream_context_create([
    'http' => ['timeout' => 5, 'user_agent' => 'Mozilla/5.0']
]);

$html = @file_get_contents($url, false, $ctx);

if (!$html) {
    echo json_encode(['og_image' => null]);
    exit;
}

libxml_use_internal_errors(true);
$dom = new DOMDocument();
@$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

$queries = [
    "//meta[@property='og:image']/@content",
    "//meta[@property='og:image:secure_url']/@content",
    "//meta[@name='twitter:image']/@content",
    "//meta[@name='twitter:image:src']/@content",
];

foreach ($queries as $q) {
    $nodes = $xpath->query($q);
    if ($nodes->length > 0) {
        echo json_encode(['og_image' => $nodes->item(0)->nodeValue]);
        exit;
    }
}

echo json_encode(['og_image' => null]);
