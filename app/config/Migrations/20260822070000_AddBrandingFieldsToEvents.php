<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Adds the public-facing branding fields shown on the SCR-OPR-2403 大会新規作成
 * "基本情報" step: English name, URL slug (globally unique - forms the
 * public event page URL), organizer name, and a logo image path. Entry
 * settings (SCR-OPR-2403 step 4) were deliberately dropped from the
 * creation wizard and deferred to a future edit-screen feature per user
 * direction, so no entries-related columns are added here.
 */
final class AddBrandingFieldsToEvents extends BaseMigration
{
    /**
     * @return void
     */
    public function change(): void
    {
        $this->table('events')
            ->addColumn('name_en', 'string', ['limit' => 255, 'null' => true, 'after' => 'name'])
            ->addColumn('slug', 'string', ['limit' => 64, 'null' => true, 'after' => 'name_en'])
            ->addColumn('organizer', 'string', ['limit' => 255, 'null' => true, 'after' => 'contact_email'])
            ->addColumn('logo', 'string', ['limit' => 255, 'null' => true, 'after' => 'organizer'])
            ->addIndex(['slug'], ['unique' => true])
            ->update();
    }
}
