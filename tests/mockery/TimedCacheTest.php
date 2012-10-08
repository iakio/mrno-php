<?php
namespace mrno\tests\mockery;

use \Mockery as m,
    mrno\TimedCache;

class TimedCacheTest extends \PHPUnit_Framework_TestCase
{
    function setUp() {
        $this->mockClock = m::mock('mrno\\Clock');
        $this->mockLoader = m::mock('mrno\\ObjectLoader');
        $this->mockReloadPolicy = m::mock('mrno\\ReloadPolicy');
        $this->cache = new TimedCache($this->mockLoader, $this->mockClock, $this->mockReloadPolicy);
    }

    function tearDown() {
        m::close();
    }

    /** @test */
    function キャッシュされていないオブジェクトのロード() {
        $this->mockClock->shouldReceive('getCurrentTime')
            ->withNoArgs()
            ->andReturn("nonsense");

        $this->mockLoader->shouldReceive('load')
            ->with('KEY')
            ->once()
            ->andReturn('VALUE');

        $this->mockLoader->shouldReceive('load')
            ->with('KEY2')
            ->once()
            ->andReturn('VALUE2');

        $this->mockReloadPolicy->shouldReceive('shouldReload')
            ->never();

        $this->assertThat($this->cache->lookup("KEY"), $this->equalTo("VALUE"));
        $this->assertThat($this->cache->lookup("KEY2"), $this->equalTo("VALUE2"));
    }


    /** @test */
    function キャッシュが有効なオブジェクトのロード() {
        $loadTime = strtotime('2012-01-01 00:00:00');
        $fetchTime = strtotime('2012-01-01 01:00:00');

        $this->mockLoader->shouldReceive('load')
            ->with('KEY')
            ->once()
            ->globally()->ordered()
            ->andReturn('VALUE');

        $this->mockClock->shouldReceive('getCurrentTime')
            ->withNoArgs()
            ->atLeast()->once()
            ->globally()->ordered()
            ->andReturn($loadTime, $fetchTime);

        $this->mockReloadPolicy->shouldReceive('shouldReload')
            ->with($loadTime, $fetchTime)
            ->atLeast()->once()
            ->andReturn(false);

        $this->assertThat($this->cache->lookup("KEY"), $this->equalTo("VALUE"));
        $this->assertThat($this->cache->lookup("KEY"), $this->equalTo("VALUE"));
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

        $this->mockClock->shouldReceive('getCurrentTime')
            ->withNoArgs()
            ->times(3)
            ->andReturn($loadTime, $fetchTime, $reloadTime);

        $this->mockLoader->shouldReceive('load')
            ->with('KEY')
            ->times(2)
            ->andReturn('VALUE', 'NEW_VALUE');

        $this->mockReloadPolicy->shouldReceive('shouldReload')
            ->with($loadTime, $fetchTime)
            ->atLeast()->once()
            ->andReturn(true);

        $this->assertThat($this->cache->lookup("KEY"), $this->equalTo("VALUE"));
        $this->assertThat($this->cache->lookup("KEY"), $this->equalTo("NEW_VALUE"));
    }
}
