<?php
namespace ManaPHP\Authorization\Rbac\Models;

use ManaPHP\Db\Model;

/**
 * Class ManaPHP\Authorization\Rbac\Models\UserRole
 *
 * @package rbac\models
 */
class UserRole extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var string
     */
    public $user_name;

    /**
     * @var int
     */
    public $role_id;

    /**
     * @var string
     */
    public $role_name;

    /**
     * @var int
     */
    public $creator_id;

    /**
     * @var string
     */
    public $creator_name;

    /**
     * @var int
     */
    public $created_time;

    public function getSource($context = null)
    {
        return 'rbac_user_role';
    }
}