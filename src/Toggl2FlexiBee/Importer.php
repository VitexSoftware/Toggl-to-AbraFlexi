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

    /**
     *
     * @var \DateTime 
     */
    public $since = null;

    /**
     *
     * @var \DateTime 
     */
    public $until = null;

    /**
     *
     * @var array<int> 
     */
    public $workspaces = null;

    /**
     *
     * @var string 
     */
    private $apiKey = null;

    /**
     * Toggle client nest
     * @var \AJT\Toggl\TogglClient 
     */
    private $toggl_client = null;

    /**
     *
     * @var \AJT\Toggl\ReportsClient 
     */
    private $reports_client = null;

    /**
     *
     * @var array
     */
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
        $this->apiKey = \Ease\Functions::cfg('TOGGLE_TOKEN');
        $this->toggl_client = \AJT\Toggl\TogglClient::factory(['api_key' => $this->apiKey]);
        $this->workspaces = $this->getWorkSpaces();
        $this->me = $this->toggl_client->getCurrentUser(['with_related_data' => true])->toArray();
        $this->reports_client = \AJT\Toggl\ReportsClient::factory(['api_key' => $this->apiKey, 'debug' => false]);
    }

    /**
     * Use configured workspaces or obtain all user's workspaces
     * 
     * @return array
     */
    public function getWorkSpaces() {
        $env = \Ease\Functions::cfg('TOGGLE_WORKSPACE');
        $workspaces = [];
        if (empty($env)) {
            $workspacesRaw = $this->toggl_client->getWorkspaces(array());
            foreach ($workspacesRaw as $workspace) {
                $workspaces[$workspace['name']] = intval($workspace['id']);
            }
        } else {
            $workspaces = strstr($env, ',') ? explode(',', $env) : [intval($env)];
        }
        return $workspaces;
    }

    /**
     * Prepare processing interval
     * 
     * @param string $scope 
     * @throws \Ease\Exception
     */
    public function scopeToInterval($scope) {
        switch ($scope) {
            case 'last_month':
                $this->since = new \DateTime("first day of last month");
                $this->until = new \DateTime("last day of last month");
                break;

            case 'previous_month':
                $this->since = new \DateTime("first day of -2 month");
                $this->until = new \DateTime("last day of -2 month");
                break;

            case 'two_months_ago':
                $this->since = new \DateTime("first day of -3 month");
                $this->until = new \DateTime("last day of -3 month");
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
        $this->logBanner('Import Initiated. From: ' . $this->since->format('c') . ' To: ' . $this->until->format('c'));


        $invoiceItems = [];
        $projects = [];
        $durations = [];

        foreach ($this->workspaces as $wsname => $workspace) {
            $this->addStatusMessage('Workspace: ' . (is_string($wsname) ? $wsname . ' ' : '' ) . $workspace, 'info');
            $detailsData = $this->reports_client->Details([
                        'since' => $this->since->format('Y-m-d'),
                        'until' => $this->until->format('Y-m-d'),
                        'display_hours' => 'decimal',
                        'workspace_id' => $workspace,
                        'user_agent' => \Ease\Functions::cfg('APP_NAME'),
                        'user_ids' => $this->me['data']['id'],
                    ])->toArray();



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
        }
        $this->takeItemsFromArray($invoiceItems);
        $this->setData([
            'typDokl' => \FlexiPeeHP\FlexiBeeRO::code(empty(\Ease\Functions::cfg('FLEXIBEE_TYP_FAKTURY')) ? 'FAKTURA' : \Ease\Functions::cfg('FLEXIBEE_TYP_FAKTURY')),
            'firma' => \FlexiPeeHP\FlexiBeeRO::code(\Ease\Functions::cfg('FLEXIBEE_CUSTOMER')),
            'popis' => sprintf(_('Work from %s to %s'), $this->since->format('Y-m-d'), $this->until->format('Y-m-d')),
            'poznam' => 'Toggl Workspace: ' . implode(',', $this->workspaces)
        ]);

        $created = $this->sync();
        \FlexiPeeHP\Priloha::addAttachment($this, sprintf(_('tasks_timesheet_%s_%s.csv'), $this->since->format('Y-m-d'), $this->until->format('Y-m-d')), self::csvReport($invoiceItems), 'text/csv');
        \FlexiPeeHP\Priloha::addAttachment($this, sprintf(_('projects_timesheet_%s_%s.csv'), $this->since->format('Y-m-d'), $this->until->format('Y-m-d')), self::cvsReportPerProject($invoiceItems), 'text/csv');

        $this->addStatusMessage($this->getDataValue('kod') . ': ' . $this->getApiUrl(), $created ? 'success' : 'danger');
        return $created;
    }

    /**
     * Prepare data for CSV Report
     * 
     * @param array $timeEntries 
     * 
     * @return array data for CSV export
     */
    public static function csvData($timeEntries) {
        $columns = ['project', 'name', 'hours', 'duration'];
        $reportData[] = array_combine($columns, $columns);
        foreach ($timeEntries as $projectName => $projectTimeEntries) {
            foreach ($projectTimeEntries as $nazev => $duration) {
                $reportData[] = [
                    'project' => $projectName,
                    'name' => $nazev,
                    'hours' => round($duration / 3600000, 3),
                    'duration' => self::formatMilliseconds($duration),
                ];
            }
        }
        return $reportData;
    }

    /**
     * Convert CSV data to CSV multiline string
     * 
     * @param array $timeEntries
     * 
     * @return string
     */
    public static function csvReport($timeEntries) {
        $csvData = self::csvData($timeEntries);
        $csvRows[] = implode(';', (current($csvData)));
        foreach ($csvData as $dataRow) {
            $csvRows[] = implode(';', $dataRow);
        }
        return implode("\n", $csvRows);
    }

    /**
     * CSV Report with sums by project
     * 
     * @param  array $timeEntries
     * 
     * @return string
     */
    public static function cvsReportPerProject($timeEntries) {
        $columns = ['project', 'hours', 'duration'];
        $reportData[] = array_combine($columns, $columns);
        foreach ($timeEntries as $projectName => $projectTimeEntries) {
            $duration = array_sum($projectTimeEntries);
            $reportData[] = [
                'project' => $projectName,
                'hours' => round($duration / 3600000, 3),
                'duration' => self::formatMilliseconds($duration),
            ];
        }
        foreach ($reportData as $dataRow) {
            $csvRows[] = implode(';', $dataRow);
        }
        return implode("\n", $csvRows);
    }

}
