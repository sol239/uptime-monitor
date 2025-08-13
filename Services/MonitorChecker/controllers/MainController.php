<?php
class MainController {
    public function index() {
        if (!isset($_GET['monitor_id'])) {
            http_response_code(400);
            echo json_encode([
                "error" => "Missing required parameter: monitor_id"
            ]);
            return;
        }

        $monitorId = $_GET['monitor_id'];

        echo json_encode([
            "status" => "success",
            "monitor_id" => $monitorId,
            "message" => "Data retrieved successfully"
        ]);
    }
}
