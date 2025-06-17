# Tasks Module

The Tasks Module provides a Kanban-style task management system for the Smart Home Hub. It allows users to create, organize, and track tasks across different lanes (columns).

## Features

### Lane Management
- Create, edit, and delete lanes to organize tasks
- Drag and drop lanes to reorder them
- Each lane represents a stage in your workflow (e.g., "To Do", "In Progress", "Done")

### Task Management
- Create tasks with detailed information:
  - Title
  - Rich text description (using TinyMCE editor)
  - Priority levels (Low, Medium, High)
  - Custom labels (Bug, Feature, Enhancement, Documentation, Question, or custom)
  - Due dates
  - Notifications for upcoming due dates
- Edit and delete tasks
- Drag and drop tasks between lanes or to reorder within a lane
- View task details in a modal

### Task Attachments
- Upload file attachments to tasks
- View and download attachments
- Delete attachments when no longer needed

### Task Filtering and Search
- Search tasks by title or description
- Filter tasks by:
  - Priority
  - Label
  - Due date (Overdue, Due Today, Due This Week, etc.)
- Clear filters with a single click

### Task Notifications
- View tasks that are about to expire
- View overdue tasks
- Enable notifications for tasks with approaching due dates

## User Interface

### Tasks Board
The main interface is a Kanban board with lanes and tasks. Each task is displayed as a card with key information visible at a glance:
- Title
- Description (truncated if long)
- Priority indicator
- Label
- Due date
- Attachment count
- Overdue or "Due Soon" indicators

### Task Details
Clicking on a task opens a detailed view with:
- Full description with rich text formatting
- All metadata (priority, label, due date)
- Attachment management

### Task Creation and Editing
Modal forms for creating and editing tasks with:
- Title and description fields
- Dropdown menus for priority and predefined labels
- Date picker for due dates
- Attachment upload interface

## Technical Implementation

### Models
- **Lane**: Represents a column on the Kanban board
- **Task**: The main task entity with all task properties
- **TaskAttachment**: Stores file attachments for tasks

### Controllers
- **TasksController**: Handles all task-related actions
  - Lane CRUD operations
  - Task CRUD operations
  - Task movement between lanes
  - Attachment management
  - Task search and filtering

### Services
- **TasksService**: Contains business logic for task operations
  - Lane management
  - Task management
  - Attachment handling
  - Task notifications

### Views
- Kanban board layout with lanes and tasks
- Task cards with responsive design
- Modal forms for task creation and editing
- Task detail view

### JavaScript
- Drag and drop functionality using SortableJS
- Rich text editing with TinyMCE
- AJAX requests for seamless interaction
- Responsive UI components

## Getting Started

1. Navigate to the Tasks module from the main dashboard
2. Create your first lane by clicking "Add Lane"
3. Add tasks to your lane by clicking "Add Task" in the lane header
4. Organize your tasks by dragging them between lanes
5. Track your progress and manage your workflow
