<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Roles Model. Fixed event-staff roles seeded by the SeedEventRoles
 * migration (docs/specifications/020_UserRoles.md "イベントスタッフのロール").
 * Read-only from the application's perspective in this phase - no
 * create/edit/delete API exists for roles.
 *
 * @extends \Cake\ORM\Table<array{}, \Cake\ORM\Entity>
 */
class RolesTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('roles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }
}
