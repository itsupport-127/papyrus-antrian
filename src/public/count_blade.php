<?php
$content = file_get_contents('resources/views/operator/dashboard.blade.php');
$lines = explode("\n", $content);

$ifStack = [];

foreach ($lines as $i => $line) {
    $lineNum = $i + 1;
    
    $pos = 0;
    while (($pos = strpos($line, '@if(', $pos)) !== false) {
        $ifStack[] = $lineNum;
        $pos++;
    }
    
    $pos = 0;
    while (($pos = strpos($line, '@endif', $pos)) !== false) {
        if (empty($ifStack)) {
            echo "EXTRA @endif at line $lineNum" . PHP_EOL;
        } else {
            $matched = array_pop($ifStack);
        }
        $nextPos = $pos + 7;
        if ($nextPos < strlen($line)) {
            $pos = $nextPos;
        } else {
            break;
        }
    }
}

echo "Remaining unclosed @if(: " . (empty($ifStack) ? 'none' : implode(', ', $ifStack)) . PHP_EOL;
