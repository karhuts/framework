<?php
declare(strict_types=1);

namespace Karthus\Contract;

interface NormalizerInterface {
    /**
     * Normalizes an object into a set of arrays/scalars.
     *
     * @param mixed $object
     * @return array|bool|float|int|string
     */
    public function normalize($object);

    /**
     * Denormalizes data back into an object of the given class.
     *
     * @param mixed $data Data to restore
     * @param string $class The expected class to instantiate
     * @return object
     */
    public function denormalize($data, $class);
}
