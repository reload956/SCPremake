<?php

namespace App\Controller;

use App\Entity\Delivery;
use App\Entity\Order;
use App\Entity\User;
use App\Form\OrderType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderlistController extends AbstractController
{
    /**
     * @Route("/orderlist", name="orderlist")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->findOneBy(['email'=>$this->getUser()->getUsername()]);

        $OrderSet = $user->getOrders();

        $orders=$paginator->paginate($OrderSet,$request->query

            ->getInt('page',1),10);

        return $this->render('orderlist/index.html.twig', [
            'controller_name' => 'OrderlistController',
            'orders' => $orders
        ]);
    }

    /**
     * @Route ("/orderlist/{slug}", name="order.detail")
     * @param Order $order
     * @param Request $request
     * @return Response
     */
    public function orderDetails(Order $order, Request $request):Response
    {
        return $this->render('orderlist/order_show.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * @Route ("/orderlist/{slug}/delivery", name="order.detail.delivery")
     * @param Order $order
     * @param Request $request
     * @return Response
     */
    public function deliveryDetails(Order $order, Request $request):Response
    {
        $processingTime =(date_diff( new \DateTime("now") , $order->getUpdatedAt()));

        return $this->render('orderlist/delivery.html.twig', [
            'delivery' => $order->getDelivery(),
            'processing' =>$processingTime->format(" days: %a: hours: %h:%i:%s"),
        ]);
    }
}
