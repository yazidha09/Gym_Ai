<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\ChatMessage;
use Symfony\Bundle\SecurityBundle\Security;



class MainController extends AbstractController
{
    private $httpClient;
    
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('main/home.html.twig');
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('main/about.html.twig');
    }

     #[Route('/ai', name: 'ai')]
    public function ai(Request $request, EntityManagerInterface $em): Response
{
    $responseMessage = null;
    /** @var User|null $user */
    $user = $this->getUser();

    if ($request->isMethod('POST')) {
        $question = $request->request->get('question');
        $responseMessage = $this->askNutritionAI($question);

        if ($user instanceof User) {  // Explicit type check
            $chat = new ChatMessage();
            $chat->setQuestion($question);
            $chat->setResponse($responseMessage);
            $chat->setCreatedAt(new \DateTime());
            $chat->setUser($user);

            $em->persist($chat);
            $em->flush();
        }
    }

    $history = $user ? $em->getRepository(ChatMessage::class)->findBy(
        ['user' => $user],
        ['createdAt' => 'DESC']
    ) : [];

    return $this->render('main/ai.html.twig', [
        'responseMessage' => $responseMessage,
        'history' => $history,
    ]);
}
    // Fonction pour envoyer une question à l'API Nutrition AI (OpenAI ou autre)
private function askNutritionAI(string $question): string
{
    $apiKey = $_ENV['AIML_API_KEY'];
    
    try {
        $response = $this->httpClient->request('POST', 'https://api.aimlapi.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful nutrition assistant.'],
                    ['role' => 'user', 'content' => $question],
                ],
                'temperature' => 0.7,
                'max_tokens' => 300,
            ]
        ]);

        $data = $response->toArray();
        return $data['choices'][0]['message']['content'] ?? 'Désolé, aucune réponse générée.';

    } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e) {
        // Log the full error response
        $errorResponse = $e->getResponse()->getContent(false);
        error_log("API Error: " . $errorResponse);
        return "Erreur API: " . $errorResponse; // Return or handle the error
    }
}


    #[Route('/signup', name: 'signup')]
    public function signup(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setEmail($request->request->get('email'));
            $user->setPassword(password_hash($request->request->get('password'), PASSWORD_BCRYPT));
            $em->persist($user);
            $em->flush();
            return $this->redirectToRoute('login');
        }

        return $this->render('main/signup.html.twig');
    }

    #[Route('/login', name: 'login')]
    public function login(Request $request, SessionInterface $session, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user && password_verify($password, $user->getPassword())) {
                $session->set('user', $user->getId());
                return $this->redirectToRoute('home');
            } else {
                return new Response('Invalid credentials');
            }
        }

        return $this->render('main/login.html.twig');
    }

    #[Route('/logout', name: 'logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->remove('user');
        return $this->redirectToRoute('home');
    }
    #[Route('/addtocart/{id}', name: 'addtocart')]
public function addtocart(Request $request, int $id = null): Response
{
    $session = $request->getSession();
    $cart = $session->get('cart', []);

    // If an ID is provided (adding a product)
    if ($id !== null) {
        // Define our products (in a real app, these would come from a database)
        $products = [
            1 => ['name' => 'Comressed Black Shirt', 'price' => 79.99, 'image' => 'https://www.teefitfashion.com/cdn/shop/files/BlackFullCompression_1080x.png?v=1744984939'],
            2 => ['name' => 'Iron Grip with Wrist Support', 'price' => 129.99, 'image' => 'https://jnbfitness.com/cdn/shop/products/image_c5d65415-fbd0-41fa-943a-d437fd1bc3a8_1024x1024.jpg?v=1549447464'],
            3 => ['name' => 'GripMaster Gloves', 'price' => 59.99, 'image' => 'https://lsmedia.linker-cdn.net/62267/2025/14047617.jpeg?d=400x400']
        ];

        if (isset($products[$id])) {
            if (isset($cart[$id])) {
                $cart[$id]['quantity']++;
            } else {
                $cart[$id] = [
                    'name' => $products[$id]['name'],
                    'price' => $products[$id]['price'],
                    'old_price' => $products[$id]['price'],
                    'image' => $products[$id]['image'],
                    'quantity' => 1
                ];
            }
        }
        
        $session->set('cart', $cart);
        $this->addFlash('success', 'Product added to cart!');
        return $this->redirectToRoute('addtocart');
    }

    // If removing an item
    if ($request->query->has('remove')) {
        $removeId = $request->query->get('remove');
        if (isset($cart[$removeId])) {
            unset($cart[$removeId]);
            $session->set('cart', $cart);
            $this->addFlash('success', 'Product removed from cart!');
        }
        return $this->redirectToRoute('addtocart');
    }

    return $this->render('main/addtocart.html.twig', [
        'cart' => $cart
    ]);
}
}

