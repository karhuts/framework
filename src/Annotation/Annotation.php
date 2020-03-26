<?php
declare(strict_types=1);
namespace Karthus\Annotation;

class Annotation {
    private $parserTags = [];

    /**
     * Annotation constructor.
     *
     * @param array $parserTags
     */
    public function __construct(array $parserTags = []) {
        $this->parserTags = $parserTags;
    }

    /**
     * @param AbstractAnnotation $abstractAnnotation
     * @return Annotation
     */
    public function addParserTag(AbstractAnnotation $abstractAnnotation): Annotation {
        $this->parserTags[strtolower($abstractAnnotation->tagName())] = $abstractAnnotation;
        foreach ($abstractAnnotation->aliasMap() as $item){
            if(!isset($this->aliasMap[md5($item)])){
                $this->aliasMap[md5(strtolower($item))] = $abstractAnnotation->tagName();
            }else{
                throw new Exception("alias name {$item} for tag:{$abstractAnnotation->tagName()} is duplicate with tag:{$this->aliasMap[md5($item)]}");
            }
        }
        return $this;
    }

    /**
     * @param string $tagName
     * @return Annotation
     */
    public function deleteParserTag(string $tagName): Annotation {
        unset($this->parserTags[$tagName]);
        return $this;
    }

    public static function parser(string $line):? LineItem {
        $pattern = '/@(\\\?[a-zA-Z][0-9a-zA-Z_\\\]*?)\((.*)\)/';
        preg_match($pattern, $line,$match);
        if(is_array($match) && (count($match) === 3)){
            $item = new LineItem();
            $item->setName(trim($match[1]," \t\n\r\0\x0B\\"));
            $item->setValue(trim($match[2]));
            return $item;
        }else{
            return null;
        }
    }
}
