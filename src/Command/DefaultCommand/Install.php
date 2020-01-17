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
            unlink(KARTHUS_ROOT . '/karthus');
        }

        //保存一个可执行文件
        file_put_contents(
            KARTHUS_ROOT . '/katthus',
            file_get_contents(__DIR__.'/../../../bin/karthus')
        );

        releaseResource(__DIR__ . '/../../Resource/KarthusEvent.php', KARTHUS_ROOT . '/KarthusEvent.php');
        releaseResource(__DIR__ . '/../../Resource/Http/Index.php', KARTHUS_ROOT . '/App/Controller/Index.php');
        releaseResource(__DIR__ . '/../../Resource/Config.php', KARTHUS_ROOT . '/dev.php');
        releaseResource(__DIR__ . '/../../Resource/Config.php', KARTHUS_ROOT . '/produce.php');


        echo chr(27)."[42minstall success,enjoy! ".chr(27)."[0m \n";

        $arr = json_decode(file_get_contents(KARTHUS_ROOT.'/composer.json'),true);
        $arr['autoload']['psr-4']['App\\'] = "App/";
        FileHelper::createFile(KARTHUS_ROOT.'/composer.json',
            json_encode($arr,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)
        );

        @exec('composer dump-autoload');
        echo chr(27)."[42mdonot forget run composer dump-autoload ".chr(27)."[0m \n";
        return "";
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        return welcome().'install or reinstall Karthus';
    }
}
