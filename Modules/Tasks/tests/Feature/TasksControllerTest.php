<?php

namespace Modules\Tasks\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tasks\Models\Lane;
use Modules\Tasks\Models\Task;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TasksControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user and authenticate
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        // Create a test lane
        $this->lane = Lane::create([
            'name' => 'Test Lane',
            'order' => 0,
        ]);
    }

    /**
     * Test the index page loads successfully.
     *
     * @return void
     */
    public function test_index_page_loads()
    {
        $response = $this->get(route('tasks.index'));
        $response->assertStatus(200);
        $response->assertViewIs('tasks::index');
        $response->assertViewHas('lanes');
    }

    /**
     * Test creating a new lane.
     *
     * @return void
     */
    public function test_can_create_lane()
    {
        $response = $this->postJson(route('tasks.lanes.store'), [
            'name' => 'New Lane',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('lanes', [
            'name' => 'New Lane',
        ]);
    }

    /**
     * Test updating a lane.
     *
     * @return void
     */
    public function test_can_update_lane()
    {
        $response = $this->putJson(route('tasks.lanes.update', $this->lane->id), [
            'name' => 'Updated Lane',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('lanes', [
            'id' => $this->lane->id,
            'name' => 'Updated Lane',
        ]);
    }

    /**
     * Test deleting a lane.
     *
     * @return void
     */
    public function test_can_delete_lane()
    {
        $response = $this->deleteJson(route('tasks.lanes.destroy', $this->lane->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('lanes', [
            'id' => $this->lane->id,
        ]);
    }

    /**
     * Test creating a new task.
     *
     * @return void
     */
    public function test_can_create_task()
    {
        $response = $this->postJson(route('tasks.tasks.store'), [
            'title' => 'New Task',
            'description' => 'This is a new task',
            'lane_id' => $this->lane->id,
            'label' => 'feature',
            'priority' => 'high',
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'notify_before_expiry' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'description' => 'This is a new task',
            'lane_id' => $this->lane->id,
            'label' => 'feature',
            'priority' => 'high',
            'notify_before_expiry' => 1,
        ]);
    }

    /**
     * Test updating a task.
     *
     * @return void
     */
    public function test_can_update_task()
    {
        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'lane_id' => $this->lane->id,
            'order' => 0,
        ]);

        $response = $this->putJson(route('tasks.tasks.update', $task->id), [
            'title' => 'Updated Task',
            'description' => 'This is an updated task',
            'label' => 'bug',
            'priority' => 'medium',
            'due_date' => now()->addDays(5)->format('Y-m-d'),
            'notify_before_expiry' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task',
            'description' => 'This is an updated task',
            'label' => 'bug',
            'priority' => 'medium',
            'notify_before_expiry' => 1,
        ]);
    }

    /**
     * Test moving a task to a different lane.
     *
     * @return void
     */
    public function test_can_move_task()
    {
        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'lane_id' => $this->lane->id,
            'order' => 0,
        ]);

        $newLane = Lane::create([
            'name' => 'New Lane',
            'order' => 1,
        ]);

        $response = $this->putJson(route('tasks.tasks.move', $task->id), [
            'lane_id' => $newLane->id,
            'order' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'lane_id' => $newLane->id,
            'order' => 0,
        ]);
    }

    /**
     * Test deleting a task.
     *
     * @return void
     */
    public function test_can_delete_task()
    {
        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'lane_id' => $this->lane->id,
            'order' => 0,
        ]);

        $response = $this->deleteJson(route('tasks.tasks.destroy', $task->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    /**
     * Test uploading a task attachment.
     *
     * @return void
     */
    public function test_can_upload_task_attachment()
    {
        Storage::fake('public');

        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'lane_id' => $this->lane->id,
            'order' => 0,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->postJson(route('tasks.tasks.attachments.store', $task->id), [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $task->id,
            'original_filename' => 'document.pdf',
            'mime_type' => 'application/pdf',
        ]);

        // Check that the file was stored
        $attachment = \Modules\Tasks\Models\TaskAttachment::where('task_id', $task->id)->first();
        Storage::disk('public')->assertExists($attachment->path);
    }

    /**
     * Test deleting a task attachment.
     *
     * @return void
     */
    public function test_can_delete_task_attachment()
    {
        Storage::fake('public');

        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'lane_id' => $this->lane->id,
            'order' => 0,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 1000);
        $path = $file->store('task-attachments/' . $task->id, 'public');

        $attachment = \Modules\Tasks\Models\TaskAttachment::create([
            'task_id' => $task->id,
            'filename' => basename($path),
            'original_filename' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'path' => $path,
            'size' => 1000,
        ]);

        $response = $this->deleteJson(route('tasks.tasks.attachments.destroy', [
            'task' => $task->id,
            'attachment' => $attachment->id,
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('task_attachments', [
            'id' => $attachment->id,
        ]);

        // Check that the file was deleted
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * Test the notifications page loads successfully.
     *
     * @return void
     */
    public function test_notifications_page_loads()
    {
        $response = $this->get(route('tasks.notifications'));
        $response->assertStatus(200);
        $response->assertViewIs('tasks::notifications');
        $response->assertViewHas('tasksAboutToExpire');
        $response->assertViewHas('overdueTasks');
    }

    /**
     * Test searching and filtering tasks.
     *
     * @return void
     */
    public function test_can_search_and_filter_tasks()
    {
        // Create some test tasks
        Task::create([
            'title' => 'High Priority Task',
            'description' => 'This is a high priority task',
            'lane_id' => $this->lane->id,
            'priority' => 'high',
            'label' => 'bug',
            'order' => 0,
        ]);

        Task::create([
            'title' => 'Medium Priority Task',
            'description' => 'This is a medium priority task',
            'lane_id' => $this->lane->id,
            'priority' => 'medium',
            'label' => 'feature',
            'order' => 1,
        ]);

        Task::create([
            'title' => 'Low Priority Task',
            'description' => 'This is a low priority task',
            'lane_id' => $this->lane->id,
            'priority' => 'low',
            'label' => 'enhancement',
            'order' => 2,
        ]);

        // Test search by title
        $response = $this->getJson(route('tasks.tasks.search', ['search' => 'High Priority']));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'tasks' => [
                ['title' => 'High Priority Task']
            ]
        ]);

        // Test filter by priority
        $response = $this->getJson(route('tasks.tasks.search', ['priority' => 'medium']));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'tasks' => [
                ['title' => 'Medium Priority Task']
            ]
        ]);

        // Test filter by label
        $response = $this->getJson(route('tasks.tasks.search', ['label' => 'bug']));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'tasks' => [
                ['title' => 'High Priority Task']
            ]
        ]);

        // Test filter by lane
        $response = $this->getJson(route('tasks.tasks.search', ['lane_id' => $this->lane->id]));
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'tasks');
    }
}
