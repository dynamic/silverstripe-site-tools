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
     * applied to, not on SiteTree itself, so each class that carries the relation has to be asked
     * separately. Duplicates are removed by record ID rather than by skipping page classes as
     * "already covered": a class's own DataList does reach the classes below it, so a descendant
     * is usually found twice, but that has to be checked rather than assumed.
     *
     * @return SiteTree[]
     */
    private function getOwnerPages(): array
    {
        if (!$this->isInDB()) {
            return [];
        }

        $schema = DataObject::getSchema();
        // Keyed by record ID: the same page can be reached through its own class, through a class
        // above it, and on both stages, and is still a single owner.
        $owners = [];

        foreach (ClassInfo::subclassesFor(SiteTree::class) as $class) {
            if ($schema->hasOneComponent($class, 'HeaderImage') !== self::class) {
                continue;
            }

            if (!$this->isQueryablePageClass($class)) {
                continue;
            }

            foreach ($class::get()->filter('HeaderImageID', $this->ID) as $owner) {
                // Keyed by record ID: the same page can be reached through its own class and
                // through a class above it, and is still a single owner.
                $owners[(int)$owner->ID] = $owner;
            }
        }

        return array_values($owners);
    }

    /**
     * Whether the owner walk may ask this page class for its records directly.
     *
     * SiteTree::get() resolves its DataObject through the Injector, which cannot do so for an
     * abstract class, so a page class carrying the HeaderImage relation but not instantiable has
     * to be skipped - and must not then be taken as having been searched on behalf of the page
     * classes below it.
     *
     * Protected rather than private so the skip itself can be driven from a test. An abstract
     * DataObject class cannot exist anywhere in the class manifest while the test database is
     * built: TableBuilder::buildTables() runs `new $dataClass([], DataObject::CREATE_SINGLETON)`
     * over every DataObject class in the manifest before it applies the TestOnly exclusion, so
     * any abstract page class aborts the whole suite with "Cannot instantiate abstract class".
     * The stand-in that overrides this is
     * \Dynamic\SiteTools\Test\TestOnly\UnqueryableOwnerClassHeaderImage.
     *
     * @param string $class A SiteTree subclass the walk is considering.
     * @return bool
     */
    protected function isQueryablePageClass(string $class): bool
    {
        // ::get() resolves its DataObject through the Injector, which an abstract class
        // cannot do.
        return (new ReflectionClass($class))->isInstantiable();
    }

    /**
     * Grant the permission only when every owning page grants it.
     *
     * One record can be pointed at by more than one page - the add-existing autocompleter on the
     * HeaderImage field will link any image to a page without checking who else already owns it -
     * so granting on any single owner would let a member who can edit one open page reach the
     * image shown on a page they cannot.
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
                if (!$owner->$method($member)) {
                    return false;
                }
            }

            return true;
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
