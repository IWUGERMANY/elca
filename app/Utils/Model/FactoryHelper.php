<?php declare(strict_types=1);

namespace Utils\Model;

use Doctrine\Instantiator\Instantiator;
use ReflectionObject;

/**
 * Helper methods for factories
 *
 * @author Tobias Lode <tobias@beibob.de>
 */
class FactoryHelper
{
    /**
     * Creates an instance of the given class without calling its constructor
     * and fills in all properties given by $properties map
     *
     * @see https://sebastian-bergmann.de/archives/831-Freezing-and-Thawing-PHP-Objects.html
     *
     * @param       $className
     * @param array $properties
     * @return mixed
     */
    public static function createInstanceWithoutConstructor($className, array $properties = [])
    {
        $instantiator = new Instantiator();
        $instance = $instantiator->instantiate($className);

        $reflector = new ReflectionObject($instance);

        foreach ($properties as $name => $value) {
            $attribute = $reflector->getProperty($name);
            $attribute->setAccessible(true);
            $attribute->setValue($instance, $value);
        }

        return $instance;
    }
}
