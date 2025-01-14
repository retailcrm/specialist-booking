<?php

use App\Controller\EmbedStaticController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes
        ->add('embed_index', EmbedStaticController::EMBED_JS_PATH . '/index')
        ->controller([EmbedStaticController::class, 'index'])
        ->methods(['GET'])
    ;

    $routes
        ->add('embed_static', EmbedStaticController::EMBED_JS_PATH . '/{path}')
        ->controller([EmbedStaticController::class, 'staticFile'])
        ->methods(['GET'])
    ;
};
