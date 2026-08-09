<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php extract_docx_text.php <docx-path>\n");
    exit(1);
}

$path = $argv[1];
$zip = new ZipArchive();

if ($zip->open($path) !== true) {
    fwrite(STDERR, "Unable to open DOCX: {$path}\n");
    exit(1);
}

$xml = $zip->getFromName('word/document.xml');
$zip->close();

if ($xml === false) {
    fwrite(STDERR, "word/document.xml not found.\n");
    exit(1);
}

$doc = new DOMDocument();
$doc->loadXML($xml);

$xpath = new DOMXPath($doc);
$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

$paragraphs = [];

foreach ($xpath->query('//w:body/w:p') as $paragraph) {
    $parts = [];

    foreach ($xpath->query('.//w:t', $paragraph) as $textNode) {
        $parts[] = $textNode->textContent;
    }

    $text = trim(implode('', $parts));

    if ($text !== '') {
        $paragraphs[] = $text;
    }
}

echo implode(PHP_EOL, $paragraphs);
