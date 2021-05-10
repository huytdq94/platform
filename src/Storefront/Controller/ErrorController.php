<?php declare(strict_types=1);

namespace Shopware\Storefront\Controller;

use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Twig\ErrorTemplateResolver;
use Shopware\Storefront\Page\Navigation\Error\ErrorPageLoaderInterface;
use Shopware\Storefront\Pagelet\Header\HeaderPageletLoaderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationList;

class ErrorController extends StorefrontController
{
    /**
     * @var ErrorTemplateResolver
     */
    protected $errorTemplateResolver;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var HeaderPageletLoaderInterface
     */
    private $headerPageletLoader;

    /**
     * @var ErrorPageLoaderInterface
     */
    private $errorPageLoader;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(
        ErrorTemplateResolver $errorTemplateResolver,
        Session $session,
        HeaderPageletLoaderInterface $headerPageletLoader,
        SystemConfigService $systemConfigService,
        ErrorPageLoaderInterface $errorPageLoader
    ) {
        $this->errorTemplateResolver = $errorTemplateResolver;
        $this->session = $session;
        $this->headerPageletLoader = $headerPageletLoader;
        $this->errorPageLoader = $errorPageLoader;
        $this->systemConfigService = $systemConfigService;
    }

    public function error(\Throwable $exception, Request $request, SalesChannelContext $context): Response
    {
        try {
            $is404StatusCode = $exception instanceof HttpException
                && $exception->getStatusCode() === Response::HTTP_NOT_FOUND;

            if (!$is404StatusCode && !$this->session->getFlashBag()->has('danger')) {
                $this->session->getFlashBag()->add('danger', $this->trans('error.message-default'));
            }

            $request->attributes->set('navigationId', $context->getSalesChannel()->getNavigationCategoryId());

            $salesChannelId = $context->getSalesChannel()->getId();
            $cmsErrorLayoutId = $this->systemConfigService->getString('core.basicInformation.http404Page', $salesChannelId);
            if ($cmsErrorLayoutId !== '' && $is404StatusCode) {
                $errorPage = $this->errorPageLoader->load($cmsErrorLayoutId, $request, $context);

                $response = $this->renderStorefront(
                    '@Storefront/storefront/page/content/index.html.twig',
                    ['page' => $errorPage]
                );
            } else {
                $errorTemplate = $this->errorTemplateResolver->resolve($exception, $request);

                if (!$request->isXmlHttpRequest()) {
                    $header = $this->headerPageletLoader->load($request, $context);
                    $errorTemplate->setHeader($header);
                }

                $response = $this->renderStorefront($errorTemplate->getTemplateName(), ['page' => $errorTemplate]);
            }

            if ($exception instanceof HttpException) {
                $response->setStatusCode($exception->getStatusCode());
            }
        } catch (\Exception $e) { //final Fallback
            $response = $this->renderStorefront(
                '@Storefront/storefront/page/error/index.html.twig',
                ['exception' => $exception, 'followingException' => $e]
            );

            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // After this controllers content is rendered (even if the flashbag was not used e.g. on a 404 page),
        // clear the existing flashbag messages
        $this->session->getFlashBag()->clear();

        return $response;
    }

    /**
     * @internal (flag:FEATURE_NEXT_12455)
     */
    public function onCaptchaFailure(
        ConstraintViolationList $violations,
        Request $request
    ): Response {
        $formViolations = new ConstraintViolationException($violations, []);
        if (!$request->isXmlHttpRequest()) {
            return $this->forwardToRoute($request->get('_route'), ['formViolations' => $formViolations]);
        }

        $response = [];
        $response[] = [
            'type' => 'danger',
            'alert' => $this->renderView('@Storefront/storefront/utilities/alert.html.twig', [
                'type' => 'danger',
                'list' => [$this->trans('error.' . $formViolations->getViolations()->get(0)->getCode())],
            ]),
        ];

        return new JsonResponse($response);
    }
}
