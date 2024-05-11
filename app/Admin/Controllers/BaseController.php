<?php
/**
 * User: jiahao.dong
 * Date: 2023/4/19
 * Time: 下午8:25
 */
namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;

class BaseController extends Controller{
    public function __construct()
    {
        if(env("DEBUG_SQL")){
            $this->debugSql();
        }
    }

    private function debugSql()
    {
        \DB::listen(
            function ($sql) {
                foreach ($sql->bindings as $i => $binding) {
                    if ($binding instanceof \DateTime) {
                        $sql->bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                    } else {
                        if (is_string($binding)) {
                            $sql->bindings[$i] = "'$binding'";
                        }
                    }
                }

                // Insert bindings into query
                $query = str_replace(array('%', '?'), array('%%', '%s'), $sql->sql);

                $query = vsprintf($query, $sql->bindings);

                // Save the query to file
                $logFile = fopen(
                    storage_path('logs' . DIRECTORY_SEPARATOR . date('Y-m-d') . '_query.log'),
                    'a+'
                );
                fwrite($logFile, date('Y-m-d H:i:s') . ': ' . $query . PHP_EOL);
                fclose($logFile);
            }
        );
    }

    public function jsonSuccess($msg="success",$status=200,$data="")
    {
        return response()->json([
            "status"=>$status,
            "msg"=>$msg,
            "data"=>$data
        ]);
    }

    public function jsonError($data,$status=500,$msg="error")
    {
        return response()->json([
            "status"=>$status,
            "msg"=>$msg,
            "data"=>$data
        ]);
    }
}
