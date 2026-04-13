<?php

declare(strict_types=1);

namespace R3H6\FormTranslator;

use R3H6\FormTranslator\Facade\FormPersistenceManagerInterface;
use R3H6\FormTranslator\Facade\FormPersistenceManagerV13;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->setAlias(FormPersistenceManagerInterface::class, FormPersistenceManagerV13::class);
};