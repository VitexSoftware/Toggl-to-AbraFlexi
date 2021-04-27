<?php

/**
 * ToggleToAbraFlexi - AppInit.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2020 Vitex Software
 */

namespace Toggl2AbraFlexi;

require_once '../vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/.env')) {
    \Ease\Shared::instanced()->loadConfig(dirname(__DIR__) . '/.env', true);
}

new \Ease\Locale('cs_CZ', '../i18n', 'toggl-to-abraflexi');

define('EASE_LOOGER', 'console|syslog');

$engine = new Importer();
$engine->import();

if (\Ease\Functions::cfg('INVOICE_DOWNLOAD')) {
    $engine->addStatusMessage(sprintf(_('Invoice saved as %s'), $engine->downloadInFormat('pdf', \Ease\Functions::cfg('REPORTS_DIR'))));
    $engine->addStatusMessage(sprintf(_('Invoice saved as %s'), $engine->downloadInFormat('isdocx', \Ease\Functions::cfg('REPORTS_DIR'))));
}
