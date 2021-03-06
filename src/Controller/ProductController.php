<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\Comment;
use App\Event\CommentCreatedEvent;
use App\Form\AddToCartType;
use App\Form\CommentType;
use App\Manager\CartManager;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\ProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
@Route("/product")
 */
const MaxPage=10;

class ProductController extends AbstractController
{
    /**
     * @Route("/", defaults={"page": "1", "_format"="html"}, methods="GET", name="product_index")
     * @Route("/page/{page<[1-9]\d*>}", defaults={"_format"="html"}, methods="GET", name="product_index_paginated")
     * @Cache(smaxage="10")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $em = $this->getDoctrine()->getManager();

        $ProductRepository = $em->getRepository(Product::class);

        $ProductsQuery=$ProductRepository->createQueryBuilder('p')
            ->where("p.quantity > 0")
            ->getQuery();
        $CategoryRepository=$em->getRepository(Category::class);
        $CategoryQuery=$CategoryRepository->findAll();

        $Products=$paginator->paginate($ProductsQuery,$request->query

            ->getInt('page',1),MaxPage);


        return $this->render('product/index.html.twig', [
            'products' => $Products,
            'categories'=>$CategoryQuery]);
    }

    /**
     * @Route("/product/{slug}", name="product.detail")
     * @param Product $product
     * @param Request $request
     * @param CartManager $cartManager
     * @return Response
     */
    public function detail(Product $product, Request $request, CartManager $cartManager): Response
    {
        $orderItem = new OrderItem();
        $orderItem->setProduct($product);
        $form = $this->createForm(AddToCartType::class, $orderItem);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item = $form->getData();
            $cart = $cartManager->getCurrentCart();
            $cart
                ->addItem($item)
                ->setUpdatedAt(new \DateTime());

            $cartManager->save($cart);

            return $this->redirectToRoute('product.detail', ['slug' => $product->getSlug()]);
        }

        return $this->render('product/product_show.html.twig', [
            'product' => $product,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/comment/{productSlug}/new", methods="POST", name="comment_new")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @ParamConverter("product", options={"mapping": {"productSlug": "slug"}})
     * @param Request $request
     * @param Product $product
     * @param EventDispatcherInterface $eventDispatcher
     * @return Response
     */
    public function commentNew(Request $request, Product $product, EventDispatcherInterface $eventDispatcher): Response
    {
        $comment = new Comment();
        $comment->setAuthor($this->getUser());
        $product->addComment($comment);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $comment->setPublishedAt(new \DateTime());
            $em->persist($comment);
            $em->flush();
            $eventDispatcher->dispatch(new CommentCreatedEvent($comment));

            return $this->redirectToRoute('product.detail', ['slug' => $product->getSlug()]);
        }

        return $this->render('product/comment_form_error.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    public function commentForm(Product $product): Response
    {
        $form = $this->createForm(CommentType::class);

        return $this->render('product/_comment_form.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/search", methods="GET", name="product_search")
     * @param Request $request
     * @param ProductRepository $product
     * @return Response
     */
    public function search(Request $request, ProductRepository $product): Response
    {
        $query = $request->query->get('q', '');
        $limit = $request->query->get('l', 10);

        if (!$request->isXmlHttpRequest()) {
            return $this->render('product/search.html.twig', ['query' => $query]);
        }

        $foundProducts = $product->findBySearchQuery($query, $limit);

        $results = [];
        foreach ($foundProducts as $product) {
            $results[] = [
                'title' => htmlspecialchars($product->getName(), ENT_COMPAT | ENT_HTML5),
                'date' => $product->getCreatedAt()->format('M d, Y'),
                'image' => htmlspecialchars($product->getImage(), ENT_COMPAT | ENT_HTML5),
                'summary' => htmlspecialchars($product->getSummary(), ENT_COMPAT | ENT_HTML5),
                'url' => $this->generateUrl('product.detail', ['slug' => $product->getSlug()]),
            ];
        }

        return $this->json($results);
    }


    /**
     * @Route("/search/cat/{id}", methods="GET", name="category_search")
     * @param Request $request
     * @param Category $category
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function catsearch(Request $request, Category $category,PaginatorInterface $paginator): Response
    {
        $em = $this->getDoctrine()->getManager();

        $foundProducts = $category->getProduct();

        $Products=$paginator->paginate($foundProducts,$request->query

            ->getInt('page',1),MaxPage);

        $CategoryRepository=$em->getRepository(Category::class);
        $CategoryQuery=$CategoryRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $Products,'categories'=>$CategoryQuery]);
    }
}
