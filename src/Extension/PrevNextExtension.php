<?php

namespace Dynamic\SiteTools\Extension;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\Deprecation;

/**
 * Class PrevNextExtension
 *
 * @deprecated 6.1.0 Will be removed without equivalent functionality to replace it in a future major release.
 * @property PrevNextExtension $owner
 */
class PrevNextExtension extends Extension
{
    /**
     * previous/next page links for pages in the same section
     *
     * @param string $mode
     * @return bool
     */
    public function PrevNext($mode = 'next')
    {
        // SCOPE_GLOBAL is deliberate: this method is an extension method, so it is always reached through
        // supported-module code (SiteTree::__call()), which hides a method-scoped notice by default. Global
        // scope keeps it visible to the installing project, which is the only code able to stop using it.
        Deprecation::notice(
            '6.1.0',
            'PrevNextExtension::PrevNext() is deprecated. Will be removed without equivalent functionality to '
            . 'replace it in a future major release.',
            Deprecation::SCOPE_GLOBAL
        );

        switch ($mode) {
            case "next":
                $filter = "ParentID = (" . $this->owner->ParentID . ") AND Sort > (" . $this->owner->Sort . ")";
                $sort = "Sort ASC";
                break;
            case "prev":
                $filter = "ParentID = (" . $this->owner->ParentID . ") AND Sort < (" . $this->owner->Sort . ")";
                $sort = "Sort DESC";
                break;
            default:
                return false;
        }

        if ($page = SiteTree::get()->where($filter)->sort($sort)->first()) {
            return $page;
        }
        return false;
    }
}
