<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'google_id',
        'role',
        'is_active',
        'reputation',
        'avatar_path',
        'cover_path',
        'email_verified_at',
    ];

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? (str_starts_with($this->avatar_path, 'http') ? $this->avatar_path : Storage::disk('public')->url($this->avatar_path))
            : null;
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_path
            ? (str_starts_with($this->cover_path, 'http') ? $this->cover_path : Storage::disk('public')->url($this->cover_path))
            : null;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'last_seen_on'      => 'date',
            'last_daily_rewarded_on' => 'date',
        ];
    }

    // ── Helper Methods ────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isModerator(): bool
    {
        return $this->role === 'MODERATOR';
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * We send our own verification email in RegisteredUserController.
     * This override prevents the default Laravel notification from firing.
     */
    public function sendEmailVerificationNotification(): void {}

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles);
    }

    // ── Relationships ─────────────────────────────────────────────────────

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'user_id');
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class, 'user_id');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'user_id');
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(
            Badge::class,
            'user_badges',
            'user_id',
            'badge_id',
            'id',
            'badge_id'
        )->withPivot(['earned_at', 'earned_reputation_bonus', 'earned_snapshot_value']);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(UserActivityLog::class, 'user_id');
    }

    // ── Social graph ──────────────────────────────────────────────────────

    /** Users this user is following. */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_follows',
            'follower_user_id',
            'followed_user_id'
        )->withPivot('created_at');
    }

    /** Users following this user. */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_follows',
            'followed_user_id',
            'follower_user_id'
        )->withPivot('created_at');
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->where('followed_user_id', $user->id)->exists();
    }

    public function feedItems(): HasMany
    {
        return $this->hasMany(\App\Models\FeedItem::class, 'actor_user_id');
    }
}
