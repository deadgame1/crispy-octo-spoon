<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Entity\StockPrices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use League\Csv\Reader;
use League\Csv\Statement;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Repository\StockRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class CsvController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    /**
     * @Route("/postCsv", name="app_csv", methods="post")
     */
    public function index(Request $request, StockRepository $stockRepo): Response
    {
        $csv = $request->files->get('csv');
        if(!$csv || $csv->getClientMimeType() != 'text/csv')
        {
            return $this->json(['message'=>'Bad CSV'],400);
        }
        
        $csv = Reader::createFromPath($csv->getRealPath(), 'r');
        $csv->setHeaderOffset(0); //set the CSV header offset
        $stmt = Statement::create();

        $records = $stmt->process($csv);

        //clear existing database records
        $this->truncateEntities([StockPrices::class,Stock::class]);

        $stocksAdded=[];
        foreach ($records as $record) {
            $stockExists=$stockRepo->findOneBy(['name' => strtolower($record['stock_name'])]);
            if(!$stockExists)
            {
                $stock = new Stock();
                $stock->setName(strtolower($record['stock_name']));
                $stocksAdded[] = $record['stock_name'];
                $this->em->persist($stock);
                $this->em->flush();
            }
            else
            {
                $stock = $stockExists;
            }

            $date = $this->createDate($record['date']);
            if(!$date)
            {
                continue; //if date could not be generated then skip this record
            }
            $price = new StockPrices();
            $price->setPrice($record['price'])
                ->setStock($stock)
                ->setDate($date);
            $this->em->persist($price);
        }
        $this->em->flush();

        return $this->json(['message'=>'ok','allStocks' => $stocksAdded]);
    }

    /**
     * Helper fn to delete the existing database records, if user uploads a new csv
     */
    private function truncateEntities(array $entities)
    {
        $connection = $this->em->getConnection();
        $databasePlatform = $connection->getDatabasePlatform();
        // if ($databasePlatform->supportsForeignKeyConstraints()) {
        //     $connection->query('SET FOREIGN_KEY_CHECKS=0');
        // }
        foreach ($entities as $entity) {
            $query = $databasePlatform->getTruncateTableSQL(
                $this->em->getClassMetadata($entity)->getTableName(),true
            );
            $connection->executeUpdate($query);
        }
        // if ($databasePlatform->supportsForeignKeyConstraints()) {
        //     $connection->query('SET FOREIGN_KEY_CHECKS=1');
        // }
    }

    /**
     * Function to create a date object from a date string
     * Tries different date formats in the order of their priority
     * 
     */
    private function createDate(string $date)
    {
        $date1Format = \DateTime::createFromFormat('d-m-Y',$date);
        $date2Format = \DateTime::createFromFormat('d-m-Y H:i:s',$date);
        $date3Format = \DateTime::createFromFormat('d:m:Y',$date);
        $date4Format = \DateTime::createFromFormat('d:m:Y H:i:s',$date);
        $date5Format = \DateTime::createFromFormat('d/m/Y',$date);
        $date6Format = \DateTime::createFromFormat('d/m/Y H:i:s',$date);

        if($date1Format)
        {
            return $date1Format;
        }
        else if($date2Format)
        {
            return $date2Format;
        }
        else if($date3Format)
        {
            return $date3Format;
        }
        else if($date4Format)
        {
            return $date4Format;
        }
        else if($date5Format)
        {
            return $date5Format;
        }
        else if($date6Format)
        {
            return $date6Format;
        }
        else
        {
            return false;
        }
    }
}
