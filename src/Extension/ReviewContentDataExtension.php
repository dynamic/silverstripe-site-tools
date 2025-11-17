<?php

namespace Dynamic\SiteTools\Extension;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Core\Extension;

/**
 * Class ReviewContentDataExtension
 *
 * @property SiteConfig|ReviewContentDataExtension $owner
 * @property bool $ReviewContent
 */
class ReviewContentDataExtension extends Extension
{
    /**
     * @var array
     */
    private static $db = [
        'ReviewContent' => 'Boolean',
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Advanced',
            CheckboxField::create('ReviewContent')
                ->setTitle('Show Review Content')
                ->setDescription('Display placeholder text and images. Useful during website build.')
        );
    }
}
