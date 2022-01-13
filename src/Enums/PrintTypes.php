<?php

namespace Weboccult\EatcardCompanion\Enums;

/**
 * Class PaymentTypes.
 */
class PrintTypes extends BaseEnum
{
    public const DEFAULT = 'DEFAULT'; //add
    public const MAIN_KITCHEN_LABEL = 'MAIN_KITCHEN_LABEL'; //Main+Kitchen+Label
    public const MAIN = 'MAIN';
    public const KITCHEN_LABEL = 'KITCHEN_LABEL'; //reservation order item
    public const KITCHEN = 'KITCHEN';
    public const LABEL = 'LABEL';
    public const PROFORMA = 'PROFORMA';
    public const REVENUE = 'REVENUE';
}
