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
    public function home(Request $request, SessionInterface $session): Response
    {
        $products = $session->get('products', [
            1 => [
                'name' => 'Comressed Black Shirt',
                'price' => 79.99,
                'image' => 'https://www.teefitfashion.com/cdn/shop/files/BlackFullCompression_1080x.png?v=1744984939'
            ],
            2 => [
                'name' => 'Iron Grip with Wrist Support',
                'price' => 129.99,
                'image' => 'https://jnbfitness.com/cdn/shop/products/image_c5d65415-fbd0-41fa-943a-d437fd1bc3a8_1024x1024.jpg?v=1549447464'
            ],
            3 => [
                'name' => 'GripMaster Gloves',
                'price' => 59.99,
                'image' => 'https://lsmedia.linker-cdn.net/62267/2025/14047617.jpeg?d=400x400'
            ]
        ]);
        $cart = $session->get('cart', []);
        return $this->render('main/home.html.twig', [
            'products' => $products,
            'cart' => $cart
        ]);
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
        $response = $this->httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'google/gemma-3-27b-it:free',
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
        return $this->render('main/logout.html.twig');
    }
    #[Route('/addtocart/{id}', name: 'addtocart')]
    public function addtocart(Request $request, int $id = null): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);

        if ($id !== null) {
            $products = $session->get('products', [
                1 => ['name' => 'Comressed Black Shirt', 'price' => 79.99, 'image' => 'https://www.teefitfashion.com/cdn/shop/files/BlackFullCompression_1080x.png?v=1744984939'],
                2 => ['name' => 'Iron Grip with Wrist Support', 'price' => 129.99, 'image' => 'https://jnbfitness.com/cdn/shop/products/image_c5d65415-fbd0-41fa-943a-d437fd1bc3a8_1024x1024.jpg?v=1549447464'],
                3 => ['name' => 'GripMaster Gloves', 'price' => 59.99, 'image' => 'https://lsmedia.linker-cdn.net/62267/2025/14047617.jpeg?d=400x400']
            ]);

            if (isset($products[$id])) {
                if (isset($cart[$id])) {
                    $cart[$id]['quantity']++;
                } else {
                    $cart[$id] = [
                        'name' => $products[$id]['name'],
                        'price' => $products[$id]['price'],
                        'image' => $products[$id]['image'],
                        'quantity' => 1
                    ];
                }
            }
            $session->set('cart', $cart);
            // If AJAX, return JSON with new cart count
            if ($request->isXmlHttpRequest()) {
                $cartCount = 0;
                foreach ($cart as $item) { $cartCount += $item['quantity']; }
                return $this->json(['success' => true, 'cartCount' => $cartCount]);
            }
            // Only redirect if not AJAX and not from home page
            if ($request->headers->get('referer') && strpos($request->headers->get('referer'), '/addtocart') !== false) {
                $this->addFlash('success', 'Product added to cart!');
                return $this->redirectToRoute('addtocart');
            }
            // Otherwise, stay on the same page (home)
            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('home'));
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

#[Route('/dashboard', name: 'dashboard')]
    public function dashboard(Request $request, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $products = $session->get('products', [
            1 => [
                'name' => 'Comressed Black Shirt',
                'price' => 79.99,
                'image' => 'https://www.teefitfashion.com/cdn/shop/files/BlackFullCompression_1080x.png?v=1744984939'
            ],
            2 => [
                'name' => 'Iron Grip with Wrist Support',
                'price' => 129.99,
                'image' => 'https://jnbfitness.com/cdn/shop/products/image_c5d65415-fbd0-41fa-943a-d437fd1bc3a8_1024x1024.jpg?v=1549447464'
            ],
            3 => [
                'name' => 'GripMaster Gloves',
                'price' => 59.99,
                'image' => 'https://lsmedia.linker-cdn.net/62267/2025/14047617.jpeg?d=400x400'
            ]
        ]);

        // Handle delete
        if ($request->query->has('delete')) {
            $deleteId = $request->query->get('delete');
            if (isset($products[$deleteId])) {
                unset($products[$deleteId]);
                $session->set('products', $products);
                $this->addFlash('success', 'Product deleted!');
            }
            return $this->redirectToRoute('dashboard');
        }

        // Handle edit
        if ($request->isMethod('POST') && $request->query->has('edit')) {
            $editId = $request->query->get('edit');
            if (isset($products[$editId])) {
                $products[$editId]['name'] = $request->request->get('edit_name');
                $products[$editId]['price'] = (float)$request->request->get('edit_price');
                $products[$editId]['image'] = $request->request->get('edit_image');
                $session->set('products', $products);
                $this->addFlash('success', 'Product updated!');
            }
            return $this->redirectToRoute('dashboard');
        }

        // Add new product
        if ($request->isMethod('POST') && !$request->query->has('edit')) {
            $id = count($products) > 0 ? max(array_keys($products)) + 1 : 1;
            $products[$id] = [
                'name' => $request->request->get('name'),
                'price' => (float)$request->request->get('price'),
                'image' => $request->request->get('image')
            ];
            $session->set('products', $products);
            $this->addFlash('success', 'Product added!');
            return $this->redirectToRoute('dashboard');
        }

        $users = $em->getRepository(User::class)->findAll();
        $purchases = $session->get('purchases', []);
        return $this->render('main/dashboard.html.twig', [
            'products' => $products,
            'users' => $users,
            'purchases' => $purchases
        ]);
    }

    #[Route('/orders', name: 'orders')]
public function orders(Request $request, SessionInterface $session): Response
{
    $orders = $session->get('orders', []);
    // Mark as shipped
    if ($request->query->has('ship')) {
        $shipId = $request->query->get('ship');
        if (isset($orders[$shipId]) && $orders[$shipId]['status'] === 'pending') {
            $orders[$shipId]['status'] = 'shipped';
            $orders[$shipId]['shippedAt'] = date('Y-m-d H:i:s');
            $session->set('orders', $orders);
            $this->addFlash('success', 'Order marked as shipped!');
        }
        return $this->redirectToRoute('orders');
    }
    return $this->render('main/orders.html.twig', [
        'orders' => $orders
    ]);
}

#[Route('/checkout', name: 'checkout')]
public function checkout(Request $request, SessionInterface $session): Response
{
    $cart = $session->get('cart', []);
    $userId = $session->get('user');
    if (!$userId || empty($cart)) {
        $this->addFlash('danger', 'You must be logged in and have items in your cart to checkout.');
        return $this->redirectToRoute('addtocart');
    }
    $orders = $session->get('orders', []);
    $orderId = count($orders) > 0 ? max(array_keys($orders)) + 1 : 1;
    $total = 0;
    foreach ($cart as $item) { $total += $item['price'] * $item['quantity']; }
    $orders[$orderId] = [
        'id' => $orderId,
        'userId' => $userId,
        'items' => $cart,
        'total' => $total,
        'status' => 'pending',
        'createdAt' => date('Y-m-d H:i:s'),
        'shippedAt' => null
    ];
    $session->set('orders', $orders);
    // Record purchases
    $purchases = $session->get('purchases', []);
    foreach ($cart as $productId => $item) {
        $purchases[] = [
            'userId' => $userId,
            'productId' => $productId,
            'productName' => $item['name'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'purchasedAt' => date('Y-m-d H:i:s')
        ];
    }
    $session->set('purchases', $purchases);
    $session->set('cart', []);
    $this->addFlash('success', 'Order placed successfully!');
    return $this->redirectToRoute('orders');
}
}

