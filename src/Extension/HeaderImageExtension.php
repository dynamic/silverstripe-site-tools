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
