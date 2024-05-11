<?php
/**
 * User: jiahao.dong
 * Date: 2023/4/19
 * Time: 下午3:41
 */
namespace App\Service;

use Encore\Admin\Layout\Content;

class AdminLayoutContentService extends Content
{
    /**
     * Render this content.
     *
     * @return string
     */
    public function render()
    {
        $items = [
            'header'      => $this->title,
            'description' => $this->description,
            'breadcrumb'  => $this->breadcrumb,
            '_content_'   => $this->build(),
            '_view_'      => $this->view,
            '_user_'      => $this->getUserData(),
        ];

        return view('admin::custom_content', $items)->render();
    }
}
