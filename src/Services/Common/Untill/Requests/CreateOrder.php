<?php

namespace Weboccult\EatcardCompanion\Services\Common\Untill\Requests;

use Weboccult\EatcardCompanion\Services\Core\Untill;

/**
 * @description Prepare Close Order API
 * @mixin Untill
 *
 * @author Darshit Hedpara
 */
trait CreateOrder
{
    /**
     * @param string|int $persons
     *
     * @return Untill
     */
    public function setPersons($persons): Untill
    {
        $this->xmlData = $this->replacer($this->xmlData, [
            'PERSON' => $persons,
        ]);

        return $this;
    }

    /**
     * @param $firstName
     *
     * @return Untill
     */
    public function setFirstName($firstName): Untill
    {
        $this->xmlData = $this->replacer($this->xmlData, [
            'FIRST_NAME' => $firstName,
        ]);

        return $this;
    }

    /**
     * @param array $items
     *
     * @return Untill
     */
    public function createOrder(array $items): Untill
    {
        $itemsXML = collect($items)->map(function ($currentItem, $key) {
            return $this->replacer($this->getTemplateXML('CreateOrder/Item.xml'), [
                'KEY'       => $key + 1,
                'UNTILL_ID' => $currentItem['untill_id'],
                'QUANTITY'  => $currentItem['quantity'],
            ]);
        })->join('');
        $this->build('CreateOrder/CreateOrder.xml', [
            'ITEMS'       => $itemsXML,
            'ITEMS_COUNT' => count($items),
        ])->setCredentials()->setTableNumber();

        return $this;
    }
}
