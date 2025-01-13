<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\CodeQuality\Rector\Class_\LoadValidatorMetadataToAnnotationRector;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
    $rectorConfig->phpstanConfig(__DIR__ . '/phpstan.neon.dist');
    $rectorConfig->cacheDirectory(__DIR__ . '/var/cache/rector');

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,
        SymfonySetList::SYMFONY_72,
        DoctrineSetList::DOCTRINE_DBAL_30,
    ]);

    $rectorConfig->skip([
        // удобно видеть все поля сущности явно объявленными
        // плюс аннотации/атрибуты в конструкторе тяжеловесно смотрятся
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/src/Entity/*',
        ],
    ]);

    $rectorConfig->parallel();
};
