# CodeIgniter 4 Module Generator

A powerful CLI tool for generating modular structures in CodeIgniter 4 applications, specifically designed for the ci4ms framework. The `ModuleGenerator` simplifies the creation of standardized module structures, including configuration files, controllers, views, and language files.

## Overview

The `ModuleGenerator` is a CodeIgniter 4 CLI command that creates a complete module structure with a single command. It follows CodeIgniter 4 conventions and best practices, making it an essential tool for developers building modular applications with the ci4ms framework.

### Key Features
- Generates a complete module structure with a single CLI command
- Creates configuration, routes, controllers, views, and language files
- Supports English and Turkish language files by default
- Seamlessly integrates with CodeIgniter's CLI
- Customizable templates for flexibility
- Ensures consistent module structure across projects

## Installation

To use the `ModuleGenerator`, install it via Composer:

```bash
composer require ci4-cms-erp/ext_module_generator
```

## Usage

Run the following command in your CodeIgniter 4 project directory to create a new module:

```bash
php spark make:module ModuleName
```

If no module name is provided, the CLI will prompt you to enter one. The module name will be automatically capitalized (e.g., `user` becomes `User`).

### Example
```bash
$ php spark make:module Admin
Module 'Admin' created successfully!

$ php spark make:module Blog
Module 'Blog' created successfully!
```

This will generate modules named `Admin` and `Blog` in the `modules` directory with all necessary files and folders.

## Generated Module Structure

The `ModuleGenerator` creates the following directory structure for each module:

```
modules/ModuleName/
├── Config/
│   ├── ModuleNameConfig.php
│   └── Routes.php
├── Controllers/
│   └── ModuleName.php
├── Database/
│   ├── Migrations/
│   └── Seeds/
├── Helpers/
├── Language/
│   ├── en/
│   │   └── ModuleName.php
│   └── tr/
│       └── ModuleName.php
├── Libraries/
├── Models/
├── Validation/
└── Views/
    ├── create.php
    ├── list.php
    └── update.php
```

Each directory and file serves a specific purpose, adhering to CodeIgniter 4 conventions.

## Generated Files

The generator creates the following template files with predefined content:

- **Config/ModuleNameConfig.php**: Defines module-specific configurations, including CSRF exceptions and filters.
- **Config/Routes.php**: Sets up routes for the module, including index, create, update, and delete routes.
- **Controllers/ModuleName.php**: A controller with methods for index, create, update, and delete actions, including basic validation.
- **Language Files**: English (`en/ModuleName.php`) and Turkish (`tr/ModuleName.php`) language files with welcome messages.
- **Views**: Templates for `create.php`, `list.php`, and `update.php` views, integrated with a base layout.

### Example Controller
Below is an example of the generated controller for a module named `Blog`:

```php
namespace Modules\Blog\Controllers;

class Blog extends \Modules\Backend\Controllers\BaseController {
    public function index() {
        return view('Modules\Blog\Views\list', $this->defData);
    }
    
    public function create() {
        if ($this->request->is('post')) {
            $vdata = [
                ''=>['label'=>'', 'rules'=>''],
            ];
            $valData = ($vdata);
            if ($this->validate($valData) == false) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        return view('Modules\Blog\Views\create', $this->defData);
    }
    
    public function update(int $id) {
        if ($this->request->is('post')) {
            $vdata = [
                ''=>['label'=>'', 'rules'=>''],
            ];
            $valData = ($vdata);
            if ($this->validate($valData) == false) return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        return view('Modules\Blog\Views\update', $this->defData);
    }

    public function delete(int $id)
    {
        $infos=$this->commonModel->selectOne('your_table',['id'=>$id]);
        if($this->commonModel->remove('your_table',['id'=>$id]))
            return $this->redirect()->back()->with('success',$infos->attr.' deleted.');
        return $this->redirect()->back()->with('error','Can not deleted.');
    }
}
```

## Customization

The `ModuleGenerator` uses template methods to generate files, allowing developers to customize the templates in the `ModuleGenerator.php` class to fit specific project requirements.

## Requirements
- CodeIgniter 4
- PHP 7.4 or higher
- Composer for installation

## License
This project is licensed under the MIT License.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue on the project's GitHub repository.

## Support
For issues or questions, refer to the GitHub repository or contact the maintainers.

---

Made with ❤️ for the CodeIgniter community  
© 2025 - All rights reserved