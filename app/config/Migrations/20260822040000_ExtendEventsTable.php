<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Adds the remaining SCR-OPR-2402 fields
 * (docs/specifications/200_Operator.md) that `CreateFoundationTables`
 * didn't include: subtitle, registration window, contact info, and the
 * long-form description/notes text. Image gallery, marker default
 * language and OBS overlay presets are deliberately excluded - see the
 * implementation plan's judgment call #5.
 */
final class ExtendEventsTable extends BaseMigration
{
    public function change(): void
    {
        $this->table('events')
            ->addColumn('subtitle', 'string', ['limit' => 255, 'null' => true, 'after' => 'name'])
            ->addColumn('registration_start_at', 'datetime', ['null' => true, 'after' => 'end_at'])
            ->addColumn('registration_end_at', 'datetime', ['null' => true, 'after' => 'registration_start_at'])
            ->addColumn('contact_email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('contact_info', 'text', ['null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->update();
    }
}
