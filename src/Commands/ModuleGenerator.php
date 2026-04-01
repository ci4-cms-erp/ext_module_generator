<?php
declare(strict_types=1);

namespace ext_module_generator\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ModuleGenerator extends BaseCommand
{
    protected $group = 'Ci4MS';
    protected $name = 'make:module';
    protected $description = 'Creates a custom module structure for ci4ms (v0.31.2.0 compatible).';

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
        $folders = [
            $modulePath . '/Config',
            $modulePath . '/Controllers',
            $modulePath . '/Database/Migrations',
            $modulePath . '/Language/en',
            $modulePath . '/Language/tr',
            $modulePath . '/Libraries',
            $modulePath . '/Views',
        ];

        foreach ($folders as $folder) {
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }
        }

        $this->createFile($modulePath . '/Config/' . $moduleName . 'Config.php', $this->getConfigTemplate($moduleName));
        $this->createFile($modulePath . '/Config/Routes.php', $this->getRoutesTemplate($moduleName));
        $this->createFile($modulePath . '/Controllers/' . $moduleName . '.php', $this->getControllerTemplate($moduleName));
        $this->createFile($modulePath . '/Language/en/' . $moduleName . '.php', $this->getLanguageTemplate($moduleName));
        $this->createFile($modulePath . '/Language/tr/' . $moduleName . '.php', $this->getLanguageTemplate($moduleName));

        $this->createFile($modulePath . '/Views/create.php', $this->getViewTemplate($moduleName));
        $this->createFile($modulePath . '/Views/list.php', $this->getViewTemplateList($moduleName));
        $this->createFile($modulePath . '/Views/update.php', $this->getViewTemplate($moduleName));
    }

    protected function createFile($path, $content)
    {
        if (!file_exists($path)) {
            file_put_contents($path, $content);
        }
    }

    protected function getConfigTemplate($moduleName)
    {
        $l_moduleName = lcfirst($moduleName);
        return <<<EOD
<?php
declare(strict_types=1);
namespace Modules\\{$moduleName}\\Config;

class {$moduleName}Config {
    public \$csrfExcept = [
        'backend/{$l_moduleName}', 'backend/{$l_moduleName}/*'
    ];

    public \$filters = [
        'backendGuard' => ['before' => [
            'backend/{$l_moduleName}', 'backend/{$l_moduleName}/*'
        ]]
    ];

    public \$moduleInfo = [
        'icon' => 'fas fa-cube',
    ];

    public \$menus = [
        '{$moduleName}.{$l_moduleName}' => [
            'icon'         => 'fas fa-list',
            'inNavigation' => true,
            'hasChild'     => false,
            'pageSort'     => 0,
            'parent_pk'    => null
        ],
    ];
}
EOD;
    }

    protected function getRoutesTemplate($moduleName)
    {
        $l_moduleName = lcfirst($moduleName);
        return <<<EOD
<?php
\$routes->group('backend/{$l_moduleName}', ['namespace' => 'Modules\\{$moduleName}\\Controllers'], function(\$routes) {
    \$routes->match(['GET', 'POST'], '/', '{$moduleName}::index', ['as' => '{$l_moduleName}', 'role' => 'read']);
    \$routes->match(['GET', 'POST'], 'create', '{$moduleName}::create', ['as' => '{$l_moduleName}Create', 'role' => 'create']);
    \$routes->match(['GET', 'POST'], 'update/(:num)', '{$moduleName}::update/$1', ['as' => '{$l_moduleName}Update', 'role' => 'update']);
    \$routes->post('delete/(:num)', '{$moduleName}::delete/$1', ['as' => '{$l_moduleName}Delete', 'role' => 'delete']);
});
EOD;
    }

    protected function getControllerTemplate($moduleName)
    {
        $l_moduleName = lcfirst($moduleName);
        $tableName = $l_moduleName; // Varsayılan tablo ismi
        return <<<EOD
<?php
declare(strict_types=1);
namespace Modules\\{$moduleName}\\Controllers;

class {$moduleName} extends \Modules\Backend\Controllers\BaseController {

    public function index() {
        if (\$this->request->isAJAX() && \$this->request->is('post')) {
            \$parsed = \$this->commonBackendLibrary->getDatatablesPagination(\$this->request->getPost());

            \$like = [];
            if (!empty(\$parsed['searchString'])) {
                \$like = ['title' => \$parsed['searchString']]; // Değiştirin
            }

            \$totalRecords = \$this->commonModel->count('{$tableName}');
            \$filteredCount = !empty(\$like)
                ? count(\$this->commonModel->lists('{$tableName}', 'id', [], 'id ASC', 0, 0, \$like))
                : \$totalRecords;

            \$results = \$this->commonModel->lists('{$tableName}', '*', [], 'id DESC',
                \$parsed['length'], \$parsed['start'], \$like);

            foreach (\$results as \$result) {
                \$result->actions = '<a href="' . route_to('{$l_moduleName}Update', \$result->id) . '" class="btn btn-default btn-sm">
                    <i class="fas fa-edit"></i>
                </a>
                <button type="button" class="btn btn-default btn-sm btnDelete" data-id="' . \$result->id . '">
                    <i class="fas fa-trash"></i>
                </button>';
            }

            return \$this->respond([
                'draw' => \$parsed['draw'],
                'iTotalRecords' => \$totalRecords,
                'iTotalDisplayRecords' => \$filteredCount,
                'aaData' => \$results,
            ], 200);
        }
        return view('Modules\\{$moduleName}\\Views\\list', \$this->defData);
    }

    public function create() {
        if (\$this->request->is('post')) {
            \$valData = [
                'title' => ['label' => lang('Backend.title'), 'rules' => 'required|max_length[255]|regex_match[/^[^<>{}=]+$/u]'],
            ];
            if (\$this->validate(\$valData) === false) {
                return redirect()->back()->withInput()->with('errors', \$this->validator->getErrors());
            }

            // \$this->commonModel->create('{$tableName}', \$data);
        }
        return view('Modules\\{$moduleName}\\Views\\create', \$this->defData);
    }

    public function update(int \$id) {
        \$this->defData['infos'] = \$this->commonModel->selectOne('{$tableName}', ['id' => \$id]);
        if (empty(\$this->defData['infos'])) return show_404();

        if (\$this->request->is('post')) {
            // Validation ve Update mantığı
        }
        return view('Modules\\{$moduleName}\\Views\\update', \$this->defData);
    }

    public function delete(int \$id) {
        if (\$this->request->isAJAX()) {
            if (\$this->commonModel->remove('{$tableName}', ['id' => \$id])) {
                return \$this->respond(['result' => true], 200);
            }
            return \$this->fail('Error');
        }
        return \$this->failForbidden();
    }
}
EOD;
    }

    protected function getLanguageTemplate($moduleName)
    {
        return <<<EOD
<?php
declare(strict_types=1);

return [
    '{$moduleName}' => '{$moduleName}',
    'welcome' => 'Welcome to {$moduleName} module',
];
EOD;
    }

    protected function getViewTemplate($moduleName)
    {
        $l_moduleName = lcfirst($moduleName);
        return <<<EOD
<?php echo \$this->extend(\$backConfig->viewLayout);
echo \$this->section('title');
echo lang(\$title->pagename);
echo \$this->endSection();
echo \$this->section('head');
echo \$this->endSection();
echo \$this->section('content'); ?>

<section class="content p-3">
    <div class="card card-outline shadow-sm">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title font-weight-bold mb-0"><?php echo lang(\$title->pagename) ?></h3>
            <div class="ml-auto">
                <a href="<?php echo route_to('{$l_moduleName}') ?>" class="btn btn-sm btn-outline-info">
                    <?php echo lang('Backend.backToList') ?>
                </a>
            </div>
        </div>
        <form action="<?php echo current_url() ?>" method="post">
            <?php echo csrf_field() ?>
            <div class="card-body">
                <!-- Form Fields -->
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save mr-1"></i> <?php echo lang('Backend.save') ?>
                </button>
            </div>
        </form>
    </div>
</section>
<?php echo \$this->endSection(); ?>
EOD;
    }

    protected function getViewTemplateList($moduleName)
    {
        $l_moduleName = lcfirst($moduleName);
        return <<<EOD
<?php echo \$this->extend(\$backConfig->viewLayout);
echo \$this->section('title');
echo lang(\$title->pagename);
echo \$this->endSection();
echo \$this->section('head');
echo link_tag('be-assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css');
echo link_tag('be-assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css');
echo \$this->endSection();
echo \$this->section('content'); ?>

<section class="content p-3">
    <div class="card premium-card">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title font-weight-bold mb-0"><?php echo lang(\$title->pagename) ?></h3>
            <div class="ml-auto">
                <a href="<?php echo route_to('{$l_moduleName}Create') ?>" class="btn btn-sm btn-outline-success">
                    <?php echo lang('Backend.add') ?>
                </a>
                <button class="btn btn-sm btn-outline-secondary ml-1" id="btnRefresh" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="moduleTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th><?php echo lang('Backend.title') ?></th>
                            <th><?php echo lang('Backend.transactions') ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php echo \$this->endSection();
echo \$this->section('javascript');
echo script_tag('be-assets/plugins/datatables/jquery.dataTables.min.js');
echo script_tag('be-assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js');
echo script_tag('be-assets/plugins/datatables-responsive/js/dataTables.responsive.min.js'); ?>
<script {csp-script-nonce}>
    $(function() {
        var table = $("#moduleTable").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?php echo route_to('{$l_moduleName}') ?>',
                type: 'POST'
            },
            columns: [
                { data: 'title' },
                { data: 'actions', className: 'text-center' }
            ],
            language: ci4msDtLanguage('<?php echo lang('Backend.search') ?>')
        });
        $('#btnRefresh').click(() => table.ajax.reload());
    });
</script>
<?php echo \$this->endSection() ?>
EOD;
    }
}
