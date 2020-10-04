<?php

/**
 * ToggleToFlexiBee - Import Handler.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2020 Vitex Software
 */

namespace Toggl2FlexiBee;

/**
 * Description of Importer
 *
 * @author vitex
 */
class Importer extends FakturaVydana {

    public $since = null;
    public $until = null;

    /**
     *
     * @var int 
     */
    public $workspace = null;
    private $apiKey = null;
    private $toggl_client = null;
    private $reports_client = null;
    public $me = [];

    /**
     * 
     * @param string $init
     * @param array $options
     */
    public function __construct($init = null, $options = []) {
        $this->defaultUrlParams;
        parent::__construct($init, $options);
        $this->scopeToInterval(\Ease\Functions::cfg('TOGGLE_SCOPE'));
        $this->workspace = (int) \Ease\Functions::cfg('TOGGLE_WORKSPACE');
        $this->apiKey = \Ease\Functions::cfg('TOGGLE_TOKEN');
        $this->toggl_client = \AJT\Toggl\TogglClient::factory(['api_key' => $this->apiKey]);
        $this->me = $this->toggl_client->getCurrentUser(['with_related_data' => true])->toArray();
        $this->reports_client = \AJT\Toggl\ReportsClient::factory(['api_key' => $this->apiKey, 'debug' => false]);
    }

    public function scopeToInterval($scope) {
        switch ($scope) {
            case 'last_month':
                $this->since = new \DateTime("first day of last month");
                $this->until = new \DateTime("last day of last month");
                break;

            default:
                throw new \Ease\Exception('Unknown scope ' . $scope);
                break;
        }
    }

    /**
     * 
     * @return \Toggl2FlexiBee\FakturaVydana
     */
    public function import() {
        $this->logBanner('Import Initiated. Workspace: ' . $this->workspace . ' From: ' . $this->since->format('c') . ' To: ' . $this->until->format('c'));

        $detailsData = $this->reports_client->Details([
                    'since' => $this->since->format('Y-m-d'),
                    'until' => $this->until->format('Y-m-d'),
                    'display_hours' => 'decimal',
                    'workspace_id' => $this->workspace,
                    'user_agent' => \Ease\Functions::cfg('APP_NAME'),
                    'user_ids' => $this->me['data']['id'],
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

        $this->setData([
            'typDokl' => \FlexiPeeHP\FlexiBeeRO::code(empty(\Ease\Functions::cfg('FLEXIBEE_TYP_FAKTURY')) ? 'FAKTURA' : \Ease\Functions::cfg('FLEXIBEE_TYP_FAKTURY')),
            'firma' => \FlexiPeeHP\FlexiBeeRO::code(\Ease\Functions::cfg('FLEXIBEE_CUSTOMER')),
            'popis' => sprintf(_('Work from %s to %s'), $this->since->format('Y-m-d'), $this->until->format('Y-m-d')),
            'poznam' => 'Toggl Workspace: ' . $this->workspace
        ]);

        $this->takeItemsFromArray($invoiceItems);
        $created = $this->sync();

        $this->addStatusMessage($this->getDataValue('kod') . ': ' . $this->getApiUrl(), $created ? 'success' : 'danger');
        return $created;
    }

}
