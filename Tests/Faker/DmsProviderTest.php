<?php

namespace Erichard\DmsBundle\Tests\Faker;

use Erichard\DmsBundle\Faker\DmsProvider;
use Mockery as m;

class DmsProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testSlug()
    {
        $this->assertEquals(
            'a-very-long-title-with-accent-like-in-cafe',
            DmsProvider::slug('A very long title with accent like in "café"')
        );
    }

    public function testImageLink()
    {
        $this->assertEquals(
            'http://placehold.it/250x350',
            DmsProvider::imageLink(250, 350)
        );
    }

    public function testImage()
    {
        $container = m::mock('Symfony\Component\DependencyInjection\Container');
        $container->shouldReceive('getParameter')->with('media_path')->andReturn('/tmp');
        $provider  = new DmsProvider($container);
        $imagePath = $provider->image('dummy', 200, 300);

        $this->assertTrue(is_file($imagePath), 'Image not downloaded from provider');
        $this->assertRegExp('/200x300/', $imagePath, 'Wrong filename');

        unlink($imagePath);
    }
}