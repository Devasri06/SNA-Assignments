<?php
class Task {
    private $collection;
    private $userId;
    private $activityCollection;

    // Available priorities
    public static $PRIORITIES = ['low', 'medium', 'high'];

    // Available statuses
    public static $STATUSES = ['pending', 'in_progress', 'completed'];

    public function __construct($db, $userId) {
        $this->collection = $db->getCollection('tasks');
        $this->activityCollection = $db->getCollection('activity_log');
        $this->userId = $userId;
    }

    // Log activity for security audit
    private function logActivity($action, $details = '') {
        $this->activityCollection->insertOne([
            'user_id' => $this->userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => new MongoDB\BSON\UTCDateTime()
        ]);
    }

    // CREATE: Add a new task
    public function create($title, $description = '', $priority = 'medium', $dueDate = null) {
        // Defensive: Validate input
        if (empty($title) || strlen($title) > 255) {
            throw new Exception("Title is required and must be under 255 characters");
        }
        if (strlen($description) > 5000) {
            throw new Exception("Description must be under 5000 characters");
        }
        if (!in_array($priority, self::$PRIORITIES)) {
            $priority = 'medium';
        }

        $taskData = [
            'user_id' => $this->userId,
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
            'priority' => $priority,
            'status' => 'pending',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];

        if ($dueDate) {
            $taskData['due_date'] = new MongoDB\BSON\UTCDateTime(strtotime($dueDate) * 1000);
        }

        $result = $this->collection->insertOne($taskData);

        $this->logActivity('task_created', "Created task: $title");
        return $result->getInsertedId();
    }

    // READ: Get all tasks for user with optional filters
    public function getAll($search = null, $priorityFilter = null, $statusFilter = null) {
        $filter = ['user_id' => $this->userId];
        
        if ($search) {
            $filter['$or'] = [
                ['title' => ['$regex' => $search, '$options' => 'i']],
                ['description' => ['$regex' => $search, '$options' => 'i']]
            ];
        }
        
        if ($priorityFilter && in_array($priorityFilter, self::$PRIORITIES)) {
            $filter['priority'] = $priorityFilter;
        }

        if ($statusFilter && in_array($statusFilter, self::$STATUSES)) {
            $filter['status'] = $statusFilter;
        }

        $cursor = $this->collection->find(
            $filter,
            ['sort' => ['created_at' => -1]]
        );
        
        $tasks = [];
        foreach ($cursor as $doc) {
            $task = [
                'id' => (string)$doc['_id'],
                'title' => $doc['title'],
                'description' => $doc['description'] ?? '',
                'priority' => $doc['priority'] ?? 'medium',
                'status' => $doc['status'] ?? 'pending',
                'created_at' => $doc['created_at']->toDateTime()->format('M d, Y H:i')
            ];
            
            if (isset($doc['due_date'])) {
                $task['due_date'] = $doc['due_date']->toDateTime()->format('Y-m-d');
            }
            
            $tasks[] = $task;
        }
        return $tasks;
    }

    // READ: Get single task
    public function getById($taskId) {
        $task = $this->collection->findOne([
            '_id' => new MongoDB\BSON\ObjectId($taskId),
            'user_id' => $this->userId
        ]);

        if (!$task) {
            throw new Exception("Task not found");
        }

        $result = [
            'id' => (string)$task['_id'],
            'title' => $task['title'],
            'description' => $task['description'] ?? '',
            'priority' => $task['priority'] ?? 'medium',
            'status' => $task['status'] ?? 'pending',
            'created_at' => $task['created_at']->toDateTime()->format('M d, Y H:i')
        ];

        if (isset($task['due_date'])) {
            $result['due_date'] = $task['due_date']->toDateTime()->format('Y-m-d');
        }

        return $result;
    }

    // UPDATE: Update a task
    public function update($taskId, $title, $description = '', $priority = null, $status = null, $dueDate = null) {
        if (empty($title) || strlen($title) > 255) {
            throw new Exception("Title is required and must be under 255 characters");
        }

        $updateData = [
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];

        if ($priority && in_array($priority, self::$PRIORITIES)) {
            $updateData['priority'] = $priority;
        }

        if ($status && in_array($status, self::$STATUSES)) {
            $updateData['status'] = $status;
        }

        if ($dueDate) {
            $updateData['due_date'] = new MongoDB\BSON\UTCDateTime(strtotime($dueDate) * 1000);
        }

        $result = $this->collection->updateOne(
            [
                '_id' => new MongoDB\BSON\ObjectId($taskId),
                'user_id' => $this->userId
            ],
            ['$set' => $updateData]
        );

        if ($result->getModifiedCount() === 0) {
            throw new Exception("Task not found or not modified");
        }

        $this->logActivity('task_updated', "Updated task: $title");
        return true;
    }

    // Toggle status
    public function toggleStatus($taskId) {
        $task = $this->getById($taskId);
        $newStatus = $task['status'] === 'completed' ? 'pending' : 'completed';
        
        $result = $this->collection->updateOne(
            [
                '_id' => new MongoDB\BSON\ObjectId($taskId),
                'user_id' => $this->userId
            ],
            ['$set' => [
                'status' => $newStatus,
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]]
        );

        $this->logActivity('task_status_changed', "Changed status to: $newStatus");
        return $newStatus;
    }

    // DELETE: Delete a task
    public function delete($taskId) {
        $task = $this->getById($taskId);
        
        $result = $this->collection->deleteOne([
            '_id' => new MongoDB\BSON\ObjectId($taskId),
            'user_id' => $this->userId
        ]);

        if ($result->getDeletedCount() === 0) {
            throw new Exception("Task not found");
        }

        $this->logActivity('task_deleted', "Deleted task: " . $task['title']);
        return true;
    }

    // Get tasks count by status/priority
    public function getStats() {
        $total = $this->collection->countDocuments(['user_id' => $this->userId]);
        $completed = $this->collection->countDocuments(['user_id' => $this->userId, 'status' => 'completed']);
        $pending = $this->collection->countDocuments(['user_id' => $this->userId, 'status' => 'pending']);
        $high = $this->collection->countDocuments(['user_id' => $this->userId, 'priority' => 'high']);
        
        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'high_priority' => $high
        ];
    }

    // Get recent activity
    public function getActivity($limit = 10) {
        $cursor = $this->activityCollection->find(
            ['user_id' => $this->userId],
            ['sort' => ['timestamp' => -1], 'limit' => $limit]
        );

        $activities = [];
        foreach ($cursor as $doc) {
            $activities[] = [
                'action' => $doc['action'],
                'details' => $doc['details'],
                'timestamp' => $doc['timestamp']->toDateTime()->format('M d, H:i')
            ];
        }
        return $activities;
    }

    // Export tasks as JSON
    public function exportTasks() {
        $tasks = $this->getAll();
        $this->logActivity('tasks_exported', "Exported " . count($tasks) . " tasks");
        return $tasks;
    }
}
?>
