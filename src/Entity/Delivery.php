<?php

namespace App\Entity;

use App\Repository\DeliveryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DeliveryRepository::class)
 */
class Delivery
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=40)
     */
    private $Address;

    /**
     * @ORM\Column(type="integer")
     */
    private $home_number;

    /**
     * @ORM\Column(type="integer")
     */
    private $flat;

    /**
     * @ORM\OneToOne(targetEntity=Order::class, inversedBy="delivery", cascade={"persist", "remove"})
     */
    private $rel_order;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $City;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->Address;
    }

    public function setAddress(string $Address): self
    {
        $this->Address = $Address;

        return $this;
    }

    public function getHomeNumber(): ?int
    {
        return $this->home_number;
    }

    public function setHomeNumber(int $home_number): self
    {
        $this->home_number = $home_number;

        return $this;
    }

    public function getFlat(): ?int
    {
        return $this->flat;
    }

    public function setFlat(int $flat): self
    {
        $this->flat = $flat;

        return $this;
    }

    public function getRelOrder(): ?Order
    {
        return $this->rel_order;
    }

    public function setRelOrder(?Order $rel_order): self
    {
        $this->rel_order = $rel_order;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->City;
    }

    public function setCity(string $City): self
    {
        $this->City = $City;

        return $this;
    }
}
