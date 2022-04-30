<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \ManaPHP\Data\Model\ManagerInterface $modelManager
 */
class ViewCommand extends Command
{
    /**
     * @param \ManaPHP\Data\ModelInterface $model
     *
     * @return string
     * @noinspection PhpUnusedParameterInspection
     */
    public function renderRequestForm(ModelInterface $model): string
    {
        $content = <<<HTML
<request-form>
</request-form>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     *
     * @return string
     */
    public function renderDetailForm(ModelInterface $model): string
    {
        $content = PHP_EOL . <<<HTML
<detail-form>
HTML;
        foreach ($this->modelManager->getFields($model::class) as $field) {
            if ($this->isTimestampField($model, $field)) {
                $content .= PHP_EOL . <<<HTML
    <detail-timestamp prop="$field"></detail-timestamp>
HTML;
            } else {
                $content .= PHP_EOL . <<<HTML
    <detail-text prop="$field"></detail-text>
HTML;
            }
        }

        $content .= PHP_EOL . <<<HTML
</detail-form>
HTML;

        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     *
     * @return string
     */
    public function renderCreateForm(ModelInterface $model): string
    {
        if (!$fields = $model->safeFields()) {
            return '';
        }

        $content = PHP_EOL . <<<HTML
<create-form>
HTML;
        foreach ($fields as $field) {
            $content .= PHP_EOL . <<<HTML
    <create-text prop="$field"></create-text>
HTML;
        }

        $content .= PHP_EOL . <<<HTML
</create-form>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     *
     * @return string
     */
    public function renderEditForm(ModelInterface $model): string
    {
        if (!$fields = $model->safeFields()) {
            return '';
        }

        $content = PHP_EOL . <<<HTML
<edit-form>
HTML;
        $primaryKey = $this->modelManager->getPrimaryKey($model::class);
        $content .= PHP_EOL . <<<HTML
    <edit-text prop="$primaryKey" disabled></edit-text>
HTML;

        foreach ($fields as $field) {
            $content .= PHP_EOL . <<<HTML
    <edit-text prop="$field"></edit-text>
HTML;
        }

        $content .= PHP_EOL . <<<HTML
</edit-form>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     * @param string                       $field
     *
     * @return bool
     */
    public function isTimestampField(ModelInterface $model, string $field): bool
    {
        if (!in_array($field, $model->intFields(), true)) {
            return false;
        }

        return in_array($field, ['updated_time', 'created_time', 'deleted_time'], true);
    }

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     *
     * @return string
     */
    public function renderResultTable(ModelInterface $model): string
    {
        $content = PHP_EOL . <<<HTML
<result-table>
    <result-index></result-index>
HTML;
        foreach ($this->modelManager->getFields($model::class) as $field) {
            if ($this->isTimestampField($model, $field)) {
                $content .= PHP_EOL . <<<HTML
    <result-timestamp prop="$field"></result-timestamp>
HTML;
            } elseif (str_ends_with($field, '_id')) {
                $content .= PHP_EOL . <<<HTML
    <result-id prop="$field"></result-id>
HTML;
            } elseif ($field === 'email') {
                $content .= PHP_EOL . <<<HTML
    <result-email></result-email>
HTML;
            } elseif ($field === 'id' || str_ends_with($field, '_ip')) {
                $content .= PHP_EOL . <<<HTML
    <result-ip prop="$field"></result-ip>
HTML;
            } elseif (in_array($field, ['admin_name', 'user_name', 'updator_name', 'creator_name'], true)) {
                $content .= PHP_EOL . <<<HTML
    <result-account prop="$field"></result-account>
HTML;
            } elseif ($field === 'enabled') {
                $content .= PHP_EOL . <<<HTML
    <result-enabled></result-enabled>
HTML;
            } else {
                $content .= PHP_EOL . <<<HTML
    <result-column prop="$field" width="100"></result-column>
HTML;
            }
        }

        $content .= PHP_EOL . <<<HTML
    <result-op></result-op>
HTML;
        $content .= PHP_EOL . <<<HTML
</result-table>
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     *
     * @return string
     * @noinspection PhpUnusedParameterInspection
     */
    public function renderCss(ModelInterface $model): string
    {
        $content = PHP_EOL . <<<HTML
@section('css')
    <style>
    
    </style>
@append
HTML;
        return $content . PHP_EOL;
    }

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     *
     * @return string
     */
    public function renderScript(ModelInterface $model): string
    {
        $fields = $model->safeFields();

        $content = PHP_EOL . <<<HTML
@section('script')
    <script>
        vm = new App({
            data: {
                topic: '',
                request: {
                    page: 1,
                    size: 10
                },
                response: {},
HTML;

        if ($fields) {
            $content .= PHP_EOL . <<<HTML
                create: {
HTML;
            $rules = $model->rules();
            $iniFields = $model->intFields();
            foreach ($fields as $field) {
                $rule = $rules[$field] ?? [];
                if (is_array($rule) && isset($rule['default'])) {
                    $value = json_stringify($rule['default']);
                } elseif (in_array($field, $iniFields, true)) {
                    $value = 0;
                } else {
                    $value = "''";
                }
                $content .= PHP_EOL . "                    $field: $value, ";
            }

            $content .= PHP_EOL . <<<HTML
                },
                edit: {
HTML;
            $content .= PHP_EOL . '                    ' . $this->modelManager->getPrimaryKey($model::class) . ': 0,';

            foreach ($fields as $field) {
                $rule = $rules[$field] ?? [];
                if (is_array($rule) && isset($rule['default'])) {
                    $value = json_stringify($rule['default']);
                } elseif (in_array($field, $iniFields, true)) {
                    $value = 0;
                } else {
                    $value = "''";
                }
                $content .= PHP_EOL . "                    $field: $value,";
            }
            $content .= PHP_EOL . '                },';
        }

        $content .= PHP_EOL . <<<HTML
            }
        });
    </script>
@append
HTML;
        return $content;
    }

    /**
     * @param \ManaPHP\Data\ModelInterface $model
     *
     * @return string
     */
    public function render(ModelInterface $model): string
    {
        $content = $this->renderRequestForm($model);
        $content .= $this->renderDetailForm($model);
        $content .= $this->renderCreateForm($model);
        $content .= $this->renderEditForm($model);
        $content .= $this->renderResultTable($model);
        $content .= $this->renderCss($model);
        $content .= $this->renderScript($model);

        return $content;
    }

    /**
     * auto generate views file
     *
     * @return void
     */
    public function defaultAction(): void
    {
        foreach (LocalFS::glob('@app/Models/*.php') as $model_file) {
            if (basename($model_file) === 'Model.php') {
                continue;
            }
            $plain = basename($model_file, '.php');
            $view_file = "@runtime/view/Views/{$plain}.sword";
            $model = "App\Models\\$plain";
            $instance = new $model();
            LocalFS::filePut($view_file, $this->render($instance));
            $this->console->writeLn("view of `$model` saved to `$view_file`");
        }

        foreach (LocalFS::glob('@app/Areas/*/Models/*.php') as $model_file) {
            if (basename($model_file) === 'Model.php') {
                continue;
            }
            preg_match('#Areas/(\w+)/Models/(\w+).php$#', $model_file, $match);
            list(, $area, $plain) = $match;

            $view_file = "@runtime/view/Areas/$area/Views/{$plain}.sword";
            $model = "App\\Areas\\$area\\Models\\$plain";
            $instance = new $model();
            LocalFS::filePut($view_file, $this->render($instance));
            $this->console->writeLn("view of `$model` saved to `$view_file`");
        }
    }
}
