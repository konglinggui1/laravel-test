<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SqlExport;
use Illuminate\Support\Facades\Storage;

class DevController extends Controller
{
    // 确保只有admin可以访问
    public function __construct()
    {
        $this->middleware(function ($request , $next) {
            if (!auth()->user()->hasRole('admin')) {
                abort(403 , 'Unauthorized');
            }
            return $next($request);
        });
    }

    // 显示页面
    public function index()
    {
        return view('dev.index');
    }

    // 执行SQL查询并分页
    public function executeSql(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->validate($request , [
            'sql'  => 'required|string' ,
            'page' => 'nullable|integer|min:1' ,
        ]);

        $sql  = $request->input('sql');
        $page = $request->input('page' , 1);

        // 只允许执行 SELECT 语句
        if (stripos($sql , 'select') !== 0) {
            return response()->json(['error' => '只允许SELECT查询。']);
        }

        // 使用分页查询
        try {
            $results           = DB::select($this->applyPagination($sql , $page));
            $totalResultsCount = DB::table(DB::raw("({$sql}) AS total"))->count();
            $totalPages        = ceil($totalResultsCount / 10);

            // 记录SQL日志
            $this->logSqlQuery($sql , null);

            return response()->json([
                'results'      => $results ,
                'total_pages'  => $totalPages ,
                'current_page' => $page ,
            ]);
        } catch (\Exception $e) {
            // 记录错误日志
            $this->logSqlQuery($sql , $e->getMessage());
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    // 分页SQL处理
    private function applyPagination($sql , $page): string
    {
        $offset = ($page - 1) * 10;  // 每页 10 条记录
        return $sql . " LIMIT $offset, 10";
    }

    // SQL日志记录
    private function logSqlQuery($sql , $error)
    {
        DB::table('sql_logs')->insert([
            'user_id'    => auth()->id() ,
            'sql'        => $sql ,
            'error'      => $error ,
            'created_at' => now() ,
        ]);
    }

    // 导出为Excel
    public function exportExcel(Request $request): \Illuminate\Http\JsonResponse
    {
        $sql = $request->input('sql');
        try {
            $results  = DB::select($sql);
            $fileName = 'export_' . time() . '.xlsx';
            $filePath = 'exports/' . $fileName;

            // 使用Excel导出
            Excel::store(new SQLExport($results) , $filePath , 'public');
            return response()->json(['file_url' => Storage::url($filePath)]);
        } catch (\Exception $e) {
            return response()->json(['error' => '导出失败: ' . $e->getMessage()]);
        }
    }

    // 导出为JSON
    public function exportJson(Request $request): \Illuminate\Http\JsonResponse
    {
        $sql = $request->input('sql');
        try {
            $results      = DB::select($sql);
            $resultsArray = json_decode(json_encode($results) , true);
            $fileName     = 'export_' . time() . '.json';
            $filePath     = 'exports/' . $fileName;

            // 使用Storage保存JSON文件
            Storage::disk('public')->put($filePath , json_encode($resultsArray , JSON_PRETTY_PRINT));
            return response()->json(['file_url' => Storage::url($filePath)]);
        } catch (\Exception $e) {
            return response()->json(['error' => '导出失败: ' . $e->getMessage()]);
        }
    }
}
