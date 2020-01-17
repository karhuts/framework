<?php
declare(strict_types=1);
namespace Karthus\Command\DefaultCommand;

use Karthus\Command\CommandContainer;
use Karthus\Command\CommandInterface;

class Help implements CommandInterface {

    /**
     * @inheritDoc
     */
    public function commandName(): string {
        return 'help';
    }

    /**
     * @inheritDoc
     */
    public function exec(array $args): ?string {
        if (!isset($args[0])) {
            return $this->help($args);
        } else {
            $actionName = $args[0];
            array_shift($args);
            $call = CommandContainer::getInstance()->get($actionName);
            if ($call instanceof CommandInterface) {
                return $call->help($args);
            } else {
                return "no help message for command {$actionName} was found";
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function help(array $args): ?string {
        $allCommand = implode(PHP_EOL, CommandContainer::getInstance()->getCommandList());
        $logo = welcome();
        return $logo.<<<HELP
Welcome To Karthus Command Console!
Usage: php karthus [command] [arg]
Get help : php karthus help [command]
Current Register Command:
{$allCommand}
HELP;
    }
}
