<?php

namespace Toggl2AbraFlexi;

use Ease\Shared;

/**
 * ToggleToAbraFlexi - Invoice Handler.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2020 Vitex Software
 */
class FakturaVydana extends \AbraFlexi\FakturaVydana {

    /**
     * AbraFlexi Invoice
     *
     * @param array $options Connection settings override
     */
    public function __construct($init = null, $options = []) {
        parent::__construct($init, $options);
        if (!array_key_exists('typDokl', $options)) {
            $this->setDataValue('typDokl', self::code(\Ease\Functions::cfg('ABRAFLEXI_TYP_FAKTURY')));
        }
    }

    /**
     * Format milliseconds time
     * 
     * @param double $milliseconds
     * 
     * @return string
     */
    public static function formatMilliseconds($milliseconds) {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $ms = $milliseconds % 1000;
        $secs = $seconds % 60;
        $mins = $minutes % 60;

        $format = '%u:%02u:%02u.%03u';
        $time = sprintf($format, $hours, $mins, $secs, $ms);
        return rtrim($time, '0');
    }

    /**
     * 
     * @param array $timeEntries
     */
    public function takeItemsFromArray($timeEntries) {
        foreach ($timeEntries as $projectName => $projectTimeEntries) {
            $projectSum = _('Project') . ': ' . $projectName . ' ' . _('Duration') . ': ' . round(array_sum($projectTimeEntries) / 3600000, 3) . ' h';
            $this->addArrayToBranch(['typPolozkyK' => 'typPolozky.text', 'nazev' => $projectSum], 'polozkyFaktury'); // Task Title as Heading/TextRow
            foreach ($projectTimeEntries as $nazev => $duration) {
                $taskData = [
//                            'id' => 'ext:redmine:'.$rowId,
                    'typPolozkyK' => 'typPolozky.katalog',
                    'nazev' => self::formatMilliseconds($duration) . ' ' . $nazev,
                    'mnozMj' => round($duration / 3600000, 3),
                    'cenik' => self::code(Shared::instanced()->getConfigValue('ABRAFLEXI_CENIK'))];

                $this->addArrayToBranch($taskData, 'polozkyFaktury');
            }
            $this->addStatusMessage($projectSum, count($projectTimeEntries) ? 'success' : 'warning');
        }
    }

}
