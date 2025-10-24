<?php

namespace Medpzl\ClubdataCart\Controller;

use Extcode\Cart\Domain\Model\Order\Item;
use Extcode\Cart\Domain\Repository\Order\ItemRepository;
use Medpzl\ClubdataCart\Domain\Model\Order\Product;
use Medpzl\ClubdataCart\Domain\Repository\Order\ProductRepository;
use Medpzl\ClubdataCart\Domain\Repository\PauseRepository;
use Medpzl\Clubdata\Domain\Repository\ProgramRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Fluid\View\StandaloneView;

class BackendController extends ActionController
{
    public function __construct(
        protected PauseRepository $pauseRepository,
        protected ProgramRepository $programRepository,
        protected ItemRepository $itemRepository,
        protected ProductRepository $productRepository,
        ConfigurationManagerInterface $configurationManager,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly PageRenderer $pageRenderer,
        private readonly ViewFactoryInterface $viewFactory
    ) {
    }

    protected function initializeAction(): void
    {
        $cartConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Cart'
        );

        if (isset($cartConfiguration['settings']['cart']['pid'])) {
            $this->settings['cart']['pid'] = $cartConfiguration['settings']['cart']['pid'];
        }
    }

    // TODO why called interface? Isn't that a classic indexAction()?
    public function interfaceAction(): ResponseInterface
    {
        $this->now = $this->settings['scanner']['showFrom'] ?? 'now';

        $exportPrograms = $this->filterData();

        $usercheck = false;
        $denies = explode(',', $this->settings['refund']['denyGroups'] ?? '');
        $groups = $GLOBALS['BE_USER']->user['usergroup'] ?? '';

        foreach ($denies as $deny) {
            if ($deny && strpos($groups, $deny) !== false) {
                $usercheck = true;
            }
        }
        $refundPrograms = [];
        if (!$usercheck) {
            $this->now = $this->settings['refund']['showFrom'] ?? 'now';
            $refundPrograms = $this->filterData();
        }

        // TODO: necessary!?
        $options = [];
        $options[] = [
            'id' => 'future',
            'title' => 'zukünftige'
        ];
        $options[] = [
            'id' => 'all',
            'title' => 'alle'
        ];

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setTitle('Cart Interface');

        $moduleTemplate->assign('exportPrograms', $exportPrograms);
        $moduleTemplate->assign('refundPrograms', $refundPrograms);
        $moduleTemplate->assign('options', $options);

        return $moduleTemplate->renderResponse('Backend/Interface');
    }

    protected function filterData()
    {
        $programs = $this->programRepository->findWithinMonth([], 0, 0, 1, $this->now);
        $uids = [];

        foreach ($programs as $program) {
            $uids[] = $program->getUid();
        }

        $uids = array_unique($uids);

        if (empty($uids)) {
            return [];
        }

        $orders = $this->productRepository->findSku($uids);

        $filtered = [];
        $failed = [];
        foreach ($orders as $order) {
            $item = $order->getItem();

            if (is_null($item)) {
                $failed[] = $order;
            } else {
                if ($item->getShipping()->getStatus() == 'shipped' &&
                    $item->getPayment()->getStatus() == 'paid') {
                    $filtered[] = $order;
                } else {
                    $failed[] = $order;
                }
            }
        }

        if (!count($failed)) {
            dd($failed);
        }

        return $filtered;
    }

    public function ticketCheckAction(): ResponseInterface
    {
        $args = $this->request->getArguments();
        $what = $args['ticketCheck']['what'];

        if ($what == 'all') {
            $programs = $this->programRepository->findWithinMonth([], 0, 0, 0, '');
        } else {
            $programs = $this->programRepository->findWithinMonth([], 0, 0, 1, 'now');
        }

        $i = 0;
        $rows = [];
        foreach ($programs as $program) {
            $uids = [];
            $uid = $program->getUid();
            $uids[] = $uid;
            $disposed = 0;
            $open = 0;
            $cancelled = 0;
            $pending = 0;
            $shipped = 0;
            $not_shipped = 0;

            $orders = $this->productRepository->findSku($uids);

            foreach ($orders as $order) {
                switch ($order->getItem()->getPayment()->getStatus()) {
                    case 'paid':
                        $disposed += $order->getCount();
                        break;
                    case 'open':
                        $open += $order->getCount();
                        break;
                    case 'canceled':
                        $cancelled += $order->getCount();
                        break;
                    case 'pending':
                        $pending += $order->getCount();
                        break;
                }
                switch ($order->getItem()->getShipping()->getStatus()) {
                    case 'shipped':
                        $shipped += $order->getCount();
                        break;
                    default:
                        $not_shipped += $order->getCount();
                        break;
                }
            }
            if (count($orders)) {
                $mark = '';
                $i++;
                $rows[] = [
                 'uid' => $uid ,
                 'mark' => $mark,
                 'title' => $order->getTitle(),
                 'disposed' => $disposed,
                 'open' => $open,
                 'cancelled' => $cancelled,
                 'pending' => $pending,
                 'shipped' => $shipped,
                 'not_shippeed' => $not_shipped,
                 'club_sold' => $program->getSoldTickets(),
                 //'club_cancelled' => $program->getCancelledTickets(),
                 'club_max' => $program->getMaxTickets(),
                 //'club_corrected' => intval($program->getSoldTickets()-$program->getCancelledTickets()),
                 //'club_disposed' => $program->getDisposedTickets()
                ];
            }
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setTitle('Ticket Check');

        $moduleTemplate->assign('Rows', $rows);

        return $moduleTemplate->renderResponse('Backend/TicketCheck');
    }

    public function ticketCheckDetailAction(): ResponseInterface
    {
        $uids = [];
        $uid = $this->request->getArgument('showUid');
        $uids[] = $uid;
        $orders = $this->productRepository->findSku($uids);
        $disposed = 0;
        $open = 0;
        $cancelled = 0;
        $pending = 0;
        $shipped = 0;
        $not_shipped = 0;

        foreach ($orders as $order) {
            switch ($order->getItem()->getPayment()->getStatus()) {
                case 'paid':
                    $disposed += $order->getCount();
                    break;
                case 'open':
                    $open += $order->getCount();
                    break;
                case 'canceled':
                    $cancelled += $order->getCount();
                    break;
                case 'pending':
                    $pending += $order->getCount();
                    break;
            }
            switch ($order->getItem()->getShipping()->getStatus()) {
                case 'shipped':
                    $shipped += $order->getCount();
                    break;
                default:
                    $not_shipped += $order->getCount();
                    break;
            }
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setTitle('Ticket Check Detail');

        $moduleTemplate->assign('Orders', $orders);
        $moduleTemplate->assign('Disposed', $disposed);

        return $moduleTemplate->renderResponse('Backend/TicketCheckDetail');
    }

    public function ticketCheckWriteAction(): void
    {
        $args = $this->request->getArguments();

        $values = [];
        $data = $args['ticketCheck'];
        foreach ($data as $key => $value) {
            if ($value['disposed'] != $value['disposed-old']) {
                $values[$key] = $value['disposed'];
            }
        }

        foreach ($values as $key => $value) {
            $program = $this->programRepository->findByUid($key);
            $program->setDisposedTickets($value);
            $this->programRepository->update($program);
        }

        $this->redirect('interface');
    }

    public function ticketExportAction(): ResponseInterface
    {
        $args = $this->request->getArguments();
        $format = $args['ticketExport']['format'] ?? 'html';
        $uids = [];
        $tickets = [];
        $failed = [];
        $filtered_tickets = [];
        $numbers = [];
        foreach ($args['ticketExport']['program'] as $prog => $uid) {
            $uids[] = $uid;
        }

        if (empty($uids)) {
            $this->addFlashMessages('Can\'t export empty data!', 'Warning', ContextualFeedbackSeverity::WARNING);
            return $this->redirect('interface');
        }

        $now = 'tomorrow - 1 second';
        $orders = $this->productRepository->findSku($uids);

        foreach ($orders as $order) {
            if ($order->getItem()->getShipping()->getStatus() == 'shipped'
                && $order->getItem()->getPayment()->getStatus() == 'paid') {
                if ($order->getCount() > 1) {
                    $ticket_number = substr($order->getProductType(), 0, -1);
                    $tickets[] = $order;
                    for ($i = 1; $i < $order->getCount(); $i++) {
                        $code = $this->addEanCheck($ticket_number += 1);
                        $orderProduct = GeneralUtility::makeInstance(
                            Product::class,
                            $order->getSku(),
                            $order->getTitle(),
                            $order->getCount()
                        );
                        $orderProduct->setProductType($code);
                        $tickets[] = $orderProduct;
                    }
                } else {
                    $tickets[] = $order;
                } // only 1 ticket
            }
        }

        foreach ($tickets as $ticket) {
            $filtered_tickets[] = $ticket;
        }

        $message = '';
        if (count($tickets) != count($filtered_tickets)) {
            $message = "Achtung: doppelte Ticketnummern vorhanden";
        }

        if ($format === 'html') { // Druckausgabe
            $names = [];
            foreach ($filtered_tickets as $object) {
                if ($object->getItem()) {
                    $names[] = $object->getItem()->getBillingAddress()->getLastName();
                }
            }

            array_multisort($names, SORT_ASC, $filtered_tickets);

            $failures = 0;
            foreach ($failed as $order) {
                if ($order->getItem()->getShipping()->getStatus() != 'shipped' ||
                    $order->getItem()->getPayment()->getStatus() != 'paid') {
                    $failures += $order->getCount();
                }
            }

            $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
            $moduleTemplate->setTitle('Ticket Export');
            $moduleTemplate->assignMultiple([
                'orders' => $filtered_tickets,
                'message' => $message,
                'failures' => $failures,
            ]);
            return $moduleTemplate->renderResponse('Backend/TicketExport');
        } else {
            $clubdataCartConfiguration = $this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
                'ClubdataCart'
            );
            $viewFactoryData = new ViewFactoryData(
                templateRootPaths: $clubdataCartConfiguration['view']['templateRootPaths'],
                partialRootPaths: $clubdataCartConfiguration['view']['partialRootPaths'],
                layoutRootPaths: $clubdataCartConfiguration['view']['layoutRootPaths'],
                request: $this->request,
                format: $format
            );
            $view = $this->viewFactory->create($viewFactoryData);
            $view->assign('orders', $filtered_tickets);
            $output = $view->render('Backend/TicketExport');

            $title = 'Scanner-Export-' . ($orders[0]?->getTitle() ?? 'Unknown');
            $filename = $title . '.' . $format;

            return $this->responseFactory->createResponse()
                ->withHeader('Content-Type', 'text/html; charset=utf-8')
                ->withHeader('Content-Description', 'File transfer')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withBody($this->streamFactory->createStream($output));
        }
    }

    public function refundCheckOrdersAction(): ResponseInterface
    {
        $args = $this->request->getArguments();
        $sku = $args['refundOrders']['program'];
        $countOrders = 0;
        if ($sku) {
            $filtered_orders = [];
            $orders = $this->itemRepository->findAll();
            foreach ($orders as $order) {
                foreach ($order->getProducts() as $product) {
                    if ($product->getSku() == $sku) {
                        if ($order->getPayment()->getStatus() == 'paid') {
                            $filtered_orders[] = $order;
                        }
                    }
                }
            }
            $countOrders = count($filtered_orders);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setTitle('Refund Check Orders');

        $moduleTemplate->assign('CountOrders', $countOrders);
        $moduleTemplate->assign('Sku', $sku ?? '');

        return $moduleTemplate->renderResponse('Backend/RefundCheckOrders');
    }

    public function refundOrdersAction(): ResponseInterface
    {
        $args = $this->request->getArguments();
        $sku = $args['refundOrders']['program']; // find expects list
        if ($sku) {
            $filtered_orders = [];
            $orders = $this->itemRepository->findAll();
            foreach ($orders as $order) {
                foreach ($order->getProducts() as $product) {
                    if ($product->getSku() == $sku) {
                        if ($order->getPayment()->getStatus() == 'paid') {
                            $filtered_orders[] = $order;
                        }
                    }
                }
            }
            foreach ($filtered_orders as $order) {
                $this->handleRefund($order, $sku);
            }
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setTitle('Refund Orders');

        return $moduleTemplate->renderResponse('Backend/RefundOrders');
    }

    public function refundOrderAction(Item $orderItem): ResponseInterface
    {
        if ($orderItem->getPayment()->getStatus() == 'paid') {
            $this->handleRefund($orderItem);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setTitle('Refund Order');

        return $moduleTemplate->renderResponse('Backend/RefundOrder');
    }

    public function handleRefund(Item $orderItem, $sku = ''): void
    {
        $payment = $orderItem->getPayment();
        $provider = $payment->getProvider();

        $data = [
            'orderItem' => $orderItem,
            'provider' => $provider,
            'providerUsed' => false,
            'sku' => $sku
        ];

        // TODO: Replace with PSR-14 Event when available
        // For now, call payment provider directly if available
        // $event = new RefundEvent($data);
        // $this->eventDispatcher->dispatch($event);
        exit;
    }

    protected function addEanCheck($code)
    {
        $key = 0;
        $mult = [1, 3];

        for ($i = 0; $i < strlen($code); $i++) {
            $key += substr($code, $i, 1) * $mult[$i % 2];
        }

        $key = 10 - ($key % 10);

        if ($key == 10) {
            $key = 0;
        }

        // in key steht die prüfziffer - an den code anhängen
        $code .= $key;
        return $code;
    }

    private function addFlashMessages($message, $header, $severity)
    {
        $message = GeneralUtility::makeInstance(FlashMessage::class,
           $message,
           $header,
           $severity,
           true
        );
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage($message);
    }
}
