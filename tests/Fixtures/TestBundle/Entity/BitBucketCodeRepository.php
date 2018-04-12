<?php
declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class BitBucketCodeRepository extends CodeRepository
{
    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $integratedWithHipChat;

    public function isIntegratedWithHipChat(): bool
    {
        return $this->integratedWithHipChat;
    }

    public function setIntegratedWithHipChat(bool $integratedWithHipChat): BitBucketCodeRepository
    {
        $this->integratedWithHipChat = $integratedWithHipChat;
        return $this;
    }
}
