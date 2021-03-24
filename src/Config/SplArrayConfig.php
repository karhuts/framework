<?php
declare(strict_types=1);
namespace Karthus\Config;

use Karthus\Spl\SplArray;

class SplArrayConfig extends AbstractConfig {

    /**
     * @var SplArray
     */
    private $splArray;

    /**
     * SplArrayConfig constructor.
     */
    public function __construct() {
        $this->splArray = new SplArray();
    }

    /**
     * @param null $key
     * @return array|mixed|null
     */
    public function getConf($key = null): ?array{
        if ($key === null) {
            return $this->splArray->getArrayCopy();
        }
        return $this->splArray->get($key);
    }

    /**
     * @param $key
     * @param $val
     * @return bool
     */
    public function setConf($key, $val): bool {
        $this->splArray->set($key, $val);
        return true;
    }

    /**
     * @param array $array
     * @return bool
     */
    public function load(array $array): bool {
        $this->splArray->loadArray($array);
        return true;
    }

    /**
     * @param array $array
     * @return bool
     */
    public function merge(array $array): bool {
        $this->splArray->merge($array);
        return true;
    }

    /**
     * @return bool
     */
    public function clear(): bool {
        $this->load([]);
        return true;
    }
}
