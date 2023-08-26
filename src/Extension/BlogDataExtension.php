<?php

namespace Dynamic\SiteTools\Extension;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\PaginatedList;

/**
 * Class \Dynamic\SiteTools\Extension\BlogDataExtension
 *
 * @property BlogDataExtension $owner
 */
class BlogDataExtension extends DataExtension
{
    /**
     * @param $tags
     */
    public function MetaComponents(&$tags)
    {
        $request = Controller::curr()->getRequest();
        $action = $request->param('Action');
        $id = $request->param('ID');
        $var = PaginatedList::create(ArrayList::create())->getPaginationGetVar();

        if (($action && $id) || $request->getVar($var)) {
            $link = Controller::join_links(Director::absoluteBaseURL(), $this->owner->Link($action), $id);

            $tags['canonical'] = [
                'attributes' => [
                    'rel' => 'canonical',
                    'content' => $link,
                ],
            ];
        }
    }
}
