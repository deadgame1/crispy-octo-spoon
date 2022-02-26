<?php

namespace App\Service;

class StockService
{
    public function getStockMeanPrice(array $stockPrices)
    {
        $mean = array_sum($stockPrices)/count($stockPrices);
        return number_format($mean, 2, '.','');
    }   

    public function getStockStandardDeviation(array $stockPrices)
    {
        $variance = 0.0;
        $stockMean = $this->getStockMeanPrice($stockPrices);
        foreach($stockPrices as $price)
        {
            $variance += pow(($price - $stockMean), 2);
        }

        $sd=(float)sqrt($variance/count($stockPrices));
        return number_format($sd, 4, '.','');
    }
}