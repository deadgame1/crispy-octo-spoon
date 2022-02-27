<?php

namespace App\Service;

class StockService
{
    /**
     * Calculates arithmetic mean from array of stock prices
     */
    public function getStockMeanPrice(array $stockPrices)
    {
        $mean = array_sum($stockPrices)/count($stockPrices);
        return number_format($mean, 2, '.','');
    }   

    /**
     * Calculates standard deviation mean from array of stock prices
     */
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

    /**
     * calculates stock advice from stock data
     * @param array $stockData data structure in the form of ['date'=>date1,'price'=>stockPrice1],['date'=>date2,'price'=>stockPrice2]
     * stock advice consists of when to buy and sell
     * and total profit/loss made from stocks
     */
    public function stockStatistics(array $stockData, $stockQuantity)
    {
        // Prices must be given for at least two days
        $count = count($stockData);
        if ($count == 1)
        {
            return;
        }
        
        // Traverse through given price array
        $i = 0;
        $totalProfit=0;
        $transactionalData=[];
        while ($i < $count - 1) {

            while (($i < $count - 1) && ($stockData[$i + 1]['price'] <= $stockData[$i]['price']))
            {//finding Local minima by comparing current index with next Price
                $i++;
            }   

            if ($i == $count - 1)
            {
                break; //end if i has reached the end of prices array
            } 

            $buy = $i++;

            while (($i < $count) && ($stockData[$i]['price'] >= $stockData[$i - 1]['price']))
            {//Local Maxima by comparing current index with previous price
                $i++;
            }
                
            $sell = $i - 1;
            $profit=$stockQuantity*($stockData[$sell]['price']-$stockData[$buy]['price']);
            $totalProfit+=$profit;

            $transactionalData[] = "Buy on {$stockData[$buy]['date']} for Rs {$stockData[$buy]['price']}, Sell on {$stockData[$sell]['date']} for Rs {$stockData[$sell]['price']}, for a profit of Rs {$profit}";
            // echo "Buy on day: $buy <br>";
            // echo "Sell on day: $sell <br>";
            // echo "For profit of $profit <br>";
        }

        if(count($transactionalData) == 0)
        {
            $transactionalData[] = 'No transactions possible in which profit can be made';
        }

        return [
            'totalProfit'=>$totalProfit,
            'transactionalData'=>$transactionalData
        ];
    }
}