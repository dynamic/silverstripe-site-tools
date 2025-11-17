<?php

namespace Dynamic\SiteTools\Extension;

use SilverStripe\Core\Extension;

/**
 * Class DataobjectPermissionExtension.
 *
 * @property EditableCustomRule|EditableFormField|DataobjectPermissionExtension $owner
 */
class DataobjectPermissionExtension extends Extension
{
    /**
     * @param null $member
     *
     * @return bool
     */
    public function canCreate($member = null)
    {
        return true;
    }

    /**
     * @param null $member
     *
     * @return bool
     */
    public function canView($member = null)
    {
        return true;
    }

    /**
     * @param null $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        return true;
    }

    /**
     * @param null $member
     *
     * @return bool
     */
    public function canDelete($member = null)
    {
        return true;
    }
}
