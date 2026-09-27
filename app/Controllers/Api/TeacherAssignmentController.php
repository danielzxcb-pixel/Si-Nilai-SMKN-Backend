<?php

namespace App\Controllers\Api;

use App\Database\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use PDO;

class TeacherAssignmentController
{
    /**
     * Update teacher's assigned subjects and classes in real-time
     * POST /api/teachers/{id}/permissions
     */
    public function updatePermissions($teacherId = null): void
    {
        $currentUser = AuthMiddleware::authenticate();
        RoleMiddleware::authorize($currentUser, ['admin', 'waka']);

        $teacherId = (int)$teacherId;
        if ($teacherId <= 0) {
            http_response_code(422);
            echo json_encode([
                'status' => 422,
                'error' => 'Unprocessable Entity',
                'message' => 'Teacher ID tidak valid.'
            ]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $subjectIds = $input['subjects'] ?? []; // e.g. [1, 2] or ['1', '2']
        $classIds = $input['classes'] ?? [];    // e.g. [1, 2] or ['1', '2']

        $db = Database::connect();

        try {
            $db->beginTransaction();

            // 1. Clear existing assignments for this teacher
            $delStmt = $db->prepare("DELETE FROM teacher_assignments WHERE teacher_id = :tid");
            $delStmt->execute([':tid' => $teacherId]);

            // 2. Insert new assignment combinations
            $insertStmt = $db->prepare("
                INSERT INTO teacher_assignments (teacher_id, subject_id, class_id, created_at)
                VALUES (:tid, :sid, :cid, :created_at)
            ");
            $now = date('Y-m-d H:i:s');

            foreach ($subjectIds as $subjId) {
                foreach ($classIds as $clsId) {
                    $insertStmt->execute([
                        ':tid' => $teacherId,
                        ':sid' => $subjId,
                        ':cid' => $clsId,
                        ':created_at' => $now,
                    ]);
                }
            }

            $db->commit();

            // 3. Broadcast Real-Time Event via WebSocket Server if active
            $this->broadcastRealtimeEvent([
                'event' => 'TEACHER_PERMISSIONS_UPDATED',
                'data' => [
                    'teacher_id' => $teacherId,
                    'assigned_subjects' => $subjectIds,
                    'assigned_classes' => $classIds,
                    'updated_at' => date('d M Y H:i:s') . ' WIB',
                ]
            ]);

            http_response_code(200);
            echo json_encode([
                'status' => 200,
                'message' => 'Hak akses dan penugasan mengajar berhasil diperbarui seketika (Live Synced).',
                'data' => [
                    'teacher_id' => $teacherId,
                    'assigned_subjects' => $subjectIds,
                    'assigned_classes' => $classIds,
                    'updated_at' => date('d M Y H:i:s') . ' WIB',
                ]
            ]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error updating teacher permissions: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'status' => 500,
                'error' => 'Internal Server Error',
                'message' => 'Gagal memperbarui alokasi mengajar guru.'
            ]);
        }
    }

    /**
     * Broadcasts event to local Ratchet / ReactPHP WebSocket daemon (Port 8080)
     */
    private function broadcastRealtimeEvent(array $payload): void
    {
        try {
            $socket = @fsockopen('127.0.0.1', 8080, $errno, $errstr, 0.5);
            if ($socket) {
                fwrite($socket, json_encode($payload) . "\n");
                fclose($socket);
            }
        } catch (\Throwable $e) {
            // Silently continue if daemon is offline
        }
    }
}
