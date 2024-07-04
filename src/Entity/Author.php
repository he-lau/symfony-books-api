<?php

namespace App\Entity;

use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
//use Symfony\Component\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Groups;

use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: AuthorRepository::class)]
class Author
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getBooks","getAuthors"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getBooks","getAuthors"])]
    #[Assert\NotBlank(message: "The firstname is required")]
    #[Assert\Length(min: 2, max: 255, minMessage: "The firstname must be at least {{ limit }} characters long", maxMessage: "The firstname cannot be more than {{ limit }} characters")]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getBooks","getAuthors"])]
    #[Assert\NotBlank(message: "The last name is required")]
    #[Assert\Length(min: 2, max: 255, minMessage: "The last name must be at least {{ limit }} characters long", maxMessage: "The last name cannot be more than {{ limit }} characters")]
    private ?string $lastName = null;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\ManyToMany(targetEntity: Book::class, inversedBy: 'authors')]
    #[Groups(["getAuthors"])]
    private Collection $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return Collection<int, Book>
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): static
    {
        if (!$this->books->contains($book)) {
            $this->books->add($book);
        }

        return $this;
    }

    public function removeBook(Book $book): static
    {
        $this->books->removeElement($book);

        return $this;
    }
}
