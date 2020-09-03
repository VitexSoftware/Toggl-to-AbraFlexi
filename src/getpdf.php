<?php

/**
 * Feed Browser pdf of documnet in default english language
 * 
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */

namespace Toggl2FlexiBee;

use Ease\Locale;
use Ease\Shared;
use FlexiPeeHP\FlexiBeeRO;

require_once '../vendor/autoload.php';
new Locale('cs_CZ', '../i18n', 'toggl2flexibee');
session_start();

Shared::instanced()->loadConfig('../config.json', true);


$embed = \Ease\WebPage::getRequestValue('embed');
$id = \Ease\WebPage::getRequestValue('id');
$evidence = \Ease\WebPage::getRequestValue('evidence');
$lang = \Ease\WebPage::getRequestValue('lang');


$document = new FlexiBeeRO(is_numeric($id) ? intval($id) : $id,
        ['evidence' => $evidence]);

if (!is_null($document->getMyKey())) {
    $documentBody = $document->getInFormat('pdf', null, empty($lang) ? 'en' : $lang);

    if ($embed != 'true') {
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename=' . $document->getEvidence() . '_' . $document . '.pdf');
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: binary');
    } else {
        header('Content-Type: application/pdf');
    }
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . strlen($documentBody));
    echo $documentBody;
} else {
    die(_('Wrong call'));
}
