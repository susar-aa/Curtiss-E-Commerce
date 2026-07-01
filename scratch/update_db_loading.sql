-- Update route status to Loading
UPDATE rep_daily_routes SET status = 'Loading' WHERE status IN ('Pending Loading', 'Final Loading');
