<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;
use Karthus\Helper\FileHelper;

class Install implements CommandInterface {

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return "install";
    }

    /**
     * @inheritDoc
     */
    public function exec(array $args): ?string {
        echo welcome();
        if(is_file(KARTHUS_ROOT . '/karthus')){
            @unlink(KARTHUS_ROOT . '/karthus');
        }

        $exeContents    = file_get_contents(__DIR__.'/../../../bin/karthus');
        //保存一个可执行文件
        @file_put_contents(KARTHUS_ROOT . '/karthus', $exeContents);

        releaseResource(__DIR__ . '/../../Resource/KarthusEvent.php', KARTHUS_ROOT . '/KarthusEvent.php');
        releaseResource(__DIR__ . '/../../Resource/Http/Index.php', KARTHUS_ROOT . '/Apps/Controller/Index.php');
        releaseResource(__DIR__ . '/../../Resource/Config.php', KARTHUS_ROOT . '/Config/dev.php');
        releaseResource(__DIR__ . '/../../Resource/Config.php', KARTHUS_ROOT . '/Config/produce.php');
        releaseResource(__DIR__ . '/../../Resource/Router.php', KARTHUS_ROOT . '/Config/router.php');

        //开始安装单元测试case了
        releaseResource(__DIR__ . '/../../../PHPUnit/CoreTest.php', KARTHUS_ROOT . '/PHPunit/CoreTest.php');

        echo "\e[42minstall success,enjoy! \e[0m \n";

        $arr = json_decode(file_get_contents(KARTHUS_ROOT.'/composer.json'),true);
        $arr['autoload']['psr-4']['Apps\\'] = "Apps/";
        $arr['autoload']['psr-4']['Karthus\\PHPUnit\\'] = "PHPUnit/";
        FileHelper::createFile(KARTHUS_ROOT.'/composer.json',
            json_encode($arr,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)
        );

        echo "\e[42mdonot forget run composer dump-autoload \e[0m \n";
        @exec('composer dump-autoload');
        return "";
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        return welcome().'install or reinstall Karthus';
    }
}
