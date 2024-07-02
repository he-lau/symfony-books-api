<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthorController extends AbstractController
{
    #[Route('api/authors', name: 'list_author',methods:['GET'])]
    public function getAllAuthor(AuthorRepository $authorRepository,SerializerInterface $serializerInterface): JsonResponse
    {

        $authors = $authorRepository->findAll();
        $jsonAuthors = $serializerInterface->serialize($authors,"json",['groups'=>'getAuthors']);

        return new JsonResponse($jsonAuthors,Response::HTTP_OK,[],true);
    }

    #[Route('api/authors/{id}', name: 'detail_author',methods:['GET'])]
    public function getAuthor(Author $author, AuthorRepository $authorRepository,SerializerInterface $serializerInterface): JsonResponse
    {

        if ($author !== null) {
            $jsonBook = $serializerInterface->serialize($author, 'json',['groups'=>'getAuthors']);
            return new JsonResponse($jsonBook, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
    

    // TODO : suppression des livres en cascade
    #[Route('/api/authors/{id}', name: 'delete_author', methods: ['DELETE'])]
    public function deleteBook(Author $author, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($author);
        $em->flush();
 
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/authors',name:'create_author',methods:['POST'])]
    public function createAuthor(
        Request $request,
        SerializerInterface $serializerInterface,
        EntityManagerInterface $entityManagerInterface, 
        UrlGeneratorInterface $urlGeneratorInterface,
        ValidatorInterface $validator
    ) : JsonResponse 
    {
        $author = $serializerInterface->deserialize($request->getContent(),Author::class,'json');

        $errors = $validator->validate($author);

        if ($errors->count() > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }     

        // Insertion en BDD
        $entityManagerInterface->persist($author);
        $entityManagerInterface->flush();
        
        // Réponse
        $jsonAuthor = $serializerInterface->serialize($author,'json',['groups'=>'getBooks']);
        $location = $urlGeneratorInterface->generate('detail_author',['id'=>$author->getId()],UrlGeneratorInterface::ABSOLUTE_PATH);
        
        return new JsonResponse($jsonAuthor,Response::HTTP_CREATED,['Location'=>$location],true);

    }

    #[Route('api/authors/{id}',name:'update_author',methods:['PUT'])]
    public function updateAuthor(Author $author,Request $request, SerializerInterface $serializer, EntityManagerInterface $manager, ValidatorInterface $validator) : JsonResponse {

        $rawContent = $request->getContent();

        $data = json_decode($rawContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "Invalid JSON data");
        }

        $requiredKeys = ['first_name', 'last_name']; 
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "Missing required field: $key");
            }
        }
        
        $updatedAuthor = $serializer->deserialize($rawContent,Author::class,'json',[AbstractNormalizer::OBJECT_TO_POPULATE => $author]);

        // Valider les données désérialisées
        $errors = $validator->validate($updatedAuthor);

        // Si des erreurs de validation sont présentes, renvoyer une réponse d'erreur
        if ($errors->count() > 0) {
            throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, $serializer->serialize($errors, 'json'));
        }
    
        // orm query
        $manager->persist($updatedAuthor);
        $manager->flush();        

        //return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
        throw new HttpException(JsonResponse::HTTP_NO_CONTENT);

    }




}
