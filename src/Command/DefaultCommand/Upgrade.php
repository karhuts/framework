<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandInterface;
use Karthus\SystemConst;

class Upgrade implements CommandInterface {

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return "upgrade";
    }

    /**
     * @inheritDoc
     */
    public function exec(array $args): ?string {
        $version    = SystemConst::KARTHUS_VERSION;
        echo "current version $version, do you want to replace it? [ Y / N (default) ] : ";
        $answer = strtolower(strtoupper(trim(fgets(STDIN))));
        if (!in_array($answer, [ 'y', 'yes' ])) {
            echo "upgrade fail\n";
            return null;
        }

        @exec('composer update');
        echo "\e[42minstall success,enjoy! \e[0m \n";
        return "";
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        $logo = welcome();
        return $logo . <<<HELP
\e[33mOperation:\e[0m
\e[31m  php karthus upgrade [arg1]\e[0m
\e[33mUsage:\e[0m
\e[36m  you can upgrade karthus version\e[0m
\e[33mArgs:\e[0m
\e[32m  produce \e[0m                     load Config/produce.php
HELP;
    }
}
