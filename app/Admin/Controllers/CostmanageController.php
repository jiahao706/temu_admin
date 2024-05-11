<?php
/**
 * User: jiahao.dong
 * Date: 2023/4/27
 * Time: 上午12:25
 */
namespace App\Admin\Controllers;


use App\Export\SkuCostExport;
use App\Imports\SkuCostImport;
use App\Model\TemuGoodsSku;
use App\Service\AdminLayoutContentService;
use Encore\Admin\Admin;
use Encore\Admin\Form;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CostmanageController extends AdminController
{
    public function collectView(AdminLayoutContentService $content)
    {
        $allMall = \Admin::user()->mall_permissions;
        if(empty($allMall->toArray())){
            return $content
                ->title("Temu店铺成本录入")
                ->description($this->description['index'] ?? trans('admin.list'));
        }

        $allowExt = ["xlsx","xls","csv"];
        $form = new Form(new TemuGoodsSku());
        $form->radio('mall_id','选择店铺')
            ->options($allMall->pluck("mall_name","mall_id"))
            ->stacked();

        $form->file('resource', '上传文件')->options([
            'language'=>'zh',
            'maxFileCount'=>1,
            'maxFileSize'=>1024*500,
            'allowedFileExtensions'=>$allowExt,
            'dropZoneEnabled'=>true,
            'autoReplace'=>true,
            'browseOnZoneClick'=>true,
            'showPreview'=>true,
            'showUpload'=>true,
            'uploadLabel'=>'上传',
            'showRemove'=>true,
            'removeLabel'=>'删除',
            'showCancel'=>true,
            'msgSizeTooLarge'=>'文件"{name}" (<b>{size}KB</b>) 超过了允许的最大上传大小<b>{maxSize} KB</b>.',
            'msgInvalidFileExtension'=>'文件"{name}"的扩展名无效. 仅支持 "{extensions}" 文件.',
            'dropZoneTitle'=>'将文件拖放到此处',
            'dropZoneClickTitle'=>'或单击以选择文件',
            'msgFilesTooMany'=>'选择上载的文件数（2）超过了允许的最大限制1。',
            'uploadAsync' =>true,
            'uploadUrl' => '/admin/costmanage/upload',
            'uploadExtraData' => 'function(){
                var obj = {};
                if($(".iradio_minimal-blue").hasClass("checked")){
                     obj.mall_id=$(".iradio_minimal-blue.checked").children(".mall_id").val();
                }else{
                    obj.mall_id="";
                }
                obj._token ="'. csrf_token().'";
                obj._method="POST";
                return obj;
            }',
            'istrue'=>false,
            'layoutTemplates'=>[
                'actionDelete'=>'',
                'actionUpload'=>'',
                'actionZoom'=>'',
            ],
            'msgUploadThreshold'=>'上传中...',
            'msgUploadBegin'=>'开始上传...',
            'msgUploadEnd'=>'上传结束',
            'msgUploadEmpty'=>'请选择上传文件...',
            'msgValidationError'=>'上传失败',
        ])->help('可上传'.implode(",",$allowExt).'格式文件,小于500M');

        $js = <<<JS
    $('input.resource').on('filepreajax', function(event, previewId, index) {
        if(!$(".iradio_minimal-blue").hasClass("checked")){
            var container = $(".file-input");
            var processDiv = container.find('.kv-upload-progress');
            processDiv.hide();
            $('input.resource').fileinput('enable');

            swal({
                    title:'上传错误,请先选择店铺',
                    type:'info',
                });
            return false;
        }
    });
JS;
        Admin::script($js);

        $form->tools(function (Form\Tools $tools){
            $tools->disableList();
            $tools->disableView();
            $tools->disableDelete();
        });

        $form->header(function (Form\Tools $header){
            $html = '<div class="btn-group pull-right" style="margin-right: 10px">
                <a href="/admin/costmanage/export" target="_blank" class="btn btn-sm btn-twitter" title="下载成本模版">
                <i class="fa fa-download"></i>
                <span class="hidden-xs"> 下载成本模版</span>
                </a>
             </div>';
            $header->append($html);
        });

        $form->footer(function (Form\Footer $footer){
            $footer->disableEditingCheck();
            $footer->disableReset();
            $footer->disableSubmit();
            $footer->disableViewCheck();
            $footer->disableCreatingCheck();
        });
       return $content->body($form);
    }

    public function upload(Request $request)
    {
        $file = $request->file("resource");
        $mallId = $request->get("mall_id");
        if(empty($mallId)){
            return response()->json([
                'error'=>"请先选择店铺"
            ]);
        }
        if(!empty($file) && $file->isValid()){
            $allMall = \Admin::user()->mall_permissions;
            if(!empty($allMall->toArray()) && !in_array($mallId,$allMall->pluck("mall_id")->toArray())){
                return response()->json([
                    'error'=>"没有店铺操作权限"
                ]);
            }
            Excel::import(new SkuCostImport($mallId),$file);
            return response()->json([]);
        }else{
            $err = !empty($file)?$file->getErrorMessage():"上传失败";
            return response()->json([
                'error'=>$err
            ]);
        }
    }

    public function export()
    {
        return Excel::download(new SkuCostExport(),"成本模版.csv",\Maatwebsite\Excel\Excel::CSV, [
            'Content-Type' => 'text/csv;charset=UTF-8',
            'Content-Encoding' => 'UTF-8',
        ]);
    }
}
