<?php

namespace App\Admin\Controllers;

use App\Service\AdminLayoutContentService;
use Encore\Admin\Controllers\HasResourceActions;


class AdminController extends BaseController
{
    use HasResourceActions;

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Title';

    /**
     * Set description for following 4 action pages.
     *
     * @var array
     */
    protected $description = [
        //        'index'  => 'Index',
        //        'show'   => 'Show',
        //        'edit'   => 'Edit',
        //        'create' => 'Create',
    ];

    /**
     * Get content title.
     *
     * @return string
     */
    protected function title()
    {
        return $this->title;
    }

    /**
     * Index interface.
     *
     * @param AdminLayoutContentService $content
     *
     * @return AdminLayoutContentService
     */
    public function index(AdminLayoutContentService $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['index'] ?? trans('admin.list'))
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param AdminLayoutContentService $content
     *
     * @return AdminLayoutContentService
     */
    public function show($id, AdminLayoutContentService $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['show'] ?? trans('admin.show'))
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param AdminLayoutContentService $content
     *
     * @return AdminLayoutContentService
     */
    public function edit($id, AdminLayoutContentService $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param AdminLayoutContentService $content
     *
     * @return AdminLayoutContentService
     */
    public function create(AdminLayoutContentService $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['create'] ?? trans('admin.create'))
            ->body($this->form());
    }
}
