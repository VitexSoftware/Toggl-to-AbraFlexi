<?php

/**
 * ToggleToFlexiBee - AppInit.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2020 Vitex Software
 */

namespace Toggl2FlexiBee;

use Dotenv\Dotenv;

require_once '../vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    //$dotenv = Dotenv::create(dirname(__DIR__));
    $dotenv->load();
}

new \Ease\Locale('cs_CZ', '../i18n', 'toggl2flexibee');

$engine = new Importer();
$engine->import();
