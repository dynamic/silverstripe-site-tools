<?php

namespace Dynamic\SiteTools\Extension;

use Dynamic\Base\Page\HomePage;
use Dynamic\Base\Page\BlockPage;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\LiteralField;
use Dynamic\SiteTools\Model\HeaderImage;
use Dynamic\Base\Page\CampaignLandingPage;
use SilverShop\HasOneField\HasOneButtonField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Security;

/**
 * Class HeaderImageDataExtension.
 *
 * @property HeaderImageExtension $owner
 * @property int $HeaderImageID
 * @method HeaderImage HeaderImage()
 */
class HeaderImageExtension extends Extension
{
    /**
     * @var array
     */
    private static $has_one = array(
        'HeaderImage' => HeaderImage::class,
    );

    /**
     * @var string[]
     */
    private static $cascade_duplicates = [
        'HeaderImage',
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->replace(
            'HeaderImageID',
            $header_image = HasOneButtonField::create(
                $this->owner,
                'HeaderImage',
                ''
            )
        );

        $fields->insertBefore(
            'Content',
            $header_image
        );
    }

    /**
     * Refuse to point this page at an existing header image the current member may not edit.
     *
     * HeaderImage resolves its permissions from every page that points at it, so linking an image
     * to a page also makes that page one of its owners. The add-existing autocompleter on the
     * HeaderImage field writes the new pointer without checking the image it was given, and a
     * guessed ID gets past its search filter, so without this check a member who can edit only
     * this page could attach another page's image here and lock that page's editors out of it.
     *
     * The image is asked before this page is written, so its answer comes from the owners it
     * already has; an image nothing points at yet falls back to the site-wide page edit rights.
     *
     * Only a real change of value is checked, and the change is measured against the page's stored
     * draft row rather than the in-memory record's change list: a rollback or a restore from the
     * archive writes an older version whose change list is empty, while a publish forces every
     * field to look changed. A page re-saved or published with the image its draft already holds,
     * or one letting go of its image, takes nothing from anyone. A page with no draft row - a new
     * record, or one being restored from the archive - is checked for any image it points at.
     * A write with no member logged in - a dev/build, a fixture load, a CLI task - is not checked,
     * as the ORM does not enforce can*() on writes anywhere else and those callers have no member
     * to ask about.
     *
     * @param ValidationResult $result
     */
    public function updateValidate(ValidationResult $result): void
    {
        $page = $this->getOwner();
        $imageID = (int)$page->HeaderImageID;

        $member = Security::getCurrentUser();
        if (!$imageID || !$member || $imageID === $this->getStoredDraftHeaderImageID()) {
            return;
        }

        $image = HeaderImage::get()->byID($imageID);
        if (!$image || $image->canEdit($member)) {
            return;
        }

        $result->addFieldError(
            'HeaderImage',
            _t(
                __CLASS__ . '.CANNOT_LINK_HEADER_IMAGE',
                'You do not have permission to use this header image on this page.'
            )
        );
    }

    /**
     * The HeaderImageID this page's draft row holds in the database, or 0 when it has none. A class
     * without Versioned has a single row, which is read instead.
     *
     * Read fresh from the draft stage, whatever the ambient reading mode, so neither a stale
     * in-memory record nor the stage being written decides what counts as a new link.
     *
     * @return int
     */
    private function getStoredDraftHeaderImageID(): int
    {
        $page = $this->getOwner();

        if (!$page->isInDB()) {
            return 0;
        }

        $stored = $page->hasExtension(Versioned::class)
            ? Versioned::get_by_stage(get_class($page), Versioned::DRAFT)->byID($page->ID)
            : DataObject::get(get_class($page))->byID($page->ID);

        return $stored ? (int)$stored->HeaderImageID : 0;
    }

    /**
     *
     */
    public function getPageHeaderImage()
    {
        if ($this->owner->HeaderImageID) {
            return $this->owner->HeaderImage();
        } else {
            return self::getParentHeaderImage($this->owner);
        }
    }

    /**
     * @param $page
     */
    private function getParentHeaderImage($page)
    {
        $parent = $page->Parent;
        if ($parent && $parent->HeaderImageID) {
            return $parent->HeaderImage();
        } elseif ($parent) {
            return self::getParentHeaderImage($parent);
        }

        return;
    }
}
