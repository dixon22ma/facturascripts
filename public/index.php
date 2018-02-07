<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  carlos@facturascripts.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use FacturaScripts\Kernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

// Composer installed?
if (!file_exists(__DIR__ . '/../vendor')) {
    die('<h1>COMPOSER ERROR</h1><p>You need to run: composer install<br/>cd public<br/>npm install</p>'
        . '----------------------------------------'
        . '<p>Debes ejecutar: composer install<br/>cd public<br/>npm install</p>');
}

require __DIR__ . '/../vendor/autoload.php';

// Config.php present?
if (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
}

// The check is to ensure we don't use .env in production
if (!isset($_SERVER['FACTURASCRIPTS_ENV'])) {
    if (!class_exists(Dotenv::class)) {
        throw new \RuntimeException('FACTURASCRIPTS_ENV environment variable is not defined.'
        . ' You need to define environment variables for configuration or add'
        . ' "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
    }
    (new Dotenv())->load(__DIR__ . '/../.env');
}

$env = $_SERVER['FACTURASCRIPTS_ENV'] ?? 'dev';
$debug = $_SERVER['FACTURASCRIPTS_DEBUG'] ?? ('prod' !== $env);

if ($debug) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts(explode(',', $trustedHosts));
}

$kernel = new Kernel($env, $debug);

if (!defined('FS_FOLDER')) {
    define('FS_FOLDER', $kernel->getProjectDir());
}

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
