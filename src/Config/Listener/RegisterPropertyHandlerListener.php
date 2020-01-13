<?php
declare(strict_types=1);
namespace Karthus\Config\Listener;

use Karthus\Annotation\Value;
use Karthus\Contract\ConfigInterface;
use Karthus\Contract\ListenerInterface;
use Karthus\Definition\ObjectDefinition;
use Karthus\Definition\PropertyHandlerManager;
use Karthus\Definition\PropertyInjection;
use Karthus\Framework\BootApplication;
use Karthus\Service\ApplicationContext;

class RegisterPropertyHandlerListener implements ListenerInterface {
    /**
     * @return string[] returns the events that you want to listen
     */
    public function listen(): array {
        return [
            BootApplication::class,
        ];
    }
    /**
     * Handle the Event when the event is triggered, all listeners will
     * complete before the event is returned to the EventDispatcher.
     */
    public function process($event) {
        PropertyHandlerManager::register(Value::class, function (ObjectDefinition $definition, string $propertyName, $annotation) {
            if ($annotation instanceof Value && ApplicationContext::hasContainer()) {
                $key = $annotation->key;
                $propertyInjection = new PropertyInjection($propertyName, function () use ($key) {
                    $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
                    return $config->get($key, null);
                });
                $definition->addPropertyInjection($propertyInjection);
            }
        });
    }
}
