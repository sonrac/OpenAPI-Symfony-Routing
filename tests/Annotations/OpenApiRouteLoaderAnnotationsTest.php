<?php

declare(strict_types=1);

namespace Tobion\OpenApiSymfonyRouting\Tests\Annotations;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Tobion\OpenApiSymfonyRouting\OpenApiRouteLoader;
use Tobion\OpenApiSymfonyRouting\Tests\Annotations\Fixtures\Basic\Controller as BasicController;
use Tobion\OpenApiSymfonyRouting\Tests\Annotations\Fixtures\FormatSuffix\Controller as FormatSuffixController;
use Tobion\OpenApiSymfonyRouting\Tests\Annotations\Fixtures\OperationId\Controller as OperationIdController;
use Tobion\OpenApiSymfonyRouting\Tests\Annotations\Fixtures\PathParameterPattern\Controller as PathParameterPatternController;
use Tobion\OpenApiSymfonyRouting\Tests\Annotations\Fixtures\Priority\Controller as PriorityController;
use Tobion\OpenApiSymfonyRouting\Tests\Annotations\Fixtures\SeveralClasses\BarController;
use Tobion\OpenApiSymfonyRouting\Tests\Annotations\Fixtures\SeveralClasses\FooController;
use Tobion\OpenApiSymfonyRouting\Tests\Annotations\Fixtures\SeveralClasses\SubNamespace\SubController;
use Tobion\OpenApiSymfonyRouting\Tests\Annotations\Fixtures\SeveralHttpMethods\Controller as SeveralHttpMethodsController;
use Tobion\OpenApiSymfonyRouting\Tests\Annotations\Fixtures\SeveralRoutesOnOneAction\Controller as SeveralRoutesOnOneActionController;

final class OpenApiRouteLoaderAnnotationsTest extends TestCase
{
    private const FIXTURES_ROUTE_NAME_PREFIX = 'tobion_openapisymfonyrouting_tests_annotations_fixtures_';

    public function testBasic(): void
    {
        $routeLoader = OpenApiRouteLoader::fromDirectories(__DIR__.'/Fixtures/Basic');

        $routes = $routeLoader->__invoke();

        $expectedRoutes = new RouteCollection();
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'basic__invoke',
            (new Route('/foobar'))->setMethods('GET')->setDefault('_controller', BasicController::class.'::__invoke')
        );

