<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Book;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Serializer;

class BookController extends AbstractController
{
    #[Route('api/books', name: 'list_book',methods:['GET'])]
    public function getBookList(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
    {
        $books = $bookRepository->findAll();
        $jsonBooks = $serializer->serialize($books,"json",['groups'=>'getBooks']); 

        return new JsonResponse($jsonBooks, Response::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}', name: 'detail_book', methods: ['GET'])]
    public function getDetailBook(int $id, SerializerInterface $serializer, BookRepository $bookRepository): JsonResponse {

        $book = $bookRepository->find($id);

        if ($book) {
            $jsonBook = $serializer->serialize($book, 'json',['groups'=>'getBooks']);
            return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
   }

   #[Route('/api/books/{id}', name: 'delete_book', methods: ['DELETE'])]
   public function deleteBook(Book $book, EntityManagerInterface $em): JsonResponse 
   {
        $em->remove($book);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
   }


   // https://symfony.com/doc/current/components/http_foundation.html
   #[Route('/api/books',name:'create_book',methods:['POST'])]
   public function createBook(
    Request $request,
    SerializerInterface $serializerInterface,
    EntityManagerInterface $entityManagerInterface, 
    UrlGeneratorInterface $urlGeneratorInterface,
    AuthorRepository $authorRepository ): JsonResponse 
   {
    
        // Récupérer les données $_POST  
        $book = $serializerInterface->deserialize($request->getContent(),Book::class,'json');

        // Data POST en array
        $content = $request->toArray();

        // Retrouvé l'auteur
        $idAuthor = $content['idAuthor'] ? $content['idAuthor'] : -1;
        $author = $authorRepository->find($idAuthor);

        // Si existe, setter
        if($author) {
            $book->addAuthor($author);
        }

        // Insertion en BDD
        $entityManagerInterface->persist($book);
        $entityManagerInterface->flush();

        // Réponse
        $jsonBook = $serializerInterface->serialize($book,'json',['groups'=>'getBooks']);
        $location = $urlGeneratorInterface->generate('detail_book',['id'=>$book->getId()],UrlGeneratorInterface::ABSOLUTE_PATH);

        return new JsonResponse($jsonBook,Response::HTTP_CREATED,['Location'=>$location],true);

   }
}
