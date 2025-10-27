<?php

namespace App\Http\Controllers;

class ErrorLogController extends Controller
{
    public function index()
    {
        $logPath = storage_path('logs/laravel.log');
        $logs = [];
        
        if (file_exists($logPath)) {
            $logContent = file_get_contents($logPath);
            // Ubah pattern untuk menangkap stack trace lengkap
            $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*ERROR:\s*([\s\S]*?)(?=\[\d{4}-\d{2}-\d{2}|$)/';
            
            preg_match_all($pattern, $logContent, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $logs[] = [
                    'datetime' => $match[1],
                    'message' => trim($match[2])  // Pesan error lengkap termasuk stack trace
                ];
            }
            
            // Sort logs by datetime (newest first)
            usort($logs, function($a, $b) {
                return strtotime($b['datetime']) - strtotime($a['datetime']);
            });
        }
    
        return view('error-logs.index', compact('logs'));
    }

    public function clear()
    {
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            unlink($logPath);
        }

        return redirect()->route('error-logs.index')
            ->with('success', 'Log file has been cleared.');
    }
}