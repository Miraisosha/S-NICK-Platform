<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Model\Entity\Category;
use App\Model\Table\CategoriesTable;
use App\Model\Table\EventsTable;
use Authentication\IdentityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Exception\PersistenceFailedException;

/**
 * JSON API for SCR-OPR-2405 カテゴリ管理, nested under an event
 * (`/api/v1/events/{eventId}/categories`). Only the event's owner or an
 * `event_manager`-role staff member may manage its categories
 * (EventsTable::userCanManage()), same as EventsController.
 */
class EventsCategoriesController extends AppController
{
    /**
     * @param string|null $eventId Event id.
     * @return \Cake\Http\Response
     */
    public function index(?string $eventId = null): Response
    {
        $this->request->allowMethod(['get']);

        $identity = $this->requireIdentity();
        if ($identity === null) {
            return $this->unauthenticated();
        }

        if (!$this->eventsTable()->userCanManage((int)$eventId, (int)$identity->getIdentifier())) {
            return $this->jsonError('not_found', __('イベントが見つかりません。'), 404);
        }

        $categories = $this->categoriesTable()->find('active')
            ->where(['event_id' => $eventId])
            ->orderBy(['display_order' => 'ASC'])
            ->all();

        return $this->json(['categories' => $categories]);
    }

    /**
     * @param string|null $eventId Event id.
     * @return \Cake\Http\Response
     */
    public function add(?string $eventId = null): Response
    {
        $this->request->allowMethod(['post']);

        $identity = $this->requireIdentity();
        if ($identity === null) {
            return $this->unauthenticated();
        }

        if (!$this->eventsTable()->userCanManage((int)$eventId, (int)$identity->getIdentifier())) {
            return $this->jsonError('not_found', __('イベントが見つかりません。'), 404);
        }

        $data = (array)$this->request->getData();
        $data['event_id'] = (int)$eventId;
        if (!isset($data['display_order']) || $data['display_order'] === '') {
            $data['display_order'] = $this->categoriesTable()->nextDisplayOrder((int)$eventId);
        }
        $data += ['publication_status' => Category::PUBLICATION_STATUS_UNPUBLISHED];

        $category = $this->categoriesTable()->newEntity($data, ['accessibleFields' => ['event_id' => true]]);

        if ($category->hasErrors()) {
            return $this->validationError($category->getErrors());
        }

        try {
            $this->categoriesTable()->saveOrFail($category);
        } catch (PersistenceFailedException $e) {
            return $this->validationError($e->getEntity()->getErrors());
        }

        // Reload rather than returning $category as-is: newEntity()/save()
        // never populate an entity's in-memory properties from DB column
        // defaults (warmup_seconds etc. are all DEFAULT-valued and
        // deliberately omitted from most requests) - confirmed directly
        // against a real save() call, where the in-memory entity kept
        // `warmup_seconds === null` even though the row was correctly
        // persisted as 120. Only a fresh SELECT reflects what MySQL
        // actually applied, which the estimated_match_seconds virtual
        // field's calculation depends on being correct.
        return $this->json(['category' => $this->categoriesTable()->get($category->id)], 201);
    }

    /**
     * @param string|null $eventId Event id.
     * @param string|null $id Category id.
     * @return \Cake\Http\Response
     */
    public function edit(?string $eventId = null, ?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'patch', 'put']);

        $identity = $this->requireIdentity();
        if ($identity === null) {
            return $this->unauthenticated();
        }

        if (!$this->eventsTable()->userCanManage((int)$eventId, (int)$identity->getIdentifier())) {
            return $this->jsonError('not_found', __('イベントが見つかりません。'), 404);
        }

        $category = $this->findCategory($eventId, $id);
        if ($category === null) {
            return $this->jsonError('not_found', __('カテゴリが見つかりません。'), 404);
        }

        $category = $this->categoriesTable()->patchEntity($category, (array)$this->request->getData());

        if ($category->hasErrors()) {
            return $this->validationError($category->getErrors());
        }

        try {
            $this->categoriesTable()->saveOrFail($category);
        } catch (PersistenceFailedException $e) {
            return $this->validationError($e->getEntity()->getErrors());
        }

        return $this->json(['category' => $category]);
    }

    /**
     * Soft-deletes the category. Per SCR-OPR-2405, categories referenced by
     * entries/draws/matches must not be deletable - none of those exist
     * yet in this phase, so the reference guard is a no-op for now (see
     * the implementation plan).
     *
     * @param string|null $eventId Event id.
     * @param string|null $id Category id.
     * @return \Cake\Http\Response
     */
    public function delete(?string $eventId = null, ?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $identity = $this->requireIdentity();
        if ($identity === null) {
            return $this->unauthenticated();
        }

        if (!$this->eventsTable()->userCanManage((int)$eventId, (int)$identity->getIdentifier())) {
            return $this->jsonError('not_found', __('イベントが見つかりません。'), 404);
        }

        $category = $this->findCategory($eventId, $id);
        if ($category === null) {
            return $this->jsonError('not_found', __('カテゴリが見つかりません。'), 404);
        }

        $category->set('deleted_at', DateTime::now(), ['guard' => false]);
        $this->categoriesTable()->saveOrFail($category);

        return $this->json(['status' => 'deleted']);
    }

    /**
     * @param string|null $eventId Event id.
     * @param string|null $id Category id.
     * @return \App\Model\Entity\Category|null
     */
    private function findCategory(?string $eventId, ?string $id): ?Category
    {
        try {
            /** @var \App\Model\Entity\Category */
            return $this->categoriesTable()->find('active')
                ->where(['id' => $id, 'event_id' => $eventId])
                ->firstOrFail();
        } catch (RecordNotFoundException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $errors Validation errors from the entity.
     * @return \Cake\Http\Response
     */
    private function validationError(array $errors): Response
    {
        return $this->jsonError('validation_failed', __('入力内容を確認してください。'), 422, ['fields' => $errors]);
    }

    /**
     * @return \Cake\Http\Response
     */
    private function unauthenticated(): Response
    {
        return $this->jsonError('unauthenticated', __('ログインしていません。'), 401);
    }

    /**
     * @return \Authentication\IdentityInterface|null
     */
    private function requireIdentity(): ?IdentityInterface
    {
        return $this->Authentication->getIdentity();
    }

    /**
     * @return \App\Model\Table\EventsTable
     */
    private function eventsTable(): EventsTable
    {
        /** @var \App\Model\Table\EventsTable */
        return $this->fetchTable('Events');
    }

    /**
     * @return \App\Model\Table\CategoriesTable
     */
    private function categoriesTable(): CategoriesTable
    {
        /** @var \App\Model\Table\CategoriesTable */
        return $this->fetchTable('Categories');
    }
}
