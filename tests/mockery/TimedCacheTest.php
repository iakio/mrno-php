<?php
namespace mrno\tests\mockery;

use \Mockery as m,
    mrno\TimedCache;

class TimedCacheTest extends \PHPUnit_Framework_TestCase
{
    function tearDown() {
        m::close();
    }

    /** @test */
    function キャッシュされていないオブジェクトのロード() {
        $mockClock = m::mock('mrno\\Clock');
        $mockLoader = m::mock('mrno\\ObjectLoader');
        $mockReloadPolicy = m::mock('mrno\\ReloadPolicy');

        $mockClock->shouldReceive('getCurrentTime')
            ->withNoArgs()
            ->times(2)
            ->andReturn(strtotime('2012-01-01 00:00:00'));

        $mockLoader->shouldReceive('load')
            ->with('KEY')
            ->once()
            ->andReturn('VALUE');

        $mockLoader->shouldReceive('load')
            ->with('KEY2')
            ->once()
            ->andReturn('VALUE2');

        $mockReloadPolicy->shouldReceive('shouldReload')
            ->never();

        $cache = new TimedCache($mockLoader, $mockClock, $mockReloadPolicy);
        $this->assertThat($cache->lookup("KEY"), $this->equalTo("VALUE"));
        $this->assertThat($cache->lookup("KEY2"), $this->equalTo("VALUE2"));
    }


    /** @test */
    function キャッシュが有効なオブジェクトのロード() {
        $loadTime = strtotime('2012-01-01 00:00:00');
        $fetchTime = strtotime('2012-01-01 01:00:00');

        $mockClock = m::mock('mrno\\Clock');
        $mockLoader = m::mock('mrno\\ObjectLoader');
        $mockReloadPolicy = m::mock('mrno\\ReloadPolicy');

        $mockClock->shouldReceive('getCurrentTime')
            ->withNoArgs()
            ->times(2)
            ->andReturn($loadTime, $fetchTime);

        $mockLoader->shouldReceive('load')
            ->with('KEY')
            ->once()
            ->andReturn('VALUE');

        $mockReloadPolicy->shouldReceive('shouldReload')
            ->with($loadTime, $fetchTime)
            ->atLeast()->once()
            ->andReturn(false);

        $cache = new TimedCache($mockLoader, $mockClock, $mockReloadPolicy);
        $this->assertThat($cache->lookup("KEY"), $this->equalTo("VALUE"));
        $this->assertThat($cache->lookup("KEY"), $this->equalTo("VALUE"));

    }
}
