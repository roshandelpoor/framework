<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class QueueSizeTest extends TestCase
{
    public function test_queue_size()
    {
        Queue::fake();

        $this->assertEquals(0, Queue::size());
        $this->assertEquals(0, Queue::size('Q2'));

        $job = new TestJob1;

        dispatch($job);
        dispatch(new TestJob2);
        dispatch($job)->onQueue('Q2');

        $this->assertEquals(2, Queue::size());
        $this->assertEquals(1, Queue::size('Q2'));
    }

    public function test_multiple_queues_size()
    {
        Queue::fake();

        dispatch(new TestJob1)->onQueue('queue1');
        dispatch(new TestJob2)->onQueue('queue2');
        dispatch(new TestJob1)->onQueue('queue1');
        dispatch(new TestJob2)->onQueue('queue3');

        $this->assertEquals(2, Queue::size('queue1'));
        $this->assertEquals(1, Queue::size('queue2'));
        $this->assertEquals(1, Queue::size('queue3'));

        $this->assertEquals(0, Queue::size());
    }

    public function test_empty_queue_after_jobs_processed()
    {
        Queue::fake();

        dispatch(new TestJob1);
        dispatch(new TestJob2);

        $this->assertEquals(2, Queue::size());

        Queue::assertPushed(TestJob1::class);
        Queue::assertPushed(TestJob2::class);

        Queue::assertPushed(TestJob1::class, function ($job) {
            return true;
        });
        Queue::assertPushed(TestJob2::class, function ($job) {
            return true;
        });
    }

    public function test_queue_size_with_delayed_jobs()
    {
        Queue::fake();

        dispatch(new TestJob1)->delay(now()->addMinutes(5));
        dispatch(new TestJob2)->delay(now()->addMinutes(10));
        dispatch(new TestJob1);

        $this->assertEquals(3, Queue::size());
        Queue::assertPushed(TestJob1::class, 2);
        Queue::assertPushed(TestJob2::class, 1);
    }
}

class TestJob1 implements ShouldQueue
{
    use Queueable;
}

class TestJob2 implements ShouldQueue
{
    use Queueable;
}
