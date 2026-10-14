<?php

namespace Dynamic\SiteTools\Test\TestOnly;

use Dynamic\SiteTools\Model\HeaderImage;
use SilverStripe\Dev\TestOnly;

/**
 * A HeaderImage that lets a test pretend specific page classes cannot be queried by the owner
 * walk, which is what an abstract page base class carrying the HeaderImage relation really is.
 *
 * Declaring such a class for real is not possible in a test run: TableBuilder::buildTables()
 * instantiates every DataObject class in the class manifest before it applies the TestOnly
 * exclusion, so an abstract page class - in tests/ as anywhere else - aborts the temp database
 * build with "Cannot instantiate abstract class" (vendor/silverstripe/framework
 * src/ORM/Connect/TableBuilder.php line 28) and takes the entire suite with it. Overriding
 * HeaderImage::isQueryablePageClass() reproduces the one property of that class the owner walk
 * actually depends on - it is skipped, and being skipped does not mean the classes below it were
 * searched - without putting an abstract DataObject in the manifest.
 *
 * The class declares no database fields of its own, so it needs no table of its own, and it is
 * never written: a test copies an existing record's ID onto it and asks it the permission
 * question.
 */
class UnqueryableOwnerClassHeaderImage extends HeaderImage implements TestOnly
{
    /**
     * Page classes the owner walk must treat as not queryable.
     *
     * A declared property, not a dynamic one: DataObject::__set() would take an undeclared
     * assignment into the record instead.
     *
     * @var string[]
     */
    public $unqueryableClasses = [];

    /**
     * @param string $class
     * @return bool
     */
    protected function isQueryablePageClass(string $class): bool
    {
        if (in_array($class, $this->unqueryableClasses ?: [], true)) {
            return false;
        }

        return parent::isQueryablePageClass($class);
    }
}