        self::assertEquals($expectedRoutes, $routes);
    }

    public function testFormatSuffix(): void
    {
        $routeLoader = OpenApiRouteLoader::fromDirectories(__DIR__.'/Fixtures/FormatSuffix');

        $routes = $routeLoader->__invoke();

        $expectedRoutes = new RouteCollection();
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'formatsuffix_inheritenabledformatsuffix',
            (new Route('/a.{_format}'))->setDefault('_format', null)->setMethods('GET')->setDefault('_controller', FormatSuffixController::class.'::inheritEnabledFormatSuffix')
        );
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'formatsuffix_defineformatpattern',
            (new Route('/b.{_format}'))->setDefault('_format', null)->setRequirement('_format', 'json|xml')->setMethods('GET')->setDefault('_controller', FormatSuffixController::class.'::defineFormatPattern')
        );
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'formatsuffix_disableformatsuffix',
            (new Route('/c'))->setMethods('GET')->setDefault('_controller', FormatSuffixController::class.'::disableFormatSuffix')
        );

        self::assertEquals($expectedRoutes, $routes);
    }

    public function testOperationId(): void
    {
        $routeLoader = OpenApiRouteLoader::fromDirectories(__DIR__.'/Fixtures/OperationId');

        $routes = $routeLoader->__invoke();

        $expectedRoutes = new RouteCollection();
        $expectedRoutes->add(
            'my-name',
            (new Route('/foobar'))->setMethods('GET')->setDefault('_controller', OperationIdController::class.'::__invoke')
        );

        self::assertEquals($expectedRoutes, $routes);
    }

    public function testPathParameterPattern(): void
    {
        $routeLoader = OpenApiRouteLoader::fromDirectories(__DIR__.'/Fixtures/PathParameterPattern');

        $routes = $routeLoader->__invoke();

        $expectedRoutes = new RouteCollection();
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'pathparameterpattern_nopattern',
            (new Route('/foo/{id}'))->setMethods('GET')->setDefault('_controller', PathParameterPatternController::class.'::noPattern')
        );
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'pathparameterpattern_noschema',
            (new Route('/baz/{id}'))->setMethods('GET')->setDefault('_controller', PathParameterPatternController::class.'::noSchema')
        );
        // OpenAPI needs the param pattern to be anchored (^$) to have the desired effect. Symfony automatically trims those to get a valid full path regex.
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'pathparameterpattern_withpattern',
            (new Route('/bar/{id}'))->setRequirement('id', '^[a-zA-Z0-9]+$')->setMethods('GET')->setDefault('_controller', PathParameterPatternController::class.'::withPattern')
        );

        self::assertEquals($expectedRoutes, $routes);
    }

    public function testPriority(): void
    {
        $routeLoader = OpenApiRouteLoader::fromDirectories(__DIR__.'/Fixtures/Priority');

        $routes = $routeLoader->__invoke();

        $expectedRoutes = new RouteCollection();
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'priority_foo',
            (new Route('/foo'))->setMethods('GET')->setDefault('_controller', PriorityController::class.'::foo')
        );
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'priority_catchall',
            (new Route('/{catchall}'))->setMethods('GET')->setDefault('_controller', PriorityController::class.'::catchall'),
            -100
        );
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'priority_bar',
            (new Route('/bar'))->setMethods('GET')->setDefault('_controller', PriorityController::class.'::bar'),
            10
        );

        self::assertEquals($expectedRoutes, $routes);
    }

    public function testSeveralClasses(): void
    {
        $routeLoader = OpenApiRouteLoader::fromDirectories(__DIR__.'/Fixtures/SeveralClasses');

        $routes = $routeLoader->__invoke();

        $expectedRoutes = new RouteCollection();
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'severalclasses_bar__invoke',
            (new Route('/bar'))->setMethods('GET')->setDefault('_controller', BarController::class.'::__invoke')
        );
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'severalclasses_foo__invoke',
            (new Route('/foo'))->setMethods('GET')->setDefault('_controller', FooController::class.'::__invoke')
        );
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'severalclasses_subnamespace_sub__invoke',
            (new Route('/sub'))->setMethods('GET')->setDefault('_controller', SubController::class.'::__invoke')
        );

        self::assertEquals($expectedRoutes, $routes);
    }

    public function testSeveralHttpMethods(): void
    {
        $routeLoader = OpenApiRouteLoader::fromDirectories(__DIR__.'/Fixtures/SeveralHttpMethods');

        $routes = $routeLoader->__invoke();

        $expectedRoutes = new RouteCollection();
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'severalhttpmethods_get',
            (new Route('/foobar'))->setMethods('GET')->setDefault('_controller', SeveralHttpMethodsController::class.'::get')
        );
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'severalhttpmethods_put',
            (new Route('/foobar'))->setMethods('PUT')->setDefault('_controller', SeveralHttpMethodsController::class.'::put')
        );
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'severalhttpmethods_post',
            (new Route('/foobar'))->setMethods('POST')->setDefault('_controller', SeveralHttpMethodsController::class.'::post')
        );
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'severalhttpmethods_delete',
            (new Route('/foobar'))->setMethods('DELETE')->setDefault('_controller', SeveralHttpMethodsController::class.'::delete')
        );

        self::assertEquals($expectedRoutes, $routes);
    }

    public function testSeveralRoutesOnOneAction(): void
    {
        $routeLoader = OpenApiRouteLoader::fromDirectories(__DIR__.'/Fixtures/SeveralRoutesOnOneAction');

        $routes = $routeLoader->__invoke();

        $expectedRoutes = new RouteCollection();
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'severalroutesononeaction__invoke',
            (new Route('/foobar'))->setMethods('GET')->setDefault('_controller', SeveralRoutesOnOneActionController::class.'::__invoke')
        );
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'severalroutesononeaction__invoke_1',
            (new Route('/foobar'))->setMethods('POST')->setDefault('_controller', SeveralRoutesOnOneActionController::class.'::__invoke')
        );
        $expectedRoutes->add(
            'my-name',
            (new Route('/foo-bar'))->setMethods('GET')->setDefault('_controller', SeveralRoutesOnOneActionController::class.'::__invoke')
        );

        self::assertEquals($expectedRoutes, $routes);
    }

    public function testSeveralDirectories(): void
    {
        $routeLoader = OpenApiRouteLoader::fromDirectories(__DIR__.'/Fixtures/Basic', __DIR__.'/Fixtures/SeveralClasses/SubNamespace');

        $routes = $routeLoader->__invoke();

        $expectedRoutes = new RouteCollection();
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'basic__invoke',
            (new Route('/foobar'))->setMethods('GET')->setDefault('_controller', BasicController::class.'::__invoke')
        );
        $expectedRoutes->add(
            self::FIXTURES_ROUTE_NAME_PREFIX.'severalclasses_subnamespace_sub__invoke',
            (new Route('/sub'))->setMethods('GET')->setDefault('_controller', SubController::class.'::__invoke')
        );

        self::assertEquals($expectedRoutes, $routes);
    }

    public function testSrcDirectoryDoesNotExist(): void
    {
        self::expectException(DirectoryNotFoundException::class);
        self::expectExceptionMessage('/../../../../src" directory does not exist');

        OpenApiRouteLoader::fromSrcDirectory();
    }
}
