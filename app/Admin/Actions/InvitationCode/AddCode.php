<?php

namespace App\Admin\Actions\InvitationCode;

use App\Model\InvitationCodeManage;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class AddCode extends Action
{
    protected $selector = '.add-code';

    public function handle(Request $request)
    {
        // $request ...
        $codeNum = $request->get("code_num");
        $codeTimes = $request->get("code_times");
        if(!empty($codeNum) && !empty($codeTimes)){
            $model = new InvitationCodeManage();
            for($i = 0;$i<$codeNum;$i++){
                $code = $model->getInviteCode();
                InvitationCodeManage::create([
                    "code"=>$code,
                    "allow_times"=>(int)$codeTimes,
                ]);
            }
        }
        return $this->response()->success('Success message...')->refresh();
    }

    public function form()
    {
        $this->name ="生成邀请码";
        $this->text('code_num', '生成邀请码个数')->default(1)->rules("required|integer|min:1|max:100",[
            "code_num.require"=>"邀请码个数必须填写",
            "code_num.integer"=>"邀请码个数必须为整数",
            "code_num.min"=>"邀请码个数最小为1",
            "code_num.max"=>"邀请码个数最大为100",
        ]);
        $this->text('code_times', '邀请码可使用次数')->default(10)->rules("integer|min:1|max:100000",[
            "code_num.integer"=>"邀请码可使用次数必须为整数",
            "code_num.min"=>"邀请码可使用次数最小为1",
            "code_num.max"=>"邀请码可使用次数最大为100000",
        ]);
    }


    public function html()
    {
        return <<<HTML
        <div class="btn-group">
                <a class="btn btn-sm btn-success add-code">
        <i class="fa fa-plus"></i>
        <span class="hidden-xs">生成邀请码</span>
        </a>
</div>
HTML;
    }
}
