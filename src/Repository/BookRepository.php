<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }
    
    /**
     * Récuperer les livres avec pagination
     * @param int $page
     * @param int $limit
     * @return mixed 
     */

    public function findAllWithPagination($page, $limit) {

        // qyery avec offset + limit
        $qb = $this->createQueryBuilder('b')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $query = $qb->getQuery();
        
        // 
        //$query->setFetchMode(Book::class, "author", \Doctrine\ORM\Mapping\ClassMetadata::FETCH_EAGER);    

        return $query->getResult();
    }
}
