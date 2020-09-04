<?php

namespace Toggl2FlexiBee;

use Ease\Locale;
use Ease\Shared;
use Ease\TWB4\WebPage;
use AJT\Toggl\ReportsClient;
use AJT\Toggl\TogglClient;

require_once '../vendor/autoload.php';
new Locale('cs_CZ', '../i18n', 'toggl2flexibee');
session_start();

Shared::instanced()->loadConfig('../config.json', true);

$oPage = new WebPage('Toggl2FlexiBee: Invoice');

$apiKey = Shared::instanced()->getConfigValue('TOGGLE_TOKEN');

$toggl_client = TogglClient::factory(['api_key' => $apiKey]);

$me = $toggl_client->getCurrentUser(['with_related_data' => true])->toArray();

$toggl_client = ReportsClient::factory(['api_key' => $apiKey, 'debug' => false]);
$detailsData = $toggl_client->Details([
            'since' => WebPage::getRequestValue('since'),
            'until' => WebPage::getRequestValue('until'),
            'display_hours' => 'decimal',
            'workspace_id' => WebPage::getRequestValue('workspace', 'int'),
            'user_agent' => \Ease\Functions::cfg('EASE_APPNAME'),
            'user_ids' => $me['data']['id'],
        ])->toArray();

$invoiceItems = [];
$projects = [];
$durations = [];



foreach ($detailsData['data'] as $detail) {

    $project = empty($detail['project']) ? _('No Project') : $detail['project'];
    $task = $detail['description'];
    $duration = $detail['dur'];

    $durations[] = FakturaVydana::formatMilliseconds($duration) . ' ' . $project . ' ' . $task;

    if (!array_key_exists($project, $invoiceItems)) {
        $invoiceItems[$project] = [];
    }
    if (!array_key_exists($task, $invoiceItems[$project])) {
        $invoiceItems[$project][$task] = 0;
    }
    $invoiceItems[$project][$task] += $duration;
    $projects[$project] = $project;
}

//
//echo '<pre>';
//
//echo count($detailsData['data']);
//
//print_r($durations);
//
//
//print_r($invoiceItems);
//
//echo '</pre>';

$invoicer = new FakturaVydana([
    'typDokl' => \FlexiPeeHP\FlexiBeeRO::code('FAKTURA'),
    'firma' => \FlexiPeeHP\FlexiBeeRO::code($oPage->getRequestValue('firma') ? current($oPage->getRequestValue('firma')) : Shared::instanced()->getConfigValue('FLEXIBEE_CUSTOMER')),
    'popis' => sprintf(_('Work from %s to %s'), WebPage::getRequestValue('since'), WebPage::getRequestValue('until')),
    'poznam' => 'Toggl Workspace: ' . WebPage::getRequestValue('workspace', 'int')
        ]);



$invoicer->takeItemsFromArray($invoiceItems);
$created = $invoicer->sync();

$invoiceTabs = new \Ease\TWB4\Tabs(null, ['id' => 'Invoices']);

$invoiceTabs->addTab(_('Html'), new \FlexiPeeHP\ui\EmbedResponsiveHTML($invoicer));
$invoiceTabs->addTab(_('PDF'), new \FlexiPeeHP\ui\EmbedResponsivePDF($invoicer));

$oPage->addItem(new \Ease\TWB4\Container(new \Ease\TWB4\Panel('Doklad ' . new \Ease\Html\ATag($invoicer->getApiUrl(),
                                $invoicer->getDataValue('kod')) . ' ' . ($created ? 'byl' : 'nebyl') . ' vystaven',
                        $created ? 'success' : 'danger', $invoiceTabs)));
$oPage->draw();
