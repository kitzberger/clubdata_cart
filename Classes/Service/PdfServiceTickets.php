<?php

namespace Medpzl\ClubdataCart\Service;

use Extcode\Cart\Domain\Model\Order\Item as OrderItem;
use Medpzl\ClubdataCart\Utility\OrderUtility;
use TYPO3\CkClubdata\Domain\Repository\ProgramRepository;

class PdfServiceTickets extends \Extcode\CartPdf\Service\PdfService
{
    public function __construct(
        protected ProgramRepository $programRepository
    ) {
    }

    /**
     * @param string $pdfType
     * @param \Extcode\Cart\Domain\Model\Order\Item $orderItem
     */
    protected function renderPdf(OrderItem $orderItem, string $pdfType): void
    {
        $pluginSettings = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'cartpdf'
        );

        $this->pdf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Extcode\TCPDF\Service\TsTCPDF::class
        );
        $this->pdf->setSettings($pluginSettings);
        $this->pdf->setCartPdfType($pdfType . 'Pdf');

        if ($pdfType == 'delivery') {
            $this->pdf->setPrintHeader(false);
        } else {
            if ($pdfType == 'delivery') {
                $this->pdf->SetMargins(PDF_MARGIN_LEFT, $this->pdfSettings['ticket']['ticket']['margin-top'], $this->pdfSettings['ticket']['ticket']['margin-right']);
            } else {
                $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            }
        }
        if (!$this->pdfSettings['footer']) {
            $this->pdf->setPrintFooter(false);
        } else {
            if ($this->pdfSettings['footer']['margin']) {
                $this->pdf->setFooterMargin($this->pdfSettings['footer']['margin']);
                $this->pdf->setAutoPageBreak(true, $this->pdfSettings['footer']['margin']);
            } elseif ($this->pdfSettings['ticket']['ticket']) {
                $this->pdf->setAutoPageBreak(true, $this->pdfSettings['ticket']['ticket']['margin-bottom']);
            } else {
                $this->pdf->setAutoPageBreak(true, PDF_MARGIN_BOTTOM);
            }
        }
        if ($pdfType == 'delivery') {
            $this->pdf->setAutoPageBreak(false, 0);
            $this->pdf->AddPage('L', [210,99]);
        } else {
            $this->pdf->AddPage();
        }

        $font = 'Helvetica';
        if ($this->pdfSettings['font']) {
            $font = $this->pdfSettings['font'];
        }

        $fontStyle = '';
        if ($this->pdfSettings['fontStyle']) {
            $fontStyle = $this->pdfSettings['fontStyle'];
        }

        $fontSize = 8;
        if ($this->pdfSettings['fontSize']) {
            $fontSize = $this->pdfSettings['fontSize'];
        }

        $this->pdf->SetFont($font, $fontStyle, $fontSize);

        $colorArray = [0, 0, 0];
        if ($this->pdfSettings['drawColor']) {
            $colorArray = explode(',', $this->pdfSettings['drawColor']);
        }
        $this->pdf->setDrawColorArray($colorArray);
        if ($pdfType == 'delivery') {
            $this->renderTicket($pdfType, $orderItem);
        } else {
            $this->renderMarker();

            if ($this->pdfSettings['letterhead']['html']) {
                foreach ($this->pdfSettings['letterhead']['html'] as $partName => $partConfig) {
                    $templatePath = '/' . ucfirst($pdfType) . 'Pdf/Letterhead/';
                    $assignToView = ['orderItem' => $orderItem];
                    $this->pdf->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
                }
            }

            if ($this->pdfSettings['body']['before']['html']) {
                foreach ($this->pdfSettings['body']['before']['html'] as $partName => $partConfig) {
                    $templatePath = '/' . ucfirst($pdfType) . 'Pdf/Body/Before/';
                    $assignToView = ['orderItem' => $orderItem];
                    $this->pdf->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
                }
            }

            $this->renderCart($pdfType, $orderItem);

            if ($this->pdfSettings['body']['after']['html']) {
                foreach ($this->pdfSettings['body']['after']['html'] as $partName => $partConfig) {
                    $templatePath = '/' . ucfirst($pdfType) . 'Pdf/Body/After/';
                    $assignToView = ['orderItem' => $orderItem];
                    $this->pdf->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
                }
            }
        }

        $pdfFilename = '/tmp/tempfile.pdf';

        $this->pdf->Output($pdfFilename, 'F');
    }

    /**
     * @param string $pdfType
     * @param \Extcode\Cart\Domain\Model\Order\Item $orderItem
     */
    protected function renderTicket($pdfType, $orderItem)
    {
        $pdfType .= 'Pdf';

        $config = $this->pdfSettings['body']['order'];
        $config['height'] = 0;

        if (!$config['spacingY'] && !$config['positionY']) {
            $config['spacingY'] = 5;
        }

        $content = $this->renderTicketBody($pdfType, $orderItem);
    }

    /**
     * Render Cart Body
     *
     * @param string $pdfType
     * @param \Extcode\Cart\Domain\Model\Order\Item $orderItem
     *
     * @return string
     */
    protected function renderTicketBody($pdfType, $orderItem)
    {
        $bodyOut = '';
        $ypos = 30;
        $anz = 0;
        foreach ($orderItem->getProducts() as $product) {
            $program = $this->programRepository->findByUid($product->getSku());
            $base = substr($product->getproductType(), 0, -1);
            $code = $product->getproductType();
            for ($i = 0; $i < $product->getCount(); $i++) {
                $mod = false;
                //if($i+1 % 4 == 0) {
                if ($anz) {
                    $mod = true;
                    $this->pdf->addPage('L', [210,99]);
                    $ypos = 30;
                }
                if ($i) {
                    $code = OrderUtility::addEanCheck($base += 1);
                }

                //define barcode style
                $style = [
                    'position' => '',
                    'align' => 'C',
                    'stretch' => false,
                    'fitwidth' => true,
                    'cellfitalign' => '',
                    'border' => false,
                    'hpadding' => 'auto',
                    'vpadding' => 'auto',
                    'fgcolor' => [0, 0, 0],
                    'bgcolor' => false, //array(255,255,255),
                    'text' => true,
                    'font' => 'helvetica',
                    'fontsize' => 8,
                    'stretchtext' => 4
                ];
                foreach ($this->pdfSettings['ticket'] as $partName => $partConfig) {
                    if ($anz && !$mod) {
                        $partConfig['positionY'] = $anz * 80;
                    }
                    $templatePath = '/TicketPdf/';
                    $assignToView = ['product' => $product, 'program' => $program, 'config' => $partConfig,'code' => $code, 'orderItem' => $orderItem];

                    $this->pdf->renderStandaloneView($templatePath, $partName, $partConfig, $assignToView);
                }

                $this->pdf->write1DBarcode($code, 'EAN13', '150', $ypos, '', 18, 0.4, $style, 'N');
                $anz++;
            }
        }

        return $bodyOut;
    }
}
