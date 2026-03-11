export const notifIcon = {
    badge_earned:       '🏅',
    level_up:           '⬆️',
    comment_replied:    '💬',
    comment_liked:      '❤️',
    review_helpful:     '👍',
    new_follower:       '👤',
    collection_invited: '📚',
};

export const notifColor = {
    badge_earned:       'text-yellow-400',
    level_up:           'text-purple-400',
    comment_replied:    'text-blue-400',
    comment_liked:      'text-red-400',
    review_helpful:     'text-emerald-400',
    new_follower:       'text-sky-400',
    collection_invited: 'text-teal-400',
};

export function notifTitle(n) {
    const d = n.data;
    switch (n.type) {
        case 'badge_earned':
            return `Bạn đã đạt huy hiệu "${d.badge_name}"!`;
        case 'level_up':
            return `Lên cấp: ${d.rank_icon} ${d.rank_label}${d.level ? ` · Lv ${d.level}` : ''}`;
        case 'comment_replied':
            return `${d.actor_name} đã trả lời bình luận của bạn`;
        case 'comment_liked': {
            const count = d.count ?? 1;
            const names = (d.actor_names ?? []).slice(0, 2).join(', ');
            return count > 2
                ? `${names} và ${count - 2} người khác đã thích bình luận của bạn`
                : `${names} đã thích bình luận của bạn`;
        }
        case 'review_helpful': {
            const count = d.count ?? 1;
            const names = (d.actor_names ?? []).slice(0, 2).join(', ');
            return count > 2
                ? `${names} và ${count - 2} người khác thấy đánh giá của bạn hữu ích`
                : `${names} thấy đánh giá của bạn hữu ích`;
        }
        case 'new_follower':
            return `${d.follower_name ?? d.actor_name} đã bắt đầu theo dõi bạn`;
        case 'collection_invited':
            return `${d.inviter_name} đã mời bạn tham gia bộ sưu tập "${d.collection_name}"`;
        default:
            return 'Thông báo mới';
    }
}
