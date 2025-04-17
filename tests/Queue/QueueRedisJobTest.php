<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Queue\RedisQueue;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class QueueRedisJobTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock(stdClass::class));
        $handler->shouldReceive('fire')->once()->with($job, ['data']);

        $job->fire();
    }

    public function testDeleteRemovesTheJobFromRedis()
    {
        $job = $this->getJob();
        $job->getRedisQueue()->shouldReceive('deleteReserved')->once()
            ->with('default', $job);

        $job->delete();
    }

    public function testReleaseProperlyReleasesJobOntoRedis()
    {
        $job = $this->getJob();
        $job->getRedisQueue()->shouldReceive('deleteAndRelease')->once()
            ->with('default', $job, 1);

        $job->release(1);
    }

    public function testAttemptsReturnsCorrectValue()
    {
        $job = $this->getJob();
        $this->assertEquals(2, $job->attempts());
    }

    public function testGetJobIdReturnsNullWhenNoId()
    {
        $job = $this->getJob();
        $this->assertNull($job->getJobId());
    }

    public function testGetRawBodyReturnsCorrectPayload()
    {
        $job = $this->getJob();
        $expectedPayload = json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 1]);
        $this->assertEquals($expectedPayload, $job->getRawBody());
    }

    public function testGetReservedJobReturnsCorrectPayload()
    {
        $job = $this->getJob();
        $expectedPayload = json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 2]);
        $this->assertEquals($expectedPayload, $job->getReservedJob());
    }

    public function testGetRedisQueueReturnsCorrectInstance()
    {
        $job = $this->getJob();
        $this->assertInstanceOf(\Illuminate\Queue\RedisQueue::class, $job->getRedisQueue());
    }

    public function testFireProperlyCallsTheJobHandlerWithFailedMethod()
    {
        $job = $this->getJob();
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock(stdClass::class));
        $handler->shouldReceive('fire')->once()->with($job, ['data']);
        $handler->shouldReceive('failed')->never();

        $job->fire();
    }

    public function testJobCanBeDeletedAndReleased()
    {
        $job = $this->getJob();
        $job->getRedisQueue()->shouldReceive('deleteAndRelease')->once()
            ->with('default', $job, 0);

        $job->release();
    }

    protected function getJob()
    {
        return new RedisJob(
            m::mock(Container::class),
            m::mock(RedisQueue::class),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 1]),
            json_encode(['job' => 'foo', 'data' => ['data'], 'attempts' => 2]),
            'connection-name',
            'default'
        );
    }
}
