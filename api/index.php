<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require 'config.php';

$database = new Database();
$db = $database->getConnection();

$request_method = $_SERVER["REQUEST_METHOD"];
$url = isset($_GET['url']) ? explode('/', rtrim($_GET['url'], '/')) : [];

switch($request_method) {
    case 'GET':
        if (!empty($url[0]) && $url[0] == 'users') {
            if (isset($url[1])) {
                getUser($url[1]);
            } else {
                getUsers();
            }
        }
        break;
    case 'POST':
        if (!empty($url[0]) && $url[0] == 'users') {
            createUser();
        }
        break;
    case 'PUT':
        if (!empty($url[0]) && $url[0] == 'users' && isset($url[1])) {
            updateUser($url[1]);
        }
        break;
    case 'DELETE':
        if (!empty($url[0]) && $url[0] == 'users' && isset($url[1])) {
            deleteUser($url[1]);
        }
        break;
    default:
        header("HTTP/1.0 405 Method Not Allowed");
        break;
}

function getUsers() {
    global $db;
    $query = "SELECT * FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
}

function getUser($id) {
    global $db;
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($user);
}

function createUser() {
    global $db;
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if name and email fields are present
    if (isset($data['name']) && isset($data['email'])) {
        $name = $data['name'];
        $email = $data['email'];

        // Ensure name and email are not empty
        if (!empty($name) && !empty($email)) {
            $query = "INSERT INTO users (name, email) VALUES (:name, :email)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);

            try {
                if ($stmt->execute()) {
                    echo json_encode(["message" => "User created successfully."]);
                } else {
                    echo json_encode(["message" => "Failed to create user."]);
                }
            } catch (PDOException $e) {
                echo json_encode([
                    "message" => "Database error: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "message" => "Name and email fields cannot be empty."
            ]);
        }
    } else {
        echo json_encode([
            "message" => "Name and email fields are required."
        ]);
    }
}


function updateUser($id) {
    global $db;
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if name and email fields are present
    if (isset($data['name']) && isset($data['email'])) {
        $name = $data['name'];
        $email = $data['email'];

        // Ensure name and email are not empty
        if (!empty($name) && !empty($email)) {
            try {
                // Check if the email already exists for another user
                $query = "SELECT id FROM users WHERE email = :email AND id != :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    echo json_encode([
                        "message" => "Email already exists for another user."
                    ]);
                } else {
                    // Update user if email is unique
                    $query = "UPDATE users SET name = :name, email = :email WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':id', $id);
                    if ($stmt->execute()) {
                        echo json_encode(["message" => "User updated successfully."]);
                    } else {
                        echo json_encode(["message" => "Failed to update user."]);
                    }
                }
            } catch (PDOException $e) {
                echo json_encode([
                    "message" => "Database error: " . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                "message" => "Name and email fields cannot be empty."
            ]);
        }
    } else {
        echo json_encode([
            "message" => "Name and email fields are required."
        ]);
    }
}


function deleteUser($id) {
    global $db;
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$id])) {
        echo json_encode(["message" => "User deleted successfully."]);
    } else {
        echo json_encode(["message" => "Failed to delete user."]);
    }
}
?>
