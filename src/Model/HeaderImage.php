<?php

namespace Dynamic\SiteTools\Model;

use ReflectionClass;
use SilverStripe\Assets\Image;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Versioned\Versioned;
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
        'HeaderLink',
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
     * header image is ever owned: nothing writes the Page has_one this class used to check.
     *
     * HeaderImageExtension puts the HeaderImageID foreign key on the tables of the page classes it
     * is applied to rather than on SiteTree, so every class carrying the relation has to be asked
     * for its own records - SiteTree::get() cannot filter on a column it does not have. Both
     * stages are asked, because a page that still shows this image on Live owns it just as much as
     * one whose draft does, and the permission check must not be escapable by clearing the draft.
     *
     * The result is deliberately not cached on the record: a page can gain or lose its pointer
     * during the same request that then asks who may edit the image, and a stale owner list would
     * be a stale permission answer in either direction.
     *
     * Only SiteTree subclasses are searched. HeaderImageExtension applied to any other class still
     * adds the relation, but records of that class are never counted as owners and do not take
     * part in these permission checks.
     *
     * @return SiteTree[]
     */
    private function getOwnerPages(): array
    {
        if (!$this->isInDB()) {
            return [];
        }

        $schema = DataObject::getSchema();
        // Keyed by record ID: the same page is reachable on both stages, and is still a single
        // owner.
        $owners = [];

        foreach (ClassInfo::subclassesFor(SiteTree::class) as $class) {
            if ($schema->hasOneComponent($class, 'HeaderImage') !== self::class) {
                continue;
            }

            // ::get() resolves its DataObject through the Injector, which cannot do so for an
            // abstract page class.
            if (!(new ReflectionClass($class))->isInstantiable()) {
                continue;
            }

            // A class list is scoped to its own class and its descendants, so the class that
            // carries the column already covers every page below it. Asking the descendants as
            // well would be one pair of queries per page type in a site that has dozens of them,
            // on every permission check a GridField render makes.
            if ($this->isCoveredByCarrierAncestor($class, $schema)) {
                continue;
            }

            foreach ($this->getOwnerPagesForClass($class) as $owner) {
                $owners[(int)$owner->ID] = $owner;
            }
        }

        return array_values($owners);
    }

    /**
     * Whether an instantiable class above this one carries the HeaderImage column, and so is
     * already asked for this record's owners.
     *
     * Only instantiable ancestors count as covering: an abstract one cannot be queried itself, so
     * its concrete descendants still have to be asked rather than left to a class that will never
     * be queried.
     *
     * @param string $class A SiteTree subclass carrying the HeaderImage relation.
     * @param DataObjectSchema $schema
     * @return bool
     */
    private function isCoveredByCarrierAncestor(string $class, DataObjectSchema $schema): bool
    {
        for ($parent = get_parent_class($class); $parent !== false; $parent = get_parent_class($parent)) {
            if ($schema->hasOneComponent($parent, 'HeaderImage') !== self::class) {
                continue;
            }

            if ((new ReflectionClass($parent))->isInstantiable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * The records of one page class that point at this header image, on the draft and Live stages.
     *
     * Versioned::withVersionedMode() keeps the ambient reading mode - the draft stage in the CMS -
     * from deciding which of the two stages either query answers. The lists are read out inside the
     * closure rather than returned from it: that works either way here, because Versioned copies
     * the reading mode into the DataQuery's parameters when the list is created, but reading them
     * inside is what keeps this method's result independent of that detail.
     *
     * @param string $class A SiteTree subclass carrying the HeaderImage relation.
     * @return SiteTree[]
     */
    private function getOwnerPagesForClass(string $class): array
    {
        $owners = [];

        foreach ([Versioned::DRAFT, Versioned::LIVE] as $stage) {
            $stageOwners = Versioned::withVersionedMode(function () use ($class, $stage) {
                Versioned::set_stage($stage);

                return $class::get()
                    ->filter('HeaderImageID', $this->ID)
                    ->toArray();
            });

            foreach ($stageOwners as $owner) {
                $owners[] = $owner;
            }
        }

        return $owners;
    }

    /**
     * Grant the permission only when every owning page grants it.
     *
     * One header image can be pointed at by more than one page - through the add-existing
     * autocompleter on the HeaderImage field, a write made with no member logged in, or a page
     * that still shows it on Live - so granting on any single owner would let a member who can
     * edit one open page reach the image shown on a page they cannot. HeaderImageExtension's
     * validation stops a member linking an image they may not edit, which keeps this rule from
     * being turned against an image's existing owners.
     *
     * Without an owner - a record the add form has not written yet, or one nothing points at -
     * fall back to whether the member may edit pages at all. DataObject::can*() is deliberately
     * not used there: it is ADMIN-only and would lock content editors out of adding an image.
     *
     * @param string $method The SiteTree permission method to ask each owning page for.
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
