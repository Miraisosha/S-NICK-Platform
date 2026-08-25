<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * OPR-230 運営者メニュー・ダッシュボード.
 *
 * The landing page after login. Requires an authenticated identity -
 * unlike AppController's site-wide default (`requireIdentity` is false
 * there so public pages like Pages/Users stay open), this controller
 * opts back into the guard for itself.
 *
 * The stat cards, schedule, progress and notices sections are placeholder
 * "empty state" content: event/entry/match management (OPR-240 etc.) is
 * not implemented yet, so there is no real data to show.
 */
class DashboardController extends AppController
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Authentication->setConfig('requireIdentity', true);
        $this->viewBuilder()->setLayout('operator');
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        return null;
    }
}
