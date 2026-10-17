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
     * Only a real change of value is checked: publishing forces every field to look changed, and a
     * page re-saved with the same image, or one letting go of its image, takes nothing from anyone.
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

        if (!$imageID || !$page->isChanged('HeaderImageID', DataObject::CHANGE_VALUE)) {
            return;
        }

        $member = Security::getCurrentUser();
        if (!$member) {
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
