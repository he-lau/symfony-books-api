<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

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


}
