<?php

namespace App\Controller;

use App\Entity\Delivery;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Form\CartType;
use App\Form\DeliveryType;
use App\Manager\CartManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Class CartController
 * @package App\Controller
 */

class CartController extends AbstractController
{
    /**
     * @Route("/cart", name="cart")
     * @param CartManager $cartManager
     * @param Request $request
     * @return Response
     */
    public function index(CartManager $cartManager, Request $request): Response
    {

        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Authorize first!');

        $cart = $cartManager->getCurrentCart();
        $form = $this->createForm(CartType::class, $cart);

        $form->handleRequest($request);
        //$em = $this->getDoctrine()->getManager();

        if ($form->isSubmitted() && $form->isValid()) {
            $cart->setUpdatedAt(new \DateTime());
            $cartManager->save($cart);
            //foreach ($cart->getItems() as $item) {
            //    $em->persist($item);
            //    $em->flush();
            //  }
            return $this->redirectToRoute('cart');
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route ("/cart/checkout", name="checkout")
     * @param CartManager $cartManager
     * @param Request $request
     * @param SluggerInterface $slugger
     * @return Response
     */
    public function pay(CartManager $cartManager, Request $request, SluggerInterface $slugger): Response
    {


        $cart = $cartManager->getCurrentCart();
        $delivery = new Delivery();

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findOneBy(['email' => $this->getUser()->getUsername()]);

        $form = $this->createForm(DeliveryType::class, $delivery);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && ($cart->getTotal() > 0)) {
            $em = $this->getDoctrine()->getManager();
            $cart->setUpdatedAt(new \DateTime());
            $delivery->setRelOrder($cart);
            $cart->setDelivery($delivery);
            $user->addOrder($cart);
            $cart->setSlug($slugger->slug(date_format($cart->getUpdatedAt(), 'Y-m-d H:i:s') . $user->getUsername()));
            $cart->setStatus('order');
            $cartManager->cartOrdered($cart);
            $em->persist($delivery);
            $em->flush();
            $em->persist($user);
            $em->flush();
            foreach($cart->getItems() as $item){
                $item->getProduct()->setQuantity($item->getProduct()->getQuantity() - $item->getQuantity());
                $em->persist($item);
                $em->flush();
            }
            return $this->redirectToRoute('cart');
        }

        return $this->render('cart/checkout.html.twig', [
            'cart' => $cart,
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }


    /**
     * @Route("/cart/increase/{id}", name="increase_cart_product_process")
     * @param OrderItem $orderItem
     * @return Response
     */

    public function increaseProduct(OrderItem $orderItem)
    {

        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Authorize first!');

        $em = $this->getDoctrine()->getManager();

        if ($orderItem->getQuantity() >= $orderItem->getProduct()->getQuantity()) {
            return $this->redirectToRoute("cart");
        } else {
            $orderItem->setQuantity($orderItem->getQuantity() + 1);
            $em->persist($orderItem);
            $em->flush();

        }

        return $this->redirectToRoute("cart");
    }

    /**
     * @Route("/cart/decrease/{id}", name="decrease_cart_product_process")
     * @param OrderItem $orderItem
     * @return Response
     */
    public function decreaseProduct(OrderItem $orderItem)
    {

        $em = $this->getDoctrine()->getManager();


        if ($orderItem->getQuantity() > 1) {
            $orderItem->setQuantity($orderItem->getQuantity() - 1);
            $em->persist($orderItem);
            $em->flush();

        } else {
            $em->remove($orderItem);
            $em->flush();
        }

        return $this->redirectToRoute("cart");
    }

    /**
     * @Route("/cart/maxAV/{id}", name="set_max_cart_product_process")
     * @param OrderItem $orderItem
     * @return Response
     */
    public function setMaxAvailable(OrderItem $orderItem)
    {

        $em = $this->getDoctrine()->getManager();

        if ($orderItem->getQuantity() >= $orderItem->getProduct()->getQuantity()) {
            $orderItem->setQuantity($orderItem->getProduct()->getQuantity());
            $em->persist($orderItem);
            $em->flush();
        }

        return $this->redirectToRoute("cart");
    }
}