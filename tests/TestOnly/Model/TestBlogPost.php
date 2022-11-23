<?php

namespace Dynamic\SiteTools\Test\TestOnly\Model;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use Dynamic\SiteTools\Extension\BlogPostDataExtension;
use SilverStripe\Blog\Model\BlogPost;
use SilverStripe\Dev\TestOnly;

/**
 * Class TestBlogPost
 * @package Dynamic\SiteTools\Test
 */
class TestBlogPost extends BlogPost implements TestOnly
{
    /**
     * @var string
     */
    private static $table_name = 'TestBlogPost';
}
