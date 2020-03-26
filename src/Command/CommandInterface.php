<?php
declare(strict_types=1);
namespace Karthus\Command;

interface CommandInterface {

    /**
     * 命令名称
     *
     * @return string
     */
    public function commandName():string;

    /**
     * 执行方法
     *
     * @param array $args
     * @return string|null
     */
    public function exec(array $args):?string ;

    /**
     * 帮助
     *
     * @param array $args
     * @return string|null
     */
    public function help(array $args):?string ;

}
