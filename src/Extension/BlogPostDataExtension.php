<?php

namespace Dynamic\SiteTools\Extension;

use DNADesign\Elemental\Models\ElementContent;
use SilverStripe\Blog\Model\BlogPost;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\PaginatedList;

/**
 * Class BlogPostDataExtension
 * @package Dynamic\SiteTools\ORM
 */
class BlogPostDataExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $db = [
        'SubTitle' => 'Varchar',
    ];

    /**
     * @var array
     */
    private static $casting = [
        'FirstContent' => 'HTMLText',
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName(array(
            'SubTitle',
            'CustomSummary',
        ));

        $fields->insertAfter('Title', TextField::create('SubTitle', 'Sub Title'));

        $featured = $fields->dataFieldByName('FeaturedImage')
            ->setAllowedFileCategories('image')
            ->setIsMultiUpload(false)
            ->setFolderName('Uploads/Blog');
        $fields->insertBefore('Content', $featured);
    }

    /**
     * @param $tags
     */
    public function MetaComponents(&$tags)
    {
        $request = Controller::curr()->getRequest();
        $vars = $request->getVars();

        if (count(preg_grep('/^commentsstart[0-9]+/', array_keys($vars)))) {
            $link = Controller::join_links(Director::absoluteBaseURL(), $this->owner->Link());

            $tags['canonical'] = [
                'attributes' => [
                    'rel' => 'canonical',
                    'content' => $link,
                ],
            ];
        }
    }

    /**
     * @return DataList
     */
    public function getRelatedPosts()
    {
        $posts = BlogPost::get()
            ->filter(array(
                'ParentID' => $this->owner->ParentID,
            ))
            ->exclude('ID', $this->owner->ID);

        if ($this->owner->Tags()->count() > 0) {
            $posts->filterAny(array(
                'Tags.ID' => $this->owner->Tags()->map('ID', 'ID')->toArray(),
            ));
        }

        return $posts;
    }

    /**
     * Returns the content of the first content element block
     *
     * @return DBHTMLText
     */
    public function getFirstContent()
    {
        if ($this->owner->hasMethod('getElementalRelations') && $this->owner->getElementalRelations()) {
            $content = $this->owner->ElementalArea()
                ->Elements()->filter([
                    'ClassName' => ElementContent::class
                ])->first();
            if ($content && $content->exists()) {
                return $content->HTML;
            }
            return DBHTMLText::create();
        }

        return $this->owner->Content;
    }
}
