<?php
$servername = "localhost:3308";
$username = "root";
$password = "";
$dbname = "erpportal";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function executeQuery($query, $params = [], $types = '', $single = false) {
    global $conn;

    if ($stmt = $conn->prepare($query)) {
        if ($params) {
            if (empty($types)) {
                // Automatically generate the types string if not provided
                $types = str_repeat('s', count($params)); // Assuming all params are strings
            }
            $stmt->bind_param($types, ...$params);
        }

        if ($stmt->execute()) {
            logQuery($query, $params);

            $result = $stmt->get_result();

            if ($result !== false) {
                if ($single) {
                    $data = $result->fetch_assoc();
                    $stmt->close();
                    return $data ? array_values($data)[0] : null;
                } else {
                    $data = $result->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    return $data;
                }
            } else {
                $affected_rows = $stmt->affected_rows;
                $stmt->close();
                return $affected_rows;
            }
        } else {
            logError("Execute failed: " . $stmt->error);
            $stmt->close();
            die("Execute failed: " . $stmt->error);
        }
    } else {
        logError("Prepare failed: " . $conn->error);
        die("Prepare failed: " . $conn->error);
    }
}

function logQuery($query, $params) {
    global $conn;

    // Avoid logging the query if already logging another query
    static $logging = false;
    if ($logging) return;

    $logging = true;
    $queryString = $conn->real_escape_string($query);
    $paramsString = $conn->real_escape_string(json_encode($params));
    $sql = "INSERT INTO query_logs (query, params) VALUES ('$queryString', '$paramsString')";

    // Execute the logging query on a new MySQLi connection
    $logConn = new mysqli($GLOBALS['servername'], $GLOBALS['username'], $GLOBALS['password'], $GLOBALS['dbname']);
    if ($logConn->connect_error) {
        logError("Log connection failed: " . $logConn->connect_error);
    } else {
        $logConn->query($sql);
        $logConn->close();
    }

    $logging = false;
}

function logError($error) {
    error_log($error, 3, 'log.txt');
}

?>