<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Category Entity (SCR-OPR-2405).
 *
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string $gender
 * @property int|null $age_min
 * @property int|null $age_max
 * @property string|null $level
 * @property bool $squash_association_registration
 * @property string|null $eligibility
 * @property int $entry_fee
 * @property int $capacity
 * @property \Cake\I18n\DateTime $registration_start_at
 * @property \Cake\I18n\DateTime $registration_end_at
 * @property bool $waitlist_allowed
 * @property string $match_format
 * @property int $max_games
 * @property int $game_end_score
 * @property int|null $required_point_diff
 * @property int $estimated_game_minutes
 * @property int $warmup_seconds
 * @property int $pre_match_interval_seconds
 * @property int $between_game_interval_seconds
 * @property int $min_rest_seconds
 * @property int $display_order
 * @property string $publication_status
 * @property string|null $notes
 * @property \Cake\I18n\DateTime|null $deleted_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property int $estimated_match_seconds Virtual field, see CategoriesTable::estimatedMatchSeconds().
 */
class Category extends Entity
{
    public const GENDER_MALE = 'male';
    public const GENDER_FEMALE = 'female';
    public const GENDER_NONE = 'none';

    public const MATCH_FORMAT_TOURNAMENT = 'tournament';
    public const MATCH_FORMAT_ROUND_ROBIN = 'round_robin';

    public const PUBLICATION_STATUS_PUBLISHED = 'published';
    public const PUBLICATION_STATUS_UNPUBLISHED = 'unpublished';

    /**
     * Included in toArray()/JSON output so the FRONT can use it directly
     * for scheduling-slot display without recomputing the formula
     * client-side.
     *
     * @var list<string>
     */
    protected array $_virtual = ['estimated_match_seconds'];

    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'id' => false,
        'event_id' => false,
        'name' => true,
        'gender' => true,
        'age_min' => true,
        'age_max' => true,
        'level' => true,
        'squash_association_registration' => true,
        'eligibility' => true,
        'entry_fee' => true,
        'capacity' => true,
        'registration_start_at' => true,
        'registration_end_at' => true,
        'waitlist_allowed' => true,
        'match_format' => true,
        'max_games' => true,
        'game_end_score' => true,
        'required_point_diff' => true,
        'estimated_game_minutes' => true,
        'warmup_seconds' => true,
        'pre_match_interval_seconds' => true,
        'between_game_interval_seconds' => true,
        'min_rest_seconds' => true,
        'display_order' => true,
        'publication_status' => true,
        'notes' => true,
        'deleted_at' => false,
    ];

    /**
     * SCR-OPR-2405's scheduling-slot formula:
     * warmup*2 + pre-match interval + max_games*estimated_minutes*60 + (max_games-1)*between-game interval,
     * rounded up to the next whole minute.
     *
     * @return int
     */
    protected function _getEstimatedMatchSeconds(): int
    {
        $seconds = $this->warmup_seconds * 2
            + $this->pre_match_interval_seconds
            + $this->max_games * $this->estimated_game_minutes * 60
            + ($this->max_games - 1) * $this->between_game_interval_seconds;

        return (int)(ceil($seconds / 60) * 60);
    }
}
