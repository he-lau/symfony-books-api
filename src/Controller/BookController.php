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
use phpDocumentor\Reflection\Types\AbstractList;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\ValidValidator;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
   #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un livre')]
   public function createBook(
    Request $request,
    SerializerInterface $serializerInterface,
    EntityManagerInterface $entityManagerInterface, 
    UrlGeneratorInterface $urlGeneratorInterface,
    AuthorRepository $authorRepository ,
    ValidatorInterface $validatorInterface
    ): JsonResponse 
   {
    
        // Récupérer les données $_POST  
        $book = $serializerInterface->deserialize($request->getContent(),Book::class,'json');

        // On vérifie les erreurs
        $errors = $validatorInterface->validate($book);

        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [],true);
            //throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "La requête est invalide");
        }        

        // Data POST en array
        $content = $request->toArray();

        if(isset($content['idAuthors']) && is_array($content['idAuthors'])) {
            $idAuthors = $content['idAuthors'];
    
            // Ajoutez les auteurs
            foreach($idAuthors as $id) {
                $author = $authorRepository->find($id);
                $book->addAuthor($author);
            }
        }

        // Insertion en BDD
        $entityManagerInterface->persist($book);
        $entityManagerInterface->flush();

        // Réponse
        $jsonBook = $serializerInterface->serialize($book,'json',['groups'=>'getBooks']);
        $location = $urlGeneratorInterface->generate('detail_book',['id'=>$book->getId()],UrlGeneratorInterface::ABSOLUTE_PATH);

        return new JsonResponse($jsonBook,Response::HTTP_CREATED,['Location'=>$location],true);

   }

   #[Route('/api/books/{id}',name:'update_book',methods:['PUT'])]
   public function updateBook
   (
    Book $toUpdateBook,
    Request $request,
    SerializerInterface $serializerInterface,
    EntityManagerInterface $entityManagerInterface, 
    AuthorRepository $authorRepository,
    ValidatorInterface $validator
   ) : JsonResponse {

    $rawContent = $request->getContent();

    $data = json_decode($rawContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "Invalid JSON data");
    }

    $requiredKeys = ['title', 'cover_text']; 
    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $data)) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "Missing required field: $key");
        }
    }

    // Récuperer les données et deserialisation
    $updatedBook = $serializerInterface->deserialize(
        $rawContent,
        Book::class,
        'json',
        [AbstractNormalizer::OBJECT_TO_POPULATE => $toUpdateBook]
    );

    // Valider les données désérialisées
    $errors = $validator->validate($updatedBook);

    // Si des erreurs de validation sont présentes, renvoyer une réponse d'erreur
    if ($errors->count() > 0) {
        throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $serializerInterface->serialize($errors, 'json'));
    }

    // Récuperer id de l'auteur + maj 
    $content = $request->toArray();

    if(isset($content['idAuthors']) && is_array($content['idAuthors'])) {
        $idAuthors = $content['idAuthors'];

        // Vider les auteurs
        foreach ($toUpdateBook->getAuthors() as $author) {
            $toUpdateBook->removeAuthor($author);
        }

        // Ajoutez les auteurs
        foreach($idAuthors as $id) {
            $author = $authorRepository->find($id);
            $updatedBook->addAuthor($author);
        }
    }

    // orm query
    $entityManagerInterface->persist($updatedBook);
    $entityManagerInterface->flush();

    return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);

   }

}