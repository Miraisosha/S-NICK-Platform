<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\Api\V1\AppController as ApiAppController;
use App\Model\Table\AdminsTable;

/**
 * Base controller for the `/api/v1/admin/*` JSON API, authenticated
 * against the separate `admins` table (see
 * Application::getAdminAuthenticationService() and
 * docs/specifications/500_Admin.md §501).
 *
 * Deliberately does NOT set `requireIdentity => true`: the Authentication
 * plugin's own enforcement of that responds to an unauthenticated request
 * with a redirect (`unauthenticatedRedirect`), which is wrong for a JSON
 * API. Every protected action instead checks
 * `$this->Authentication->getIdentity()` itself and returns a clean 401 via
 * `jsonError()`, matching `Api\V1\UsersController::me()`'s existing pattern.
 */
abstract class AppController extends ApiAppController
{
    /**
     * @return \App\Model\Table\AdminsTable
     */
    protected function adminsTable(): AdminsTable
    {
        /** @var \App\Model\Table\AdminsTable */
        return $this->fetchTable('Admins');
    }
}
