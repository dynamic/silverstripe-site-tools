<?php

namespace Dynamic\SiteTools\Extension;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Extension;

/**
 * Class ContactDataExtension
 *
 * @property ContactDataExtension $owner
 * @property string $Phone
 * @property string $Phone2
 * @property string $Fax
 * @property string $Email
 */
class ContactDataExtension extends Extension
{
    /**
     * @var array
     */
    private static $db = [
        'Phone' => 'Varchar(20)',
        'Phone2' => 'Varchar(20)',
        'Fax' => 'Varchar(20)',
        'Email' => 'Varchar(100)',
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner->config()->contact_tab_name) {
            $tab_name = $this->owner->config()->contact_tab_name;
        } else {
            $tab_name = Config::inst()->get(ContactDataExtension::class, 'contact_tab_name');
        }

        $fields->addFieldsToTab(
            'Root.' . $tab_name,
            [
                TextField::create('Phone', 'Primary phone'),
                TextField::create('Phone2', 'Secondary phone'),
                TextField::create('Fax'),
                EmailField::create('Email'),
            ]
        );
    }
}
