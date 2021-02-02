<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\CategoryType;
use App\Form\ProductType;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/Admin")
 * @IsGranted("ROLE_ADMIN")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/", methods="GET", name="admin_index")
     * @Route("/", methods="GET", name="admin_product_index")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator): Response
    {

        $em = $this->getDoctrine()->getManager();

        $ProductRepository = $em->getRepository(Product::class);

        $ProductsQuery = $ProductRepository->createQueryBuilder('p')
            ->getQuery();

        $Products = $paginator->paginate($ProductsQuery, $request->query
            ->getInt('page', 1), 10);

        return $this->render('admin/product/index.html.twig', ['products' => $Products]);
    }



    /**
     * @Route("/new_category", methods="GET|POST", name="admin_category_new")
     */
    public function addCategory(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category)
            ->add('saveAndCreateNew', SubmitType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'category added successfully');

            if ($form->get('saveAndCreateNew')->isClicked()) {
                return $this->redirectToRoute('admin_category_new');
            }

            return $this->redirectToRoute('admin_product_index');
        }

        $em = $this->getDoctrine()->getManager();
        $CategoryRepository = $em->getRepository(Category::class);

        $CategoriesQuery = $CategoryRepository->createQueryBuilder('p')
            ->getQuery();

        return $this->render('admin/product/new_category.html.twig', [
            'form' => $form->createView(),
            'categoryTable' => $CategoriesQuery,
        ]);
    }



    /**
     * @Route("/new", methods="GET|POST", name="admin_product_new")
     */
    public function addProduct(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product)
            ->add('saveAndCreateNew', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $product->setCreatedAt(new \DateTime());
            $file = $form->get('image_form')->getData();
            if (!$file) {
                $form->get('image_form')->addError(new FormError('Image is required'));
            } else {
                $filename = md5($product->getName() . '' . $product->getCreatedAt()->format("Y-m-d H:i:s"));

                $file->move($this->getParameter('brochures_directory'),
                    $filename
                );

                $product->setImage($filename);
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'product added successfully');

            if ($form->get('saveAndCreateNew')->isClicked()) {
                return $this->redirectToRoute('admin_product_new');
            }

            return $this->redirectToRoute('admin_product_index');
        }
        return $this->render('admin/product/new.html.twig', [
            'products' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     *
     * @Route("/{id<\d+>}", methods="GET", name="admin_product_show")
     */
    public function show(Product $product): Response
    {
        return $this->render('admin/product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * Displays a form to edit an existing Post entity.
     *
     * @Route("/{id<\d+>}/edit", methods="GET|POST", name="admin_product_edit")
     */
    public function edit(Request $request, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'post.updated_successfully');

            return $this->redirectToRoute('admin_product_edit', ['id' => $product->getId()]);
        }

        return $this->render('admin/product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", methods="POST", name="admin_product_delete")
     * @IsGranted("delete", subject="product")
     */
    public function delete(Request $request, Product $product): Response
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_product_index');
        }
        $product->getTags()->clear();

        $em = $this->getDoctrine()->getManager();
        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'product.deleted_successfully');

        return $this->redirectToRoute('admin_product_index');
    }

}
