<?php
/**
 * User: jiahao.dong
 * Date: 2023/5/26
 * Time: 上午8:20
 */
namespace App\Compoents;


use Closure;
use Encore\Admin\Form;

class CustomForm extends Form
{
    public function footer(Closure $callback = null)
    {
        $footer = new CustomFooter($this->builder());
        if (func_num_args() === 0) {
            return $footer ;
        }

        $callback($footer);
    }
}
