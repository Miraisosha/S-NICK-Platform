<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Model\Table\FacilitiesTable;
use Cake\Http\Response;

/**
 * Read-only JSON API letting operators browse the admin-managed
 * facility/court master (SCR-ADM-522) when selecting courts for an event
 * (SCR-OPR-261 "運営者はイベント作成・編集時に登録済みの施設と個別コートを選択
 * する...共通マスターを変更できない"). Operator-authenticated (regular
 * Api\V1\AppController), unlike Api\V1\Admin\FacilitiesController which
 * owns create/edit/delete and requires the separate admin identity.
 */
class FacilitiesController extends AppController
{
    /**
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        $this->request->allowMethod(['get']);

        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->jsonError('unauthenticated', __('ログインしていません。'), 401);
        }

        $facilities = $this->facilitiesTable()->find('active')
            ->contain(['Courts' => function ($query) {
                return $query->find('active');
            }])
            ->orderBy(['name' => 'ASC'])
            ->all();

        return $this->json(['facilities' => $facilities]);
    }

    /**
     * @return \App\Model\Table\FacilitiesTable
     */
    private function facilitiesTable(): FacilitiesTable
    {
        /** @var \App\Model\Table\FacilitiesTable */
        return $this->fetchTable('Facilities');
    }
}
