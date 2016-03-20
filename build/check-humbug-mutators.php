#!/usr/bin/env php
<?php

declare(strict_types=1);

call_user_func(function () {
    $log = json_decode(file_get_contents(__DIR__ . '/../humbug-log.json'), true);

    if (! array_key_exists('escapes', $log['summary'])) {
        throw new \UnexpectedValueException('Could not find "escapes" in summary');
    }

    echo file_get_contents(__DIR__ . '/../humbug-log.txt');

    if ($log['summary']['escapes']) {
        throw new \UnexpectedValueException('Humbug reported escaped mutants');
    }
});
