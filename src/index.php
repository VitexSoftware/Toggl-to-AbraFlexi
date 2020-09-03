<?php

namespace Toggl2FlexiBee;

use AJT\Toggl\TogglClient;
use DateTime;
use Ease\Html\InputDateTag;
use Ease\Html\SelectTag;
use Ease\Locale;
use Ease\Shared;
use Ease\TWB4\Container;
use Ease\TWB4\Form;
use Ease\TWB4\SubmitButton;
use Ease\TWB4\WebPage;

require_once '../vendor/autoload.php';

Shared::instanced()->loadConfig('../config.json', true);

new Locale('cs_CZ', '../i18n', 'toggl2flexibee');

session_start();

$oPage = new WebPage('Toggle2FlexiBee');

$toggl_client = TogglClient::factory(array('api_key' => Shared::instanced()->getConfigValue('TOGGLE_TOKEN'),
            'debug' => false));

$cstmrForm = new Form(['name' => 'cstmr', 'action' => 'invoicer.php']);


//$cstmrForm->addInput(new SearchBox('firma[0]', constant('FLEXIBEE_CUSTOMER'),
//        [
//        'data-remote-list' => 'firmy.php',
//        'data-list-highlight' => 'true',
//        'data-list-value-completion' => 'true'
//        ]), _('Default Customer'), _('COMPANY_CODE'),
//    _('Use chosen company as customer if not overrided'));
//

$cstmrForm->addInput(new InputDateTag('since',
                new DateTime("first day of last month")), _('From'));
$cstmrForm->addInput(new InputDateTag('until',
                new DateTime("last day of last month")), _('To'));


foreach ($toggl_client->GetWorkspaces() as $workspaceInfo) {
    $wssel[$workspaceInfo['id']] = $workspaceInfo['name'];
}

$cstmrForm->addInput(new SelectTag('workspace', $wssel),
        _('Workspace'));

//$cstmrForm->addInput(new \FlexiPeeHP\ui\RecordTypeSelect(
//        new \FlexiPeeHP\FlexiBeeRO(null, ['evidence' => 'typ-faktury-vydane']),
//        'kod'), _('Create invoice of type'));

$cstmrForm->addItem(new SubmitButton(_('Get invoice'), 'success'));

$oPage->addItem(new Container($cstmrForm));

//$oPage->addItem(new ui\HealthCehck());

$oPage->draw();
