<?php

namespace App\Manager;

use App\Entity\Order;
use App\Factory\OrderFactory;
use App\Storage\CartSessionStorage;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class CartManager
 * @package App\Manager
 */
class CartManager
{
    /**
     * @var CartSessionStorage
     */
    private $cartSessionStorage;

    /**
     * @var OrderFactory
     */
    private $cartFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * CartManager constructor.
     *
     * @param CartSessionStorage $cartStorage
     * @param OrderFactory $orderFactory
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        CartSessionStorage $cartStorage,
        OrderFactory $orderFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->cartSessionStorage = $cartStorage;
        $this->cartFactory = $orderFactory;
        $this->entityManager = $entityManager;
    }

    /**
     * Gets the current cart.
     *
     * @return Order
     */
    public function getCurrentCart(): Order
    {
        $cart = $this->cartSessionStorage->getCart();

        if (!$cart) {
            $cart = $this->cartFactory->create();
        }

        return $cart;
    }

    /**
     * Persists the cart in database and session.
     *
     * @param Order $cart
     */
    public function save(Order $cart): void
    {
        // Persist in database
        $this->entityManager->persist($cart);
        $this->entityManager->flush();
        // Persist in session
        $this->cartSessionStorage->setCart($cart);
    }

    /**
     * Cart saves in database and replaces with empty one
     *
     * @param Order $cart
     */
    public function cartOrdered(Order $cart): void
    {
        $this->entityManager->persist($cart);
        $this->entityManager->flush();
        $this->cartSessionStorage->setCart($this->cartFactory->create());
    }
}