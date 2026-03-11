-- =============================================================================
-- TRIGGERS
-- Note: No DELIMITER directive — executed via PDO (DB::unprepared)
-- =============================================================================

-- After INSERT review: accumulate rating
create trigger trg_update_title_rating_insert after
   insert on reviews
   for each row
begin
   update titles
      set rating_sum = rating_sum + new.rating,
          rating_count = rating_count + 1,
          avg_rating = ( rating_sum + new.rating ) / ( rating_count + 1 )
    where title_id = new.title_id;
end;

-- After DELETE review: subtract rating
create trigger trg_update_title_rating_delete after
   delete on reviews
   for each row
begin
   update titles
      set rating_sum = rating_sum - old.rating,
          rating_count = rating_count - 1,
          avg_rating = if(
             (rating_count - 1) <= 0,
             0.00,
             (rating_sum - old.rating) /(rating_count - 1)
          )
    where title_id = old.title_id;
end;

-- After UPDATE review: adjust rating
create trigger trg_update_title_rating_update after
   update on reviews
   for each row
begin
   update titles
      set rating_sum = rating_sum - old.rating + new.rating,
          avg_rating = if(
             rating_count = 0,
             0.00,
             (rating_sum - old.rating + new.rating) / rating_count
          )
    where title_id = new.title_id;
end;

-- Audit: log INSERT title
create trigger trg_audit_title_insert after
   insert on titles
   for each row
begin
   insert into title_audit_log (
      title_id,
      action_type,
      new_title,
      action_user
   ) values ( new.title_id,
              'INSERT',
              new.title_name,
              user() );
end;

-- Audit: log UPDATE title
create trigger trg_audit_title_update after
   update on titles
   for each row
begin
   insert into title_audit_log (
      title_id,
      action_type,
      old_title,
      new_title,
      action_user
   ) values ( new.title_id,
              'UPDATE',
              old.title_name,
              new.title_name,
              user() );
end;

-- Audit: log DELETE title
create trigger trg_audit_title_delete after
   delete on titles
   for each row
begin
   insert into title_audit_log (
      title_id,
      action_type,
      old_title,
      action_user
   ) values ( old.title_id,
              'DELETE',
              old.title_name,
              user() );
end;