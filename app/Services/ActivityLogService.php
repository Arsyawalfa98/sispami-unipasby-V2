<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogService
{
    public static function log($action, $module, $description, $subject = null, $oldData = null, $newData = null)
    {
        try {
            //get Aktif Role
            $activeRole = session('active_role');
            // Bersihkan data dari karakter escape
            if (is_array($oldData)) {
                array_walk_recursive($oldData, function(&$item) {
                    if (is_string($item)) {
                        $item = stripslashes($item);
                    }
                });
            }
            
            if (is_array($newData)) {
                array_walk_recursive($newData, function(&$item) {
                    if (is_string($item)) {
                        $item = stripslashes($item);
                    }
                });
            }

            $logData = [
                'user_id' => Auth::id() ?? 0,
                'action' => $action,
                'roleactive' => $activeRole,
                'module' => $module,
                'description' => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject ? $subject->id : null,
                'old_data' => is_array($oldData) ? json_encode($oldData, JSON_UNESCAPED_SLASHES) : $oldData,
                'new_data' => is_array($newData) ? json_encode($newData, JSON_UNESCAPED_SLASHES) : $newData
            ];

            ActivityLog::create($logData);

        } catch (\Exception $e) {
            Log::error('Failed to log activity', [
                'error' => $e->getMessage(),
                'action' => $action,
                'module' => $module,
                'description' => $description
            ]);
        }
    }
}