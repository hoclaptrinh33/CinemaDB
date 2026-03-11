<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BadgeSeeder extends Seeder
{
    private int $sortOrder = 0;

    public function run(): void
    {
        $badges = array_merge(
            $this->buildFamily(
                category: 'reviews',
                family: 'review-milestones',
                conditionType: 'review_count',
                thresholds: [1, 3, 5, 10, 15, 20, 35, 50, 75, 100, 150, 200, 300, 500, 750, 1000, 1500, 2500, 5000, 10000],
                iconSet: [
                    'base' => [
                        '/images/badges/review-quill.svg',
                        '/images/badges/review-scroll.svg',
                        '/images/badges/critic-apprentice.svg',
                        '/images/badges/review-spotlight.svg',
                    ],
                    'gold' => [
                        '/images/badges/critic-regular.svg',
                        '/images/badges/critic-master.svg',
                    ],
                    'platinum' => ['/images/badges/review-platinum.svg'],
                    'diamond' => ['/images/badges/review-diamond.svg'],
                ],
                nameFormatter: fn(int $threshold, int $stage): string => "Nhà phê bình mốc {$stage}",
                descriptionFormatter: fn(int $threshold, int $stage): string => "Viết {$threshold} review.",
                overrides: [
                    1 => [
                        'slug' => 'first-review',
                        'name' => 'Nhà phê bình mới',
                        'description' => 'Viết review đầu tiên của bạn.',
                        'icon_path' => '/images/badges/first-review.svg',
                    ],
                    5 => [
                        'slug' => 'critic-scout',
                        'name' => 'Nhà phê bình sơ cấp',
                        'description' => 'Đã viết 5 reviews.',
                        'icon_path' => '/images/badges/critic-apprentice.svg',
                    ],
                    10 => [
                        'slug' => 'critic-apprentice',
                        'name' => 'Nhà phê bình tập sự',
                        'description' => 'Đã viết 10 reviews.',
                        'icon_path' => '/images/badges/critic-apprentice.svg',
                    ],
                    50 => [
                        'slug' => 'critic-regular',
                        'name' => 'Nhà phê bình thường trú',
                        'description' => 'Đã viết 50 reviews.',
                        'icon_path' => '/images/badges/critic-regular.svg',
                    ],
                    200 => [
                        'slug' => 'critic-master',
                        'name' => 'Bậc thầy phê bình',
                        'description' => 'Đã viết 200 reviews.',
                        'icon_path' => '/images/badges/critic-master.svg',
                    ],
                    500 => [
                        'slug' => 'critic-oracle',
                        'name' => 'Hiền triết điện ảnh',
                        'description' => 'Đã viết 500 reviews.',
                    ],
                    1000 => [
                        'slug' => 'critic-immortal',
                        'name' => 'Biểu tượng phê bình',
                        'description' => 'Đã viết 1000 reviews.',
                    ],
                ],
            ),
            $this->buildFamily(
                category: 'community',
                family: 'helpful-milestones',
                conditionType: 'helpful_votes',
                thresholds: [1, 5, 10, 20, 35, 50, 75, 100, 150, 200, 300, 500, 750, 1000, 1500, 2500, 5000, 10000],
                iconSet: [
                    'base' => [
                        '/images/badges/helpful-voice.svg',
                        '/images/badges/community-megaphone.svg',
                        '/images/badges/community-medal.svg',
                    ],
                    'gold' => [
                        '/images/badges/trusted-critic.svg',
                        '/images/badges/community-beacon.svg',
                    ],
                    'platinum' => ['/images/badges/community-platinum.svg'],
                    'diamond' => ['/images/badges/community-diamond.svg'],
                ],
                nameFormatter: fn(int $threshold, int $stage): string => "Tiếng nói cộng đồng mốc {$stage}",
                descriptionFormatter: fn(int $threshold, int $stage): string => "Nhận được {$threshold} lượt vote \"Hữu ích\".",
                overrides: [
                    10 => [
                        'slug' => 'helpful-voice',
                        'name' => 'Giọng nói hữu ích',
                        'description' => 'Nhận được 10 lượt vote "Hữu ích".',
                        'icon_path' => '/images/badges/helpful-voice.svg',
                    ],
                    50 => [
                        'slug' => 'trusted-critic',
                        'name' => 'Nhà phê bình đáng tin',
                        'description' => 'Nhận được 50 lượt vote "Hữu ích".',
                        'icon_path' => '/images/badges/trusted-critic.svg',
                    ],
                    200 => [
                        'slug' => 'community-beacon',
                        'name' => 'Hải đăng cộng đồng',
                        'description' => 'Nhận được 200 lượt vote "Hữu ích".',
                        'icon_path' => '/images/badges/trusted-critic.svg',
                    ],
                    500 => [
                        'slug' => 'voice-of-the-people',
                        'name' => 'Thủ lĩnh dư luận',
                        'description' => 'Nhận được 500 lượt vote "Hữu ích".',
                    ],
                ],
            ),
            $this->buildFamily(
                category: 'collections',
                family: 'collection-milestones',
                conditionType: 'collections_count',
                thresholds: [1, 2, 3, 5, 8, 10, 15, 20, 30, 40, 50, 75, 100, 150, 250],
                iconSet: [
                    'base' => [
                        '/images/badges/collector.svg',
                        '/images/badges/collection-archive.svg',
                        '/images/badges/collection-shelf.svg',
                    ],
                    'gold' => ['/images/badges/collection-curator.svg'],
                    'platinum' => ['/images/badges/collection-platinum.svg'],
                    'diamond' => ['/images/badges/collection-diamond.svg'],
                ],
                nameFormatter: fn(int $threshold, int $stage): string => "Nhà sưu tầm mốc {$stage}",
                descriptionFormatter: fn(int $threshold, int $stage): string => "Tạo {$threshold} bộ sưu tập.",
                overrides: [
                    1 => [
                        'slug' => 'first-list',
                        'name' => 'Người sưu tập mới',
                        'description' => 'Tạo bộ sưu tập đầu tiên.',
                    ],
                    5 => [
                        'slug' => 'collector',
                        'name' => 'Nhà sưu tầm',
                        'description' => 'Tạo 5 bộ sưu tập.',
                    ],
                    15 => [
                        'slug' => 'curation-room',
                        'name' => 'Phòng tuyển chọn',
                        'description' => 'Tạo 15 bộ sưu tập.',
                    ],
                ],
            ),
            $this->buildFamily(
                category: 'timing',
                family: 'timing-milestones',
                conditionType: 'early_bird',
                thresholds: [7],
                iconSet: [
                    'base' => ['/images/badges/early-bird.svg'],
                    'gold' => ['/images/badges/early-bird.svg'],
                    'platinum' => ['/images/badges/early-bird.svg'],
                    'diamond' => ['/images/badges/early-bird.svg'],
                ],
                nameFormatter: fn(int $threshold, int $stage): string => 'Người đi trước',
                descriptionFormatter: fn(int $threshold, int $stage): string => "Review trong vòng {$threshold} ngày kể từ khi phim ra mắt.",
                overrides: [
                    7 => [
                        'slug' => 'early-bird',
                        'name' => 'Người đi trước',
                        'description' => 'Review trong vòng 7 ngày kể từ khi phim ra mắt.',
                    ],
                ],
            ),
            $this->buildFamily(
                category: 'social',
                family: 'followers-milestones',
                conditionType: 'follower_count',
                thresholds: [1, 3, 5, 10, 20, 35, 50, 100, 250, 500, 1000, 2500],
                iconSet: [
                    'base' => [
                        '/images/badges/social-crowd.svg',
                        '/images/badges/social-orbit.svg',
                        '/images/badges/social-handshake.svg',
                    ],
                    'gold' => ['/images/badges/community-megaphone.svg'],
                    'platinum' => ['/images/badges/social-platinum.svg'],
                    'diamond' => ['/images/badges/social-diamond.svg'],
                ],
                nameFormatter: fn(int $threshold, int $stage): string => "Sức ảnh hưởng mốc {$stage}",
                descriptionFormatter: fn(int $threshold, int $stage): string => "Có {$threshold} người follow bạn.",
                overrides: [
                    1 => [
                        'slug' => 'first-follower',
                        'name' => 'Tiếng nói đầu tiên',
                        'description' => 'Có 1 người follow bạn.',
                    ],
                    10 => [
                        'slug' => 'rising-voice',
                        'name' => 'Tiếng nói đang lên',
                        'description' => 'Có 10 người follow bạn.',
                    ],
                ],
            ),
            $this->buildFamily(
                category: 'social',
                family: 'following-milestones',
                conditionType: 'following_count',
                thresholds: [5, 10, 25, 50, 75, 100, 150, 250, 400, 600],
                iconSet: [
                    'base' => [
                        '/images/badges/social-handshake.svg',
                        '/images/badges/social-orbit.svg',
                        '/images/badges/social-crowd.svg',
                    ],
                    'gold' => ['/images/badges/collection-curator.svg'],
                    'platinum' => ['/images/badges/social-platinum.svg'],
                    'diamond' => ['/images/badges/social-diamond.svg'],
                ],
                nameFormatter: fn(int $threshold, int $stage): string => "Người kết nối mốc {$stage}",
                descriptionFormatter: fn(int $threshold, int $stage): string => "Follow {$threshold} người dùng khác.",
                overrides: [
                    25 => [
                        'slug' => 'people-person',
                        'name' => 'Người kết nối',
                        'description' => 'Follow 25 người dùng khác.',
                    ],
                ],
            ),
            $this->buildFamily(
                category: 'engagement',
                family: 'activity-streak-milestones',
                conditionType: 'activity_streak',
                thresholds: [3, 5, 7, 14, 21, 30, 45, 60, 90, 180],
                iconSet: [
                    'base' => [
                        '/images/badges/engagement-calendar.svg',
                        '/images/badges/engagement-flame.svg',
                        '/images/badges/engagement-comet.svg',
                    ],
                    'gold' => ['/images/badges/engagement-flame.svg'],
                    'platinum' => ['/images/badges/engagement-platinum.svg'],
                    'diamond' => ['/images/badges/engagement-diamond.svg'],
                ],
                nameFormatter: fn(int $threshold, int $stage): string => "Chuỗi hoạt động mốc {$stage}",
                descriptionFormatter: fn(int $threshold, int $stage): string => "Hoạt động liên tiếp {$threshold} ngày.",
                overrides: [
                    3 => [
                        'slug' => 'daily-checkin',
                        'name' => 'Điểm danh điện ảnh',
                        'description' => 'Hoạt động liên tiếp 3 ngày.',
                    ],
                    7 => [
                        'slug' => 'steady-presence',
                        'name' => 'Hiện diện bền bỉ',
                        'description' => 'Hoạt động liên tiếp 7 ngày.',
                    ],
                    30 => [
                        'slug' => 'marathon-viewer',
                        'name' => 'Marathon điện ảnh',
                        'description' => 'Hoạt động liên tiếp 30 ngày.',
                    ],
                ],
            ),
            $this->buildFamily(
                category: 'engagement',
                family: 'login-streak-milestones',
                conditionType: 'login_streak',
                thresholds: [3, 5, 7, 14, 21, 30, 45, 60, 90, 180],
                iconSet: [
                    'base' => [
                        '/images/badges/engagement-calendar.svg',
                        '/images/badges/engagement-comet.svg',
                        '/images/badges/engagement-flame.svg',
                    ],
                    'gold' => ['/images/badges/engagement-comet.svg'],
                    'platinum' => ['/images/badges/engagement-platinum.svg'],
                    'diamond' => ['/images/badges/engagement-diamond.svg'],
                ],
                nameFormatter: fn(int $threshold, int $stage): string => "Điểm danh mốc {$stage}",
                descriptionFormatter: fn(int $threshold, int $stage): string => "Đăng nhập liên tiếp {$threshold} ngày.",
                overrides: [],
            ),
            $this->buildFamily(
                category: 'collections',
                family: 'collection-nomination-milestones',
                conditionType: 'collection_nominations_received',
                thresholds: [1, 5, 10, 20, 35, 50, 75, 100, 150, 250, 500, 1000],
                iconSet: [
                    'base' => [
                        '/images/badges/collection-curator.svg',
                        '/images/badges/collection-archive.svg',
                        '/images/badges/collection-shelf.svg',
                    ],
                    'gold' => ['/images/badges/collector.svg'],
                    'platinum' => ['/images/badges/collection-platinum.svg'],
                    'diamond' => ['/images/badges/collection-diamond.svg'],
                ],
                nameFormatter: fn(int $threshold, int $stage): string => "Tuyển chọn nổi bật mốc {$stage}",
                descriptionFormatter: fn(int $threshold, int $stage): string => "Các bộ sưu tập của bạn nhận được {$threshold} lượt đề cử.",
                overrides: [
                    10 => [
                        'slug' => 'spotlight-curator',
                        'name' => 'Người tuyển chọn nổi bật',
                        'description' => 'Các bộ sưu tập của bạn nhận được 10 lượt đề cử.',
                    ],
                    50 => [
                        'slug' => 'crowd-favorite-curator',
                        'name' => 'Tuyển chọn của đám đông',
                        'description' => 'Các bộ sưu tập của bạn nhận được 50 lượt đề cử.',
                    ],
                ],
            ),
            $this->buildFamily(
                category: 'breadth',
                family: 'breadth-milestones',
                conditionType: 'distinct_types',
                thresholds: [1, 2, 3],
                iconSet: [
                    'base' => [
                        '/images/badges/breadth-compass.svg',
                        '/images/badges/cinephile.svg',
                        '/images/badges/review-spotlight.svg',
                    ],
                    'gold' => ['/images/badges/review-spotlight.svg'],
                    'platinum' => ['/images/badges/breadth-platinum.svg'],
                    'diamond' => ['/images/badges/breadth-diamond.svg'],
                ],
                nameFormatter: fn(int $threshold, int $stage): string => "Nhà thám hiểm màn ảnh mốc {$stage}",
                descriptionFormatter: fn(int $threshold, int $stage): string => "Review {$threshold} loại nội dung khác nhau.",
                overrides: [
                    3 => [
                        'slug' => 'cinephile',
                        'name' => 'Mọt phim',
                        'description' => 'Review cả 3 loại nội dung (phim lẻ, series, tập phim).',
                    ],
                ],
            ),
        );

        foreach ($badges as $data) {
            Badge::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }

    private function buildFamily(
        string $category,
        string $family,
        string $conditionType,
        array $thresholds,
        array $iconSet,
        callable $nameFormatter,
        callable $descriptionFormatter,
        array $overrides = [],
    ): array {
        $total = count($thresholds);
        $badges = [];

        foreach (array_values($thresholds) as $index => $threshold) {
            $stage = $index + 1;
            $meta = $this->resolveStageMeta($stage, $total);
            $override = $overrides[$threshold] ?? [];

            $badges[] = array_merge([
                'slug' => Str::slug($family . '-' . $threshold),
                'name' => $nameFormatter($threshold, $stage),
                'description' => $descriptionFormatter($threshold, $stage),
                'icon_path' => $this->resolveIconPath($iconSet, $meta['tier'], $stage),
                'tier' => $meta['tier'],
                'category' => $category,
                'badge_family' => $family,
                'badge_stage' => $stage,
                'rarity_tier' => $meta['rarity_tier'],
                'frame_style' => $meta['frame_style'],
                'sort_order' => $this->nextSortOrder(),
                'condition_type' => $conditionType,
                'condition_value' => $threshold,
            ], $override);
        }

        return $badges;
    }

    private function resolveIconPath(array $iconSet, string $tier, int $stage): ?string
    {
        $pool = match ($tier) {
            'DIAMOND' => $iconSet['diamond'] ?? $iconSet['platinum'] ?? $iconSet['gold'] ?? $iconSet['base'] ?? [],
            'PLATINUM' => $iconSet['platinum'] ?? $iconSet['gold'] ?? $iconSet['base'] ?? [],
            'GOLD' => $iconSet['gold'] ?? $iconSet['base'] ?? [],
            default => $iconSet['base'] ?? [],
        };

        if ($pool === []) {
            return null;
        }

        $index = ($stage - 1) % count($pool);

        return $pool[$index];
    }

    private function resolveStageMeta(int $stage, int $total): array
    {
        $tiers = ['WOOD', 'IRON', 'BRONZE', 'SILVER', 'GOLD', 'PLATINUM', 'DIAMOND'];
        $rarities = ['COMMON', 'COMMON', 'UNCOMMON', 'RARE', 'EPIC', 'LEGENDARY', 'MYTHIC'];
        $frames = [
            'WOOD' => 'plain',
            'IRON' => 'ring-iron',
            'BRONZE' => 'ring-bronze',
            'SILVER' => 'ring-silver',
            'GOLD' => 'ring-gold',
            'PLATINUM' => 'halo-platinum',
            'DIAMOND' => 'halo-diamond',
        ];

        $lastIndex = count($tiers) - 1;
        $progress = $total > 1 ? ($stage - 1) / ($total - 1) : 0;
        $index = (int) floor($progress * $lastIndex);
        $tier = $tiers[$index];

        return [
            'tier' => $tier,
            'rarity_tier' => $rarities[$index],
            'frame_style' => $frames[$tier],
        ];
    }

    private function nextSortOrder(): int
    {
        $this->sortOrder += 10;

        return $this->sortOrder;
    }
}
