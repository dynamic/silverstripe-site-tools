<?php

namespace Dynamic\SiteTools\Extension;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Extension;

/**
 * Class ElementListLayoutExtension
 *
 * @property ElementListLayoutExtension $owner
 * @property string $Columns
 */
class ElementListLayoutExtension extends Extension
{
    /**
     * @var array
     */
    private static $db = [
        'Columns' => 'Enum("3,4,6,8,9,10,12")',
    ];

    /**
     * @var array
     */
    private static $defaults = [
        'Columns' => 12,
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $cols = [
            '3' => 'One Quarter',
            '4' => 'One Third',
            '6' => 'Half',
            '8' => 'Two Thirds',
            '9' => 'Three Quarters',
            '10' => '10 Columns Centered',
            '12' => 'Full'
        ];

        $fields->addFieldToTab(
            'Root.Layout',
            DropdownField::create(
                'Columns',
                'Columns',
                $cols
            )->setEmptyString('select')
        );
    }
}
