<?php

namespace Toggl2FlexiBee;

use Ease\Shared;

/**
 * ToggleToFlexiBee - Invoice Handler.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2020 Vitex Software
 */
class FakturaVydana extends \FlexiPeeHP\FakturaVydana {

    /**
     * FlexiBee Invoice
     *
     * @param array $options Connection settings override
     */
    public function __construct($init = null, $options = []) {
        parent::__construct($init, $options);
        if (!array_key_exists('typDokl', $options)) {
            $this->setDataValue('typDokl', self::code(\Ease\Functions::cfg('FLEXIBEE_TYP_FAKTURY')));
        }
    }

    /**
     * 
     * @param type $milliseconds
     * @return type
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
            $this->addArrayToBranch(['typPolozkyK' => 'typPolozky.text', 'nazev' => 'Projekt: ' . $projectName],
                    'polozkyFaktury'); // Task Title as Heading/TextRow

            foreach ($projectTimeEntries as $nazev => $duration) {

                $taskData = [
//                            'id' => 'ext:redmine:'.$rowId,
                    'typPolozkyK' => 'typPolozky.katalog',
                    'nazev' => self::formatMilliseconds($duration) . ' ' . $nazev,
                    'mnozMj' => round($duration / 3600000, 3),
                    'cenik' => self::code(Shared::instanced()->getConfigValue('FLEXIBEE_CENIK'))];

                $this->addArrayToBranch($taskData, 'polozkyFaktury');
            }
        }
    }

}
