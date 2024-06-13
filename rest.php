<?php
require 'config.php'; // Include your database configuration

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $allow_origin); // Update with your Angular app's URL
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, origin');


// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}



// Check for Authorization header
if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized. No header found"]);
    exit();
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'];
list($type, $receivedKey) = explode(' ', $authHeader);

if ($type !== 'Bearer' || $receivedKey !== $api_key) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized. Key invalid"]);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get the raw POST data
        $data = file_get_contents("php://input");
        $json_data = json_decode($data, true);
    
        if ($json_data === null) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid JSON"]);
            exit();
        }
    
        // Extract fields from JSON object
        $timestamp = date("Y-m-d H:i:s");
        $mac_address = isset($json_data['mac_address']) ? $json_data['mac_address'] : null;
        $overall_result = isset($json_data['overall_result']) ? $json_data['overall_result'] : null;
        $device_type = isset($json_data['device_type']) ? $json_data['device_type'] : null;
        $additional_info = isset($json_data['additional_info']) ? $json_data['additional_info'] : null;
    
        // Validate required fields
        if ($mac_address === null || $overall_result === null || $device_type === null || $additional_info === null) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required fields"]);
            exit();
        }
        $conn = new mysqli($servername, $username, $password, $dbname);
        // Start transaction
        $conn->begin_transaction();
    
        try {
            // Insert into test_results table
            $stmt = $conn->prepare("INSERT INTO test_results (timestamp, mac_address, overall_result, device_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $timestamp, $mac_address, $overall_result, $device_type);
    
            if (!$stmt->execute()) {
                throw new Exception("Failed to save test result");
            }
    
            // Get the last inserted ID
            $test_id = $stmt->insert_id;
            $stmt->close();
    
            // Insert each item in additional_info into test_result_items table
            $stmt = $conn->prepare("INSERT INTO test_result_items (test_id, name, value, result) VALUES (?, ?, ?, ?)");
            foreach ($additional_info as $item) {
                $name = $item['name'];
                $value = $item['value'];
                $result = $item['result'];
    
                $stmt->bind_param("isss", $test_id, $name, $value, $result);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to save test result item");
                }
            }
    
            // Commit transaction
            $conn->commit();
    
            http_response_code(201);
            echo json_encode(["message" => "Test result saved successfully"]);
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        } finally {
            $stmt->close();
            $conn->close();
        }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}


?>
