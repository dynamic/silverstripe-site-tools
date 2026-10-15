<?php

namespace Dynamic\SiteTools\Extension;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\Deprecation;

/**
 * Class ElementListLayoutExtension
 *
 * @deprecated 6.1.0 Will be removed without equivalent functionality to replace it in a future major release.
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
        // SCOPE_GLOBAL is deliberate: this is an extension hook, so a method-scoped notice would be attributed
        // to the supported-module caller (DataObject::getCMSFields()) and hidden by default. Global scope keeps
        // it visible to the installing project, which is the only code able to stop using this extension.
        Deprecation::notice(
            '6.1.0',
            'ElementListLayoutExtension is deprecated. Will be removed without equivalent functionality to '
            . 'replace it in a future major release.',
            Deprecation::SCOPE_GLOBAL
        );

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
