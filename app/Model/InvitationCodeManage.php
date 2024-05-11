<?php

namespace App\Model;

use App\Compoents\Common;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvitationCodeManage extends Model
{
    use SoftDeletes;

    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'invitation_code_manage';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'allow_times',
        'curr_times',
        'created_at',
        'updated_at',
    ];

    /**
     * 生成唯一邀请码
     * return int.
     */
    public function getInviteCode()
    {
        do {
            $id = $this->createCode();
            $row = $this->withTrashed()->find($id);//软删除的也不能使用。
        } while ($row);

        return $id;
    }

    /**
     * 生成ID算法
     * @return string
     */
    private function createCode()
    {
        $realParam = dechex(crc32($this->table . Common::getMsUnixTime() . random_int(111111, 999999)));

        $id = str_pad($realParam, 8, 0, STR_PAD_LEFT);

        return $id;
    }

}
