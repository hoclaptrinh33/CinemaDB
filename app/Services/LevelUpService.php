<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\LevelUp;

/**
 * Kiểm tra xem user có lên cấp mới sau khi tăng reputation không.
 * Dùng sau mỗi lần gọi increment('reputation') ở nhiều nơi.
 */
class LevelUpService
{
    // Phải giữ đồng bộ với resources/js/composables/useRank.js
    private static array $levels = [
        ['level' => 24, 'min' => 18000, 'label' => 'Bậc thầy điện ảnh', 'icon' => '👑'],
        ['level' => 23, 'min' => 15000, 'label' => 'Bậc thầy điện ảnh', 'icon' => '👑'],
        ['level' => 22, 'min' => 12500, 'label' => 'Bậc thầy điện ảnh', 'icon' => '👑'],
        ['level' => 21, 'min' => 10000, 'label' => 'Bậc thầy điện ảnh', 'icon' => '👑'],
        ['level' => 20, 'min' => 8000, 'label' => 'Bậc thầy điện ảnh', 'icon' => '👑'],
        ['level' => 19, 'min' => 6500, 'label' => 'Bậc thầy điện ảnh', 'icon' => '👑'],
        ['level' => 18, 'min' => 5200, 'label' => 'Bậc thầy điện ảnh', 'icon' => '👑'],
        ['level' => 17, 'min' => 4200, 'label' => 'Huyền thoại phòng vé', 'icon' => '🏆'],
        ['level' => 16, 'min' => 3700, 'label' => 'Huyền thoại phòng vé', 'icon' => '🏆'],
        ['level' => 15, 'min' => 3333, 'label' => 'Huyền thoại phòng vé', 'icon' => '🏆'],
        ['level' => 14, 'min' => 2800, 'label' => 'Nhà phê bình uy tín', 'icon' => '✍️'],
        ['level' => 13, 'min' => 2300, 'label' => 'Nhà phê bình uy tín', 'icon' => '✍️'],
        ['level' => 12, 'min' => 1800, 'label' => 'Nhà phê bình uy tín', 'icon' => '✍️'],
        ['level' => 11, 'min' => 1300, 'label' => 'Nhà phê bình uy tín', 'icon' => '✍️'],
        ['level' => 10, 'min' => 850, 'label' => 'Kẻ sành phim', 'icon' => '🎬'],
        ['level' => 9, 'min' => 600, 'label' => 'Kẻ sành phim', 'icon' => '🎬'],
        ['level' => 8, 'min' => 380, 'label' => 'Kẻ sành phim', 'icon' => '🎬'],
        ['level' => 7, 'min' => 240, 'label' => 'Thợ cày phim', 'icon' => '🍿'],
        ['level' => 6, 'min' => 150, 'label' => 'Thợ cày phim', 'icon' => '🍿'],
        ['level' => 5, 'min' => 95, 'label' => 'Thợ cày phim', 'icon' => '🍿'],
        ['level' => 4, 'min' => 60, 'label' => 'Thợ cày phim', 'icon' => '🍿'],
        ['level' => 3, 'min' => 30, 'label' => 'Khán giả phổ thông', 'icon' => '🏟️'],
        ['level' => 2, 'min' => 10, 'label' => 'Khán giả phổ thông', 'icon' => '🏟️'],
        ['level' => 1, 'min' => 0, 'label' => 'Khán giả phổ thông', 'icon' => '🏟️'],
    ];

    /**
     * Gọi sau khi reputation của $user đã được tăng.
     * $previousReputation là giá trị trước khi increment.
     */
    public static function check(User $user, int $previousReputation): void
    {
        $newReputation = $user->fresh()->reputation;

        if ($newReputation <= $previousReputation) {
            return;
        }

        $oldLevel = self::resolve($previousReputation);
        $newLevel = self::resolve($newReputation);

        if ($newLevel['level'] !== $oldLevel['level']) {
            $user->notify(new LevelUp($newLevel));
        }
    }

    private static function resolve(int $rep): array
    {
        foreach (self::$levels as $level) {
            if ($rep >= $level['min']) {
                return $level;
            }
        }

        return self::$levels[count(self::$levels) - 1];
    }
}
