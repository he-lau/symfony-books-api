<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Book;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\AbstractList;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\ValidValidator;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

//use Symfony\Component\Serializer\SerializerInterface;
//use Symfony\Component\Serializer\Serializer;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;

class BookController extends AbstractController
{

    /**
     * TODO : 
     *  - pagination avec message d'erreur
     *  - KnpPaginator , PagerFanta
     */
    #[Route('api/books', name: 'list_book',methods:['GET'])]
    public function getBookList(BookRepository $bookRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        // $_GET
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        // generer id avec page & limit
        $idCache = "getAllBooks-" . $page . "-" . $limit;
        $dataSource = 'cache';

        // recuperer en cache le resultat si existant, sinon callback
        $jsonBooks = $cache->get($idCache, function(ItemInterface $item) use ($bookRepository, $page, $limit, $serializer) {
            
            // tag pour permettre la gestion plus tard
            $item->tag("booksCache");
            $item->expiresAfter(1);

            $dataSource = 'database';
            header('X-Data-Source: ' . $dataSource);

            $books = $bookRepository->findAllWithPagination($page,$limit);
            $context = SerializationContext::create()->setGroups(['getBooks']);

            //return $serializer->serialize($books,"json",['groups'=>'getBooks']);
            return $serializer->serialize($books,"json",$context);
        });

    // Vérifier si le header X-Data-Source est déjà défini
    $existingDataSourceHeader = headers_list();
    if (!in_array('X-Data-Source: database', $existingDataSourceHeader)) {
        header('X-Data-Source: ' . $dataSource);
    }

        return new JsonResponse($jsonBooks, Response::HTTP_OK, [], true);
    }

    #[Route('/api/books/{id}', name: 'detail_book', methods: ['GET'])]
    public function getDetailBook(int $id, SerializerInterface $serializer, BookRepository $bookRepository): JsonResponse {

        $book = $bookRepository->find($id);

        if ($book) {
            $context = SerializationContext::create()->setGroups(['getBooks']);
            //$jsonBook = $serializer->serialize($book, 'json',['groups'=>'getBooks']);
            $jsonBook = $serializer->serialize($book, 'json',$context);
            return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
        } 

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
   }

   #[Route('/api/books/{id}', name: 'delete_book', methods: ['DELETE'])]
   public function deleteBook(Book $book, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse 
   {
        $cache->invalidateTags(['booksCache']);

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
        $context = SerializationContext::create()->setGroups(['getBooks']);
        $jsonBook = $serializerInterface->serialize($book,'json',$context);
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
    ValidatorInterface $validator,
    TagAwareCacheInterface $cache
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
    $newBook = $serializerInterface->deserialize(
        $rawContent,
        Book::class,
        'json'
    );

    $toUpdateBook->setTitle($newBook->getTitle());
    $toUpdateBook->setCoverText($newBook->getCoverText());

    

    // Valider les données désérialisées
    $errors = $validator->validate($toUpdateBook);

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
            $toUpdateBook->addAuthor($author);
        }
    }

    // orm query
    $entityManagerInterface->persist($toUpdateBook);
    $entityManagerInterface->flush();

    // On vide le cache. 
    $cache->invalidateTags(["booksCache"]);

    return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);

   }

}