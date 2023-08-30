<?php

namespace Dynamic\SiteTools\Extension;

use SilverStripe\ORM\DataExtension;

/**
 * Class DataobjectPermissionExtension.
 *
 * @property EditableCustomRule|EditableFormField|DataobjectPermissionExtension $owner
 */
class DataobjectPermissionExtension extends DataExtension
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
