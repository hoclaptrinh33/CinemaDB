<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    /** @use HasFactory<\Database\Factories\BadgeFactory> */
    use HasFactory;
    public $timestamps = false;

    protected $table      = 'badges';
    protected $primaryKey = 'badge_id';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon_path',
        'tier',
        'category',
        'badge_family',
        'badge_stage',
        'rarity_tier',
        'frame_style',
        'sort_order',
        'condition_type',
        'condition_value',
        'earned_users_count',
        'earned_users_percent',
    ];

    public const CONDITION_TYPES = [
        'manual'           => 'Thủ công (admin trao)',
        'review_count'     => 'Số reviews >= N',
        'helpful_votes'    => 'Tổng helpful votes >= N',
        'collections_count' => 'Số bộ sưu tập >= N',
        'early_bird'       => 'Review trong N ngày từ khi phim ra mắt',
        'distinct_types'   => 'Review >= N loại nội dung khác nhau (phim/series/tập)',
        'collection_nominations_received' => 'Tổng đề cử mà các list của user nhận được >= N',
        'follower_count'   => 'Có N người follow',
        'following_count'  => 'Đã follow N người',
        'activity_streak'  => 'Hoạt động liên tiếp N ngày',
        'login_streak'     => 'Đăng nhập/truy cập liên tiếp N ngày',
    ];

    /** Users who have earned this badge. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_badges',
            'badge_id',
            'user_id',
            'badge_id',
            'id'
        )->withPivot('earned_at');
    }

    /** Reputation points awarded when earning this badge. */
    public function reputationBonus(): int
    {
        return (int) data_get(config('gamification.badge_tiers'), $this->tier . '.bonus', 0);
    }
}
