<?php

namespace Toggl2FlexiBee;

use Ease\Shared;

/**
 * Description of FakturaVydana
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class FakturaVydana extends \FlexiPeeHP\FakturaVydana
{

    /**
     * FlexiBee Invoice
     *
     * @param array $options Connection settings override
     */
    public function __construct($init = null, $options = [])
    {
        parent::__construct($init, $options = []);
        if (!array_key_exists('typDokl', $init)) {
            $this->setDataValue('typDokl',
                self::code(Shared::instanced()->getConfigValue('FLEXIBEE_TYP_FAKTURY')));
        }
    }

    /**
     * 
     * @param array $timeEntries
     */
    public function takeItemsFromArray($timeEntries)
    {
        $itemsData = [];

        foreach ($timeEntries as $projectName => $projectTimeEntries) {
            $this->addArrayToBranch(['typPolozkyK' => 'typPolozky.text', 'nazev' => 'Projekt: '.$projectName],
                'polozkyFaktury'); // Task Title as Heading/TextRow

            foreach ($projectTimeEntries as $nazev => $duration) {

                $taskData = [
//                            'id' => 'ext:redmine:'.$rowId,
                    'typPolozkyK' => 'typPolozky.katalog',
                    'nazev' => $nazev,
                    'mnozMj' => round(($duration / 1000) / 3600, 3) ,
                    'cenik' => self::code(Shared::instanced()->getConfigValue('FLEXIBEE_CENIK'))];

                $this->addArrayToBranch($taskData, 'polozkyFaktury');
            }
        }
    }
}
