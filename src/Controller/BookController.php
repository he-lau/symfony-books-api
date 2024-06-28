<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Book;

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
}
