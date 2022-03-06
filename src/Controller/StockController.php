<?php

namespace App\Controller;

use App\Repository\StockPricesRepository;
use App\Repository\StockRepository;
use App\Service\StockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityManager;

class StockController extends AbstractController
{
    /** @var EntityManagerInterface*/
    private $em;

    /** @var StockService */
    private $stockService;

    public function __construct(EntityManagerInterface $em, StockService $stockService)
    {
        $this->em = $em;
        $this->stockService=$stockService;
    }

    /**
     * @Route("/stockAdvice", name="app_stock", methods="get")
     */
    public function index(Request $request, StockRepository $stockRepo, StockPricesRepository $pricesRepo)
    {
        $stockName = strtolower($request->query->get('stock'));
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        if(!$stockName || !$startDate || !$endDate)
        {
            return $this->json(['error' => 'Please provide stock name, startDate and endDate']);
        }
        $startDateObj = \DateTime::createFromFormat('Y-m-d',$startDate);
        $startDateObj->setTime(0,0,0);
        $endDateObj = \DateTime::createFromFormat('Y-m-d',$endDate);
        $endDateObj->setTime(23,59,59);
        if($startDateObj > $endDateObj)
        {
            return $this->json(['error' => 'Start date cannot be after end date']);
        }
        $stockExists = $stockRepo->findOneBy(['name' => $stockName]);
        if(!$stockExists)
        {
            return $this->json(['error' => 'Stock not found'],404);
        }

        $pricesQb=$pricesRepo->createQueryBuilder('p')
            ->where('p.stock = :stock')
            ->andWhere('p.date >= :startDate')
            ->andWhere('p.date <= :endDate')
            ->setParameter('stock', $stockExists)
            ->setParameter('startDate', $startDateObj)
            ->setParameter('endDate', $endDateObj)
            ->orderBy('p.date','ASC');

        $query = $pricesQb->getQuery();
        $results = $query->execute();
        if(!$results || count($results) == 0)
        {
            return $this->json(['error' => 'No stock prices found for the given duration'],404);
        }
        
        $noOfstocks=$request->query->get('noOfStocks',200); //default 200 shares

        $data = array_map(function ($record){
            return ['date'=>$record->getDate()->format('jS M Y'),'price'=>$record->getPrice()];
        },$results);
        $prices=array_column($data,'price');

        if(count($prices) <= 1)
        {
            return $this->json(['error' => 'Stock price available for only 1 day, no stats possible', 'stockPrices' => $prices]);
        }

        //Statistics
        $stockStats = $this->stockService->stockStatistics($data,$noOfstocks);
        //Arithmetic mean
        $meanStockPrice = $this->stockService->getStockMeanPrice($prices);
        //Standard Deviation
        $sd = $this->stockService->getStockStandardDeviation($prices);

        $result = [
            'name' => $stockExists->getName(),
            'transactionalData' => $stockStats['transactionalData'],
            'totalProfit' => $stockStats['totalProfit'],
            'meanPrice' => $meanStockPrice,
            'standardDeviation' => $sd,
        ];

        // echo '<pre>';
        // var_dump($prices);
        // echo '<br>';
        // var_dump($result);exit

        return $this->json($result);
    }

    /**
     * @Route("/homepage", name="stock_home", methods="get")
     */
    public function homepage()
    {
        return $this->render('index.html.twig');
    }

    //To Do
    //front end - most probably in twig and simple javascript
    //nice to have features
    //deployment
    //unit tests
    //


    //Done
    //1. symfony setup
    //2, git repo setup
    //3. DB schema
    //4. CSV upload
    //5. Almost all requirements related to stock stats [stock controller APIs and stock service]
}
