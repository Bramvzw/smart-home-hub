<?php

namespace Modules\Tasks\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tasks\Models\Task;
use Tests\TestCase;
use Carbon\Carbon;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test task creation with basic attributes.
     *
     * @return void
     */
    public function test_can_create_task()
    {
        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'lane_id' => 1,
            'order' => 0,
        ]);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->title);
        $this->assertEquals('This is a test task', $task->description);
        $this->assertEquals(1, $task->lane_id);
        $this->assertEquals(0, $task->order);
    }

    /**
     * Test task creation with all attributes.
     *
     * @return void
     */
    public function test_can_create_task_with_all_attributes()
    {
        $dueDate = Carbon::now()->addDays(7);

        $task = Task::create([
            'title' => 'Complete Task',
            'description' => 'This is a complete task with all attributes',
            'lane_id' => 1,
            'order' => 0,
            'label' => 'feature',
            'priority' => 'high',
            'due_date' => $dueDate,
            'notify_before_expiry' => true,
        ]);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Complete Task', $task->title);
        $this->assertEquals('This is a complete task with all attributes', $task->description);
        $this->assertEquals(1, $task->lane_id);
        $this->assertEquals(0, $task->order);
        $this->assertEquals('feature', $task->label);
        $this->assertEquals('high', $task->priority);
        $this->assertEquals($dueDate->format('Y-m-d'), $task->due_date->format('Y-m-d'));
        $this->assertTrue($task->notify_before_expiry);
    }

    /**
     * Test the isAboutToExpire method.
     *
     * @return void
     */
    public function test_is_about_to_expire()
    {
        // Task due tomorrow
        $task1 = Task::create([
            'title' => 'Due Tomorrow',
            'lane_id' => 1,
            'due_date' => Carbon::now()->addDay(),
        ]);

        // Task due in 3 days
        $task2 = Task::create([
            'title' => 'Due in 3 Days',
            'lane_id' => 1,
            'due_date' => Carbon::now()->addDays(3),
        ]);

        // Task due in 10 days
        $task3 = Task::create([
            'title' => 'Due in 10 Days',
            'lane_id' => 1,
            'due_date' => Carbon::now()->addDays(10),
        ]);

        // Task with no due date
        $task4 = Task::create([
            'title' => 'No Due Date',
            'lane_id' => 1,
        ]);

        // Default is 2 days
        $this->assertTrue($task1->isAboutToExpire());
        $this->assertFalse($task2->isAboutToExpire());
        $this->assertFalse($task3->isAboutToExpire());
        $this->assertFalse($task4->isAboutToExpire());

        // With custom days parameter
        $this->assertTrue($task1->isAboutToExpire(1));
        $this->assertTrue($task2->isAboutToExpire(3));
        $this->assertFalse($task3->isAboutToExpire(5));
    }

    /**
     * Test the isOverdue method.
     *
     * @return void
     */
    public function test_is_overdue()
    {
        // Task due yesterday
        $task1 = Task::create([
            'title' => 'Due Yesterday',
            'lane_id' => 1,
            'due_date' => Carbon::now()->subDay(),
        ]);

        // Task due today
        $task2 = Task::create([
            'title' => 'Due Today',
            'lane_id' => 1,
            'due_date' => Carbon::now(),
        ]);

        // Task due tomorrow
        $task3 = Task::create([
            'title' => 'Due Tomorrow',
            'lane_id' => 1,
            'due_date' => Carbon::now()->addDay(),
        ]);

        // Task with no due date
        $task4 = Task::create([
            'title' => 'No Due Date',
            'lane_id' => 1,
        ]);

        $this->assertTrue($task1->isOverdue());
        $this->assertFalse($task2->isOverdue()); // Due today is not overdue
        $this->assertFalse($task3->isOverdue());
        $this->assertFalse($task4->isOverdue());
    }

    /**
     * Test the relationship with lane.
     *
     * @return void
     */
    public function test_belongs_to_lane()
    {
        $lane = \Modules\Tasks\Models\Lane::create([
            'name' => 'Test Lane',
            'order' => 0,
        ]);

        $task = Task::create([
            'title' => 'Test Task',
            'lane_id' => $lane->id,
            'order' => 0,
        ]);

        $this->assertInstanceOf(\Modules\Tasks\Models\Lane::class, $task->lane);
        $this->assertEquals($lane->id, $task->lane->id);
    }

    /**
     * Test the relationship with attachments.
     *
     * @return void
     */
    public function test_has_many_attachments()
    {
        $task = Task::create([
            'title' => 'Test Task',
            'lane_id' => 1,
            'order' => 0,
        ]);

        $attachment = \Modules\Tasks\Models\TaskAttachment::create([
            'task_id' => $task->id,
            'filename' => 'test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
            'path' => 'task-attachments/1/test.txt',
            'size' => 1024,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $task->attachments);
        $this->assertCount(1, $task->attachments);
        $this->assertInstanceOf(\Modules\Tasks\Models\TaskAttachment::class, $task->attachments->first());
    }
}
