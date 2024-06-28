<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Book;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 20; $i++) {
            $livre = new Book();
            $livre->setTitle('Livre ' . $i);
            $livre->setCoverText('Quatrième de couverture numéro : ' . $i);

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
}
