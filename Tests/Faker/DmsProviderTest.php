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
            'http://lorempixel.com/250/350',
            DmsProvider::imageLink(250, 350)
        );
    }

    public function testImage()
    {
        $container = m::mock('Symfony\Component\DependencyInjection\Container');
        $container->shouldReceive('getParameter')->with('dms.storage.path')->andReturn(sys_get_temp_dir().'/');
        $provider  = new DmsProvider($container);
        $imagePath = $provider->image('dummy', 200, 300);

        $this->assertTrue(is_file(sys_get_temp_dir().'/'.$imagePath), 'Image not downloaded from provider');
        $this->assertRegExp('/200x300/', $imagePath, 'Wrong filename');

        unlink(sys_get_temp_dir().'/'.$imagePath);
    }
}
