<?php
/**
 * User: jiahao.dong
 * Date: 2023/4/30
 * Time: 上午2:55
 */
 namespace App\Compoents;

 use Encore\Admin\Grid\Filter\Between;

 class CustomFilterBetween extends Between
 {
     /**
      * Build conditions of filter.
      *
      * @return mixed
      */
     public function buildCondition()
     {
         return [$this->query => func_get_args()];
     }

 }
