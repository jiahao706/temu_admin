<?php

namespace App\Model;

use Encore\Admin\Traits\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemuCategory extends Model
{
    use SoftDeletes;

    use ModelTree;

    use AdminBuilder;


    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'temu_category';

    /**
     * 可以被批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = [
        'category_name',
        'pid',
        'created_at',
        'updated_at',
    ];

    public function __construct(array $attributes = [])
    {
        $this->parentColumn = "pid";
        $this->orderColumn="id";
        $this->titleColumn="category_name";
        parent::__construct($attributes);
    }
}
