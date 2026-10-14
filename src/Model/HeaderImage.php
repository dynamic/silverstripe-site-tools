<?php

namespace Dynamic\SiteTools\Model;

use ReflectionClass;
use SilverStripe\Assets\Image;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\AssetAdmin\Forms\UploadField;

/**
 * Class \Dynamic\SiteTools\Model\HeaderImage
 *
 * @property string $Title
 * @property string $Content
 * @property int $PageID
 * @property int $HeaderLinkID
 * @property int $ImageID
 * @method SiteTree Page()
 * @method Link HeaderLink()
 * @method Image Image()
 */
class HeaderImage extends DataObject
{
    /**
     * @var array
     * @config
     */
    private static $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'HTMLText',
    ];

    /**
     * @var array
     * @config
     */
    private static $has_one = [
        'Page' => SiteTree::class,
        'HeaderLink' => Link::class,
        'Image' => Image::class,
    ];

    /**
     * @var string[]
     * @config
     */
    private static $cascade_duplicates = [
        'HeaderLink',
    ];

    /**
     * @var array
     * @config
     */
    private static $owns = [
        'Image',
    ];

    /**
     * @var array
     * @config
     */
    private static $searchable_fields = [
        'Title',
    ];

    /**
     * @var array
     * @config
     */
    private static $summary_fields = [
        'Image.CMSThumbnail' => 'Image',
        'Title',
    ];

    /**
     * @var string
     * @config
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

            $fields->insertBefore(
                'Content',
                LinkField::create('HeaderLink', 'Link', $this)
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

            $fields->dataFieldByName('Content')
                ->setRows(8);
        });

        return parent::getCMSFields();
    }

    /**
     * The pages whose own HeaderImage relation points at this record, which is the only way a
     * header image is ever owned.
     *
     * The HeaderImageID foreign key exists on the tables of the classes HeaderImageExtension is
     * applied to, not on SiteTree itself, so each of those classes has to be queried separately.
     *
     * @return SiteTree[]
     */
    private function getOwnerPages(): array
    {
        if (!$this->isInDB()) {
            return [];
        }

        $schema = DataObject::getSchema();
        $owners = [];

        foreach (ClassInfo::subclassesFor(SiteTree::class) as $class) {
            if ($schema->hasOneComponent($class, 'HeaderImage') !== self::class) {
                continue;
            }

            // Only the class the extension is applied to carries the foreign key; a subclass of
            // it inherits the relation and would find the same owners a second time.
            $parent = get_parent_class($class);
            if ($parent && $schema->hasOneComponent($parent, 'HeaderImage') === self::class) {
                continue;
            }

            if (!(new ReflectionClass($class))->isInstantiable()) {
                continue;
            }

            $owners = array_merge(
                $owners,
                $class::get()->filter('HeaderImageID', $this->ID)->toArray()
            );
        }

        return $owners;
    }

    /**
     * Grant the permission when any owning page grants it.
     *
     * Without an owner - a record the add form has not written yet, or one nothing points at -
     * fall back to whether the member may edit pages at all. DataObject::can*() is deliberately
     * not used there: it is ADMIN-only and would lock content editors out of adding an image.
     *
     * @param string $method The SiteTree permission method to ask the owning page for.
     * @param Member|null $member
     * @return bool
     */
    private function canOwner(string $method, $member = null): bool
    {
        $owners = $this->getOwnerPages();

        if ($owners) {
            foreach ($owners as $owner) {
                if ($owner->$method($member)) {
                    return true;
                }
            }

            return false;
        }

        return (bool)SiteConfig::current_site_config()->canEditPages($member);
    }

    /**
     * @param Member|null $member
     * @return bool
     */
    public function canView($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        return $this->canOwner('canEdit', $member);
    }

    /**
     * @param Member|null $member
     * @param array $context
     * @return bool
     */
    public function canCreate($member = null, $context = [])
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        return $this->canOwner('canEdit', $member);
    }

    /**
     * @param Member|null $member
     * @return bool
     */
    public function canEdit($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        return $this->canOwner('canEdit', $member);
    }

    /**
     * @param Member|null $member
     * @return bool
     */
    public function canDelete($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }

        return $this->canOwner('canDelete', $member);
    }
}
