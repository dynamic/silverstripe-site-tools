<?php

namespace Dynamic\SiteTools\Test\Extension;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use Dynamic\SiteTools\Extension\BlogPostDataExtension;
use Dynamic\SiteTools\Test\TestOnly\Model\TestBlogPost;
use SilverStripe\Blog\Model\BlogPost;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

class BlogPostDataExtensionTest extends SapphireTest
{
    /**
     * @var array
     */
    protected static $fixture_file = [
        '../fixtures.yml',
    ];

    /**
     * @var array
     */
    /*
    protected static $extra_dataobjects = [
        TestBlogPost::class,
    ];
    */

    /**
     * @var array
     */
    /*
    protected static $required_extensions = [
        TestBlogPost::class => [
            BlogPostDataExtension::class,
            ElementalPageExtension::class,
        ],
    ];
    */

    /**
     *
     */
    public function testUpdateCMSFields()
    {
        $this->markTestSkipped();
        /** @var BlogPost $object */
        $object = Injector::inst()->create(TestBlogPost::class);
        $fields = $object->getCMSFields();
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    /**
     *
     */
    public function testGetRelatedPosts()
    {
        $this->markTestSkipped();
        /** @var BlogPost $object */
        $object = $this->objFromFixture(TestBlogPost::class, 'one');
        /** @var BlogPost $expected */
        $expected = $this->objFromFixture(TestBlogPost::class, 'two');
        $this->assertEquals($expected->ID, $object->getRelatedPosts()->first()->ID);
    }

    public function testGetContent()
    {
        $this->markTestSkipped();
        $expected = "Test";
        /** @var BlogPost $post */
        $post = $this->objFromFixture(TestBlogPost::class, 'one');
        $this->assertEquals('', $post->getFirstContent());

        // todo update below test portion to be appropriate based on extension implementation
        // currently the module does not apply any elemental extensions to blog, as we don't want it tightly coupled

        /*$post->ElementalArea()->Elements()->add(ElementContent::create());
        $this->assertEquals('', $post->getFirstContent());

        $element = $post->ElementalArea()
            ->Elements()->filter([
                'ClassName' => ElementContent::class,
            ])->first();
        $element->HTML = $expected;
        $element->write();

        $this->assertEquals($expected, $post->getFirstContent());
        //*/
    }
}
