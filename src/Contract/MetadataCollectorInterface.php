<?php
declare(strict_types=1);

namespace Karthus\Contract;
interface MetadataCollectorInterface {
    /**
     * Retrieve the metadata via key.
     *
     * @param string     $key
     * @param null|mixed $default
     */
    public static function get(string $key, $default = null);

    /**
     * Set the metadata to holder.
     *
     * @param string $key
     * @param mixed  $value
     */
    public static function set(string $key, $value): void;
    /**
     * Serialize the all metadata to a string.
     */
    public static function serialize(): string;

    /**
     * Deserialize the serialized metadata and set the metadata to holder.
     *
     * @param string $metadata
     * @return bool
     */
    public static function deserialize(string $metadata): bool;
    /**
     * Return all metadata array.
     */
    public static function list(): array;
}
