<?php

namespace Dynamic\SiteTools\Model;

use Sheadawson\Linkable\Forms\LinkField;
use Sheadawson\Linkable\Models\Link;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Security;

class HeaderImage extends DataObject
{
    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'HTMLText',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Page' => SiteTree::class,
        'HeaderLink' => Link::class,
        'Image' => Image::class,
    ];

    /**
     * @var string[]
     */
    private static $cascade_duplicates = [
        'HeaderLink',
    ];

    /**
     * @var array
     */
    private static $owns = [
        'Image',
    ];

    /**
     * @var string
     */
    private static $table_name = 'HeaderImage';

    /**
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName([
                'HeaderLinkID',
                'Image',
                'PageID',
            ]);

            $fields->insertAfter(
                'Content',
                LinkField::create('HeaderLinkID', 'Link')
            );

            $image_field = UploadField::create('Image', 'Header Image')
                ->setFolderName('Uploads/HeaderImages')
                ->setIsMultiUpload(false);
            $image_field->getValidator()->allowedExtensions = [
                'jpg',
                'jpeg',
                'gif',
                'png',
            ];

            $fields->insertBefore(
                'Title',
                $image_field
            );
        });

        return parent::getCMSFields();
    }

    /**
     * @param null $member
     * @return bool
     */
    public function canView($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        if ($this->Page()) {
            return $this->Page()->canEdit($member);
        }

        return parent::canEdit($member);
    }

    /**
     * @param null $member
     * @param array $context
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        if ($this->Page()) {
            return $this->Page()->canEdit($member);
        }

        return parent::canEdit($member);
    }

    /**
     * @param null $member
     * @return bool
     */
    public function canEdit($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        if ($this->Page()) {
            return $this->Page()->canEdit($member);
        }

        return parent::canEdit($member);
    }

    /**
     * @param null $member
     * @return bool
     */
    public function canDelete($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        if ($this->Page()) {
            return $this->Page()->canDelete($member);
        }

        return parent::canDelete($member);
    }
}
