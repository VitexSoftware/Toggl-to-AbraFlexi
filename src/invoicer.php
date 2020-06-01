<?php

namespace Toggl2FlexiBee;

use Ease\Locale;
use Ease\Shared;
use Ease\TWB4\WebPage;
use Ease\WebPage as WebPage2;
use AJT\Toggl\ReportsClient;
use AJT\Toggl\TogglClient;

require_once '../vendor/autoload.php';
new Locale('cs_CZ', '../i18n', 'toggl2flexibee');
session_start();

Shared::instanced()->loadConfig('../config.json', true);

$oPage = new WebPage('Toggl2FlexiBee: Invoice');

$apiKey = Shared::instanced()->getConfigValue('TOGGLE_TOKEN');

$toggl_client = TogglClient::factory(array('api_key' => $apiKey));

$me = $toggl_client->getCurrentUser(['with_related_data' => true])->toArray();

$toggl_client = ReportsClient::factory(array('api_key' => $apiKey, 'debug' => false));
$detailsData  = $toggl_client->Details([
        'since' => WebPage2::getRequestValue('since'),
        'until' => WebPage2::getRequestValue('until'),
        'display_hours' => 'decimal',
        'workspace_id' => WebPage2::getRequestValue('workspace', 'int'),
        'user_agent' => constant('EASE_APPNAME'),
        'user_ids' => $me['data']['id']
    ])->toArray();

$invoiceItems = [];


foreach ($detailsData['data'] as $detail) {

    $project  = $detail['project'];
    $task     = $detail['description'];
    $duration = $detail['dur'] ;

    if (!array_key_exists($project, $invoiceItems)) {
        $invoiceItems[$project] = [];
    }
    if (!array_key_exists($task, $invoiceItems[$project])) {
        $invoiceItems[$project][$task] = 0;
    }
    $invoiceItems[$project][$task] += $duration;
}


echo '<pre>';

print_r($invoiceItems);

echo '</pre>';

$invoicer = new FakturaVydana([
    'typDokl' => \FlexiPeeHP\FlexiBeeRO::code('FAKTURA'),
    'firma' => \FlexiPeeHP\FlexiBeeRO::code($oPage->getRequestValue('firma') ? current($oPage->getRequestValue('firma')) : Shared::instanced()->getConfigValue('FLEXIBEE_CUSTOMER')),
    'popis' => sprintf(_('Work from %s to %s'), WebPage2::getRequestValue('since'), WebPage2::getRequestValue('until'))
    ]);



$invoicer->takeItemsFromArray($invoiceItems);
$created = $invoicer->sync();

$invoiceTabs = new \Ease\TWB4\Tabs('Invoices');

//$invoiceTabs->addTab(_('Html'),
//    new \FlexiPeeHP\ui\EmbedResponsiveHTML($invoicer));
//$invoiceTabs->addTab(_('PDF'), new \FlexiPeeHP\ui\EmbedResponsivePDF($invoicer));

$oPage->addItem(new \Ease\TWB4\Container(new \Ease\TWB4\Panel('Doklad '.new \Ease\Html\ATag($invoicer->getApiUrl(),
                $invoicer->getDataValue('kod')).' '.($created ? 'byl' : 'nebyl').' vystaven',
            $created ? 'success' : 'danger', $invoiceTabs            )));
$oPage->draw();
