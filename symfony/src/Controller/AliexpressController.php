<?php

namespace App\Controller;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AliexpressController extends AbstractController
{
    /**
     * @Route("/aliexpress", name="aliexpress")
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        set_time_limit(300);
        $url = $request->get('url', null);
        $host = 'http://firefox:4444/wd/hub';
        $desired_capabilities = DesiredCapabilities::firefox();
        $driver = RemoteWebDriver::create($host, $desired_capabilities, 120000);
        $driver->get($url);
        sleep(3);
        $content = $driver->getPageSource();
        $driver->close();

        $xpath = $this->getXpath($content);

        $lowPriceQuery = $xpath->query("//span[@itemprop='lowPrice']");
        $lowPrice = $lowPriceQuery->length > 0 ? $lowPriceQuery->item(0)->nodeValue : null;

        $highPriceQuery = $xpath->query("//span[@itemprop='highPrice']");
        $highPrice = $highPriceQuery->length > 0 ? $highPriceQuery->item(0)->nodeValue : null;

        $currencyQuery = $xpath->query("//div[@class='p-price-content notranslate']//span[@class='p-symbol']");
        $currency = $currencyQuery->length > 0 ? $currencyQuery->item(0)->nodeValue : null;

        $productNameQuery = $xpath->query("//h1[@class='product-name']");
        $productName = $productNameQuery->length > 0 ? $productNameQuery->item(0)->nodeValue : null;

        $colorListQuery = $xpath->query("//ul[@id='j-sku-list-1']/li");
        $colorList = [];
        /** @var \DOMElement $color */
        foreach ($colorListQuery as $color) {
            $colorList[] = $color->getElementsByTagName('img')->item(0)->getAttribute('src');
        }
        $colors = implode('&nbsp;', array_map(function ($color) {
            return "<img src='{$color}'>";
        }, $colorList));


        $imageListQuery = $xpath->query("//ul[@id='j-image-thumb-list']/li/span/img");
        $imageList = [];
        /** @var \DOMElement $image */
        foreach ($imageListQuery as $image) {
            $imageList[] = $image->getAttribute('src');
        }
        $images = implode('&nbsp;', array_map(function ($image) {
            return "<img src='{$image}'>";
        }, $imageList));

        $sizeListQuery = $xpath->query("//ul[@id='j-sku-list-2']/li/a/span");
        $sizeList = [];
        /** @var \DOMElement $size */
        foreach ($sizeListQuery as $size) {
            $sizeList[] = $size->nodeValue;
        }
        $sizes = implode(',', $sizeList);



        $html = "
        Product name: <b>{$productName}</b> <br>
        Currency: {$currency} <br>
        Low Price: {$lowPrice} <br>
        High Price: {$highPrice} <br>
        Sizes: {$sizes} <br>
        Colors: <br>
        {$colors} <br>
        Images: <br>
        {$images}
        ";




        return new JsonResponse([
            'lowPrice' => $lowPrice,
            'highPrice' => $highPrice,
            'currency' => $currency,
            'colorList' => $colorList,
            'sizeList' => $sizeList,
            'imageList' => $imageList,
            'html' => $html
        ]);
    }

    /**
     * @param string $content
     * @return \DOMXPath
     */
    private function getXpath(string $content): \DOMXPath
    {
        $doc = new \DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        $doc->loadHTML($content);
        libxml_use_internal_errors($internalErrors);
        return new \DOMXPath($doc);
    }
}
