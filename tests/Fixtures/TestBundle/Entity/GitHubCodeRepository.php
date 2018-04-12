<?php
declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class GitHubCodeRepository extends CodeRepository
{
    /**
     * @ORM\Column
     * @var string
     */
    private $stargazer;

    public function getStargazer(): string
    {
        return $this->stargazer;
    }

    public function setStargazer(string $stargazer): GitHubCodeRepository
    {
        $this->stargazer = $stargazer;
        return $this;
    }
}
