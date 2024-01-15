<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Post;
use App\Repository\UserRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request as Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class PostController extends AbstractController
{
    //Nomme la route et indique la fonction à utiliser en fonction de la méthode HTTP
    #[Route('/posts', name: 'api_post_index', methods: ['GET'])]
    #[OA\Tag(name: 'Posts')]
    #[OA\Get(
        path: '/api/posts',
        description: 'Récupère la liste de tous les posts.',
        summary: 'Lister les posts',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des posts au format json',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: new Model(type: Post::class,groups: ['list_posts'])
                    )
                )
            )
        ]
    )]
    public function index(PostRepository      $repository,
                          UserRepository $userRepository,
                          Request             $request,
                          SerializerInterface $serializer): Response {
        //Récupère tous les posts dans la base de données
        if ($this->getUser()){
            $posts = $repository->findBy(["user" => $this->getUser()]);
        } else {
            $posts = $repository->findAll();
        }

        //Normalisation des posts
        //$postsNormalized = $normalizer->normalize($posts);

        //Encode les posts en JSON
        //$json = json_encode($postsNormalized);

        //Serialise les posts
        $postsSerialized = $serializer->serialize($posts, 'json', ["groups" => ['list_posts']]);

//        //Crée une réponse HTTP
//        $response = new Response();
//        $response->setStatusCode(Response::HTTP_OK);
//        $response->headers->set('content-type', 'application/json');
//        $response->setContent($postsSerialized);

        return new Response($postsSerialized, 200, [
            'content-type' => 'application/json'
        ]);


//        dd($this->json($posts)->getContent());
    }
    #[OA\Tag(name: 'Posts')]
    #[OA\Get(
        path: '/api/posts/{id}',
        description: 'Récupère un post par son id.',
        summary: 'Récupère un posts',
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Id du post à rechercher',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer'
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Détail d'un post au format json",
                content: new OA\JsonContent(
                    ref: new Model(type: Post::class,groups: ['single_post'])
                )
            )
        ]
    )]
    #[Route('/posts/{id}', name: 'api_post_find', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(EntityManagerInterface $entityManager,
                         SerializerInterface    $serializer,
                         int                    $id): Response {
        //Récupère tous les posts dans la base de données
        $post = $entityManager->find(Post::class, $id);
        $postSerialized = $serializer->serialize($post, 'json', ['groups' => 'single_post']);
        return new Response($postSerialized, 200, [
            'content-type' => 'application/json'
        ]);
    }

    #[Route('/posts/publies-apres', name: 'api_post_find_posts_after_date', methods: ['GET'])]
    public function show_after_date(PostRepository      $repository,
                                    Request             $request,
                                    SerializerInterface $serializer): Response {
        //Récupère tous les posts dans la base de données
        $date = $request->get('date');
        $requestSQL = $repository->findByDateGreaterThan($date);
        $postSerialized = $serializer->serialize($requestSQL, 'json');
        return new Response($postSerialized, 200, [
            'content-type' => 'application/json'
        ]);
    }

    #[Route('/posts', name: 'api_post_create', methods: ['POST'])]
    public function create(Request                $request,
                           SerializerInterface    $serializer,
                           EntityManagerInterface $entityManager): Response {
        $body = $request->getContent();
        $parameters = json_decode($request->getContent(), true);
        //Décode le JSON en tableau PHP
        $post = $serializer->deserialize($body, Post::class, 'json');
        $categorie = $entityManager->getRepository(Categorie::class)->find($parameters['categorie_id']);
        $post->setUser($this->getUser());
        $post->setCreatedAt(new \DateTime());
        $post->setCategorie($categorie);
        $entityManager->persist($post);
        $entityManager->flush();
        return new Response($serializer->serialize($post, 'json', ['groups' => 'single_post']), 201, [
            'content-type' => 'application/json'
        ]);
    }

    #[Route('/posts/{id}', name: 'api_post_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(PostRepository $repository, EntityManagerInterface $entityManager, int $id): Response {
        $post = $repository->find($id);
        if(!$post->getUser() === $this->getUser()) {
            return new Response(null, Response::HTTP_UNAUTHORIZED,);
        }
        $entityManager->remove($post);
        $entityManager->flush();
        return new Response(null, Response::HTTP_NO_CONTENT,);
    }

    #[Route('/posts/{id}', name: 'api_post_show', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(PostRepository $repository, EntityManagerInterface $entityManager, int $id, Request $request, SerializerInterface $serializer): Response {
        $post = $repository->find($id);
        $body = $request->getContent();
        //Fusionne les données du JSON dans l'objet $post
        $serializer->deserialize($body, Post::class, 'json', ['object_to_populate' => $post]);
        $entityManager->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
