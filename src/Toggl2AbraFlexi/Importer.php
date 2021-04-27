<?php

/**
 * ToggleToAbraFlexi - Import Handler.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2020 Vitex Software
 */

namespace Toggl2AbraFlexi;

use AbraFlexi\Priloha;
use AbraFlexi\RO;
use DateTime;
use Ease\Exception;
use Ease\Functions;

/**
 * Description of Importer
 *
 * @author vitex
 */
class Importer extends FakturaVydana {

    /**
     *
     * @var DateTime 
     */
    public $since = null;

    /**
     *
     * @var DateTime 
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
        $this->scopeToInterval(Functions::cfg('TOGGLE_SCOPE'));
        $this->apiKey = Functions::cfg('TOGGLE_TOKEN');
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
        $env = Functions::cfg('TOGGLE_WORKSPACE');
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
     * @throws Exception
     */
    public function scopeToInterval($scope) {
        switch ($scope) {
            case 'current_month':
                $this->since = new DateTime("first day of this month");
                $this->until = new DateTime();
                break;
            case 'last_month':
                $this->since = new DateTime("first day of last month");
                $this->until = new DateTime("last day of last month");
                break;

            case 'last_two_months':
                $this->since = (new DateTime("first day of last month"))->modify('-1 month');
                $this->until = (new DateTime("last day of last month"));
                break;

            case 'previous_month':
                $this->since = new DateTime("first day of -2 month");
                $this->until = new DateTime("last day of -2 month");
                break;

            case 'two_months_ago':
                $this->since = new DateTime("first day of -3 month");
                $this->until = new DateTime("last day of -3 month");
                break;

            case 'this_year':
                $this->since = new DateTime('first day of January ' . date('Y'));
                $this->until = new DateTime("last day of December" . date('Y'));
                break;

            case 'January':  //1
            case 'February': //2
            case 'March':    //3
            case 'April':    //4
            case 'May':      //5
            case 'June':     //6
            case 'July':     //7
            case 'August':   //8
            case 'September'://9
            case 'October':  //10
            case 'November': //11
            case 'December': //12
                $this->since = new DateTime('first day of ' . $scope . ' ' . date('Y'));
                $this->until = new DateTime('last day of ' . $scope . ' ' . date('Y'));
                break;

            default:
                throw new Exception('Unknown scope ' . $scope);
                break;
        }
        $this->since = $this->since->setTime(0, 0);
        $this->until = $this->until->setTime(0, 0);
    }

