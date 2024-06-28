<?php
namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Author;

class AuthorFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; $i++) {
            $author = new Author();
            $author->setFirstName("Prénom " . $i);
            $author->setLastName("Nom " . $i);

            $manager->persist($author);

            // Reference each author by a unique key
            $this->addReference("author-$i", $author);
        }

        $manager->flush();
    }
}
