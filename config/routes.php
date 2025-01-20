<?php

use App\Controller\EmbedStaticController;
use App\Service\EmbedStatic;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes
        ->add('embed_static', EmbedStatic::EMBED_JS_PATH . '/{path}')
        ->controller([EmbedStaticController::class, 'staticFile'])
        ->methods(['GET'])
    ;
};
