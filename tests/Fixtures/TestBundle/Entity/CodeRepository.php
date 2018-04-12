<?php
declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * @ApiResource(collectionOperations={"get","post"})
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=16)
 * @ORM\DiscriminatorMap({
 *    "github"="ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\GitHubCodeRepository",
 *    "bitbucket"="ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\BitBucketCodeRepository"
 * })
 * @Serializer\DiscriminatorMap(typeProperty="type", mapping={
 *    "github"="ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\GitHubCodeRepository",
 *    "bitbucket"="ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\BitBucketCodeRepository"
 * })
 */
abstract class CodeRepository
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @ORM\Column()
     * @var string
     */
    private $branch;

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): CodeRepository
    {
        $this->type = $type;
        return $this;
    }

    public function getBranch(): string
    {
        return $this->branch;
    }

    public function setBranch(string $branch): CodeRepository
    {
        $this->branch = $branch;
        return $this;
    }
}
