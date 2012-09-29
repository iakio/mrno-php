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

        $mockLoader = m::mock('mrno\\ObjectLoader');

        $mockLoader->shouldReceive('load')
            ->with('KEY')
            ->once()
            ->andReturn('VALUE');

        $mockLoader->shouldReceive('load')
            ->with('KEY2')
            ->once()
            ->andReturn('VALUE2');

        $cache = new TimedCache($mockLoader);
        $this->assertThat($cache->lookup("KEY"), $this->equalTo("VALUE"));
        $this->assertThat($cache->lookup("KEY2"), $this->equalTo("VALUE2"));
    }


    /** @test */
    function キャッシュされたオブジェクトのロード() {
        $mockLoader = m::mock('mrno\\ObjectLoader');

        $mockLoader->shouldReceive('load')
            ->with('KEY')
            ->once()
            ->andReturn('VALUE');

        $cache = new TimedCache($mockLoader);
        $this->assertThat($cache->lookup("KEY"), $this->equalTo("VALUE"));
        $this->assertThat($cache->lookup("KEY"), $this->equalTo("VALUE"));

    }
}
