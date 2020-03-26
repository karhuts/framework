<?php
declare(strict_types=1);
namespace Karthus\Spl;

class SplEnum {
    private $val = null;
    private $name = null;

    /**
     * SplEnum constructor.
     *
     * @param $val
     * @throws \Exception
     */
    final public function __construct($val) {
        $list = self::getConstants();
        //禁止重复值
        if (count($list) != count(array_unique($list))) {
            $class = static::class;
            throw new \Exception("class : {$class} define duplicate value");
        }
        $this->val = $val;
        $this->name = self::isValidValue($val);
        if($this->name === false){
            throw new \Exception("invalid value");
        }
    }

    final public function getName():string {
        return $this->name;
    }

    final public function getValue() {
        return $this->val;
    }

    /**
     * @param string $name
     * @return bool
     */
    final public static function isValidName(string $name):bool {
        $list = self::getConstants();
        if(isset($list[$name])){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $val
     * @return false|int|string
     */
    final public static function isValidValue($val) {
        $list = self::getConstants();
        return array_search($val,$list);
    }

    /**
     * @return array
     */
    final public static function getEnumList():array {
        return self::getConstants();
    }

    /**
     * @return array
     */
    private final static function getConstants():array {
        try{
            return (new \ReflectionClass(static::class))->getConstants();
        }catch (\Throwable $throwable){
            return [];
        }
    }

    /**
     * @return string
     */
    public function __toString() {
        return (string)$this->getName();
    }
}
