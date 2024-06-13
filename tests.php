<?php
require 'config.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch test runs
$testRunsSql = "SELECT * FROM test_runs";
$testRunsResult = $conn->query($testRunsSql);

if ($testRunsResult->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Run Name</th><th>Run Date</th><th>Details</th></tr>";

    while($run = $testRunsResult->fetch_assoc()) {
        $runId = $run['id'];
        echo "<tr>";
        echo "<td>{$run['run_name']}</td>";
        echo "<td>{$run['run_date']}</td>";
        echo "<td><button onclick='toggleDetails($runId)'>Show Details</button></td>";
        echo "</tr>";
        
        // Fetch test results for each run
        $testResultsSql = "SELECT * FROM test_results WHERE run_id = $runId";
        $testResultsResult = $conn->query($testResultsSql);

        if ($testResultsResult->num_rows > 0) {
            echo "<tr id='details-$runId' style='display:none;'><td colspan='3'>";
            echo "<table border='1'>";
            echo "<tr><th>Test Name</th><th>Test Result</th></tr>";

            while($result = $testResultsResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$result['test_name']}</td>";
                echo "<td>{$result['test_result']}</td>";
                echo "</tr>";
            }

            echo "</table>";
            echo "</td></tr>";
        }
    }

    echo "</table>";
} else {
    echo "No test runs found.";
}

$conn->close();
?>

<script>
function toggleDetails(runId) {
    var detailsRow = document.getElementById('details-' + runId);
    if (detailsRow.style.display === 'none') {
        detailsRow.style.display = 'table-row';
    } else {
        detailsRow.style.display = 'none';
    }
}
</script>
