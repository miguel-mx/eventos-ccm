<?php

namespace App\Entity;

use App\Repository\SeminarioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: SeminarioRepository::class)]
class Seminario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $start = null;

    #[Gedmo\Slug(fields: ['name'])]
    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private ?string $slug = null;

    /**
     * @var Collection<int, Organizer>
     */
    #[ORM\OneToMany(targetEntity: Organizer::class, mappedBy: 'seminario')]
    private Collection $organizers;

    /**
     * @var Collection<int, EventSeminar>
     */
    #[ORM\OneToMany(targetEntity: EventSeminar::class, mappedBy: 'seminar')]
    private Collection $eventSeminars;

    public function __construct()
    {
        $this->organizers = new ArrayCollection();
        $this->eventSeminars = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(\DateTimeInterface $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @return Collection<int, Organizer>
     */
    public function getOrganizers(): Collection
    {
        return $this->organizers;
    }

    public function addOrganizer(Organizer $organizer): static
    {
        if (!$this->organizers->contains($organizer)) {
            $this->organizers->add($organizer);
            $organizer->setSeminario($this);
        }

        return $this;
    }

    public function removeOrganizer(Organizer $organizer): static
    {
        if ($this->organizers->removeElement($organizer)) {
            // set the owning side to null (unless already changed)
            if ($organizer->getSeminario() === $this) {
                $organizer->setSeminario(null);
            }
        }

        return $this;
    }

    // Add __toString() method
    public function __toString(): string
    {
        return $this->name ?? 'Unnamed Seminar';
    }

    /**
     * @return Collection<int, EventSeminar>
     */
    public function getEventSeminars(): Collection
    {
        return $this->eventSeminars;
    }

    public function addEventSeminar(EventSeminar $eventSeminar): static
    {
        if (!$this->eventSeminars->contains($eventSeminar)) {
            $this->eventSeminars->add($eventSeminar);
            $eventSeminar->setSeminar($this);
        }

        return $this;
    }

    public function removeEventSeminar(EventSeminar $eventSeminar): static
    {
        if ($this->eventSeminars->removeElement($eventSeminar)) {
            // set the owning side to null (unless already changed)
            if ($eventSeminar->getSeminar() === $this) {
                $eventSeminar->setSeminar(null);
            }
        }

        return $this;
    }
}
