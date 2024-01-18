<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = $_POST['task'] ?? json_decode(file_get_contents('php://input'), true)['task'] ?? null;
    if ($task !== null) {
        // Sanitize the input.
        $task = trim(preg_replace('/[^[:graph:]]+/u', ' ', $task));
        // Check if the input is not empty.
        if ($task !== '') {
            // Open file with exclusive lock and write to it.
            $fp = fopen('tasks.txt', 'a+');
            if ($fp && flock($fp, LOCK_EX)) {
                // Only count lines if not regular POST.
                if (!isset($_POST['task'])) {
                    $id = count(explode("\n", stream_get_contents($fp)));
                }
                fwrite($fp, htmlspecialchars($task) . "\n");
                /* It is generally good practice to call flock($fp, LOCK_UN) and fclose($fp) before exiting a script,
                even if the script will exit immediately after these calls. */
                flock($fp, LOCK_UN);
                fclose($fp);
                // Sending the appropriate response.
                if (isset($_POST['task'])) {
                    header('Location: ' . $_SERVER['PHP_SELF']);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['id' => $id, 'task' => htmlspecialchars($task)]);
                }
            } else {
                http_response_code(500);
            }
            /* Since the script is designed to exit immediately after attempting to acquire the lock (even if it fails),
            the file descriptor would eventually be closed by the operating system when the script exits.
            Therefore, while leaving the file open without acquiring a lock is not ideal,
            it would not cause a significant problem in this particular scenario. */
        } else {
            http_response_code(400);
        }
    } else {
        http_response_code(400);
    }
    exit;
}

if (!empty($_GET['del']) && is_numeric($_GET['del'])) {
    $taskToDelete = $_GET['del'];
    $existingTasks = file('tasks.txt', FILE_IGNORE_NEW_LINES);
    unset($existingTasks[$taskToDelete - 1]);
    /* Use file_put_contents to update tasks.txt with modified tasks list after deletion. If $existingTasks is empty,
    the file will be overwritten with an empty string.
    Otherwise, implode tasks with newline and append to the file while locking it. */
    file_put_contents(
        filename: 'tasks.txt',
        data: !$existingTasks ? '' : implode("\n", $existingTasks) . "\n",
        flags: LOCK_EX
    );
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToDo App</title>
    <link rel="icon" href="data:,">
</head>
<body>
    <h1>ToDo App</h1>
    <h2>Tasks:</h2>
    <ul id="tasks">
        <?php

        $tasks = @file('tasks.txt', FILE_IGNORE_NEW_LINES);
        if ($tasks) {
            foreach ($tasks as $taskId => $task) {
                $taskId++;
                echo "<li>$task&nbsp;<a href=\"?del=$taskId\">Delete</a></li>";
            }
        } else {
            echo '<li id="no-tasks">No tasks found.</li>';
        }

        ?>
    </ul>
    <h2>Add Task:</h2>
    <form id="form" method="post">
        <label for="input">Task:</label>
        <input autocomplete="off" id="input" maxlength="80" name="input" required type="text">
        <button type="submit">Add</button>
    </form>
    <script>
        <?php echo file_get_contents('tasks.js'); ?>
    </script>
</body>
</html>
