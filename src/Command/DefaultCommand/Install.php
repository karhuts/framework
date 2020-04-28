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

        //保存一个可执行文件
        @copy(__DIR__.'/../../../bin/karthus', KARTHUS_ROOT . '/karthus');

        releaseResource(__DIR__ . '/../../Resource/KarthusEvent.php', KARTHUS_ROOT . '/KarthusEvent.php');
        releaseResource(__DIR__ . '/../../Resource/Http/Index.php', KARTHUS_ROOT . '/Apps/Controller/Index.php');
        releaseResource(__DIR__ . '/../../Resource/Settings.php', KARTHUS_ROOT . '/Config/Settings.php');
        releaseResource(__DIR__ . '/../../Resource/Router.php', KARTHUS_ROOT . '/Config/Router.php');

        //开始安装单元测试case了
        releaseResource(__DIR__ . '/../../../PHPUnit/CoreTest.php', KARTHUS_ROOT . '/PHPunit/CoreTest.php');

        echo "\e[42minstall success,enjoy! \e[0m \n";

        // 更新composer.json文件
        $arr = json_decode(file_get_contents(KARTHUS_ROOT.'/composer.json'),true);
        $arr['autoload']['psr-4']['Apps\\']             = "Apps/";
        $arr['autoload']['psr-4']['Karthus\\PHPUnit\\'] = "PHPUnit/";

        FileHelper::createFile(KARTHUS_ROOT.'/composer.json',
            json_encode($arr,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)
        );

        // 更新composer dump-autoload
        @exec('composer dump-autoload');
        echo "\e[42mrun composer dump-autoload done!\e[0m \n";
        return "";
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        return welcome().'install or reinstall Karthus';
    }
}
