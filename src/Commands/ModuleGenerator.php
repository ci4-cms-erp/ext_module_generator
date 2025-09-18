<?php

namespace ext_module_generator\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ModuleGenerator extends BaseCommand
{
    protected $group = 'Ci4MS';
    protected $name = 'make:module';
    protected $description = 'Creates a custom module structure for ci4ms.';

    public function run(array $params)
    {
        $moduleName = $params[0] ?? CLI::prompt('Enter module name (example: Admin, User)', null, 'required');
        $moduleName = ucfirst($moduleName);
        $modulePath = ROOTPATH . 'modules/' . $moduleName;

        $this->createModuleStructure($modulePath, $moduleName);

        CLI::write("'{$moduleName}' module successfully created!", 'green');
    }

    protected function createModuleStructure($modulePath, $moduleName)
    {
        // Create directories
        $folders = [
            $modulePath . '/Config',
            $modulePath . '/Controllers',
            $modulePath . '/Database/Migrations',
            $modulePath . '/Database/Seeds',
            $modulePath . '/Helpers',
            $modulePath . '/Language/en',
            $modulePath . '/Language/tr',
            $modulePath . '/Libraries',
            $modulePath . '/Models',
            $modulePath . '/Validation',
            $modulePath . '/Views',
        ];

        foreach ($folders as $folder) {
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }
        }

        // Create files
        $this->createFile($modulePath . '/Config/' . $moduleName . 'Config.php', $this->getConfigTemplate($moduleName));
        $this->createFile($modulePath . '/Config/Routes.php', $this->getRoutesTemplate($moduleName));
        $this->createFile($modulePath . '/Controllers/' . $moduleName . '.php', $this->getControllerTemplate($moduleName));
        $this->createFile($modulePath . '/Language/en/' . $moduleName . '.php', $this->getLanguageTemplate($moduleName, 'en'));
        $this->createFile($modulePath . '/Language/tr/' . $moduleName . '.php', $this->getLanguageTemplate($moduleName, 'tr'));

