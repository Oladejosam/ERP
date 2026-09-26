<?php
namespace App\Controllers;

use PDO;

class ChatbotController {
    protected PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function chat(): void {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');
        if ($message === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Message is required.']);
            exit;
        }

        $companyId = $_SESSION['company_id'] ?? 1;
        $userRole = $_SESSION['user_role'] ?? 'Staff';

        // Fetch scoped ERP context
        $stmtProjects = $this->db->prepare("SELECT project_number, name, site_location, progress_percent, status FROM projects WHERE company_id = :cid");
        $stmtProjects->execute([':cid' => $companyId]);
        $projects = $stmtProjects->fetchAll(PDO::FETCH_ASSOC);

        $stmtItems = $this->db->prepare("SELECT item_code, name, current_stock, reorder_level, unit FROM inventory_items WHERE current_stock <= reorder_level LIMIT 10");
        $stmtItems->execute();
        $lowStock = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $stmtReqs = $this->db->prepare("SELECT requisition_no, urgency, total_amount FROM requisitions WHERE status = 'pending' LIMIT 10");
        $stmtReqs->execute();
        $pendingReqs = $stmtReqs->fetchAll(PDO::FETCH_ASSOC);

        // Check if Gemini API key exists in environment
        $apiKey = getenv('GEMINI_API_KEY');
        if (!empty($apiKey)) {
            $context = [
                'userRole' => $userRole,
                'projects' => $projects,
                'lowStockItems' => $lowStock,
                'pendingRequisitions' => $pendingReqs
            ];

            $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key=" . $apiKey);
            $prompt = "You are the ERP assistant for a construction company. Answer user inquiry based strictly on this ERP data: " . json_encode($context) . "\n\nUser Question: " . $message;
            $payload = json_encode([
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $result) {
                $responseJson = json_decode($result, true);
                $replyText = $responseJson['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($replyText) {
                    echo json_encode(['reply' => $replyText]);
                    exit;
                }
            }
        }

        // Knowledge Engine Fallback
        $q = strtolower($message);
        if (str_contains($q, 'project') || str_contains($q, 'active')) {
            $reply = "There are currently " . count($projects) . " active projects in your portfolio:\n";
            foreach ($projects as $p) {
                $reply .= "• {$p['name']} ({$p['project_number']}): {$p['progress_percent']}% complete [{$p['status']}]\n";
            }
        } elseif (str_contains($q, 'stock') || str_contains($q, 'material') || str_contains($q, 'reorder')) {
            if (!empty($lowStock)) {
                $reply = "⚠️ Low Stock Alert: " . count($lowStock) . " materials are at or below reorder level:\n";
                foreach ($lowStock as $i) {
                    $reply .= "• {$i['name']}: {$i['current_stock']} {$i['unit']} (Reorder level: {$i['reorder_level']})\n";
                }
            } else {
                $reply = "All central warehouse materials are currently above their reorder thresholds.";
            }
        } elseif (str_contains($q, 'requisition') || str_contains($q, 'pending')) {
            $reply = "There are " . count($pendingReqs) . " pending material requisitions awaiting approval in the ERP.";
        } else {
            $reply = "Hello! I am your Construction ERP Assistant. Ask me about active projects, warehouse low stock alerts, or pending material requisitions.";
        }

        echo json_encode(['reply' => $reply]);
    }
}
