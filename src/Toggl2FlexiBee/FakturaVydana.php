<?php

namespace Toggl2FlexiBee;

use Ease\Shared;

/**
 * Description of FakturaVydana
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class FakturaVydana extends \FlexiPeeHP\FakturaVydana {

    /**
     * FlexiBee Invoice
     *
     * @param array $options Connection settings override
     */
    public function __construct($init = null, $options = []) {
        parent::__construct($init, $options = []);
        if (!array_key_exists('typDokl', $init)) {
            $this->setDataValue('typDokl',
                    self::code(Shared::instanced()->getConfigValue('FLEXIBEE_TYP_FAKTURY')));
        }
    }

    public static function formatMilliseconds($milliseconds) {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $milliseconds = $milliseconds % 1000;
        $seconds = $seconds % 60;
        $minutes = $minutes % 60;

        $format = '%u:%02u:%02u.%03u';
        $time = sprintf($format, $hours, $minutes, $seconds, $milliseconds);
        return rtrim($time, '0');
    }

    /**
     * 
     * @param array $timeEntries
     */
    public function takeItemsFromArray($timeEntries) {
        $itemsData = [];

        foreach ($timeEntries as $projectName => $projectTimeEntries) {
            $this->addArrayToBranch(['typPolozkyK' => 'typPolozky.text', 'nazev' => 'Projekt: ' . $projectName],
                    'polozkyFaktury'); // Task Title as Heading/TextRow

            foreach ($projectTimeEntries as $nazev => $duration) {

                $taskData = [
//                            'id' => 'ext:redmine:'.$rowId,
                    'typPolozkyK' => 'typPolozky.katalog',
                    'nazev' => self::formatMilliseconds($duration) . ' '. $nazev,
                    'mnozMj' => round($duration / 3600000, 3),
                    'cenik' => self::code(Shared::instanced()->getConfigValue('FLEXIBEE_CENIK'))];

                $this->addArrayToBranch($taskData, 'polozkyFaktury');
            }
        }
    }

}
