<?php

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StockRepository::class)
 */
class Stock
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=StockPrices::class, mappedBy="stock", orphanRemoval=true)
     */
    private $stockPrices;

    public function __construct()
    {
        $this->stockPrices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, StockPrices>
     */
    public function getStockPrices(): Collection
    {
        return $this->stockPrices;
    }

    public function addStockPrice(StockPrices $stockPrice): self
    {
        if (!$this->stockPrices->contains($stockPrice)) {
            $this->stockPrices[] = $stockPrice;
            $stockPrice->setStock($this);
        }

        return $this;
    }

    public function removeStockPrice(StockPrices $stockPrice): self
    {
        if ($this->stockPrices->removeElement($stockPrice)) {
            // set the owning side to null (unless already changed)
            if ($stockPrice->getStock() === $this) {
                $stockPrice->setStock(null);
            }
        }

        return $this;
    }
}
