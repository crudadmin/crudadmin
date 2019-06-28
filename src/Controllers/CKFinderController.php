<?php

namespace Admin\Controllers;

use CKSource\CKFinder\CKFinder;
use \Illuminate\Routing\Controller;
use Psr\Container\ContainerInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Controller for handling requests to CKFinder connector.
 */
class CKFinderController extends Controller
{
    /**
     * Use custom middleware to handle custom authentication and redirects.
     */
    public function __construct()
    {
        app()->bind('ckfinder.connector', function() {
            $ckfinder = new \CKSource\CKFinder\CKFinder(require __DIR__.'/../Plugins/CKFinder/config.php');

            if (Kernel::MAJOR_VERSION >= 4) {
                $this->setupForV4PlusKernel($ckfinder);
            }

            return $ckfinder;
        });
    }

    /**
     * Prepares CKFinder DI container to use version version 4+ of HttpKernel.
     *
     * @param \CKSource\CKFinder\CKFinder $ckfinder
     */
    private function setupForV4PlusKernel($ckfinder)
    {
        $ckfinder['resolver'] = function () use ($ckfinder) {
            $commandResolver = new \Admin\Plugins\CKFinder\Polyfill\CommandResolver($ckfinder);
            $commandResolver->setCommandsNamespace(\CKSource\CKFinder\CKFinder::COMMANDS_NAMESPACE);
            $commandResolver->setPluginsNamespace(\CKSource\CKFinder\CKFinder::PLUGINS_NAMESPACE);

            return $commandResolver;
        };

        $ckfinder['kernel'] = function () use ($ckfinder) {
            return new HttpKernel(
                $ckfinder['dispatcher'],
                $ckfinder['resolver'],
                $ckfinder['request_stack'],
                $ckfinder['resolver']
            );
        };
    }

    /**
     * Action that handles all CKFinder requests.
     *
     * @param ContainerInterface $container
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function requestAction(ContainerInterface $container, Request $request)
    {
        /** @var CKFinder $connector */
        $connector = $container->get('ckfinder.connector');

        return $connector->handle($request, HttpKernelInterface::MASTER_REQUEST, true);
    }

    /**
     * Action that displays CKFinder browser.
     *
     * @return string
     */
    public function browserAction(ContainerInterface $container, Request $request)
    {
        return view('admin::partials.ckfinder_browser');
    }
}