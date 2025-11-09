<?php

namespace KevinBHarris\EbayConnector\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use KevinBHarris\EbayConnector\Models\EbaySyncLog;

class LogController extends Controller
{
    /**
     * Display logs page.
     */
    public function index(Request $request)
    {
        $query = EbaySyncLog::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('ebayconnector::admin.logs.index', compact('logs'));
    }

    /**
     * Delete a log entry.
     */
    public function destroy(int $id): JsonResponse
    {
        $log = EbaySyncLog::findOrFail($id);
        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'Log deleted successfully',
        ]);
    }

    /**
     * Clear all logs.
     */
    public function clear(): JsonResponse
    {
        $retentionDays = core()->getConfigData('online_merchants.ebay_connector.logging.retention_days') ?? 30;
        
        $deleted = EbaySyncLog::where('created_at', '<', now()->subDays($retentionDays))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Cleared {$deleted} old log entries",
        ]);
    }
}
