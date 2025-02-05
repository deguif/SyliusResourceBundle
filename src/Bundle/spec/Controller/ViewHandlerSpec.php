<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace spec\Sylius\Bundle\ResourceBundle\Controller;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\View\ConfigurableViewHandlerInterface;
use FOS\RestBundle\View\View;
use PhpSpec\ObjectBehavior;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\ViewHandlerInterface;
use Symfony\Component\HttpFoundation\Response;

final class ViewHandlerSpec extends ObjectBehavior
{
    function let(ConfigurableViewHandlerInterface $restViewHandler): void
    {
        $this->beConstructedWith($restViewHandler);
    }

    function it_implements_view_handler_interface(): void
    {
        $this->shouldImplement(ViewHandlerInterface::class);
    }

    function it_handles_view_normally_for_html_requests(
        RequestConfiguration $requestConfiguration,
        ConfigurableViewHandlerInterface $restViewHandler,
        Response $response,
    ): void {
        $requestConfiguration->isHtmlRequest()->willReturn(true);
        $view = View::create();

        $restViewHandler->handle($view)->willReturn($response);

        $this->handle($requestConfiguration, $view)->shouldReturn($response);
    }

    function it_sets_proper_values_for_non_html_requests(
        RequestConfiguration $requestConfiguration,
        ConfigurableViewHandlerInterface $restViewHandler,
        Response $response,
    ): void {
        $requestConfiguration->isHtmlRequest()->willReturn(false);
        $view = View::create();
        $view->setContext(new Context());

        $requestConfiguration->getSerializationGroups()->willReturn(['Detailed']);
        $requestConfiguration->getSerializationVersion()->willReturn('2.0.0');

        $restViewHandler->setExclusionStrategyGroups(['Detailed'])->shouldBeCalled();
        $restViewHandler->setExclusionStrategyVersion('2.0.0')->shouldBeCalled();

        $restViewHandler->handle($view)->willReturn($response);

        $this->handle($requestConfiguration, $view)->shouldReturn($response);
    }
}
