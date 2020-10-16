<?php

/**
 * ToggleToFlexiBee - AppInit.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2020 Vitex Software
 */

namespace Toggl2FlexiBee;

require_once '../vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/.env')) {  
    \Ease\Shared::instanced()->loadConfig(dirname(__DIR__) . '/.env',true);
}

new \Ease\Locale('cs_CZ', '../i18n', 'toggl2flexibee');

define('EASE_LOOGER', 'console|syslog');

$engine = new Importer();
$engine->import();