    /**
     * 
     * @return FakturaVydana
     */
    public function import() {
        $this->logBanner('Import Initiated. From: ' . $this->since->format('c') . ' To: ' . $this->until->format('c'));

        $invoiceItems = [];
        $projects = [];
        $durations = [];

        foreach ($this->workspaces as $wsname => $workspace) {
            $this->addStatusMessage('Workspace: ' . (is_string($wsname) ? $wsname . ' ' : '' ) . $workspace, 'info');
            $detailsData = $this->getAllDetailPages($workspace);

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

        $cc = empty(\Ease\Functions::cfg('ABRAFLEXI_CC')) ? '' : "\n".'cc:' . \Ease\Functions::cfg('ABRAFLEXI_CC');
        $this->setData([
            'typDokl' => RO::code(empty(Functions::cfg('ABRAFLEXI_TYP_FAKTURY')) ? 'FAKTURA' : Functions::cfg('ABRAFLEXI_TYP_FAKTURY')),
            'firma' => RO::code(Functions::cfg('ABRAFLEXI_CUSTOMER')),
            'popis' => sprintf(_('Work from %s to %s'), $this->since->format('Y-m-d'), $this->until->format('Y-m-d')),
            'poznam' => 'Toggl Workspace: ' . implode(',', $this->workspaces) . $cc
        ]);

        $created = $this->sync();

        $fromto = $this->since->format('Y-m-d') . '_' . $this->until->format('Y-m-d');
        Priloha::addAttachment($this, sprintf(_('tasks_timesheet_%s.csv'), $fromto), Reporter::csvReport($invoiceItems), 'text/csv');
        Priloha::addAttachment($this, sprintf(_('projects_timesheet_%s.csv'), $fromto), Reporter::cvsReportPerProject($invoiceItems), 'text/csv');

        Priloha::addAttachment($this, sprintf(_('tasks_timesheet_%s.xlsx'), $fromto), Reporter::xlsReport($invoiceItems, $fromto), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Priloha::addAttachment($this, sprintf(_('projects_timesheet_%s.xlsx'), $fromto), Reporter::xlsReportPerProject($invoiceItems, $fromto), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->addStatusMessage($this->getDataValue('kod') . ': ' . $this->getApiUrl(), $created ? 'success' : 'danger');
        return $created;
    }

    /**
     * One page of Report
     * 
     * @param int $workspace
     * @param int $pageno
     * 
     * @return array
     */
    public function getDetailsPage($workspace, $pageno = 1) {
        return $this->reports_client->Details([
                    'page' => $pageno,
                    'since' => $this->since->format('Y-m-d'),
                    'until' => $this->until->format('Y-m-d'),
                    'display_hours' => 'decimal',
                    'workspace_id' => $workspace,
                    'user_agent' => Functions::cfg('APP_NAME'),
                    'user_ids' => $this->me['data']['id'],
                ])->toArray();
    }

    /**
     * Get full set of results 
     * 
     * @param string $workspace
     * 
     * @return array
     */
    public function getAllDetailPages($workspace) {
        $result = $this->getDetailsPage($workspace);
        $pages = ceil($result['total_count'] / $result['per_page']);
        $this->addStatusMessage(sprintf(_('reading page %s of %s'), 1, $pages), 'debug');
        $records = [];
        foreach ($result['data'] as $record) {
            $records[$record['start'] . '-' . $record['end']] = $record;
        }
        $result['data'] = $records;

        if ($pages > 1) {
            $page = 2;
            while ($page <= $pages) {
                sleep(1);
                $this->addStatusMessage(sprintf(_('reading page %s of %s'), $page, $pages), 'debug');
                $nextpage = $this->getDetailsPage($workspace, $page++);
                foreach ($nextpage['data'] as $record) {
                    $result['data'][$record['start'] . '-' . $record['end']] = $record;
                }
            };
        }
        return $result;
    }

    /**
     * 
     * @return FakturaVydana
     */
    public function report() {
        $this->logBanner('Report Initiated. From: ' . $this->since->format('c') . ' To: ' . $this->until->format('c'));
        $entries = 0;
        $invoiceItems = [];
        $projects = [];
        $durations = [];

        foreach ($this->workspaces as $wsname => $workspace) {
            $this->addStatusMessage('Workspace: ' . (is_string($wsname) ? $wsname . ' ' : '' ) . $workspace, 'info');

            $detailsData = $this->getAllDetailPages($workspace);

            foreach ($detailsData['data'] as $detail) {
                $entries++;
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
//            'popis' => sprintf(_('Work from %s to %s'), $this->since->format('Y-m-d'), $this->until->format('Y-m-d')),
//            'poznam' => 'Toggl Workspace: ' . implode(',', $this->workspaces)

        $this->addStatusMessage($entries . ' entries processed');

        $fromto = $this->since->format('Y-m-d') . '_' . $this->until->format('Y-m-d');
        $saveto = \Ease\Functions::cfg('REPORTS_DIR');

        $tasksCsv = $saveto . sprintf(_('tasks_timesheet_%s.csv'), $fromto);
        $this->addStatusMessage($tasksCsv, file_put_contents($tasksCsv, Reporter::csvReport($invoiceItems)) ? 'success' : 'error');

        $projectsCsv = $saveto . sprintf(_('projects_timesheet_%s.csv'), $fromto);
        $this->addStatusMessage($projectsCsv, file_put_contents($projectsCsv, Reporter::cvsReportPerProject($invoiceItems)) ? 'success' : 'error');

        $tasksXLS = $saveto . sprintf(_('tasks_timesheet_%s.xlsx'), $fromto);
        $this->addStatusMessage($tasksXLS, file_put_contents($tasksXLS, Reporter::xlsReport($invoiceItems, $fromto)) ? 'success' : 'error');

        $projectsXLS = $saveto . sprintf(_('projects_timesheet_%s.xlsx'), $fromto);
        $this->addStatusMessage($projectsXLS, file_put_contents($projectsXLS, Reporter::xlsReportPerProject($invoiceItems, $fromto)) ? 'success' : 'error');

        return;
    }

}
