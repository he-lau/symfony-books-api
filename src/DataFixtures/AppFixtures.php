<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Book;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use DateTime;


class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 20; $i++) {
            $livre = new Book();
            $livre->setTitle('Livre ' . $i);
            $livre->setCoverText('Quatrième de couverture numéro : ' . $i);
            $livre->setPublishDate(new DateTime($this->generateRandomDate("1900-01-01")));

            $authorReference = $this->getReference("author-" . rand(0, 9));
            $livre->addAuthor($authorReference);

            $manager->persist($livre);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            AuthorFixtures::class,
        ];
    }

    public function generateRandomDate($start, $end = null) {
        // Convert the start date to a timestamp
        $startTimestamp = strtotime($start);
        
        // Use today's date as the end date if not specified
        if ($end === null) {
            $end = date("Y-m-d");
        }
        
        // Convert the end date to a timestamp
        $endTimestamp = strtotime($end);
    
        // Ensure that the start date is before the end date
        if ($startTimestamp > $endTimestamp) {
            // Swap the dates if the start date is after the end date
            $temp = $startTimestamp;
            $startTimestamp = $endTimestamp;
            $endTimestamp = $temp;
        }
    
        // Generate a random timestamp between the start and end timestamps
        $randomTimestamp = mt_rand($startTimestamp, $endTimestamp);
    
        // Convert the random timestamp to a date
        $randomDate = date("Y-m-d", $randomTimestamp);
    
        return $randomDate;
    }    
    
}
