-- =============================================================================
-- VIEWS
-- =============================================================================

-- Basic title details with language name
create or replace view vw_title_details as
   select t.title_id,
          t.title_name,
          t.slug,
          t.title_type,
          t.release_date,
          t.runtime_mins,
          t.avg_rating,
          t.rating_count,
          t.status,
          t.visibility,
          t.poster_path,
          l.language_name
     from titles t
     left join languages l
   on t.original_language_id = l.language_id;

-- Reviews with user and title info
create or replace view vw_user_reviews as
   select r.review_id,
          u.username,
          t.title_name,
          r.rating,
          r.review_text,
          r.helpful_votes,
          r.has_spoilers,
          r.created_at
     from reviews r
     join users u
   on r.user_id = u.id
     join titles t
   on r.title_id = t.title_id;