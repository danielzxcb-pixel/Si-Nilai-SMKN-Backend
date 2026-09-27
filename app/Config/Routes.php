<?php

namespace App\Config;

class Routes
{
    /**
     * Dispatch the current HTTP request to the matching controller
     */
    public static function dispatch(string $method, string $uri): bool
    {
        // 1. Health check
        if ($uri === '/api/health' && $method === 'GET') {
            echo json_encode(['status' => 'ok', 'timestamp' => date('Y-m-d H:i:s')]);
            return true;
        }

        // 2. Auth Routes
        if ($uri === '/api/login' && $method === 'POST') {
            \App\Controllers\AuthController::login();
            return true;
        }
        if ($uri === '/api/me' && $method === 'GET') {
            \App\Controllers\AuthController::me();
            return true;
        }
        if ($uri === '/api/admin/reset-password' && $method === 'POST') {
            \App\Controllers\AuthController::resetPassword();
            return true;
        }

        // 3. Grade Routes
        if ($uri === '/api/grades' && $method === 'GET') {
            \App\Controllers\GradeController::index();
            return true;
        }
        if ($uri === '/api/grades' && $method === 'POST') {
            \App\Controllers\GradeController::save();
            return true;
        }
        if ($uri === '/api/grades/submit' && $method === 'POST') {
            \App\Controllers\GradeController::submit();
            return true;
        }
        if (preg_match('#^/api/grade-categories/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
            \App\Controllers\GradeController::deleteCategory((int)$matches[1]);
            return true;
        }
        if ($uri === '/api/grade-formula-settings' && $method === 'GET') {
            \App\Controllers\FormulaSettingsController::index();
            return true;
        }
        if ($uri === '/api/grade-formula-settings' && ($method === 'POST' || $method === 'PUT')) {
            \App\Controllers\FormulaSettingsController::save();
            return true;
        }

        // 4. Site Content Routes
        if ($uri === '/api/site-content' && $method === 'GET') {
            \App\Controllers\Api\SiteContentController::index();
            return true;
        }
        if ($uri === '/api/site-content/bulk' && $method === 'POST') {
            \App\Controllers\Api\SiteContentController::bulkUpdate();
            return true;
        }
        if (preg_match('#^/api/site-content/([^/]+)$#', $uri, $matches) && $method === 'PUT') {
            \App\Controllers\Api\SiteContentController::updateSingle(urldecode($matches[1]));
            return true;
        }

        // 5. Monitoring Routes
        if ($uri === '/api/monitoring/stats' && $method === 'GET') {
            \App\Controllers\MonitoringController::stats();
            return true;
        }
        if ($uri === '/api/monitoring/deadline' && $method === 'POST') {
            \App\Controllers\MonitoringController::setDeadline();
            return true;
        }
        if ($uri === '/api/monitoring/return-revision' && $method === 'POST') {
            \App\Controllers\MonitoringController::returnRevision();
            return true;
        }

        // 6. Tracker Routes
        if ($uri === '/api/tracker/submissions' && $method === 'GET') {
            \App\Controllers\SubmissionTrackerController::index();
            return true;
        }

        // 7. Teacher Assignment & Admin Routes
        if (preg_match('#^/api/teachers/(\d+)/permissions$#', $uri, $matches) && $method === 'POST') {
            $controller = new \App\Controllers\Api\TeacherAssignmentController();
            $controller->updatePermissions((int)$matches[1]);
            return true;
        }
        if ($uri === '/api/admin/subjects' && $method === 'POST') {
            \App\Controllers\AdminController::createSubject();
            return true;
        }
        if (preg_match('#^/api/admin/subjects/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
            \App\Controllers\AdminController::deleteSubject((int)$matches[1]);
            return true;
        }
        if ($uri === '/api/admin/classes' && $method === 'POST') {
            \App\Controllers\AdminController::createClass();
            return true;
        }
        if (preg_match('#^/api/admin/classes/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
            \App\Controllers\AdminController::deleteClass((int)$matches[1]);
            return true;
        }
        if ($uri === '/api/admin/settings' && $method === 'POST') {
            \App\Controllers\AdminController::updateSiteSettings();
            return true;
        }
        if ($uri === '/api/admin/teachers' && $method === 'GET') {
            \App\Controllers\AdminController::listTeachers();
            return true;
        }
        if ($uri === '/api/admin/import-students' && $method === 'POST') {
            \App\Controllers\ImportExportController::importStudents();
            return true;
        }

        // 8. Archive Routes
        if ($uri === '/api/archive/student-grades' && $method === 'GET') {
            \App\Controllers\ArchiveController::studentGrades();
            return true;
        }
        if ($uri === '/api/archive/graduated' && $method === 'GET') {
            \App\Controllers\ArchiveController::graduatedList();
            return true;
        }

        return false;
    }
}
