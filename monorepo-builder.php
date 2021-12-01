<?php

declare(strict_types=1);

use Symplify\MonorepoBuilder\ValueObject\Option;
use Symplify\ComposerJsonManipulator\ValueObject\ComposerJsonSection;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator) :void {
    $parameters = $containerConfigurator->parameters();
    //for "merge" command
    $parameters->set(Option::DATA_TO_APPEND, [
        ComposerJsonSection::REQUIRE_DEV => [
            'lucatume/wp-browser' => '3.0.6',
            'phpunit/phpunit' => '^9.5',
        ],
    ]);
};
