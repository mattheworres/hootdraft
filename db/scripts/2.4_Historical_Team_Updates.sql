-- Rename any WAS NFL players to WFT
UPDATE players t1,
(   SELECT draft_id 
    FROM draft
    WHERE draft_sport = 'nfl' OR draft_sport = 'nfle' 
) t2
SET t1.team = 'WFT'
WHERE t1.team = 'WAS' AND t1.draft_id = t2.draft_id;

-- Rename any CLE MLB players to CLI
UPDATE players t1,
(   SELECT draft_id 
    FROM draft
    WHERE draft_sport = 'mlb'
) t2
SET t1.team = 'CLI'
WHERE t1.team = 'CLE' AND t1.draft_id = t2.draft_id;