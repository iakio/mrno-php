<?php
namespace mrno\tests\phake;

use \Phake,
    mrno\TimedCache;

class TimedCacheTest extends \PHPUnit_Framework_TestCase
{
    const LOAD_TIME = 'laodTime';
    const FETCH_TIME = 'fetchTime';
    const RELOAD_TIME = 'realoadTime';
    const NOSENSE = null;

    function setUp() {
        $this->mockClock = Phake::mock('mrno\\Clock');
        $this->mockLoader = Phake::mock('mrno\\ObjectLoader');
        $this->mockReloadPolicy = Phake::mock('mrno\\ReloadPolicy');
        $this->cache = new TimedCache($this->mockLoader, $this->mockClock, $this->mockReloadPolicy);
    }

    /** @test */
    function オブジェクトがキャッシュされていなければ、都度loadされること() {
        Phake::when($this->mockClock)->getCurrentTime()->thenReturn(self::NOSENSE);
        Phake::when($this->mockLoader)->load('KEY1')->thenReturn('VALUE1');
        Phake::when($this->mockLoader)->load('KEY2')->thenReturn('VALUE2');

        $this->assertThat($this->cache->lookup('KEY1'), $this->equalTo('VALUE1'));
        $this->assertThat($this->cache->lookup('KEY2'), $this->equalTo('VALUE2'));

        Phake::verify($this->mockClock, Phake::times(2))->getCurrentTime();
        Phake::verify($this->mockLoader, Phake::times(1))->load('KEY1');
        Phake::verify($this->mockLoader, Phake::times(1))->load('KEY2');
    }


    /** @test */
    function オブジェクトのキャッシュが有効であれば、再度loadしない() {
        Phake::when($this->mockClock)->getCurrentTime()
            ->thenReturn(self::LOAD_TIME)
            ->thenReturn(self::FETCH_TIME);
        Phake::when($this->mockReloadPolicy)->shouldReload(self::LOAD_TIME, self::FETCH_TIME)
            ->thenReturn(false);
        Phake::when($this->mockLoader)->load('KEY')
            ->thenReturn('VALUE');

        $this->assertThat($this->cache->lookup("KEY"), $this->equalTo("VALUE"));
        $this->assertThat($this->cache->lookup("KEY"), $this->equalTo("VALUE"));

        Phake::inOrder(
            Phake::verify($this->mockLoader, Phake::times(1))->load('KEY'),
            Phake::verify($this->mockClock, Phake::atLeast(1))->getCurrentTime(),
            Phake::verify($this->mockReloadPolicy, Phake::times(1))->shouldReload(self::LOAD_TIME, self::FETCH_TIME)
        );
    }


    /** @test */
    function オブジェクトのキャッシュの有効期限が切れていれば、再度loadする() {

        Phake::when($this->mockClock)->getCurrentTime()
            ->thenReturn(self::LOAD_TIME)
            ->thenReturn(self::FETCH_TIME)
            ->thenReturn(self::RELOAD_TIME);

        Phake::when($this->mockLoader)->load('KEY')
            ->thenReturn('VALUE')
            ->thenReturn('NEW_VALUE');

        Phake::when($this->mockReloadPolicy)->shouldReload(self::LOAD_TIME, self::FETCH_TIME)
            ->thenReturn(true);

        $this->assertThat($this->cache->lookup("KEY"), $this->equalTo("VALUE"));
        $this->assertThat($this->cache->lookup("KEY"), $this->equalTo("NEW_VALUE"));

        Phake::verify($this->mockClock, Phake::atLeast(3))->getCurrentTime();
        Phake::verify($this->mockLoader, Phake::times(2))->load('KEY');
        Phake::verify($this->mockReloadPolicy, Phake::atLeast(1))->shouldReload(self::LOAD_TIME, self::FETCH_TIME);

    }
}
