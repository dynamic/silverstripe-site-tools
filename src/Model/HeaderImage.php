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
     * @return SiteTree[]
     */
    private function getOwnerPages(): array
    {
        if (!$this->isInDB()) {
            return [];
        }

        $schema = DataObject::getSchema();
        // Keyed by record ID: the same page is reachable through its own class, through a class
        // above it, and on both stages, and is still a single owner.
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

            foreach ($this->getOwnerPagesForClass($class) as $owner) {
                $owners[(int)$owner->ID] = $owner;
            }
        }

        return array_values($owners);
    }

    /**
     * The records of one page class that point at this header image, on the draft and Live stages.
     *
     * The lists are read out inside Versioned::withVersionedMode() rather than returned from it:
     * a DataList is only queried when it is iterated, and by then the closure has restored the
     * ambient reading mode, which is the draft stage in the CMS and would silently return nothing
     * for the Live pass.
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
     * One header image can be pointed at by more than one page - the add-existing autocompleter on
     * the HeaderImage field links any image to a page without checking who else already owns it -
     * so granting on any single owner would let a member who can edit one open page reach the
     * image shown on a page they cannot.
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
