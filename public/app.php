<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// This is the index that will be used to bootstrap the actual app in /build.
$index = dirname(__DIR__).'/build/html/index.html';
if (file_exists($index)) {
    echo file_get_contents($index);
    exit(0);
}

$twig = new Environment(new FilesystemLoader(dirname(__DIR__).'/templates'));
echo $twig->render('html/setup.html.twig');
exit(0);
