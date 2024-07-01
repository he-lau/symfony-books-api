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
    ) : JsonResponse 
    {
        $author = $serializerInterface->deserialize($request->getContent(),Author::class,'json');

        //$content = $request->toArray();

        // Insertion en BDD
        $entityManagerInterface->persist($author);
        $entityManagerInterface->flush();
        
        // Réponse
        $jsonAuthor = $serializerInterface->serialize($author,'json',['groups'=>'getBooks']);
        $location = $urlGeneratorInterface->generate('detail_author',['id'=>$author->getId()],UrlGeneratorInterface::ABSOLUTE_PATH);
        
        return new JsonResponse($jsonAuthor,Response::HTTP_CREATED,['Location'=>$location],true);

    }


}
