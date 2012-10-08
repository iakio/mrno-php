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

        $mockLoader->shouldReceive('load')
            ->with('KEY')
            ->once()
            ->globally()->ordered()
            ->andReturn('VALUE');

        $mockClock->shouldReceive('getCurrentTime')
            ->withNoArgs()
            ->atLeast()->once()
            ->globally()->ordered()
            ->andReturn($loadTime, $fetchTime);

        $mockReloadPolicy->shouldReceive('shouldReload')
            ->with($loadTime, $fetchTime)
            ->atLeast()->once()
            ->andReturn(false);

        $cache = new TimedCache($mockLoader, $mockClock, $mockReloadPolicy);
        $this->assertThat($cache->lookup("KEY"), $this->equalTo("VALUE"));
        $this->assertThat($cache->lookup("KEY"), $this->equalTo("VALUE"));
    }


    /** @test */
    function タイムアウト後にオブジェクトがロードされる()
    {
        // 一度目のlookupでloadしてキャッシュに格納し、
        // 二度目のlookupでキャッシュから引き出すが、
        // タイムアウトしているため再びloadする
        $loadTime   = strtotime('2012-01-01 00:00:00');
        $fetchTime  = strtotime('2012-01-01 01:00:00');
        $reloadTime = strtotime('2012-01-01 02:00:00');

        $mockClock = m::mock('mrno\\Clock');
        $mockLoader = m::mock('mrno\\ObjectLoader');
        $mockReloadPolicy = m::mock('mrno\\ReloadPolicy');

        $mockClock->shouldReceive('getCurrentTime')
            ->withNoArgs()
            ->times(3)
            ->andReturn($loadTime, $fetchTime, $reloadTime);

        $mockLoader->shouldReceive('load')
            ->with('KEY')
            ->times(2)
            ->andReturn('VALUE', 'NEW_VALUE');

        $mockReloadPolicy->shouldReceive('shouldReload')
            ->with($loadTime, $fetchTime)
            ->atLeast()->once()
            ->andReturn(true);

        $cache = new TimedCache($mockLoader, $mockClock, $mockReloadPolicy);
        $this->assertThat($cache->lookup("KEY"), $this->equalTo("VALUE"));
        $this->assertThat($cache->lookup("KEY"), $this->equalTo("NEW_VALUE"));
    }
}
