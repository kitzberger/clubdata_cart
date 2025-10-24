<?php

namespace Medpzl\ClubdataCart\Controller;

use Extcode\Cart\Domain\Model\Order\Item;
use Extcode\Cart\Domain\Repository\Order\ItemRepository;
use Medpzl\ClubdataCart\Domain\Model\Order\Product;
use Medpzl\ClubdataCart\Domain\Repository\Order\ProductRepository;
use Medpzl\ClubdataCart\Domain\Repository\PauseRepository;
use Medpzl\Clubdata\Domain\Repository\ProgramRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        ConfigurationManagerInterface $configurationManager
    ) {
    }

    protected function initializeAction(): void
    {
        $cartConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Cart'
        );

        $this->settings['cart']['pid'] = $cartConfiguration['settings']['cart']['pid'];
    }

    public function interfaceAction(): ResponseInterface
    {
        $this->now = $this->settings['scanner']['showFrom'];

        $filtered_programs_export = $this->filterData();

        $this->view->assign('ExportPrograms', $filtered_programs_export);

        $usercheck = false;
        $denies = explode(',', $this->settings['refund']['denyGroups']);
        $groups = $GLOBALS['BE_USER']->user['usergroup'];

        foreach ($denies as $deny) {
            if (strpos($groups, $deny) !== false) {
                $usercheck = true;
            }
        }
        if (!$usercheck) {
            $this->now = $this->settings['refund']['showFrom'];
            $filtered_programs_refund = $this->filterData();
            $this->view->assign('RefundPrograms', $filtered_programs_refund);
        }
        $options = [];
        $options[] = [
            'id' => 'future',
            'title' => 'zukünftige'
        ];
        $options[] = [
            'id' => 'all',
            'title' => 'alle'
        ];

        $this->view->assign('Options', $options);
        return $this->htmlResponse();
    }

    protected function filterData()
    {
        $programs = $this->programRepository->findWithinMonth([], 0, 0, 1, $this->now);
        $uids = [];

        foreach ($programs as $program) {
            $uids[] = $program->getUid();
        }

        $uids = array_unique($uids);

        $orders = $this->productRepository->findSku($uids);

        $filtered = [];
        $failed = [];
        foreach ($orders as $order) {
            $item = $order->getItem();
            if ($item->getShipping()->getStatus() == 'shipped' &&
                $item->getPayment()->getStatus() == 'paid') {
                $filtered[] = $order;
            } else {
                $failed[] = $order;
            }
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

        $this->view->assign('Rows', $rows);
        return $this->htmlResponse();
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
        $this->view->assign('Orders', $orders);
        $this->view->assign('Disposed', $disposed);
        return $this->htmlResponse();
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
        $format = $args['ticketExport']['format'];
        $uids = [];
        $tickets = [];
        $failed = [];
        $filtered_tickets = [];
        $numbers = [];
        foreach ($args['ticketExport']['program'] as $prog => $uid) {
            $uids[] = $uid;
        }

        $now = 'tomorrow - 1 second';
        $orders = $this->productRepository->findSku($uids);

        if ($format == 'txt') {
            $title = 'Scanner-Export-' . $orders[0]->getTitle();
            $filename = $title . '.' . $format;

            $this->response->setHeader('Content-Type', 'text/' . $format, true);
            $this->response->setHeader('Content-Description', 'File transfer', true);
            $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);

            $this->view = GeneralUtility::makeInstance(StandaloneView::class);
            $this->view->setTemplatePathAndFilename('EXT:laboratorium/Resources/Private/Extensions/clubdata_cart/Templates/Backend/TicketExport.csv');
        }

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

        if ($format != 'txt') { // Druckausgabe
            $names = [];
            foreach ($filtered_tickets as $object) {
                if ($object->getItem()) {
                    $names[] = $object->getItem()->getBillingAddress()->getLastName();
                }
            }

            array_multisort($names, SORT_ASC, $filtered_tickets);

            $fehler = 0;
            foreach ($failed as $order) {
                if ($order->getItem()->getShipping()->getStatus() != 'shipped' ||
                    $order->getItem()->getPayment()->getStatus() != 'paid') {
                    $fehler += $order->getCount();
                }
            }
        }
        $this->view->assign('Orders', $filtered_tickets);
        $this->view->assign('Message', $message);
        $this->view->assign('Fehler', $fehler);

        return $this->htmlResponse();
    }

    public function refundCheckOrdersAction(): ResponseInterface
    {
        $args = $this->request->getArguments();
        $sku = $args['refundOrders']['program'];
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
            $this->view->assign('CountOrders', count($filtered_orders));
            $this->view->assign('Sku', $sku);
        }
        return $this->htmlResponse();
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
        return $this->htmlResponse();
    }

    public function refundOrderAction(Item $orderItem): ResponseInterface
    {
        if ($orderItem->getPayment()->getStatus() == 'paid') {
            $this->handleRefund($orderItem);
        }
        return $this->htmlResponse();
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
}
