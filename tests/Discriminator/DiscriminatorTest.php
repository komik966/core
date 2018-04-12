<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Discriminator;


use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CodeRepository;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\GitHubCodeRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

class DiscriminatorTest extends KernelTestCase
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    protected function setUp()
    {
        parent::setUp();
        self::bootKernel();
        $this->serializer = self::$kernel->getContainer()->get('serializer');
    }

    public function testDeserialize()
    {
        $result = $this->serializer->deserialize(
            '{"type": "github", "stargazer": "komik966", "branch": "foo"}',
            CodeRepository::class,
            'json'
        );
        $this->assertInstanceOf(GitHubCodeRepository::class, $result);
    }
}
