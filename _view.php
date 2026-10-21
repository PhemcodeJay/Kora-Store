<?php
$file = $argv[1];
$start = (int)$argv[2];
$end = (int)$argv[3];
$lines = file($file);
for ($i = $start - 1; $i < min($end, count($lines)); $i++) {
    echo ($i + 1) . '|' . $lines[$i];
}