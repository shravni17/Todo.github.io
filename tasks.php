<?php
require_once 'db.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all tasks
        $stmt = $pdo->query("SELECT * FROM tasks ORDER BY created_at DESC");
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $tasks]);
        break;
        
    case 'POST':
        // Create new task
        $input = json_decode(file_get_contents('php://input'), true);
        $text = $input['text'] ?? '';
        $completed = $input['completed'] ?? false;
        $location_data = $input['location_data'] ?? null;
        
        if (empty($text)) {
            http_response_code(400);
            echo json_encode(['error' => 'Task text is required']);
            break;
        }
        
        $stmt = $pdo->prepare("INSERT INTO tasks (text, completed, location_data) VALUES (?, ?, ?)");
        $stmt->execute([$text, $completed, $location_data]);
        
        $taskId = $pdo->lastInsertId();
        $stmt = $pdo->query("SELECT * FROM tasks WHERE id = $taskId");
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $task]);
        break;
        
    case 'PUT':
        // Update task
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Task ID is required']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $updates = [];
        $params = [];
        
        if (isset($input['text'])) {
            $updates[] = 'text = ?';
            $params[] = $input['text'];
        }
        
        if (isset($input['completed'])) {
            $updates[] = 'completed = ?';
            $params[] = $input['completed'];
        }
        
        if (isset($input['location_data'])) {
            $updates[] = 'location_data = ?';
            $params[] = $input['location_data'];
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            break;
        }
        
        $params[] = $id;
        $sql = "UPDATE tasks SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $stmt = $pdo->query("SELECT * FROM tasks WHERE id = $id");
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $task]);
        break;
        
    case 'DELETE':
        // Delete task or all tasks
        if (isset($_GET['id'])) {
            // Delete specific task
            $id = $_GET['id'];
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            // Delete all tasks
            $stmt = $pdo->prepare("DELETE FROM tasks");
            $stmt->execute();
        }
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>