        $this->createFile($modulePath . '/Views/create.php', $this->getViewTemplate('<a href="'.route_to(lcfirst($moduleName)).'" class="btn btn-outline-info">'.lang('Backend.backToList').'</a>'));
        $this->createFile($modulePath . '/Views/list.php', $this->getViewTemplate('<a href="'.route_to(lcfirst($moduleName).'Create').'" class="btn btn-outline-success">'.lang('Backend.add').'</a>'));
        $this->createFile($modulePath . '/Views/update.php', $this->getViewTemplate('<a href="'.route_to(lcfirst($moduleName)).'" class="btn btn-outline-info">'.lang('Backend.backToList').'</a>'));
    }

    protected function createFile($path, $content)
    {
        if (!file_exists($path)) {
            file_put_contents($path, $content);
        }
    }

    protected function getConfigTemplate($moduleName)
    {
        $l_moduleName=lcfirst($moduleName);
        return <<<EOD
<?php
namespace Modules\\{$moduleName}\\Config;

class {$moduleName}Config {
    public \$csrfExcept = [
        'backend/{$l_moduleName}','backend/{$l_moduleName}/*'
    ];

    public \$filters=[
        'backendAfterLoginFilter' => ['before' => [
            'backend/{$l_moduleName}','backend/{$l_moduleName}/*'
            ]
        ]
    ];
}
EOD;
    }

    protected function getRoutesTemplate($moduleName)
    {
        $l_moduleName = lcfirst($moduleName);
        return <<<EOD
<?php
\$routes->group('backend/{$moduleName}', ['namespace' => 'Modules\\{$moduleName}\\Controllers'], function(\$routes) {
    \$routes->match(['GET', 'POST'], '/', '{$moduleName}::index',['as' => '{$l_moduleName}', 'role' => 'read']);
    \$routes->match(['GET', 'POST'], 'create', '{$moduleName}::create', ['as' => '{$l_moduleName}Create', 'role' => 'create']);
    \$routes->match(['GET', 'POST'], 'update/(:num)', '{$moduleName}::update/$1', ['as' => '{$l_moduleName}Update', 'role' => 'update']);
    \$routes->get('delete', '{$moduleName}::delete(:num)',['as' => '{$l_moduleName}Delete/$1', 'role' => 'delete']);
});
EOD;
    }

    protected function getControllerTemplate($moduleName)
    {
        $l_moduleName=\lcfirst($moduleName);
        return <<<EOD
<?php
namespace Modules\\{$moduleName}\\Controllers;

class {$moduleName} extends \Modules\Backend\Controllers\BaseController {
    public function index() {
    if (\$this->request->is('post') && \$this->request->isAJAX()) {
            \$data = clearFilter(\$this->request->getPost());
            \$like = \$data['search']['value'];
            \$l = [];
            \$postData = [];

            if (!empty(\$like)) \$l = ['your_filed/s' => \$like];
            \$results = \$this->commonModel->lists('your_table', '*', \$postData, 'id ASC', (\$data['length'] == '-1') ? 0 : (int)\$data['length'], (\$data['length'] == '-1') ? 0 : (int)\$data['start'], \$l);
            \$totalRecords = count(\$this->commonModel->lists('your_table', '*', \$postData, 'id ASC', 0, 0, \$l));
            \$totalDisplayRecords = \$totalRecords;
            foreach (\$results as \$result) {
                \$result->actions = '<a href="' . route_to('{$l_moduleName}Update', \$result->id) . '" class="btn btn-default btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
                </svg>
            </a> <a href="' . route_to('{$l_moduleName}Delete', \$result->id) . '" class="btn btn-default btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash2-fill" viewBox="0 0 16 16">
                      <path d="M2.037 3.225A.7.7 0 0 1 2 3c0-1.105 2.686-2 6-2s6 .895 6 2a.7.7 0 0 1-.037.225l-1.684 10.104A2 2 0 0 1 10.305 15H5.694a2 2 0 0 1-1.973-1.671zm9.89-.69C10.966 2.214 9.578 2 8 2c-1.58 0-2.968.215-3.926.534-.477.16-.795.327-.975.466.18.14.498.307.975.466C5.032 3.786 6.42 4 8 4s2.967-.215 3.926-.534c.477-.16.795-.327.975-.466-.18-.14-.498-.307-.975-.466z"/>
                </svg>
            </a>';
            }

            \$data = [
                'draw' => intval(\$data['draw']),
                'iTotalRecords' => \$totalRecords,
                'iTotalDisplayRecords' => \$totalDisplayRecords,
                'aaData' => \$results,
            ];
            return \$this->respond(\$data, 200);
        }
        return view('Modules\\{$moduleName}\\Views\\list', \$this->defData);
    }

    public function create() {
        if (\$this->request->is('post')) {
            \$vdata = [
                ''=>['label'=>'', 'rules'=>''],
            ];
            \$valData = (\$vdata);
            if (\$this->validate(\$valData) == false) return redirect()->back()->withInput()->with('errors', \$this->validator->getErrors());
        }
        return view('Modules\\{$moduleName}\\Views\create', \$this->defData);
    }

    public function update(int \$id) {
        if (\$this->request->is('post')) {
            \$vdata = [
                ''=>['label'=>'', 'rules'=>''],
            ];
            \$valData = (\$vdata);
            if (\$this->validate(\$valData) == false) return redirect()->back()->withInput()->with('errors', \$this->validator->getErrors());
        }
        return view('Modules\\{$moduleName}\\Views\update', \$this->defData);
    }

    public function delete(int \$id)
    {
        \$infos=\$this->commonModel->selectOne('your_table',['id'=>\$id]);
        if(\$this->commonModel->remove('your_table',['id'=>\$id]))
            return \$this->redirect()->back()->with('success',\$infos->attr.' deleted.');
        return \$this->redirect()->back()->with('error','Can not deleted.');
    }
}
EOD;
    }

    protected function getLanguageTemplate($moduleName, $lang)
    {
        $langName = $lang === 'en' ? 'English' : 'Türkçe';
        return <<<EOD
<?php

return [
    'welcome' => 'Welcome to {$moduleName} module ({$langName})',
];
EOD;
    }

    protected function getViewTemplate($button)
    {
        return <<<EOD
<?= \$this->extend('Modules\\Backend\\Views\\base') ?>

<?= \$this->section('title') ?>
<?= lang(\$title->pagename) ?>
<?= \$this->endSection() ?>

<?= \$this->section('head') ?>
<?= \$this->endSection() ?>

<?= \$this->section('content') ?>
<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><?= lang(\$title->pagename) ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    {$button}
                </ol>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">
    <!-- Default box -->
    <div class="card card-outline card-shl">
        <div class="card-header">
            <h3 class="card-title font-weight-bold"><?= lang(\$title->pagename) ?></h3>

            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <?= view('Modules\\Backend\\Views\\sweetalert_message_block') ?>
        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
</section>
<!-- /.content -->
<?= \$this->endSection() ?>

<?= \$this->section('javascript') ?>
<?= \$this->endSection() ?>
EOD;
    }
}
